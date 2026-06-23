<?php
namespace MapasCulturais\Traits;

use MapasCulturais\App;
use MapasCulturais\Entity;

trait EntityManagerModel {

    private $entityOpportunity;
    private $entityOpportunityModel;

    function ALL_generatemodel(){
        $app = App::i();

        $this->requireAuthentication();
        $this->entityOpportunity = $this->requestedEntity;

        // Impede gerar modelo de oportunidade de outro subsite
        $currentSubsiteId = $app->getCurrentSubsiteId();
        $opportunitySubsiteId = $this->entityOpportunity->subsite ? $this->entityOpportunity->subsite->id : null;
        if ($currentSubsiteId !== $opportunitySubsiteId) {
            $this->errorJson(['message' => 'Não é possível gerar modelo de uma oportunidade de outro subsite.'], 403);
            return;
        }

        self::$duplicating = true;
        $app->em->beginTransaction();
        try {
            $this->entityOpportunityModel = $this->generateModel();

            $this->generateEvaluationMethods();
            $this->generatePhases();
            $this->generateMetadata();
            $this->generateRegistrationFieldsAndFiles($this->entityOpportunity, $this->entityOpportunityModel);
            $this->generateSealsRelations();

            // Remapeia referências a fases originais (appealPhase, etc.)
            $this->modelRemapPhaseReferences();

            $this->entityOpportunityModel->save(true);

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

    function ALL_generateopportunity(){
        $app = App::i();

        $this->requireAuthentication();
        $this->entityOpportunity = $this->requestedEntity;

        // Impede gerar oportunidade a partir de modelo de outro subsite
        $currentSubsiteId = $app->getCurrentSubsiteId();
        $opportunitySubsiteId = $this->entityOpportunity->subsite ? $this->entityOpportunity->subsite->id : null;
        if ($currentSubsiteId !== $opportunitySubsiteId) {
            $this->errorJson(['message' => 'Não é possível gerar oportunidade a partir de um modelo de outro subsite.'], 403);
            return;
        }

        $app->disableAccessControl();
        self::$duplicating = true;
        $app->em->beginTransaction();
        try {
            $this->entityOpportunityModel = $this->generateOpportunity();

            $this->generateEvaluationMethods();
            $this->generatePhases();
            $this->generateMetadata(0, 0);
            $this->generateRegistrationFieldsAndFiles($this->entityOpportunity, $this->entityOpportunityModel);

            // Remapeia referências a fases originais (appealPhase, etc.)
            $this->modelRemapPhaseReferences();

            $this->entityOpportunityModel->save(true);

            $app->em->commit();
        } catch (\Exception $e) {
            $app->em->rollback();
            self::$duplicating = false;
            $app->enableAccessControl();
            throw $e;
        } finally {
            self::$duplicating = false;
            $app->enableAccessControl();
        }

        $this->json($this->entityOpportunityModel); 
    }

    function GET_findOpportunitiesModels()
    {
        $app = App::i();
        $dataModels = [];
        
        $opportunities = $app->em->createQuery("
            SELECT 
                o.id
            FROM
                MapasCulturais\Entities\OpportunityMeta om
                JOIN MapasCulturais\Entities\Opportunity o WITH om.owner=o
            WHERE om.key = 'isModel' AND om.value = '1'
        ");

        foreach ($opportunities->getResult() as $opportunity) {
            $opp = $app->repo('Opportunity')->find($opportunity['id']);
            $phases = $opp->phases;

            $lastPhase = array_pop($phases);

            $modelIsOfficial = false;
            foreach ($opp->getSealRelations() as $sealRelation) {
                if ( in_array($sealRelation->seal->id, $app->config['app.verifiedSealsIds'])) {
                    $modelIsOfficial = true;
                }
            }
            
            $days = !is_null($opp->registrationFrom) && !is_null($lastPhase->publishTimestamp) ? $lastPhase->publishTimestamp->diff($opp->registrationFrom)->days . " Dia(s)" : 'N/A';
            $tipoAgente = $opp->registrationProponentTypes ? implode(', ', $opp->registrationProponentTypes) : 'N/A';
            $dataModels[] = [
                'id' => $opp->id,
                'numeroFases' => count($opp->phases),
                'descricao' => $opp->shortDescription,
                'tempoEstimado' => $days,
                'tipoAgente'   =>  $tipoAgente,
                'modelIsOfficial' => $modelIsOfficial
            ];
        }
        
        $this->json($dataModels);
    }

    function POST_modelpublic(){
        $app = App::i();

        $this->requireAuthentication();
        $this->entityOpportunity = $this->requestedEntity;

        $isModelPublic = $this->postData['isModelPublic'];
    
        $this->entityOpportunity->setMetadata('isModelPublic', $isModelPublic);
        $this->entityOpportunity->saveTerms();
        $this->entityOpportunity->save(true);
       
        $this->json($isModelPublic); 
    }

    private function generateModel()
    {
        $app = App::i();

        $postData = $this->postData;

        $name = $postData['name'];
        $description = $postData['description'];

        $this->entityOpportunityModel = clone $this->entityOpportunity;

        // Remove referência stale ao EvaluationMethodConfiguration original
        $this->entityOpportunityModel->evaluationMethodConfiguration = null;

        // Limpa coleção de metadata herdada do clone
        $refMeta = new \ReflectionProperty($this->entityOpportunityModel, '__metadata');
        $refMeta->setAccessible(true);
        $refMeta->setValue($this->entityOpportunityModel, new \Doctrine\Common\Collections\ArrayCollection());

        $this->entityOpportunityModel->name = $name;
        $this->entityOpportunityModel->status = -1;
        $this->entityOpportunityModel->shortDescription = $description;

        $now = new \DateTime('now');
        $this->entityOpportunityModel->createTimestamp = $now;

        $app->em->persist($this->entityOpportunityModel);
        $app->em->flush();

        // necessário adicionar as categorias, proponetes e ranges após salvar devido a trigger public.fn_propagate_opportunity_insert
        $this->entityOpportunityModel->registrationCategories = $this->entityOpportunity->registrationCategories;
        $this->entityOpportunityModel->registrationProponentTypes = $this->entityOpportunity->registrationProponentTypes;
        $this->entityOpportunityModel->registrationRanges = $this->entityOpportunity->registrationRanges;
        $this->entityOpportunityModel->save(true);

        return $this->entityOpportunityModel;

        
    }

    private function generateOpportunity()
    {
        $app = App::i();
        $postData = $this->postData;

        $name = $postData['name'];
        
        $this->entityOpportunityModel = clone $this->entityOpportunity;

        // Remove referência stale ao EvaluationMethodConfiguration original
        $this->entityOpportunityModel->evaluationMethodConfiguration = null;

        // Limpa coleção de metadata herdada do clone
        $refMeta = new \ReflectionProperty($this->entityOpportunityModel, '__metadata');
        $refMeta->setAccessible(true);
        $refMeta->setValue($this->entityOpportunityModel, new \Doctrine\Common\Collections\ArrayCollection());

        $this->entityOpportunityModel->name = $name;
        $this->entityOpportunityModel->status = Entity::STATUS_DRAFT;
        $this->entityOpportunityModel->owner = $app->user->profile;

        $now = new \DateTime('now');
        $this->entityOpportunityModel->createTimestamp = $now;

        $app->em->persist($this->entityOpportunityModel);
        $app->em->flush();

        // necessário adicionar as categorias, proponetes e ranges após salvar devido a trigger public.fn_propagate_opportunity_insert
        $this->entityOpportunityModel->registrationCategories = $this->entityOpportunity->registrationCategories;
        $this->entityOpportunityModel->registrationProponentTypes = $this->entityOpportunity->registrationProponentTypes;
        $this->entityOpportunityModel->registrationRanges = $this->entityOpportunity->registrationRanges;
        
        $this->changeObjectType($this->entityOpportunityModel->id);
        
        $this->entityOpportunityModel->save(true);

        return $this->entityOpportunityModel;
    }

    private function changeObjectType($id)
    {
        $app = App::i();
        $postData = $this->postData;

        if (isset($postData['objectType']) && isset($postData['ownerEntity'])) {
            $ownerEntity = $app->repo($postData['objectType'])->find($postData['ownerEntity']);
            // Não abre transação própria — já está dentro da transação externa
            // de ALL_generatemodel() ou ALL_generateopportunity()
            $app->em->getConnection()->update('opportunity', [
                    'object_type' => $ownerEntity->getClassName(), 
                    'object_id' => $ownerEntity->id
                ], ['id' => $id]);
        }
    }

    private function generateEvaluationMethods() : void
    {
        $this->modelGenerateEvalMethodsOf($this->entityOpportunity, $this->entityOpportunityModel);
    }

    /**
     * Mapa de IDs de fases originais para IDs de fases geradas.
     */
    private $modelPhaseMap = [];

    private function generatePhases() : void
    {
        $app = App::i();
        $this->modelPhaseMap = [];

        // Duplica recursivamente todas as fases (filhas, netas, etc.)
        $this->modelGeneratePhasesOf($this->entityOpportunity, $this->entityOpportunityModel);

        // Copia publishTimestamp da lastPhase e duplica sub-fases
        $this->modelCopyLastPhaseData();
    }

    /**
     * Duplica recursivamente as fases filhas de $originalParent,
     * atribuindo-as como filhas de $newParent.
     */
    private function modelGeneratePhasesOf($originalParent, $newParent) : void
    {
        $app = App::i();

        $phases = $app->repo('Opportunity')->findBy([
            'parent' => $originalParent
        ]);

        foreach ($phases as $phase) {
            if ($phase->getMetadata('isLastPhase')) {
                continue;
            }

            $newPhase = clone $phase;
            $newPhase->setParent($newParent);
            $newPhase->owner = $app->user->profile;

            // Remove referência stale ao EvaluationMethodConfiguration original
            $newPhase->evaluationMethodConfiguration = null;

            // Desabilita o lock para evitar que o clone herde o lock do original
            $newPhase->__lockEnable = false;

            // Limpa coleção de metadata herdada do clone
            $refMeta = new \ReflectionProperty($newPhase, '__metadata');
            $refMeta->setAccessible(true);
            $refMeta->setValue($newPhase, new \Doctrine\Common\Collections\ArrayCollection());

            // Persiste o clone para obter um ID novo
            $newPhase->save(true);

            // Registra no mapa de fases
            $this->modelPhaseMap[$phase->id] = $newPhase->id;

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

            $this->generateRegistrationFieldsAndFiles($phase, $newPhase);

            $now = new \DateTime('now');
            $newPhase->createTimestamp = $now;
            $newPhase->subsite = $phase->subsite;

            $newPhase->save(true);

            $this->changeObjectType($newPhase->id);

            // Duplica eval method configs
            $this->modelGenerateEvalMethodsOf($phase, $newPhase);

            // Duplica recursivamente sub-fases (ex: fases de recurso)
            $this->modelGeneratePhasesOf($phase, $newPhase);
        }
    }

    /**
     * Duplica os EvaluationMethodConfigurations de $originalPhase para $newPhase.
     */
    private function modelGenerateEvalMethodsOf($originalPhase, $newPhase) : void
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
        }
    }

    /**
     * Remapeia metadados que referenciam fases originais.
     */
    private function modelRemapPhaseReferences() : void
    {
        if (empty($this->modelPhaseMap)) {
            return;
        }

        $app = App::i();
        $conn = $app->em->getConnection();

        // Garante que todos os metadados pendentes estejam persistidos
        $app->em->flush();

        $newIds = array_merge(
            [$this->entityOpportunityModel->id],
            array_values($this->modelPhaseMap)
        );

        $metaKeysToRemap = ['appealPhase'];

        foreach ($metaKeysToRemap as $metaKey) {
            foreach ($newIds as $newId) {
                $rows = $conn->fetchAll(
                    "SELECT id, value FROM opportunity_meta WHERE object_id = ? AND key = ?",
                    [$newId, $metaKey]
                );

                foreach ($rows as $row) {
                    if (preg_match('/^(MapasCulturais\\\\Entities\\\\Opportunity):(\d+)$/', $row['value'], $matches)) {
                        $oldRefId = (int) $matches[2];
                        if (isset($this->modelPhaseMap[$oldRefId])) {
                            $newRefId = $this->modelPhaseMap[$oldRefId];
                            $newValue = $matches[1] . ':' . $newRefId;
                            $conn->executeUpdate(
                                "UPDATE opportunity_meta SET value = ? WHERE id = ?",
                                [$newValue, $row['id']]
                            );
                        } else {
                            // A fase referenciada não foi encontrada no mapa de duplicação.
                            // Mantém o metadado como está e loga aviso para investigação.
                            $app->log->warning("modelRemapPhaseReferences: opp $newId, metaKey '$metaKey' " .
                                "referencia fase $oldRefId que não está no modelPhaseMap. Valor mantido.");
                        }
                    }
                }
            }
        }
    }

    /**
     * Copia publishTimestamp e metadados da lastPhase, e duplica sub-fases.
     */
    private function modelCopyLastPhaseData() : void
    {
        $app = App::i();

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

        $newLastPhase = null;
        $newPhases = $app->repo('Opportunity')->findBy([
            'parent' => $this->entityOpportunityModel
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

        if ($originalLastPhase->publishTimestamp) {
            $newLastPhase->setPublishTimestamp($originalLastPhase->publishTimestamp);
        }
        $newLastPhase->subsite = $originalLastPhase->subsite;

        // Copia metadados da lastPhase original
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

        $this->changeObjectType($newLastPhase->id);

        // Registra no mapa de fases
        $this->modelPhaseMap[$originalLastPhase->id] = $newLastPhase->id;

        // Duplica sub-fases da lastPhase
        $this->modelGeneratePhasesOf($originalLastPhase, $newLastPhase);
    }


    private function generateMetadata($isModel = 1, $isModelPublic = 0) : void
    {
        $app = App::i();
        $em = $app->em;
        $conn = $em->getConnection();

        $sql = "
            SELECT 
                om.*
            FROM
                opportunity_meta om
            WHERE om.object_id = {$this->entityOpportunity->id}
        ";
        $stmt = $conn->query($sql);

        while (($row = $stmt->fetchAssociative()) !== false) {
            $this->entityOpportunityModel->setMetadata($row['key'], $row['value']);
        }

        $this->entityOpportunityModel->setMetadata('isModel', $isModel);
        $this->entityOpportunityModel->setMetadata('isModelPublic', $isModelPublic);

        $this->entityOpportunityModel->saveTerms();

        // Persiste os metadados imediatamente para que modelRemapPhaseReferences()
        // possa encontrá-los via SQL direto
        $this->entityOpportunityModel->save(true);
    }

    private function generateRegistrationFieldsAndFiles($opportunityCurrent, $opportunityNew) : void
    {
        $stepMap = [];
        $fieldNameMap = [];

        // mapear steps novos pelos ids dos steps antigos
        $existingSteps = array_column($opportunityNew->registrationSteps->toArray(), null, 'id');

        foreach ($opportunityCurrent->registrationSteps as $oldStep) {
            $stepMap[$oldStep->id] = $existingSteps[$oldStep->id] ?? (function () use ($oldStep, $opportunityNew) {
                $newStep = clone $oldStep;
                $newStep->setOpportunity($opportunityNew);
                $newStep->save(true);
                return $newStep;
            })();
        }

        // Clonar campos e criar mapeamento de fieldName
        $clonedFields = [];
        $conditionalFieldsToUpdate = [];
        
        foreach ($opportunityCurrent->getRegistrationFieldConfigurations() as $oldFieldConfiguration) {
            $oldFieldName = $oldFieldConfiguration->getFieldName();
            
            // Guardar conditional_field original antes de clonar
            $originalConditionalField = $oldFieldConfiguration->conditionalField;
            
            $newFieldConfiguration = clone $oldFieldConfiguration;
            $newFieldConfiguration->setOwnerId($opportunityNew->id);

            // aplicar step correto
            if (isset($stepMap[$oldFieldConfiguration->step->id])) {
                $newFieldConfiguration->setStep($stepMap[$oldFieldConfiguration->step->id]);
            }

            // Limpar conditional_field temporariamente para evitar salvar com valor antigo
            $newFieldConfiguration->conditionalField = null;

            $newFieldConfiguration->save(true);
            
            // mapear fieldName antigo para novo
            $newFieldName = $newFieldConfiguration->getFieldName();
            $fieldNameMap[$oldFieldName] = $newFieldName;
            
            // guardar referência para atualização posterior
            $clonedFields[] = $newFieldConfiguration;
            
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
        
        foreach ($opportunityCurrent->getRegistrationFileConfigurations() as $oldFileConfiguration) {
            // Guardar conditional_field original antes de clonar
            $originalConditionalField = $oldFileConfiguration->conditionalField;
            
            $newFileConfiguration = clone $oldFileConfiguration;
            $newFileConfiguration->setOwnerId($opportunityNew->id);

            // aplicar step correto
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

    private function generateSealsRelations() : void
    {
        foreach ($this->entityOpportunity->getSealRelations() as $sealRelation) {
            $this->entityOpportunityModel->createSealRelation($sealRelation->seal, true, true);
        }
    }
}
