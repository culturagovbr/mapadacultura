<?php

namespace WorkplanSyncTransfereGov;

use MapasCulturais\App;
use MapasCulturais\i;

class Plugin extends \MapasCulturais\Plugin {
    function _init() {
        $app = App::i();

        $app->hook('app.init:after', function () use($app) {

            $app->hook("template(opportunity-data-collection-config):end", function(){
                $this->part('opportunity-workplan-config');
            });
        });

        $app->hook('panel.nav', function (&$group) use ($app) {
            $group['admin']['items'][] = [
                'route' => 'config/transferegov',
                'icon' => 'integration',
                'label' => i::__('Integração TransfereGov'),
                'condition' => function () use ($app) {
                    return $app->user->is('saasAdmin');
                }
            ];
        });

        /* $app->hook('auth.login', function($user) use($app){ */
        /*    /** @var User $user */
        /*    // $agents = $app->repo('Agent')->findBy(['user' => $user, '_type' => 1]); */
        /*    $agents = $app->repo('Agent')->findBy(['user' => $user]); */
        /*    foreach($agents as $agent) { */
        /*        $app->enqueueEntityToPCacheRecreation($agent); */
        /*    } */
        /* }); */
    }

    function register() {
        $this->registerRegistrationMetadata('transferegov_plano_acao_id', [
            'label' => i::__('ID do plano de ação no TransfereGov'),
            'type' => 'integer',
            'private' => false,
            'default' => null
        ]);
        $this->registerRegistrationMetadata('transferegov_plano_acao_meta_id', [
            'label' => i::__('ID da meta do plano de ação no TransfereGov'),
            'type' => 'integer',
            'private' => false,
            'default' => null
        ]);
    }

