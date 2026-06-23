<?php
namespace MapasCulturais\Traits;

use MapasCulturais\App;
use MapasCulturais\Entity;
use Exception;

trait EntityOpportunityDuplicator {

    private $entityOpportunity;
    private $entityNewOpportunity;

    /**
     * Flag que indica se o processo de duplicação está em andamento.
     * Usado para desabilitar hooks que interferem na atribuição explícita
     * de EvaluationMethodConfigurations durante a duplicação.
     */
    public static $duplicating = false;

    function ALL_duplicate(){
        $app = App::i();

        $this->requireAuthentication();
        $this->entityOpportunity = $this->requestedEntity;

        // Impede duplicação de oportunidade de outro subsite
        $currentSubsiteId = $app->getCurrentSubsiteId();
        $opportunitySubsiteId = $this->entityOpportunity->subsite ? $this->entityOpportunity->subsite->id : null;
        if ($currentSubsiteId !== $opportunitySubsiteId) {
            $this->errorJson(['message' => 'Não é possível duplicar uma oportunidade de outro subsite.'], 403);
            return;
        }

        self::$duplicating = true;
        $app->em->beginTransaction();
        try {
            $this->entityNewOpportunity = $this->cloneOpportunity();

            $this->duplicateEvaluationMethods();
            $this->duplicatePhases();
            $this->duplicateMetadata();
            $this->duplicateRegistrationFieldsAndFiles();
            $this->duplicateMetalist();
            $this->duplicateFiles();
            $this->duplicateAgentRelations();
            $this->duplicateSealsRelations();

            // Remapeia referências a fases originais (appealPhase, etc.)
            // DEVE rodar DEPOIS de duplicateMetadata() pois esta copia os metadados do
            // opportunity principal que podem conter referências a fases originais
            $this->remapPhaseReferences();

            $this->entityNewOpportunity->save(true);

            $app->em->commit();
        } catch (\Exception $e) {
            $app->em->rollback();
            self::$duplicating = false;
            throw $e;
        } finally {
            self::$duplicating = false;
        }
       
        if($this->isAjax()){
            $this->json($this->entityOpportunity);
        }else{
            $app->redirect($app->request->getReferer());
        }
    }

    private function cloneOpportunity()
    {
        $app = App::i();

        $this->entityNewOpportunity = clone $this->entityOpportunity;

        // Remove referência stale ao EvaluationMethodConfiguration original
        // para evitar que hooks interpretem incorretamente que o clone já possui um eval config
        $this->entityNewOpportunity->evaluationMethodConfiguration = null;

        // Limpa coleção de metadata herdada do clone para evitar que
        // setMetadata encontre objetos do original e ignore valores iguais
        $refMeta = new \ReflectionProperty($this->entityNewOpportunity, '__metadata');
        $refMeta->setAccessible(true);
        $refMeta->setValue($this->entityNewOpportunity, new \Doctrine\Common\Collections\ArrayCollection());

        $dateTime = new \DateTime();
        $now = $dateTime->format('d-m-Y H:i:s');
        $name = $this->entityOpportunity->name;
        $this->entityNewOpportunity->name = "$name  - [Cópia][$now]";
        $this->entityNewOpportunity->status = Entity::STATUS_DRAFT;
        $app->em->persist($this->entityNewOpportunity);
        $app->em->flush();

        $this->entityNewOpportunity->registrationCategories = $this->entityOpportunity->registrationCategories;
        $this->entityNewOpportunity->registrationProponentTypes = $this->entityOpportunity->registrationProponentTypes;
        $this->entityNewOpportunity->registrationRanges = $this->entityOpportunity->registrationRanges;
        $this->entityNewOpportunity->save(true);

        return $this->entityNewOpportunity;
    }

    private function duplicateEvaluationMethods() : void
    {
        $this->duplicateEvaluationMethodsOf($this->entityOpportunity, $this->entityNewOpportunity);
    }

    /**
     * Mapa de IDs de fases originais para IDs de fases duplicadas.
     * Preenchido durante duplicatePhases() e usado para remapear
     * referências como appealPhase.
     */
    private $phaseMap = [];

    private function duplicatePhases() : void
    {
        $this->phaseMap = [];

        // Duplica recursivamente todas as fases (filhas, netas, etc.)
        $this->duplicatePhasesOf($this->entityOpportunity, $this->entityNewOpportunity);

        // Copia publishTimestamp da lastPhase original para a nova lastPhase
        // e duplica sub-fases da lastPhase
        $this->copyLastPhasePublishDate();
    }

