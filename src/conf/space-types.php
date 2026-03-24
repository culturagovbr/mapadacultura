<?php

use MapasCulturais\Utils;
/**
 * See https://github.com/Respect/Validation to know how to write validations
 */

 $items = array(

/**
* Tipos de Espaços e Equipamentos Culturais (Novo Padrão)
*/

    \MapasCulturais\i::__('Tipos de Espaços e Equipamentos Culturais') => array(
        'range' => array(2000, 2040),
        'items' => array(
            2000 => array( 'name' => \MapasCulturais\i::__('Arena ou semi arena de apresentações')),
            2001 => array( 'name' => \MapasCulturais\i::__('Associação Comunitária')),
            2002 => array( 'name' => \MapasCulturais\i::__('Atelier')),
            2003 => array( 'name' => \MapasCulturais\i::__('Auditório')),
            2004 => array( 'name' => \MapasCulturais\i::__('Biblioteca')),
            2005 => array( 'name' => \MapasCulturais\i::__('Biblioteca Comunitária')),
            2006 => array( 'name' => \MapasCulturais\i::__('Biblioteca Parque')),
            2007 => array( 'name' => \MapasCulturais\i::__('Casa da Cultura')),
            2008 => array( 'name' => \MapasCulturais\i::__('Casa de Espetáculo')),
            2009 => array( 'name' => \MapasCulturais\i::__('Centro Cultural')),
            2010 => array( 'name' => \MapasCulturais\i::__('Centro de Convenções')),
            2011 => array( 'name' => \MapasCulturais\i::__('Centro de convivência')),
            2012 => array( 'name' => \MapasCulturais\i::__('Centro de Memória e Patrimônio')),
            2013 => array( 'name' => \MapasCulturais\i::__('Centro de Tradição Regional')),
            2014 => array( 'name' => \MapasCulturais\i::__('Cinemas, cineclubes e salas de exibição')),
            2015 => array( 'name' => \MapasCulturais\i::__('Cinemateca')),
            2016 => array( 'name' => \MapasCulturais\i::__('Circo (inclusive itinerante)')),
            2017 => array( 'name' => \MapasCulturais\i::__('Escola de arte e cultura')),
            2018 => array( 'name' => \MapasCulturais\i::__('Escola de samba')),
            2019 => array( 'name' => \MapasCulturais\i::__('Escola de alimentação e cultura')),
            2020 => array( 'name' => \MapasCulturais\i::__('Espaço de Leitura')),
            2021 => array( 'name' => \MapasCulturais\i::__('Espaço Multiuso')),
            2022 => array( 'name' => \MapasCulturais\i::__('Espaços makers')),
            2023 => array( 'name' => \MapasCulturais\i::__('Estúdio de audiovisual')),
            2024 => array( 'name' => \MapasCulturais\i::__('Estúdio de Dança')),
            2025 => array( 'name' => \MapasCulturais\i::__('Estúdio de Música')),
            2026 => array( 'name' => \MapasCulturais\i::__('FabLabs')),
            2027 => array( 'name' => \MapasCulturais\i::__('Galeria e espaços de exposição')),
            2028 => array( 'name' => \MapasCulturais\i::__('Imóvel patrimonializado')),
            2029 => array( 'name' => \MapasCulturais\i::__('Laboratórios de Economia Criativa')),
            2030 => array( 'name' => \MapasCulturais\i::__('Livraria, alfarrábio ou sebo')),
            2031 => array( 'name' => \MapasCulturais\i::__('Memorial')),
            2032 => array( 'name' => \MapasCulturais\i::__('Mercados de arte e artesanato')),
            2033 => array( 'name' => \MapasCulturais\i::__('Museu')),
            2034 => array( 'name' => \MapasCulturais\i::__('Ponto de Leitura')),
            2035 => array( 'name' => \MapasCulturais\i::__('Pontos e Pontões de Cultura')),
            2036 => array( 'name' => \MapasCulturais\i::__('Rádios comunitárias')),
            2037 => array( 'name' => \MapasCulturais\i::__('Sala de Concerto')),
            2038 => array( 'name' => \MapasCulturais\i::__('Sambódromo')),
            2039 => array( 'name' => \MapasCulturais\i::__('Teatro')),
            2040 => array( 'name' => \MapasCulturais\i::__('Outros (informar qual)')),
        )
    ),
);

