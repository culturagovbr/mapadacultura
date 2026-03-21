<?php

use MapasCulturais\Entities\Agent;
use MapasCulturais\Utils;

/**
 * See https://github.com/Respect/Validation to know how to write validations
 */
return array(
    'metadata' => array(
        'nomeCompleto' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Nome Completo ou Razão Social'),
            'validations' => array(
                //'required' => \MapasCulturais\i::__('Seu nome completo ou jurídico deve ser informado.')
            ),
            'available_for_opportunities' => true
        ),

        'nomeSocial' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Nome Social ou Nome Fantasia'),
            'available_for_opportunities' => true,
        ),

        'escolaridade' => array(
            'private' => false,
            'label' => \MapasCulturais\i::__('Escolaridade'),
            'type' => 'select',
            'options' => array(
               MapasCulturais\i::__('Não possui educação formal'),
               MapasCulturais\i::__('Ensino fundamental incompleto ou equivalente'),
               MapasCulturais\i::__('Ensino fundamental completo ou equivalente'),
               MapasCulturais\i::__('Ensino médio incompleto ou equivalente'),
               MapasCulturais\i::__('Ensino médio completo ou equivalente'),
               MapasCulturais\i::__('Curso técnico incompleto'),
               MapasCulturais\i::__('Curso técnico completo'),
               MapasCulturais\i::__('Ensino superior incompleto ou equivalente'),
               MapasCulturais\i::__('Ensino superior completo'),
               MapasCulturais\i::__('Pós-graduação incompleto'),
               MapasCulturais\i::__('Pós-graduação completo'),
            ),
            'available_for_opportunities' => true,
        ),

        'renda' => array(
            'private' => false,
            'label' => \MapasCulturais\i::__('Renda'),
            'type' => 'select',
            'options' => array(
                MapasCulturais\i::__('Nenhuma renda'),
                MapasCulturais\i::__('De 1,00 a 500,00'),
                MapasCulturais\i::__('De 501,00 a 1.000,00'),
                MapasCulturais\i::__('De 1.001,00 a 2.000,00'),
                MapasCulturais\i::__('De 2.001,00 a 3.000,00'),
                MapasCulturais\i::__('De 3.001,00 a 5.000,00'),
                MapasCulturais\i::__('De 5.001,00 a 10.000,00'),
                MapasCulturais\i::__('De 10.001,00 a 20.000,00'),
                MapasCulturais\i::__('De 20.001,00 a 100.000,00'),
                MapasCulturais\i::__('Acima de 100.000,00'),
            ),
            'validations' => array(
                'required' => \MapasCulturais\i::__('O campo Renda é obrigatório.')
            ),
            'available_for_opportunities' => true,
        ),

        'pessoaDeficiente' => array(
            'label' => \MapasCulturais\i::__('Pessoa com deficiência'),
            'type' => 'multiselect',
            'options' => [
                MapasCulturais\i::__('Não'),
                MapasCulturais\i::__('Sim, Auditiva'),
                MapasCulturais\i::__('Sim, Física-motora'),
                MapasCulturais\i::__('Sim, Intelectual'),
                MapasCulturais\i::__('Sim, Múltipla'),
                MapasCulturais\i::__('Sim, Transtorno do Espectro Autista'),
                MapasCulturais\i::__('Sim, Visual'),
                MapasCulturais\i::__('Sim, Outra'),
            ],
            'available_for_opportunities' => true
        ),

        'comunidadesTradicional' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Comunidades tradicionais'),
            'type' => 'select',
            'options' => array(
                MapasCulturais\i::__('Não pertence a povos ou comunidades tradicionais.'),
                MapasCulturais\i::__('Andirobeiros'),
                MapasCulturais\i::__('Apanhadores de flores sempre vivas'),
                MapasCulturais\i::__('Benzedeiros'),
                MapasCulturais\i::__('Caatingueiros'),
                MapasCulturais\i::__('Caboclos'),
                MapasCulturais\i::__('Caiçaras'),
                MapasCulturais\i::__('Catadores de mangaba'),
                MapasCulturais\i::__('Cipozeiros'),
                MapasCulturais\i::__('Comunidades de fundos e fechos de pasto'),
                MapasCulturais\i::__('Comunidades quilombolas'),
                MapasCulturais\i::__('Extrativistas costeiros e marinhos'),
                MapasCulturais\i::__('Extrativistas'),
                MapasCulturais\i::__('Faxinalenses'),
                MapasCulturais\i::__('Geraizeiros'),
                MapasCulturais\i::__('Ilhéus'),
                MapasCulturais\i::__('Juventude de povos e comunidades tradicionais'),
                MapasCulturais\i::__('Morroquianos'),
                MapasCulturais\i::__('Pantaneiros'),
                MapasCulturais\i::__('Pescadores artesanais'),
                MapasCulturais\i::__('Povo pomerano'),
                MapasCulturais\i::__('Povos ciganos'),
                MapasCulturais\i::__('Povos e comunidades de terreiro/povos e comunidades de matriz africana'),
                MapasCulturais\i::__('Povos indígenas'),
                MapasCulturais\i::__('Quebradeiras de coco babaçu'),
                MapasCulturais\i::__('Raizeiros'),
                MapasCulturais\i::__('Retireiros do Araguaia'),
                MapasCulturais\i::__('Ribeirinhos'),
                MapasCulturais\i::__('Vazanteiros'),
                MapasCulturais\i::__('Veredeiros'),
            ),
            'available_for_opportunities' => true
        ),

        'comunidadesTradicionalOutros' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Digite sua comunidade tradicional'),
            'available_for_opportunities' => true
        ),

        'documento' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CPF ou CNPJ'),
            'serialize' => function($value, $entity = null) {
                /**@var MapasCulturais\App $this */
                if(!$this->rcache->contains("$entity:SET_documento")){
                    $this->rcache->save("$entity:SET_documento", true);
                    if($entity->type && $entity->type->id == 1 && !$this->rcache->contains("$entity:SET_cpf")){
                        $entity->cpf = $value;
                    }else if($entity->type && $entity->type->id == 2 && !$this->rcache->contains("$entity:SET_cnpj")){
                        $entity->cnpj = $value;

                    }
                }

                return Utils::formatCnpjCpf($value);
            },
            'readonly' => true
        ),

        'cnpj' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CNPJ'),
            'type' => 'cnpj',
            'serialize' => function($value, $entity = null) {
                /**@var MapasCulturais\App $this */
                if(!$this->rcache->contains("$entity:SET_cnpj")){
                    $this->rcache->save("$entity:SET_cnpj", true);
                    if($entity->type && $entity->type->id == 2 && !$this->rcache->contains("$entity:SET_documento")){
                        $entity->documento = $value;
                    }
                }
                return Utils::formatCnpjCpf($value);
            },
            'unserialize' => function($value, $entity) {
                if (!$value && isset($entity->type) && $entity->type->id == 2) {
                    $value = $entity->documento;
                }
    
                return Utils::formatCnpjCpf($value);
            },
            'validations' => array(
                'v::cnpj()' => \MapasCulturais\i::__('O número de CNPJ informado é inválido.')
             ),
            'available_for_opportunities' => true,
            'readonly' => true
        ),
        'cnpjAnexo' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CNPJ - anexo'),
            'type' => 'file',
            'available_for_opportunities' => true
        ),
        'cpf' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CPF'),
            'type' => 'cpf',
            'serialize' => function($value, $entity = null) {
                /**@var MapasCulturais\App $this */
                if(!$this->rcache->contains("$entity:SET_cpf")){
                    $this->rcache->save("$entity:SET_cpf", true);
                    
                    if($entity->type && $entity->type->id == 1 && !$this->rcache->contains("$entity:SET_documento")){
                        $entity->documento = $value;
                    }
                }
                return Utils::formatCnpjCpf($value);
            },
            'unserialize' => function($value, $entity) {
                if (!$value) {
                    $value = $entity->documento;
                }

                return Utils::formatCnpjCpf($value);
            },
            'validations' => array(
                'v::cpf()' => \MapasCulturais\i::__('O número de CPF informado é inválido.')
             ),
            'available_for_opportunities' => true,
            'readonly' => true
        ),
        'cpfAnexo' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CPF - anexo'),
            'type' => 'file',
            'available_for_opportunities' => true
        ),
        'cnhNumero' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CNH - Número de registro'),
            'type' => 'cnhNumero',
            'available_for_opportunities' => true,
            'readonly' => false
        ),
        'cnhAnexo' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CNH - anexo'),
            'type' => 'file',
            'available_for_opportunities' => true
        ),
        'cnhCategoria' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CNH - Categoria'),
            'type' => 'multiselect',
            'options' => array(
                '' => \MapasCulturais\i::__('Não informado'),
                'A' => \MapasCulturais\i::__('A'),
                'B' => \MapasCulturais\i::__('B'),
                'C' => \MapasCulturais\i::__('C'),
                'D' => \MapasCulturais\i::__('D'),
                'E' => \MapasCulturais\i::__('E')
            ),
            'available_for_opportunities' => true
        ),
        'cnhValidade' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CNH - Validade'),
            'type' => 'date',
            'serialize' => function($value, $entity = null){
               return (new DateTime($value))->format("Y-m-d");
            },
            'validations' => array(
                'v::date("Y-m-d")' => \MapasCulturais\i::__('Data inválida').'{{format}}',
            ),
            'available_for_opportunities' => true
        ),
        'rgNumero' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('RG - Documento'),
            'type' => 'rgNumero',
            'available_for_opportunities' => true,
            'readonly' => false
        ),
        'rgAnexo' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('RG - anexo'),
            'type' => 'file',
            'available_for_opportunities' => true
        ),
        'rgOrgaoEmissor' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('RG - Órgão Emissor'),
            'type' => 'text',
            'available_for_opportunities' => true
        ),
        'rgUF' => [
            'private' => true,
            'label' => \MapasCulturais\i::__('RG - UF'),
            'type' => 'select',
            'options' => array(
                'AC'=>'Acre',
                'AL'=>'Alagoas',
                'AP'=>'Amapá',
                'AM'=>'Amazonas',
                'BA'=>'Bahia',
                'CE'=>'Ceará',
                'DF'=>'Distrito Federal',
                'ES'=>'Espírito Santo',
                'GO'=>'Goiás',
                'MA'=>'Maranhão',
                'MT'=>'Mato Grosso',
                'MS'=>'Mato Grosso do Sul',
                'MG'=>'Minas Gerais',
                'PA'=>'Pará',
                'PB'=>'Paraíba',
                'PR'=>'Paraná',
                'PE'=>'Pernambuco',
                'PI'=>'Piauí',
                'RJ'=>'Rio de Janeiro',
                'RN'=>'Rio Grande do Norte',
                'RS'=>'Rio Grande do Sul',
                'RO'=>'Rondônia',
                'RR'=>'Roraima',
                'SC'=>'Santa Catarina',
                'SP'=>'São Paulo',
                'SE'=>'Sergipe',
                'TO'=>'Tocantins',
            )
        ],
        'raca' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Raça/cor'),
            'type' => 'select',
            'options' => array(
                'Branca' => \MapasCulturais\i::__('Branca'),
                'Preta' => \MapasCulturais\i::__('Preta'),
                'Amarela' => \MapasCulturais\i::__('Amarela'),
                'Parda' => \MapasCulturais\i::__('Parda'),
                'Indígena' => \MapasCulturais\i::__('Indígena')
            ),
            'validations' => array(
                'required' => \MapasCulturais\i::__('O campo Raça/cor é obrigatório.')
            ),
            'available_for_opportunities' => true
        ),

        'dataDeNascimento' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Data de Nascimento/Fundação'),
            'type' => 'date',
            'serialize' => function($value, $entity = null){
               if(is_null($value)) { return null; }
               $this->hook("entity(<<*>>).save:before", function() use ($entity){
                    /** @var MapasCulturais\Entity $entity */
                    if($this->equals($entity)){
                        $this->idoso = 1; 
                    }
               });
               return (new DateTime($value))->format("Y-m-d");
            },
            'validations' => array(
                'v::date("Y-m-d")' => \MapasCulturais\i::__('Data inválida').'{{format}}',
            ),
            'available_for_opportunities' => true
        ),

        'idoso' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Pessoa idosa'),
            'type' => 'readonly',
            'serialize' => function($value, $entity = null) {
                if ($entity->dataDeNascimento) {
                    $today = new DateTime('now');
                    $birthdate = new DateTime($entity->dataDeNascimento);
                    $age = $birthdate->diff($today)->y;
                    return ($age >= 60);
                } else {
                    return false;
                }
            },
            'unserialize' => function($value){
                return $value ? true : false;
            },
            'available_for_opportunities' => true
        ),

        'genero' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Identidade de Gênero'),
            'type' => 'select',
            'options' => array(
                'Prefiro não declarar' => \MapasCulturais\i::__('Prefiro não declarar'),
                'Homem cisgênero' => \MapasCulturais\i::__('Homem cisgênero'),
                'Mulher cisgênero' => \MapasCulturais\i::__('Mulher cisgênero'),
                'Homem trans' => \MapasCulturais\i::__('Homem trans'),
                'Mulher trans' => \MapasCulturais\i::__('Mulher trans'),
                'Travesti' => \MapasCulturais\i::__('Travesti'),
                'Não binário' => \MapasCulturais\i::__('Não binário'),
                'Outro' => \MapasCulturais\i::__('Outro'),
            ),
            'available_for_opportunities' => true,
            'field_type' => 'select'
        ),

        'pessoaTrans' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('É pessoa trans ou travesti'),
            'type' => 'select',
            'options' => array(
                'Não' => \MapasCulturais\i::__('Não'),
                'Sim' => \MapasCulturais\i::__('Sim'),
                'Prefiro não declarar' => \MapasCulturais\i::__('Prefiro não declarar'),
            ),
            'available_for_opportunities' => true,
            'field_type' => 'select'
        ),

        'pessoaIntersexo' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('É pessoa intersexo'),
            'type' => 'select',
            'options' => array(
                'Não' => \MapasCulturais\i::__('Não'),
                'Sim' => \MapasCulturais\i::__('Sim'),
                'Prefiro não declarar' => \MapasCulturais\i::__('Prefiro não declarar'),
            ),
            'available_for_opportunities' => true,
            'field_type' => 'select'
        ),

        'orientacaoSexual' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Orientação Sexual'),
            'type' => 'select',
            'options' => array(
                'Lésbica' => \MapasCulturais\i::__('Lésbica'),
                'Gay' => \MapasCulturais\i::__('Gay'),
                'Heterossexual' => \MapasCulturais\i::__('Heterossexual'),
                'Bissexual' => \MapasCulturais\i::__('Bissexual'),
                'Outra' => \MapasCulturais\i::__('Outra'),
                'Prefere não responder' => \MapasCulturais\i::__('Prefere não responder'),
            ),
            'available_for_opportunities' => true
        ),
        'agenteItinerante' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Agente Itinerante'),
            'type' => 'select',
            'options' => array(
                '' => \MapasCulturais\i::__('Não Informado'),
                'Sim' => \MapasCulturais\i::__('Sim'),
                'Não' => \MapasCulturais\i::__('Não'),
            ),
            'available_for_opportunities' => true
        ),
        'emailPublico' => array(
            'label' => \MapasCulturais\i::__('Email Público'),
            'validations' => array(
                'v::email()' => \MapasCulturais\i::__('O endereço informado não é email válido.')
            ),
            'available_for_opportunities' => true,
            'field_type' => 'email'
        ),

        'emailPrivado' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Email Privado'),
            'validations' => array(
                //'required' => \MapasCulturais\i::__('O email privado é obrigatório.'),
                'v::email()' => \MapasCulturais\i::__('O endereço informado não é um email válido.')
            ),
            'available_for_opportunities' => true,
            'field_type' => 'email',
            'unserialize' => function($value, $agent = null){
                if(!$value && $agent){
                    return $agent->user->email;
                }
                return $value;
            }
        ),

        'telefonePublico' => array(
            'label' => \MapasCulturais\i::__('Telefone Público'),
            'type' => 'string',
            'validations' => array(
                'v::brPhone()' => \MapasCulturais\i::__('O número de telefone informado é inválido.')
            ),
            'available_for_opportunities' => true,
            'field_type' => 'brPhone'
        ),

        'telefone1' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Telefone 1'),
            'type' => 'string',
            'validations' => array(
                'v::brPhone()' => \MapasCulturais\i::__('O número de telefone informado é inválido.')
            ),
            'available_for_opportunities' => true,
            'field_type' => 'brPhone'
        ),


        'telefone2' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('Telefone 2'),
            'type' => 'string',
            'validations' => array(
                'v::brPhone()' => \MapasCulturais\i::__('O número de telefone informado é inválido.')
            ),
            'available_for_opportunities' => true,
            'field_type' => 'brPhone'
        ),

        'endereco' => array(
            'private' => function(){
                return !$this->publicLocation;
            },
            'label' => \MapasCulturais\i::__('Endereço'),
            'type' => 'text'
        ),
                    
        'En_CEP' => [
            'label' => \MapasCulturais\i::__('CEP'),
            'type' => 'cep',
            'private' => function(){
                return !$this->publicLocation;
            },
        ],
        'En_Nome_Logradouro' => [
            'label' => \MapasCulturais\i::__('Logradouro'),
            'private' => function(){
                return !$this->publicLocation;
            },
        ],
        'En_Num' => [
            'label' => \MapasCulturais\i::__('Número'),
            'private' => function(){
                return !$this->publicLocation;
            },
        ],
        'En_Complemento' => [
            'label' => \MapasCulturais\i::__('Complemento'),
            'private' => function(){
                return !$this->publicLocation;
            },
        ],
        'En_Bairro' => [
            'label' => \MapasCulturais\i::__('Bairro'),
            'private' => function(){
                return !$this->publicLocation;
            },
        ],
        'En_Municipio' => [
            'label' => \MapasCulturais\i::__('Município'),
            'private' => function(){
                return !$this->publicLocation;
            },
        ],
        'En_Estado' => [
            'label' => \MapasCulturais\i::__('Estado'),
            'private' => function(){
                return !$this->publicLocation;
            },
            'type' => 'select',

            'options' => array(
                'AC'=>'Acre',
                'AL'=>'Alagoas',
                'AP'=>'Amapá',
                'AM'=>'Amazonas',
                'BA'=>'Bahia',
                'CE'=>'Ceará',
                'DF'=>'Distrito Federal',
                'ES'=>'Espírito Santo',
                'GO'=>'Goiás',
                'MA'=>'Maranhão',
                'MT'=>'Mato Grosso',
                'MS'=>'Mato Grosso do Sul',
                'MG'=>'Minas Gerais',
                'PA'=>'Pará',
                'PB'=>'Paraíba',
                'PR'=>'Paraná',
                'PE'=>'Pernambuco',
                'PI'=>'Piauí',
                'RJ'=>'Rio de Janeiro',
                'RN'=>'Rio Grande do Norte',
                'RS'=>'Rio Grande do Sul',
                'RO'=>'Rondônia',
                'RR'=>'Roraima',
                'SC'=>'Santa Catarina',
                'SP'=>'São Paulo',
                'SE'=>'Sergipe',
                'TO'=>'Tocantins',
            )
        ],
        'En_Pais' => [
            'label' => \MapasCulturais\i::__('País'),
            'type' => 'select',
            'default' => function(){
                $app = MapasCulturais\App::i();
                return $app->config['app.defaultCountry'];
            },
            'options' => [
                'AD' => 'Andorra',
                'AR' => 'Argentina',
                'BO' => 'Bolivia',
                'BR' => 'Brasil',
                'CL' => 'Chile',
                'CO' => 'Colombia',
                'CR' => 'Costa Rica',
                'CU' => 'Cuba',
                'EC' => 'Ecuador',
                'SV' => 'El Salvador',
                'ES' => 'España',
                'GT' => 'Guatemala',
                'HN' => 'Honduras',
                'MX' => 'México',
                'NI' => 'Nicarágua',
                'PA' => 'Panamá',
                'PY' => 'Paraguay',
                'PE' => 'Perú',
                'PT' => 'Portugal',
                'DO' => 'República Dominicana',
                'UY' => 'Uruguay',
                'VE' => 'Venezuela',
            ]
        ],

        'site' => array(
            'label' => \MapasCulturais\i::__('Site'),
            'validations' => array(
                "v::url()" => \MapasCulturais\i::__("A url informada é inválida."),
            ),
            'available_for_opportunities' => true
        ),
        'facebook' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Facebook'),
            'serialize' =>function($value){
                return Utils::parseSocialMediaUser('facebook.com', $value);
            },
            'validations' => array(
                "v::oneOf(v::urlDomain('facebook.com'), v::regex('/^@?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL válida ou o nome ou id do usuário.")
            ),
            'placeholder' => \MapasCulturais\i::__('nomedousuario ou iddousuario'),
            'available_for_opportunities' => true
        ),
        'twitter' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Twitter'),
            'serialize' =>function($value){
                return Utils::parseSocialMediaUser('x.com', $value);
            },
            'validations' => array(
                "v::oneOf(v::urlDomain('x.com'), v::regex('/^@?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'placeholder' => \MapasCulturais\i::__('nomedousuario'),
            'available_for_opportunities' => true
        ),
        'instagram' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Instagram'),
            'available_for_opportunities' => true,
            'serialize' =>function($value){
                $result = Utils::parseSocialMediaUser('instagram.com', $value);
                if($result && $result[0] == '@'){
                    $result = substr($result,1);
                }
                return $result;
            },
            'validations' => array(
                "v::oneOf(v::urlDomain('instagram.com'), v::regex('/^@?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'placeholder' => \MapasCulturais\i::__('nomedousuario'),
        ),
        'linkedin' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Linkedin'),
            'serialize' =>function($value){
                return Utils::parseSocialMediaUser('linkedin.com', $value, 'linkedin');
            },
            'validations' => array(
                "v::oneOf(v::urlDomain('linkedin.com'), v::regex('/^@?([\-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'placeholder' => \MapasCulturais\i::__('nomedousuario'),
            'available_for_opportunities' => true
        ),
      'vimeo' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Vimeo'),
            'validations' => array(
                "v::oneOf(v::urlDomain('vimeo.com'), v::regex('/^@?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'serialize' =>function($value){
                return Utils::parseSocialMediaUser('vimeo.com', $value);
            },
            'placeholder' => \MapasCulturais\i::__('nomedousuario'),
            'available_for_opportunities' => true
        ),
        'spotify' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Spotify'),
            'validations' => array(
                "v::oneOf(v::urlDomain('open.spotify.com'), v::regex('/^@?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'serialize' => function($value) {
                return Utils::parseSocialMediaUser('open.spotify.com', $value);
            },
            'placeholder' => \MapasCulturais\i::__('nomedousuario'),
            'available_for_opportunities' => true
        ),
        'youtube' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('YouTube'),
            'validations' => array(
                "v::oneOf(v::urlDomain('youtube.com'), v::regex('/^(@|channel\/)?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'serialize' =>function($value){
                return Utils::parseSocialMediaUser('youtube.com', $value);
            },
            'placeholder' => \MapasCulturais\i::__('iddocanal'),
            'available_for_opportunities' => true
        ),
        'pinterest' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Pinterest'),
            'validations' => array(
                "v::oneOf(v::urlDomain('pinterest.com'), v::regex('/^@?([\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'serialize' =>function($value){
                return Utils::parseSocialMediaUser('pinterest.com', $value);
            },
            'placeholder' => \MapasCulturais\i::__('nomedousuario'),
            'available_for_opportunities' => true
        ),
        'tiktok' => array(
            'type' => "socialMedia",
            'label' => \MapasCulturais\i::__('Tiktok'),
            'serialize' =>function($value){
                return Utils::parseSocialMediaUser('tiktok.com', $value);
            },
            'validations' => array(
                "v::oneOf(v::urlDomain('tiktok.com'), v::regex('/^@?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
            ),
            'placeholder' => \MapasCulturais\i::__('nomedousuario'),
            'available_for_opportunities' => true
        ),
    ),
    'items' => array(
        1 => array( 'name' => \MapasCulturais\i::__('Individual' )),
        2 => array( 'name' => \MapasCulturais\i::__('Coletivo') ),
    )
);