    /**
     * Duplica recursivamente as fases filhas de $originalParent,
     * atribuindo-as como filhas de $newParent.
     */
    private function duplicatePhasesOf($originalParent, $newParent) : void
    {
        $app = App::i();

        $phases = $app->repo('Opportunity')->findBy([
            'parent' => $originalParent
        ]);

        foreach ($phases as $phase) {
            if ($phase->getMetadata('isLastPhase')) {
                // lastPhase é recriada automaticamente pelo hook; apenas copiar publishDate depois
                continue;
            }

            $newPhase = clone $phase;
            $newPhase->setParent($newParent);

            // Remove referência stale ao EvaluationMethodConfiguration original
            $newPhase->evaluationMethodConfiguration = null;

            // Desabilita o lock para evitar que o clone herde o lock do original
            $newPhase->__lockEnable = false;

            // Limpa coleção de metadata herdada do clone para evitar que
            // setMetadata encontre objetos do original e ignore valores iguais
            $refMeta = new \ReflectionProperty($newPhase, '__metadata');
            $refMeta->setAccessible(true);
            $refMeta->setValue($newPhase, new \Doctrine\Common\Collections\ArrayCollection());

            // Persiste o clone para obter um ID novo antes de setar metadata
            $newPhase->save(true);

            // Registra no mapa de fases
            $this->phaseMap[$phase->id] = $newPhase->id;

            // duplica apenas os metadados realmente persistidos no banco
            $persistedMeta = $app->repo($phase->getMetadataClassName())->findBy(['owner' => $phase]);
            foreach ($persistedMeta as $metadataObject) {
                $metadataValue = $metadataObject->value;
                if (!is_null($metadataValue) && $metadataValue != '') {
                    if (is_array($metadataValue)) {
                        $metadataValue = json_encode($metadataValue);
                    }
                    $newPhase->setMetadata($metadataObject->key, $metadataValue);
                }
            }

            $newPhase->save(true);

            // duplica os campos e arquivos de inscrição da fase
            $this->duplicateRegistrationFieldsAndFiles($phase, $newPhase);

            // duplica os modelos de avaliações das fases
            $this->duplicateEvaluationMethodsOf($phase, $newPhase);

            // Duplica recursivamente sub-fases (ex: fases de recurso que são filhas desta fase)
            $this->duplicatePhasesOf($phase, $newPhase);
        }
    }

    /**
     * Duplica os EvaluationMethodConfigurations de $originalPhase para $newPhase.
     */
    private function duplicateEvaluationMethodsOf($originalPhase, $newPhase) : void
    {
        $app = App::i();

        $evaluationMethodConfigurations = $app->repo('EvaluationMethodConfiguration')->findBy([
            'opportunity' => $originalPhase
        ]);

        foreach ($evaluationMethodConfigurations as $evaluationMethodConfiguration) {
            $newMethodConfiguration = clone $evaluationMethodConfiguration;
            $newMethodConfiguration->setOpportunity($newPhase);

            $refMeta = new \ReflectionProperty($newMethodConfiguration, '__metadata');
            $refMeta->setAccessible(true);
            $refMeta->setValue($newMethodConfiguration, new \Doctrine\Common\Collections\ArrayCollection());

            $newMethodConfiguration->save(true);

            // duplica apenas os metadados realmente persistidos no banco
            $persistedMeta = $app->repo($evaluationMethodConfiguration->getMetadataClassName())->findBy(['owner' => $evaluationMethodConfiguration]);
            foreach ($persistedMeta as $metadataObject) {
                $metadataValue = $metadataObject->value;
                if (is_array($metadataValue)) {
                    $metadataValue = json_encode($metadataValue);
                }
                $newMethodConfiguration->setMetadata($metadataObject->key, $metadataValue);
                $newMethodConfiguration->save(true);
            }

            foreach ($evaluationMethodConfiguration->getAgentRelations() as $agentRelation_) {
                $agentRelation = clone $agentRelation_;
                $agentRelation->owner = $newMethodConfiguration;
                $agentRelation->save(true);
            }
        }
    }

