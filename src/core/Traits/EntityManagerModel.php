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
        $this->entityOpportunityModel = $this->generateModel();

        $this->generateEvaluationMethods();
        $this->generatePhases();
        $this->generateMetadata();
        $this->generateRegistrationFieldsAndFiles($this->entityOpportunity, $this->entityOpportunityModel);
        $this->generateSealsRelations();

        $this->entityOpportunityModel->save(true);
        
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

        $app->disableAccessControl();
        $this->entityOpportunityModel = $this->generateOpportunity();

        $this->generateEvaluationMethods();
        $this->generatePhases();
        $this->generateMetadata(0, 0);
        $this->generateRegistrationFieldsAndFiles($this->entityOpportunity, $this->entityOpportunityModel);

        $this->entityOpportunityModel->save(true);
       
        $app->enableAccessControl();

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
            $app->em->beginTransaction();            
            $app->em->getConnection()->update('opportunity', [
                    'object_type' => $ownerEntity->getClassName(), 
                    'object_id' => $ownerEntity->id
                ], ['id' => $id]);

            $app->em->commit();
        }
    }

    private function generateEvaluationMethods() : void
    {
        $app = App::i();

        // duplica o método de avaliação para a oportunidade primária
        $evaluationMethodConfigurations = $app->repo('EvaluationMethodConfiguration')->findBy([
            'opportunity' => $this->entityOpportunity
        ]);
        foreach ($evaluationMethodConfigurations as $evaluationMethodConfiguration) {
            $newMethodConfiguration = clone $evaluationMethodConfiguration;
            $newMethodConfiguration->setOpportunity($this->entityOpportunityModel);
            $newMethodConfiguration->save(true);

            // duplica os metadados das configurações do modelo de avaliação
            foreach ($evaluationMethodConfiguration->getMetadata() as $metadataKey => $metadataValue) {
                $newMethodConfiguration->setMetadata($metadataKey, $metadataValue);
                $newMethodConfiguration->save(true);
            }
        }
    }

    private function generatePhases() : void
    {
        $app = App::i();
        $postData = $this->postData;

        $phases = $app->repo('Opportunity')->findBy([
            'parent' => $this->entityOpportunity
        ]);
        foreach ($phases as $phase) {
            
            if (!$phase->getMetadata('isLastPhase')) {
                $newPhase = clone $phase;
                $newPhase->setParent($this->entityOpportunityModel);
                $newPhase->owner = $app->user->profile;

                foreach ($phase->getMetadata() as $metadataKey => $metadataValue) {
                    if (!is_null($metadataValue) && $metadataValue != '') {
                        $newPhase->setMetadata($metadataKey, $metadataValue);
                        $newPhase->save(true);
                    }
                }

                $this->generateRegistrationFieldsAndFiles($phase, $newPhase);

                $now = new \DateTime('now');
                $newPhase->createTimestamp = $now;
                $newPhase->subsite = $phase->subsite;

                $newPhase->save(true);

                $this->changeObjectType($newPhase->id);

                $evaluationMethodConfigurations = $app->repo('EvaluationMethodConfiguration')->findBy([
                    'opportunity' => $phase
                ]);

                foreach ($evaluationMethodConfigurations as $evaluationMethodConfiguration) {
                    $newMethodConfiguration = clone $evaluationMethodConfiguration;
                    $newMethodConfiguration->setOpportunity($newPhase);
                    $newMethodConfiguration->save(true);

                    // duplica os metadados das configurações do modelo de avaliação para a fase
                    foreach ($evaluationMethodConfiguration->getMetadata() as $metadataKey => $metadataValue) {
                        $newMethodConfiguration->setMetadata($metadataKey, $metadataValue);
                        $newMethodConfiguration->save(true);
                    }
                }
            }
            

            if ($phase->getMetadata('isLastPhase')) {
                $publishDate = $phase->publishTimestamp;
                $subsite = $phase->subsite;
            }
        }

        if (isset($publishDate)) {
            $phases = $app->repo('Opportunity')->findBy([
                'parent' => $this->entityOpportunityModel
            ]);
    
            foreach ($phases as $phase) {
                if ($phase->getMetadata('isLastPhase')) {
                    $phase->setPublishTimestamp($publishDate);
                    $phase->subsite = $subsite;
                    $phase->save(true);

                    $this->changeObjectType($phase->id);
                }
            }
        }   
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