function ordenaSubcategorias(&$array) {
    ksort($array);
    
    foreach ($array as &$item) {
        if (isset($item['items'])) {
            uasort($item['items'], function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
        }
    }
}

ordenaSubcategorias($items);

return array(
    'metadata' => array(
        'informarQualOutroTipoDeEspaco' => array(
            'label' => \MapasCulturais\i::__('Especificar o tipo de espaço'),
            'type' => 'string',
            'validations' => array(),
            'should_validate' => function($entity, $value) {
                // Campo é obrigatório apenas quando o tipo do espaço é "Outros" (2040)
                $type_id = is_object($entity->type) && isset($entity->type->id)
                    ? $entity->type->id
                    : (int)($entity->type ?? 0);
                
                if ($type_id === 2040 && (empty($value) || trim((string)$value) === '')) {
                    return \MapasCulturais\i::__('O campo especificar o tipo de espaço é obrigatório.');
                }
                
                return false;
            },
            'available_for_opportunities' => true
        ),
        'emailPublico' => array(
            'label' => \MapasCulturais\i::__('Email Público'),
            'validations' => array(
                'v::email()' => \MapasCulturais\i::__('O email público não é um email válido.')
            ),
            'available_for_opportunities' => true
        ),

        'emailPrivado' => array(
            'label' => \MapasCulturais\i::__('Email Privado'),
            'validations' => array(
                'v::email()' => \MapasCulturais\i::__('O email privado não é um email válido.')
            ),
        	'private' => true,
            'available_for_opportunities' => true
        ),
        'cnpj' => array(
            'private' => true,
            'label' => \MapasCulturais\i::__('CNPJ'),
            'validations' => array(
               'v::cnpj()' => \MapasCulturais\i::__('O número de documento informado é inválido.')
            ),
            'available_for_opportunities' => true
        ),
        'razaoSocial' => array(
            'label' => \MapasCulturais\i::__('Razão Social'),
            'type' => 'text',
            'available_for_opportunities' => true
        ),
        'telefonePublico' => array(
            'label' => \MapasCulturais\i::__('Telefone Público'),
            'type' => 'string',
            'validations' => array(
                'v::brPhone()' => \MapasCulturais\i::__('Por favor, informe o telefone público no formato (xx) xxxx-xxxx.')
            ),
            'available_for_opportunities' => true,
            'field_type' => 'brPhone'
        ),

        'telefone1' => array(
            'label' => \MapasCulturais\i::__('Telefone 1'),
            'type' => 'string',
            'validations' => array(
                'v::brPhone()' => \MapasCulturais\i::__('Por favor, informe o telefone 1 no formato (xx) xxxx xxxx.')
            ),
        	'private' => true,
            'available_for_opportunities' => true,
            'field_type' => 'brPhone'
        ),


        'telefone2' => array(
            'label' => \MapasCulturais\i::__('Telefone 2'),
            'type' => 'string',
            'validations' => array(
                'v::brPhone()' => \MapasCulturais\i::__('Por favor, informe o telefone 2 no formato ')
            ),
        	'private' => true,
            'available_for_opportunities' => true,
            'field_type' => 'brPhone',

        ),

        /*
        'virtual_fisico' => array(
            'label' => \MapasCulturais\i::__('Virtual ou físico'),
            'type' => 'select',
            'options' => array(
                '' => \MapasCulturais\i::__('Físico'),
                'virtual' => \MapasCulturais\i::__('Virtual')
            )
        ),
        */
        'acessibilidade' => array(
            'label' => \MapasCulturais\i::__('Acessibilidade'),
            'type' => 'select',
            'options' => array(
                '' => \MapasCulturais\i::__('Não Informado'),
                'Sim' => \MapasCulturais\i::__('Sim'),
                'Não' => \MapasCulturais\i::__('Não')
            )
        ),
        'acessibilidade_fisica' => array(
            'label' => \MapasCulturais\i::__('Acessibilidade física'),
            'type' => 'multiselect',
            'allowOther' => true,
            'allowOtherText' => \MapasCulturais\i::__('Outros'),
            'options' => array(
                \MapasCulturais\i::__('Banheiros adaptados'),
                \MapasCulturais\i::__('Rampa de acesso'),
                \MapasCulturais\i::__('Elevador'),
                \MapasCulturais\i::__('Sinalização tátil'),

                // vindos do sistema de museus.cultura.gov.br
                \MapasCulturais\i::__('Bebedouro adaptado'),
                \MapasCulturais\i::__('Cadeira de rodas para uso do visitante'),
                \MapasCulturais\i::__('Circuito de visitação adaptado'),
                \MapasCulturais\i::__('Corrimão nas escadas e rampas'),
                \MapasCulturais\i::__('Elevador adaptado'),
                \MapasCulturais\i::__('Rampa de acesso'),
                \MapasCulturais\i::__('Sanitário adaptado'),
                \MapasCulturais\i::__('Telefone público adaptado'),
                \MapasCulturais\i::__('Vaga de estacionamento exclusiva para deficientes'),
                \MapasCulturais\i::__('Vaga de estacionamento exclusiva para idosos')
            )
        ),
        'capacidade' => array(
            'label' => \MapasCulturais\i::__('Capacidade'),
            'validations' => array(
                "v::intVal()->positive()" => \MapasCulturais\i::__("A capacidade deve ser um número positivo.")
            ),
            'available_for_opportunities' => true
        ),

        'endereco' => array(
            'label' => \MapasCulturais\i::__('Endereço'),
            'type' => 'text'
        ),


        'En_CEP' => [
            'label' => \MapasCulturais\i::__('CEP'),
        ],
        'En_Nome_Logradouro' => [
            'label' => \MapasCulturais\i::__('Logradouro'),
        ],
        'En_Num' => [
            'label' => \MapasCulturais\i::__('Número'),
        ],
        'En_Complemento' => [
            'label' => \MapasCulturais\i::__('Complemento'),
        ],
        'En_Bairro' => [
            'label' => \MapasCulturais\i::__('Bairro'),
        ],
        'En_Municipio' => [
            'label' => \MapasCulturais\i::__('Município'),
        ],
        'En_Estado' => [
            'label' => \MapasCulturais\i::__('Estado'),
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

        'horario' => array(
            'label' => \MapasCulturais\i::__('Horário de funcionamento'),
            'type' => 'text',
            'available_for_opportunities' => true
        ),

        'criterios' => array(
            'label' => \MapasCulturais\i::__('Critérios de uso do espaço'),
            'type' => 'text'
        ),

        'site' => array(
            'label' => \MapasCulturais\i::__('Site'),
            'validations' => array(
                "v::url()" => \MapasCulturais\i::__("A url informada é inválida.")
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
                "v::oneOf(v::urlDomain('pinterest.com'), v::regex('/^@?([-\w\d\.]+)$/i'))" => \MapasCulturais\i::__("O valor deve ser uma URL ou usuário válido.")
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

/**
 * Equipamentos Culturais
 */
    'items' => $items,
    /* EXEMPLOS DE METADADOS:

    'cnpj' => array(
        'label' => 'CNPJ',
        'type' => 'text',
        'validations' => array(
            'unique' => 'Este CNPJ já está cadastrado em nosso sistema.',
            'v::cnpj()' => 'O CNPJ é inválido.'
        )
    ),
    'cpf' => array(
        'label' => 'CPF',
        'type' => 'text',
        'validations' => array(
            'required' => 'Por favor, informe o CPF.',
            'v::cpf()' => 'O CPF é inválido.'
        )
    ),
    'radio' => array(
        'label' => 'Um exemplo de input radio',
        'type' => 'radio',
        'options' => array(
            'valor1' => 'Label do valor 1',
            'valor2' => 'Label do valor 2',
        ),
        'default_value' => 'valor1'
    ),
    'checkboxes' => array(
        'label' => 'Um exemplo de grupo de checkboxes',
        'type' => 'checkboxes',
        'options' => array(
            'valor1' => 'Label do Primeiro checkbox',
            'valor2' => 'Label do Primeiro checkbox'
        ),
        'default_value' => array(),
        'validations' => array(
            'v::arrayType()->notEmpty()' => 'Você deve marcar ao menos uma opção.'
        )
    ),
    'checkbox' => array(
        'label' => 'Um exemplo de campo booleano com checkbox.',
        'type' => 'checkbox',
        'input_value' => 1,
        'default_value' => 0
    ),
    'email' => array(
        'label' => 'Email público para contato',
        'type' => 'text',
        'validations'=> array(
            'v::email()' => 'O email informado é inválido.'
        )
    ),
    'site' => array(
        'label' => 'Site',
        'type' => 'text',
        'validations'=> array(
            'v::url()' => 'A URL informada é inválida.'
        )
    ),
    'estado' => array(
        'label' => 'Estado de Residência',
        'type' => 'select',
        'options' => array(
            ''   => '',
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AM' => 'Amazonas',
            'AP' => 'Amapá',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MG' => 'Minas Gerais',
            'MS' => 'Mato Grosso do Sul',
            'MT' => 'Mato Grosso',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'PR' => 'Paraná',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'RS' => 'Rio Grande do Sul',
            'SC' => 'Santa Catarina',
            'SE' => 'Sergipe',
            'SP' => 'São Paulo',
            'TO' => 'Tocantins',
            ''   => '',
            'OUT'   => 'Resido Fora do Brasil'
        ),

        'validations' => array(
            "v::stringType()->in('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO','OUT')" => 'O estado informado não existe.'
        )
    )
     */
);