    /**
     * Remapeia metadados que referenciam fases originais para apontar
     * para as fases duplicadas correspondentes.
     * Formato: "MapasCulturais\Entities\Opportunity:XXXX"
     */
    private function remapPhaseReferences() : void
    {
        if (empty($this->phaseMap)) {
            return;
        }

        $app = App::i();
        $conn = $app->em->getConnection();

        // Garante que todos os metadados pendentes estejam persistidos no banco
        // antes de fazer queries SQL diretas
        $app->em->flush();

        // Coleta todos os IDs da nova árvore (oportunidade principal + todas as fases duplicadas)
        $newIds = array_merge(
            [$this->entityNewOpportunity->id],
            array_values($this->phaseMap)
        );

        $metaKeysToRemap = ['appealPhase'];

        foreach ($metaKeysToRemap as $metaKey) {
            foreach ($newIds as $newId) {
                $rows = $conn->fetchAll(
                    "SELECT id, value FROM opportunity_meta WHERE object_id = ? AND key = ?",
                    [$newId, $metaKey]
                );

                foreach ($rows as $row) {
                    // Formato: "MapasCulturais\Entities\Opportunity:XXXX"
                    if (preg_match('/^(MapasCulturais\\\\Entities\\\\Opportunity):(\d+)$/', $row['value'], $matches)) {
                        $oldRefId = (int) $matches[2];
                        if (isset($this->phaseMap[$oldRefId])) {
                            $newRefId = $this->phaseMap[$oldRefId];
                            $newValue = $matches[1] . ':' . $newRefId;
                            $conn->executeUpdate(
                                "UPDATE opportunity_meta SET value = ? WHERE id = ?",
                                [$newValue, $row['id']]
                            );
                        } else {
                            // A fase referenciada não foi encontrada no mapa de duplicação.
                            // Mantém o metadado como está e loga aviso para investigação.
                            $app->log->warning("remapPhaseReferences: opp $newId, metaKey '$metaKey' " .
                                "referencia fase $oldRefId que não está no phaseMap. Valor mantido.");
                        }
                    }
                }
            }
        }
    }

    /**
     * Copia o publishTimestamp e metadados da lastPhase original para a nova lastPhase,
     * e duplica sub-fases da lastPhase original (ex: fase de recurso).
     */
    private function copyLastPhasePublishDate() : void
    {
        $app = App::i();

        // Busca a lastPhase original
        $originalLastPhase = null;
        $originalPhases = $app->repo('Opportunity')->findBy([
            'parent' => $this->entityOpportunity
        ]);
        foreach ($originalPhases as $phase) {
            if ($phase->getMetadata('isLastPhase')) {
                $originalLastPhase = $phase;
                break;
            }
        }

        if (!$originalLastPhase) {
            return;
        }

        // Busca a nova lastPhase
        $newLastPhase = null;
        $newPhases = $app->repo('Opportunity')->findBy([
            'parent' => $this->entityNewOpportunity
        ]);
        foreach ($newPhases as $phase) {
            if ($phase->getMetadata('isLastPhase')) {
                $newLastPhase = $phase;
                break;
            }
        }

        if (!$newLastPhase) {
            return;
        }

        // Copia publishTimestamp
        if ($originalLastPhase->publishTimestamp) {
            $newLastPhase->setPublishTimestamp($originalLastPhase->publishTimestamp);
        }

        // Copia metadados da lastPhase original (exceto os já definidos pelo hook)
        $persistedMeta = $app->repo($originalLastPhase->getMetadataClassName())->findBy(['owner' => $originalLastPhase]);
        $hookDefinedKeys = ['isLastPhase', 'isOpportunityPhase', 'isDataCollection'];
        foreach ($persistedMeta as $metadataObject) {
            if (in_array($metadataObject->key, $hookDefinedKeys)) {
                continue;
            }
            $metadataValue = $metadataObject->value;
            if (!is_null($metadataValue) && $metadataValue != '') {
                if (is_array($metadataValue)) {
                    $metadataValue = json_encode($metadataValue);
                }
                $newLastPhase->setMetadata($metadataObject->key, $metadataValue);
            }
        }

        $newLastPhase->save(true);

        // Registra no mapa de fases
        $this->phaseMap[$originalLastPhase->id] = $newLastPhase->id;

        // Duplica sub-fases da lastPhase original (ex: fase de recurso)
        $this->duplicatePhasesOf($originalLastPhase, $newLastPhase);
    }

