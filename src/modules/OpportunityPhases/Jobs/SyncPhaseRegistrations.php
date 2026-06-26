<?php
namespace OpportunityPhases\Jobs;

use MapasCulturais\App;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Definitions\JobType;

class SyncPhaseRegistrations extends JobType
{
    const SLUG = "SyncPhaseRegistrations";

    protected function _generateId(array $data, string $start_string, string $interval_string, int $iterations)
    {
        return "SyncPhaseRegistrations:{$data['opportunity']->id}";
    }

    protected function _execute(\MapasCulturais\Entities\Job $job){
        $app = App::i();
        
        /** @var Opportunity $opportunity */
        $opportunity = $job->opportunity;

        $registrations = $job->registrations ?: [];

        // syncRegistrations verifica @control sobre a fase, mas este job é uma operação
        // de sistema — o resultado não deve depender de quem disparou o evento.
        if ($opportunity) {
            $app->disableAccessControl();
            try {
                $opportunity->syncRegistrations($registrations);
            } finally {
                $app->enableAccessControl();
            }
        }

        return true;
    }    
}