    function get_http_response_content_json($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new \Exception("Failed to fetch data from TransfereGov. HTTP Code: $http_code");
        }

        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse JSON response: " . json_last_error_msg());
        }

        return $data;
    }

    function get_transfreregov_plano_de_acao() {
        $url = 'https://api.transferegov.gestao.gov.br/fundoafundo/plano_acao?id_programa=eq.60&select=*%2Cplano_acao_analise(*%2Cplano_acao_analise_responsavel(*))&limit=100';
        return $this->get_http_response_content_json($url);
    }

    function get_transfreregov_meta($plano_acao_id) {
        if (!$plano_acao_id) {
            throw new \Exception("Invalid plano_acao_id");
        }
        $url = 'https://api.transferegov.gestao.gov.br/fundoafundo/plano_acao_meta?id_plano_acao=eq.'.$plano_acao_id.'&limit=100';
        return $this->get_http_response_content_json($url);
    }

    function get_or_create_registration($opportunity, $plano_acao) {
        $app = App::i();

        // Try to find existing registration by opportunity and plano_acao_id
        $registrations = $app->repo('Registration')->findBy(['opportunity' => $opportunity]);
        $registration = null;

        foreach ($registrations as $reg) {
            if ($reg->getMetadata('transferegov_plano_acao_id') == $plano_acao['id_plano_acao']) {
                $registration = $reg;
                break;
            }
        }

        if (!$registration) {
            $registration = new \MapasCulturais\Entities\Registration;
            $registration->opportunity = $opportunity;
            
            // Find an admin user by checking all users
            $users = $app->repo('User')->findAll();
            $admin = null;
            foreach ($users as $user) {
                if ($user->is('admin')) {
                    $admin = $user;
                    break;
                }
            }
            
            if (!$admin) {
                throw new \Exception("No admin user found in the system");
            }
            
            // Get the user's profile agent
            $agent = $admin->profile;
            if (!$agent) {
                throw new \Exception("No profile agent found for admin user");
            }
            
            $registration->owner = $agent;
            
            // Save TransfereGov metadata using setMetadata 
            $registration->setMetadata('transferegov_plano_acao_id', $plano_acao['id_plano_acao']);
            // $registration->setMetadata('transferegov_numero_plano_acao', $plano_acao['numero_plano_acao']);
            // $registration->setMetadata('transferegov_ano_plano_acao', $plano_acao['ano_plano_acao']);

            $registration->save(true);
        }

        return $registration;
    }

    function login($user_id){
        $app = App::i();
        $app->auth->authenticateUser($app->repo('User')->find($user_id));
    }

    function ensure_admin_login() {
        $app = App::i();
        
        // If no user is logged in or current user is not admin
        if (!$app->user || !$app->user->is('admin')) {
            // Find an admin user by checking all users
            $users = $app->repo('User')->findAll();
            $admin = null;
            foreach ($users as $user) {
                if ($user->is('admin')) {
                    $admin = $user;
                    break;
                }
            }
            
            if (!$admin) {
                throw new \Exception("No admin user found in the system");
            }
            
            // Login as admin
            $this->login($admin->id);
        }
        
        return $app->user;
    }

    function create_workplan_goal($workplan, $meta, $registration) {
        $goal = new \OpportunityWorkplan\Entities\Goal();
        $goal->workplan = $workplan;
        $goal->owner = $registration->owner;
        $goal->createTimestamp = new \DateTime();
        $goal->updateTimestamp = new \DateTime();

        // Set goal metadata
        $goal->monthInitial = 1;
        $goal->monthEnd = 12;
        $goal->title = $meta['nome_meta_plano_acao'];
        $goal->description = $meta['descricao_meta_plano_acao'];
        $goal->culturalMakingStage = 'Execução';
        $goal->amount = $meta['valor_meta_plano_acao'];

        // Save TransfereGov metadata using setMetadata
        $goal->setMetadata('transferegov_meta_id', $meta['id_meta_plano_acao']);
        // $goal->setMetadata('transferegov_numero_meta', $meta['numero_meta_plano_acao']);

        $goal->save(true);

        return $goal;
    }

    function create_workplan_delivery($goal, $meta, $registration) {
        $delivery = new \OpportunityWorkplan\Entities\Delivery();
        $delivery->goal = $goal;
        $delivery->owner = $registration->owner;
        $delivery->createTimestamp = new \DateTime();
        $delivery->updateTimestamp = new \DateTime();

        // Set delivery metadata
        $delivery->name = "Entrega " . $meta['numero_meta_plano_acao'];
        $delivery->description = $meta['descricao_meta_plano_acao'];
        $delivery->type = 'Meta';
        $delivery->segmentDelivery = 'Plano de Ação';
        $delivery->budgetAction = $meta['valor_meta_plano_acao'];
        $delivery->expectedNumberPeople = 0;
        $delivery->generaterRevenue = false;
        $delivery->renevueQtd = 0;
        $delivery->unitValueForecast = $meta['valor_meta_plano_acao'];
        $delivery->totalValueForecast = $meta['valor_meta_plano_acao'];

        // Save TransfereGov metadata using setMetadata
        $delivery->setMetadata('transferegov_meta_id', $meta['id_meta_plano_acao']);

        $delivery->save(true);

        return $delivery;
    }

    function generate_workplan($opportunity_id) {
        $app = App::i();
        $app->disableAccessControl();

        try {
            // Ensure we have an admin user logged in
            $this->ensure_admin_login();
            
            // Get opportunity
            $opportunity = $app->repo('Opportunity')->find($opportunity_id);
            if (!$opportunity) {
                throw new \Exception("Opportunity not found with ID: $opportunity_id");
            }

            // Fetch data from TransfereGov first
            $plano_acao_data = $this->get_transfreregov_plano_de_acao();
            if (empty($plano_acao_data)) {
                throw new \Exception("No plano de ação found in TransfereGov");
            }

            $workplans = [];
            // Iterate over all plano_acao entries
            foreach ($plano_acao_data as $plano_acao) {
                // Get or create registration with TransfereGov data
                $registration = $this->get_or_create_registration($opportunity, $plano_acao);

                // Generate workplan using the registration
                $workplan = $this->generate_workplan_from_transferegov($registration->id);
                $workplans[] = $workplan;
            }

            return $workplans;

        } catch (\Exception $e) {
            $app->enableAccessControl();
            throw $e;
        }
    }

    function generate_workplan_from_transferegov($registration_id) {
        $app = App::i();
        $app->disableAccessControl();

        try {
            // Ensure we have an admin user logged in
            $this->ensure_admin_login();
            
            // Get registration
            $registration = $app->repo('Registration')->find($registration_id);
            if (!$registration) {
                throw new \Exception("Registration not found with ID: $registration_id");
            }

            // Check if workplan already exists for this registration
            $existing_workplan = $app->repo('OpportunityWorkplan\Entities\Workplan')
                ->findOneBy(['registration' => $registration]);

            if ($existing_workplan) {
                return $existing_workplan;
            }

            // Get plano_acao_id from registration metadata using getMetadata
            $plano_acao_id = $registration->getMetadata('transferegov_plano_acao_id');
            if (!$plano_acao_id) {
                throw new \Exception("Registration does not have TransfereGov plano_acao_id");
            }

            // Fetch metas from TransfereGov
            $plano_acao_meta = $this->get_transfreregov_meta($plano_acao_id);
            if (empty($plano_acao_meta)) {
                throw new \Exception("No metas found for plano de ação ID: $plano_acao_id");
            }

            // Create Workplan
            $workplan = new \OpportunityWorkplan\Entities\Workplan();
            $workplan->registration = $registration;
            $workplan->owner = $registration->owner;
            $workplan->createTimestamp = new \DateTime();
            $workplan->updateTimestamp = new \DateTime();

            // Set workplan metadata
            $workplan->projectDuration = $plano_acao['data_fim_vigencia_plano_acao'] ?? null;
            $workplan->culturalArtisticSegment = 'Plano de Ação';

            // Save TransfereGov metadata using setMetadata
            $workplan->setMetadata('transferegov_plano_acao_id', $plano_acao_id);

            $workplan->save(true);

            // Create Goals and Deliveries
            foreach($plano_acao_meta as $meta) {
                $goal = $this->create_workplan_goal($workplan, $meta, $registration);
                $this->create_workplan_delivery($goal, $meta, $registration);
            }

            $app->enableAccessControl();
            return $workplan;

        } catch (\Exception $e) {
            $app->enableAccessControl();
            throw $e;
        }
    }
}