    private function duplicateMetadata() : void
    {
        $app = App::i();

        // duplica apenas os metadados realmente persistidos no banco
        $persistedMeta = $app->repo($this->entityOpportunity->getMetadataClassName())->findBy(['owner' => $this->entityOpportunity]);
        foreach ($persistedMeta as $metadataObject) {
            $metadataValue = $metadataObject->value;
            if (!is_null($metadataValue) && $metadataValue != '') {
                if (is_array($metadataValue)) {
                    $metadataValue = json_encode($metadataValue);
                }
                $this->entityNewOpportunity->setMetadata($metadataObject->key, $metadataValue);
            }
        }

        $this->entityNewOpportunity->setTerms(['area' => $this->entityOpportunity->terms['area']]);
        $this->entityNewOpportunity->setTerms(['tag' => $this->entityOpportunity->terms['tag']]);
        $this->entityNewOpportunity->saveTerms();

        // Persiste os metadados imediatamente para que remapPhaseReferences()
        // possa encontrá-los via SQL direto
        $this->entityNewOpportunity->save(true);
    }
   
    private function duplicateRegistrationFieldsAndFiles($sourceOpportunity = null, $targetOpportunity = null): void
    {
        $sourceOpportunity = $sourceOpportunity ?? $this->entityOpportunity;
        $targetOpportunity = $targetOpportunity ?? $this->entityNewOpportunity;

        // Criando um mapa de steps originais para os novos steps
        $stepMap = [];
        $fieldNameMap = []; // mapeamento de fieldName antigo (field_XXX) para novo (field_YYY)

        // Mapeando os steps existentes na nova Oportunidade
        $existingSteps = array_column($targetOpportunity->registrationSteps->toArray(), null, 'id');

        foreach ($sourceOpportunity->registrationSteps as $oldStep) {
            // Reutilizando step existente ou criar um novo
            $stepMap[$oldStep->id] = $existingSteps[$oldStep->id] ?? (function () use ($oldStep, $targetOpportunity) {
                $newStep = clone $oldStep;
                $newStep->setOpportunity($targetOpportunity);
                $newStep->save(true);
                return $newStep;
            })();
        }

        // Clonar campos e criar mapeamento de fieldName
        $conditionalFieldsToUpdate = [];
        
        foreach ($sourceOpportunity->getRegistrationFieldConfigurations() as $oldFieldConfiguration) {
            $oldFieldName = $oldFieldConfiguration->getFieldName();
            
            // Guardar conditional_field original antes de clonar
            $originalConditionalField = $oldFieldConfiguration->conditionalField;
            
            $newFieldConfiguration = clone $oldFieldConfiguration;
            $newFieldConfiguration->setOwnerId($targetOpportunity->id);

            // Atualizando o Step garantindo a correspondência correta
            if (isset($stepMap[$oldFieldConfiguration->step->id])) {
                $newFieldConfiguration->setStep($stepMap[$oldFieldConfiguration->step->id]);
            }

            // Limpar conditional_field temporariamente para evitar salvar com valor antigo
            $newFieldConfiguration->conditionalField = null;

            $newFieldConfiguration->save(true);
            
            // mapear fieldName antigo para novo
            $newFieldName = $newFieldConfiguration->getFieldName();
            $fieldNameMap[$oldFieldName] = $newFieldName;
            
            // guardar conditional_field original para atualizar depois
            if (!empty($originalConditionalField)) {
                $conditionalFieldsToUpdate[] = [
                    'field' => $newFieldConfiguration,
                    'oldConditionalField' => $originalConditionalField
                ];
            }
        }

        // Atualizar conditional_field dos campos clonados usando o mapeamento
        foreach ($conditionalFieldsToUpdate as $item) {
            $oldConditionalFieldName = $item['oldConditionalField'];
            
            // Se o campo referenciado foi clonado, atualizar a referência
            if (isset($fieldNameMap[$oldConditionalFieldName])) {
                $item['field']->conditionalField = $fieldNameMap[$oldConditionalFieldName];
                $item['field']->save(true);
            }
        }

        // Clonar arquivos e atualizar conditional_field
        $conditionalFilesToUpdate = [];
        
        foreach ($sourceOpportunity->getRegistrationFileConfigurations() as $oldFileConfiguration) {
            // Guardar conditional_field original antes de clonar
            $originalConditionalField = $oldFileConfiguration->conditionalField;
            
            $newFileConfiguration = clone $oldFileConfiguration;
            $newFileConfiguration->setOwnerId($targetOpportunity->id);

            // Atualizando o Step garantindo a correspondência correta
            if (isset($stepMap[$oldFileConfiguration->step->id])) {
                $newFileConfiguration->setStep($stepMap[$oldFileConfiguration->step->id]);
            }

            // Limpar conditional_field temporariamente para evitar salvar com valor antigo
            $newFileConfiguration->conditionalField = null;

            $newFileConfiguration->save(true);
            
            // guardar conditional_field original para atualizar depois
            if (!empty($originalConditionalField)) {
                $conditionalFilesToUpdate[] = [
                    'file' => $newFileConfiguration,
                    'oldConditionalField' => $originalConditionalField
                ];
            }
        }

        // Atualizar conditional_field dos arquivos clonados usando o mapeamento
        foreach ($conditionalFilesToUpdate as $item) {
            $oldConditionalFieldName = $item['oldConditionalField'];
            
            // Se o campo referenciado foi clonado, atualizar a referência
            if (isset($fieldNameMap[$oldConditionalFieldName])) {
                $item['file']->conditionalField = $fieldNameMap[$oldConditionalFieldName];
                $item['file']->save(true);
            }
        }
    }

