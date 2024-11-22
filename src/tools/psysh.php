<?php
require __DIR__ . '/../../public/bootstrap.php';

$em = $app->em;

function _array_to_print($rs, $prefix = ''){
    $sizes = [];
    $result = [];
    
    foreach($rs as $_item){
        $item = [];
        if(is_array($_item)){
            foreach($_item as $key => $value){
                $key = $prefix . $key;
                if(is_array($value)){
                    $r = _array_to_print([$value], "$key.");
                    $item += $r['result'][0];
                    foreach($r['sizes'] as $mk => $size){
                        if( !isset($sizes[$mk]) || $size > $sizes[$mk]){
                            $sizes[$mk] = $size;
                        }
                    }
                } else {
                    $item[$key] = $value;
                    if(!isset($sizes[$key]) || $sizes[$key] < mb_strlen("$value")){
                        $sizes[$key] =  mb_strlen("$value") + 2;
                    }
                }
            }
            $result[] = $item;
        }
    }
    
    return ['result' => $result, 'sizes' => $sizes];
}

function print_table($rs){
    $first = true;
    
    $rs = _array_to_print($rs);
    $sum = 0;
    $line1 = [];
    $line2 = [];
    foreach($rs['sizes'] as $k => $v){
        $v = intval($v);
        if($v < mb_strlen($k) + 2){
            $v = mb_strlen($k) + 2;
            $rs['sizes'][$k] = $v;
        }
        $line1[] = str_pad($k, $v, ' ', STR_PAD_BOTH);
        $line2[] = str_pad('', $v, '-');
        $sum += $v;
    }
    echo "\n " . implode('| ', $line1);
    echo "\n " . implode('+-', $line2);
    
    foreach($rs['result'] as $item){
        $line = [];
        foreach($item as $k => $v){
            $line[] = mb_str_pad($v, $rs['sizes'][$k]);
        }
        
        echo "\n " . implode('| ', $line);
    }
}

function login($user_id){
    $app = MapasCulturais\App::i();
    $app->auth->login($user_id);
}

function api($entity, $_params, $print=true){
    if(is_string($_params)){
        parse_str($_params,$params);
    } else {
        $params = $_params;
    }
    $rs = new MapasCulturais\ApiQuery("MapasCulturais\Entities\\$entity", $params);
    if($print){
        print_table($rs->find());
    } else {
        return $rs;
    }
}

function get_user($user){
    $app = MapasCulturais\App::i();

    if($user instanceof \MapasCulturais\Entities\User){
        return $user;
    } else if(is_numeric($user)){
        return $app->repo('User')->find($user);
    } else if(is_string($user)){
        return $app->repo('User')->findOneBy(['email' => $user]);
    }
    return null;
}

class role{
    
    static function add($user, $role, $subsite_id = null){
        $app = MapasCulturais\App::i();

        if($user = get_user($user)){
            $app->disableAccessControl();
            $user->addRole($role, $subsite_id);
            $app->enableAccessControl();
        }
    }

    static function remove($user, $role, $subsite_id = null){
        $app = MapasCulturais\App::i();
        
        if($user = get_user($user)){
            $app->disableAccessControl();
            $user->removeRole($role, $subsite_id);
            $app->enableAccessControl();
        }
    }
    
    static function ls($role = null, $subsite_id = false){
        $params = ['@SELECT' => 'name,user.{id,email,profile.name},subsite.name'];
        if($role){
            $params['name'] = "ILIKE($role)";
        }
        if($subsite_id !== false){
            if(is_null($subsite_id)){
                $params['subsite'] = "NULL()";
            } else {
                $params['subsite'] = "EQ($subsite_id)";
            }
        }
        
        $rs = api('role', $params)->find();
        print_table($rs);
    }
}

function get_http_response_content_json($url){
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
    return get_http_response_content_json($url);
}

function get_transfreregov_meta($plano_acao_id) {
    if (!$plano_acao_id) {
        throw new \Exception("Invalid plano_acao_id");
    }
    $url = 'https://api.transferegov.gestao.gov.br/fundoafundo/plano_acao_meta?id_plano_acao=eq.'.$plano_acao_id.'&limit=100';
    return get_http_response_content_json($url);
}

function get_or_create_registration($opportunity, $plano_acao) {
    $app = MapasCulturais\App::i();
    
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
        $registration->owner = $app->user;
        
        // Save TransfereGov metadata using setMetadata
        $registration->setMetadata('transferegov_plano_acao_id', $plano_acao['id_plano_acao']);
        $registration->setMetadata('transferegov_numero_plano_acao', $plano_acao['numero_plano_acao']);
        $registration->setMetadata('transferegov_ano_plano_acao', $plano_acao['ano_plano_acao']);
        
        $registration->save(true);
    }
    
    return $registration;
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
    $goal->setMetadata('transferegov_numero_meta', $meta['numero_meta_plano_acao']);
    
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
    $app = MapasCulturais\App::i();
    $app->disableAccessControl();

    try {
        // Get opportunity
        $opportunity = $app->repo('Opportunity')->find($opportunity_id);
        if (!$opportunity) {
            throw new \Exception("Opportunity not found with ID: $opportunity_id");
        }

        // Fetch data from TransfereGov first
        $plano_acao_data = get_transfreregov_plano_de_acao();
        if (empty($plano_acao_data)) {
            throw new \Exception("No plano de ação found in TransfereGov");
        }
        $plano_acao = $plano_acao_data[0];

        // Get or create registration with TransfereGov data
        $registration = get_or_create_registration($opportunity, $plano_acao);

        // Generate workplan using the registration
        return generate_workplan_from_transferegov($registration->id);

    } catch (\Exception $e) {
        $app->enableAccessControl();
        throw $e;
    }
}

function generate_workplan_from_transferegov($registration_id) {
    $app = MapasCulturais\App::i();
    $app->disableAccessControl();

    try {
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
        $plano_acao_meta = get_transfreregov_meta($plano_acao_id);
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
            $goal = create_workplan_goal($workplan, $meta, $registration);
            create_workplan_delivery($goal, $meta, $registration);
        }

        $app->enableAccessControl();
        return $workplan;

    } catch (\Exception $e) {
        $app->enableAccessControl();
        throw $e;
    }
}

echo "
================================
VARIÁVEIS DISPONÍVEIS: 
  \$app, \$em
  
para logar: login(id do usuário);

para criar uma ApiQuery: \033[33mapi(\$entity, \$params);\033[0m (exemplo: api('agent', ['@select' => 'id,name']))

para adicionar uma role a um usuário: \033[33mrole::add(\$user_id, 'roleName', \$subsite_id = null);\033[0m (exemplo role::add(1, 'saasSuperAdmin'))
para remover uma role a um usuário: \033[33mrole::remove(\$user_id, 'roleName', \$subsite_id = null);\033[0m (exemplo role::remove(1, 'saasSuperAdmin'))

";

eval(\psy\sh());
