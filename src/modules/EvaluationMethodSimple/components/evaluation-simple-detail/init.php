<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
*/

use MapasCulturais\Entities\Registration;

$entity = $this->controller->requestedEntity;

// SOLUÇÃO TEMPORÁRIA
$class = $entity->getClassName();

if($class == Registration::class) {
    $needs_tiebreaker = $entity->needsTieBreaker();
    
    $opportunity = $entity->opportunity;
    $evaluation_configuration = $opportunity->evaluationMethodConfiguration;
    $enable_external_reviews = $evaluation_configuration->showExternalReviews;
    
    $related_agents = $evaluation_configuration->relatedAgents;
    $is_minerva_group = false;
    
    foreach($related_agents as $group => $agents) {
        if($group == '@tiebreaker') {
            foreach($agents as $agent) {
                if($agent->id == $app->user->profile->id) {
                    $is_minerva_group = true;
                }
            }
        }
    }
    
    $data = [];
    if ($needs_tiebreaker && $is_minerva_group && $enable_external_reviews) {
        if ($evaluation_configuration->publishEvaluationDetails){
            $em = $evaluation_configuration->evaluationMethod;
            $data['consolidatedDetails'] = $em->getConsolidatedDetails($entity);
            $data['evaluationsDetails'] = [];
    
            $evaluations = $entity->sentEvaluations;
    
            foreach($evaluations as $eval) {
                $detail = $em->getEvaluationDetails($eval);
                if ($evaluation_configuration->publishValuerNames){
                    $detail['valuer'] = $eval->user->profile->simplify('id,name,singleUrl');
                }
                $data['evaluationsDetails'][] = $detail;
            }
        }
    }
    
    $this->jsObject['config']['simpleEvaluationDetail'] = [
        'data' => $data,
    ];
}