    private function duplicateMetalist() : void
    {
        foreach ($this->entityOpportunity->getMetaLists() as $metaList_) {
            foreach ($metaList_ as $metaList__) {
                $metalist = clone $metaList__;
                $metalist->setOwner($this->entityNewOpportunity);
            
                $metalist->save(true);
            }
        }
    }

    private function duplicateFiles() : void
    {
        $app = App::i();        

        $src = PUBLIC_PATH . 'files/opportunity/' . $this->entityOpportunity->id;
        $dst = PUBLIC_PATH . 'files/opportunity/' . $this->entityNewOpportunity->id;
        $this->copyDir($src, $dst);
        
        $conn = $app->em->getConnection();
        $files = $conn->fetchAll("SELECT * FROM file WHERE object_id = {$this->entityOpportunity->id} ORDER BY id ASC");
        foreach ($files as $file) {
            if (is_null($file['parent_id'])) {
                $parentId = null;
            } else if (isset($futureParentId)) {
                $parentId = $futureParentId;
            } else {
                throw new Exception('File parent_id unexpected');
            }

            $sql = 'INSERT INTO file (md5, mime_type, name, object_type, object_id, create_timestamp, grp, description, parent_id, path) VALUES (:md5, :mime_type, :name, :object_type, :object_id, :create_timestamp, :grp, :description, :parent_id, :path)';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('md5', $file['md5']);
            $stmt->bindValue('mime_type', $file['mime_type']);
            $stmt->bindValue('name', $file['name']);
            $stmt->bindValue('object_type', $file['object_type']);
            $stmt->bindValue('object_id', $this->entityNewOpportunity->id);
            $stmt->bindValue('create_timestamp', $file['create_timestamp']);
            $stmt->bindValue('grp', $file['grp']);
            $stmt->bindValue('description', $file['description']);
            $stmt->bindValue('parent_id', $parentId);

            $path = str_replace('opportunity/'.$this->entityOpportunity->id, 'opportunity/'.$this->entityNewOpportunity->id, $file['path']);
            $path = str_replace('file/'.$file['parent_id'], 'file/'.$parentId, $path);

            $diretorioAtual = $dst . '/file/' . $file['parent_id'];
            $novoDiretorio = $dst . '/file/' . $parentId;
            
            if (is_dir($diretorioAtual)) {
                if (!is_dir($novoDiretorio)) {
                    if (rename($diretorioAtual, $novoDiretorio)) {
                    }
                } 
            }

            $stmt->bindValue('path', $path);
            $stmt->execute();

            if (is_null($file['parent_id'])) {
                $futureParentId = $conn->lastInsertId();
            }
        }
    }

    private function copyDir($src, $dst) {
        if (!file_exists($src)) {
            return false;
        }
        if (!file_exists($dst)) {
            mkdir($dst, 0755, true);
        }
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    
        return true;
    }

    private function duplicateAgentRelations() : void
    {
        foreach ($this->entityOpportunity->getAgentRelations() as $agentRelation_) {
            $agentRelation = clone $agentRelation_;
            $agentRelation->owner = $this->entityNewOpportunity;
            $agentRelation->save(true);
        }
    }

    private function duplicateSealsRelations() : void
    {
        foreach ($this->entityOpportunity->getSealRelations() as $sealRelation) {
            $this->entityNewOpportunity->createSealRelation($sealRelation->seal, true, true);
        }
    }
}
