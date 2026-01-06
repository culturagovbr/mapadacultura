<?php
namespace OpportunityWorkplan;

use MapasCulturais\App,
    MapasCulturais\i;
use OpportunityWorkplan\Controllers\Delivery as ControllersDelivery;
use OpportunityWorkplan\Controllers\Workplan as ControllersWorkplan;
use OpportunityWorkplan\Entities\Workplan;
use OpportunityWorkplan\Entities\Goal;
use MapasCulturais\Definitions\Metadata;
use MapasCulturais\Themes\BaseV2\Theme;
use OpportunityWorkplan\Entities\Delivery;

class Module extends \MapasCulturais\Module{
    function _init(){
        $app = App::i();

        $app->hook('app.init:after', function () use($app) {
            $app->hook("template(opportunity.edit.opportunity-data-collection-config-form):after", function(){
                $this->part('opportunity-workplan-config');
            });

            $app->hook("component(registration-form):after", function(){
                /** @var Theme $this */
                if($this->controller->requestedEntity->opportunity->enableWorkplan){
                    $this->part('registration-workplan');
                }
            });

            $app->hook("template(registration.view.registration-form-view):after", function($phase){
                if ($phase->opportunity->isFirstPhase && $phase->opportunity->enableWorkplan) {
                    $this->part('registration-details-workplan');
                }
            });

            $app->hook("entity(Registration).sendValidationErrors", function (&$errorsResult) use($app) {
                $registration = $this;

                if ($registration->opportunity->enableWorkplan) {
                    $workplan = $app->repo(Workplan::class)->findOneBy(['registration' => $registration->id]);

                    $errors = [];

                    if (!$workplan) {
                        $errors['workplan'] = [i::__('Plano de metas obrigatório.')];
                    }

                    if (!$workplan?->projectDuration) {
                        $errors['projectDuration'] = [i::__('Plano de metas - Duração do projeto (meses) obrigatório.')];
                    }

                    if (!$workplan?->culturalArtisticSegment) {
                        $errors['culturalArtisticSegment'] = [i::__('Plano de metas - Segmento artistico-cultural obrigatório.')];
                    }
                   
                    if ($workplan?->goals->isEmpty()) {
                        $errors['goal'] = [i::__('Meta do plano de metas obrigatório.')];
                    }

                    if ($registration->opportunity->workplan_deliveryReportTheDeliveriesLinkedToTheGoals) {
                        if (is_iterable($workplan?->goals)) {
                            foreach ($workplan?->goals as $goal) {
                                if ($goal?->deliveries->isEmpty()) {
                                    $errors['delivery'] = [i::__('Entrega da meta do plano de metas obrigatório.')];
                                }
                            }
                        }
                    }                   

                    $errorsResult = [...$errors];
                }               
            });

            $app->hook("template(registration.registrationPrint.section):end", function(){
                $this->part('registration-details-workplan-print');
            });
            
            $app->hook('mapas.printJsObject:before', function() {
                $this->jsObject['EntitiesDescription']['workplan'] = Workplan::getPropertiesMetadata();
                $this->jsObject['EntitiesDescription']['goal'] = Goal::getPropertiesMetadata();
                $this->jsObject['EntitiesDescription']['delivery'] = Delivery::getPropertiesMetadata();
            });
        });
    }

    function register()
    {
        $app = App::i();

        $app->registerController('workplan', ControllersWorkplan::class);
        $app->registerController('delivery', ControllersDelivery::class);
        
        $this->registerOpportunityMetadata('workplanLabelDefault', [
            'label' => i::__('Plano de metas label'),
            'default_value' => 'Plano de metas'
        ]);

        $this->registerOpportunityMetadata('goalLabelDefault', [
            'label' => i::__('Meta label'),
            'default_value' => 'Metas'
        ]);

        $this->registerOpportunityMetadata('deliveryLabelDefault', [
            'label' => i::__('Entrega label'),
            'default_value' => 'Entregas '
        ]);

        // metadados opportunity
        $this->registerOpportunityMetadata('enableWorkplan', [
            'label' => i::__('Habilitar plano de metas'),
            'type' => 'boolean',
            'default_value' => false
        ]);

         
        $this->registerOpportunityMetadata('workplan_dataProjectlimitMaximumDurationOfProjects', [
            'label' => i::__('Limitar duração máxima dos projetos'),
            'type' => 'boolean',
            'default_value' => false
        ]);

        
        $this->registerOpportunityMetadata('workplan_dataProjectmaximumDurationInMonths', [
            'label' => i::__('Duração máxima em meses'),
        ]);

        
        $this->registerOpportunityMetadata('workplan_metaInformTheStageOfCulturalMaking', [
            'label' => i::__('Informar a etapa do fazer cultural'),
            'type' => 'boolean',
            'default_value' => false
        ]);        
        
        $this->registerOpportunityMetadata('workplan_metaLimitNumberOfGoals', [
            'label' => i::__('Limitar número de metas'),
            'type' => 'boolean',
            'default_value' => false
        ]);

         
        $this->registerOpportunityMetadata('workplan_metaMaximumNumberOfGoals', [
            'label' => i::__('Número máximo de metas'),
        ]);

         
        $this->registerOpportunityMetadata('workplan_deliveryReportTheDeliveriesLinkedToTheGoals', [
            'label' => i::__('Informar as entregas vinculadas à meta'),
            'type' => 'boolean',
            'default_value' => false
        ]);

         
        $this->registerOpportunityMetadata('workplan_deliveryLimitNumberOfDeliveries', [
            'label' => i::__('Limitar número de entregas'),
            'type' => 'boolean',
            'default_value' => false
        ]);

         
        $this->registerOpportunityMetadata('workplan_deliveryMaximumNumberOfDeliveries', [
            'label' => i::__('Número máximo de entregas'),
        ]);
         
        $this->registerOpportunityMetadata('workplan_registrationReportTheNumberOfParticipants', [
            'label' => i::__('Informar a quantidade estimada de público'),
            'type' => 'boolean',
            'default_value' => false
        ]);

        $this->registerOpportunityMetadata('workplan_registrationInformCulturalArtisticSegment', [
            'label' => i::__('Informar segmento artístico-cultural'),
            'type' => 'boolean',
            'default_value' => false
        ]);
         
        $this->registerOpportunityMetadata('workplan_registrationReportExpectedRenevue', [
            'label' => i::__('Informar receita prevista'),
            'type' => 'boolean',
            'default_value' => false
        ]);

        $this->registerOpportunityMetadata('workplan_monitoringInformTheFormOfAvailability', [
            'label' => i::__('Informar forma de disponibilização'),
            'type' => 'boolean',
            'default_value' => false
        ]);

        $this->registerOpportunityMetadata('workplan_monitoringInformAccessibilityMeasures', [
            'label' => i::__('Informar as medidas de acessibilidade'),
            'type' => 'boolean',
            'default_value' => false
        ]);
        
        $this->registerOpportunityMetadata('workplan_monitoringInformThePriorityAudience', [
            'label' => i::__('Informar os territórios prioritários'),
            'type' => 'boolean',
            'default_value' => false
        ]);
        
        $this->registerOpportunityMetadata('workplan_monitoringProvideTheProfileOfParticipants', [
            'label' => i::__('Informar o perfil do público'),
            'type' => 'boolean',
            'default_value' => false
        ]);

        $this->registerOpportunityMetadata('workplan_monitoringReportExecutedRevenue', [
            'label' => i::__('Informar receita executada'),
            'type' => 'boolean',
            'default_value' => false
        ]);

        $app->registerFileGroup('delivery', new \MapasCulturais\Definitions\FileGroup('evidences'));

        // metadados workplan
        $projectDuration = new Metadata('projectDuration', ['label' => \MapasCulturais\i::__('Duração do projeto (meses)')]);
        $app->registerMetadata($projectDuration, Workplan::class);

        $culturalArtisticSegment = new Metadata('culturalArtisticSegment', [
            'label' => \MapasCulturais\i::__('Segmento artistico-cultural'),
            'type' => 'select',
            'options' => array(
                \MapasCulturais\i::__('Acervos'),
                \MapasCulturais\i::__('Arquivos'),
                \MapasCulturais\i::__('Artes Visuais'),
                \MapasCulturais\i::__('Artesanato'),
                \MapasCulturais\i::__('Audiovisual'),
                \MapasCulturais\i::__('Capoeira'),
                \MapasCulturais\i::__('Circo'),
                \MapasCulturais\i::__('Cultura de Matriz Africana'),
                \MapasCulturais\i::__('Cultura dos Povos Originários'),
                \MapasCulturais\i::__('Culturas Tradicionais e Populares'),
                \MapasCulturais\i::__('Dança'),
                \MapasCulturais\i::__('Design'),
                \MapasCulturais\i::__('Edição e produção editorial'),
                \MapasCulturais\i::__('Festas e Celebrações'),
                \MapasCulturais\i::__('Hip Hop'),
                \MapasCulturais\i::__('Jogos eletrônicos'),
                \MapasCulturais\i::__('Literatura'),
                \MapasCulturais\i::__('Mediação e formação de leitores'),
                \MapasCulturais\i::__('Moda'),
                \MapasCulturais\i::__('Museu'),
                \MapasCulturais\i::__('Música'),
                \MapasCulturais\i::__('Patrimônio Arqueológico'),
                \MapasCulturais\i::__('Patrimônio Cultural Material'),
                \MapasCulturais\i::__('Patrimônio Cultural Imaterial'),
                \MapasCulturais\i::__('Patrimônio Natural'),
                \MapasCulturais\i::__('Performance'),
                \MapasCulturais\i::__('Teatro'),
                \MapasCulturais\i::__('Outros'),
            ),
        ]);
        $app->registerMetadata($culturalArtisticSegment, Workplan::class);

        // metadados goal
        $monthInitial = new Metadata('monthInitial', ['label' => \MapasCulturais\i::__('Mês inicial')]);
        $app->registerMetadata($monthInitial, Goal::class);

        $monthEnd = new Metadata('monthEnd', ['label' => \MapasCulturais\i::__('Mês final')]);
        $app->registerMetadata($monthEnd, Goal::class);

        $title = new Metadata('title', ['label' => \MapasCulturais\i::__('Título da meta')]);
        $app->registerMetadata($title, Goal::class);

        $description = new Metadata('description', ['label' => \MapasCulturais\i::__('Descrição')]);
        $app->registerMetadata($description, Goal::class);


        $culturalMakingStage = new Metadata('culturalMakingStage', [
            'label' => \MapasCulturais\i::__('Etapa do fazer cultural'),
            'type' => 'select',
            'options' => array(
                \MapasCulturais\i::__('Criação'),
                \MapasCulturais\i::__('Produção'),
                \MapasCulturais\i::__('Comercialização e Distribuição'),
                \MapasCulturais\i::__('Difusão e Circulação'),
                \MapasCulturais\i::__('Acesso, mediação e fruição'),
                \MapasCulturais\i::__('Formação'),
                \MapasCulturais\i::__('Pesquisa e reflexão'),
                \MapasCulturais\i::__('Memória e Preservação'),
                \MapasCulturais\i::__('Organização e gestão'),
                \MapasCulturais\i::__('Monitoramento e avaliação'),
                \MapasCulturais\i::__('Outra (especificar)'),
            ),
        ]);
        $app->registerMetadata($culturalMakingStage, Goal::class);


        // metadados pauta temática
        $thematicAgenda = new Metadata('thematicAgenda', [
            'label' => \MapasCulturais\i::__('Pauta temática'),
            'type' => 'select',
            'options' => array(
                \MapasCulturais\i::__('Não se relaciona a nenhuma pauta temática'),
                \MapasCulturais\i::__('Cultura Alimentar'),
                \MapasCulturais\i::__('Cultura DEF'),
                \MapasCulturais\i::__('Cultura Digital'),
                \MapasCulturais\i::__('Culturas Imigrantes e Refugiadas'),
                \MapasCulturais\i::__('Cultura LGBTQIAPN+'),
                \MapasCulturais\i::__('Cultura, Memória e Direitos Humanos'),
                \MapasCulturais\i::__('Cultura Nerd'),
                \MapasCulturais\i::__('Culturas Periféricas'),
                \MapasCulturais\i::__('Cultura Quilombola'),
                \MapasCulturais\i::__('Culturas Rurais e Agroecológicas'),
                \MapasCulturais\i::__('Culturas Urbanas'),
                \MapasCulturais\i::__('Cultura do Sertão'),
                \MapasCulturais\i::__('Cultura e Acessibilidade'),
                \MapasCulturais\i::__('Cultura e Economia Criativa'),
                \MapasCulturais\i::__('Cultura e Educação'),
                \MapasCulturais\i::__('Cultura e Gênero'),
                \MapasCulturais\i::__('Cultura e Idosos'),
                \MapasCulturais\i::__('Cultura e Infância'),
                \MapasCulturais\i::__('Cultura e Juventude'),
                \MapasCulturais\i::__('Cultura e Meio ambiente'),
                \MapasCulturais\i::__('Cultura e Negritude'),
                \MapasCulturais\i::__('Cultura e Pessoas em Situação de Privação de Liberdade'),
                \MapasCulturais\i::__('Cultura e População de Rua'),
                \MapasCulturais\i::__('Cultura e Povos Ciganos'),
                \MapasCulturais\i::__('Cultura e Saúde'),
                \MapasCulturais\i::__('Cultura e Turismo'),
                \MapasCulturais\i::__('Culturas Indígenas'),
                \MapasCulturais\i::__('Culturas Tradicionais de Matriz Africana'),
                \MapasCulturais\i::__('Outra (especificar)'),
            ),
        ]);
        $app->registerMetadata($thematicAgenda, Workplan::class);
    
        // metadados delivery
        $name = new Metadata('name', ['label' => \MapasCulturais\i::__('Nome da entrega')]);
        $app->registerMetadata($name, Delivery::class);

        $description = new Metadata('description', ['label' => \MapasCulturais\i::__('Descrição')]);
        $app->registerMetadata($description, Delivery::class);

        $type = new Metadata('type', ['label' => \MapasCulturais\i::__('Tipo de entrega')]);
        $app->registerMetadata($type, Delivery::class);

        
        $typeDelivery = new Metadata('typeDelivery', [
            'label' => \MapasCulturais\i::__('Tipo entrega'),
            'type' => 'select',
            'options' => array(
                \MapasCulturais\i::__('Álbum musical'),
                \MapasCulturais\i::__('Aplicativo / Software'),
                \MapasCulturais\i::__('Apresentação ao vivo / Show'),
                \MapasCulturais\i::__('Aquisição de acervos e bens culturais'),
                \MapasCulturais\i::__('Arte gráfica / Desenho / Gravura / Ilustração'),
                \MapasCulturais\i::__('Artesanato'),
                \MapasCulturais\i::__('Artigo / Ensaio'),
                \MapasCulturais\i::__('Audiolivro'),
                \MapasCulturais\i::__('Aula / Palestra / Conferência'),
                \MapasCulturais\i::__('Blog / Site'),
                \MapasCulturais\i::__('Caderno / Cartilha / Apostila'),
                \MapasCulturais\i::__('Circulação / Turnê'),
                \MapasCulturais\i::__('Coleção'),
                \MapasCulturais\i::__('Congresso / Encontro / Seminário / Simpósio'),
                \MapasCulturais\i::__('Curso / Oficina / Workshop'),
                \MapasCulturais\i::__('Desfile'),
                \MapasCulturais\i::__('Digitalização de acervos'),
                \MapasCulturais\i::__('Livro'),
                \MapasCulturais\i::__('Livro eletrônico (e-Book)'),
                \MapasCulturais\i::__('Ensaio fotográfico'),
                \MapasCulturais\i::__('Escultura'),
                \MapasCulturais\i::__('Espetáculo cênico'),
                \MapasCulturais\i::__('Feira'),
                \MapasCulturais\i::__('Exibição / Exposição'),
                \MapasCulturais\i::__('Festa Popular'),
                \MapasCulturais\i::__('Festival / Mostra'),
                \MapasCulturais\i::__('Filme de curta-metragem'),
                \MapasCulturais\i::__('Filme de longa-metragem'),
                \MapasCulturais\i::__('Filme de média-metragem ou telefilme'),
                \MapasCulturais\i::__('Grafitti/Mural'),
                \MapasCulturais\i::__('Intercâmbio'),
                \MapasCulturais\i::__('Instalação artística / videoarte'),
                \MapasCulturais\i::__('Jogo eletrônico'),
                \MapasCulturais\i::__('Licenciamento'),
                \MapasCulturais\i::__('Manutenção de grupos / iniciativas / espaços culturais'),
                \MapasCulturais\i::__('Melhoria em espaço cultural'),
                \MapasCulturais\i::__('Pesquisa'),
                \MapasCulturais\i::__('Plataforma digital'),
                \MapasCulturais\i::__('Podcast/ Programa de TV ou Rádio'),
                \MapasCulturais\i::__('Residência Artística'),
                \MapasCulturais\i::__('Revista / Jornal / Periódico'),
                \MapasCulturais\i::__('Roteiro de filme ou episódio'),
                \MapasCulturais\i::__('Sarau / Slam'),
                \MapasCulturais\i::__('Série / websérie'),
                \MapasCulturais\i::__('Videoclipe / Album visual'),
                \MapasCulturais\i::__('Outros (especificar)'),
            ),
        ]);
        $app->registerMetadata($typeDelivery, Delivery::class);

        $segmentDelivery = new Metadata('segmentDelivery', [
            'label' => \MapasCulturais\i::__('Segmento artístico cultural da entrega'),
            'type' => 'select',
            'options' => array(
                \MapasCulturais\i::__('Acervos'),
                \MapasCulturais\i::__('Arquivos'),
                \MapasCulturais\i::__('Artes Visuais'),
                \MapasCulturais\i::__('Artesanato'),
                \MapasCulturais\i::__('Audiovisual'),
                \MapasCulturais\i::__('Capoeira'),
                \MapasCulturais\i::__('Circo'),
                \MapasCulturais\i::__('Cultura de Matriz Africana'),
                \MapasCulturais\i::__('Cultura dos Povos Originários'),
                \MapasCulturais\i::__('Culturas Tradicionais e Populares'),
                \MapasCulturais\i::__('Dança'),
                \MapasCulturais\i::__('Design'),
                \MapasCulturais\i::__('Edição e produção editorial'),
                \MapasCulturais\i::__('Festas e Celebrações'),
                \MapasCulturais\i::__('Hip Hop'),
                \MapasCulturais\i::__('Jogos eletrônicos'),
                \MapasCulturais\i::__('Literatura'),
                \MapasCulturais\i::__('Mediação e formação de leitores'),
                \MapasCulturais\i::__('Moda'),
                \MapasCulturais\i::__('Museu'),
                \MapasCulturais\i::__('Música'),
                \MapasCulturais\i::__('Patrimônio Arqueológico'),
                \MapasCulturais\i::__('Patrimônio Cultural Material'),
                \MapasCulturais\i::__('Patrimônio Cultural Imaterial'),
                \MapasCulturais\i::__('Patrimônio Natural'),
                \MapasCulturais\i::__('Performance'),
                \MapasCulturais\i::__('Teatro'),
                \MapasCulturais\i::__('Outros'),
            ),
        ]);
        $app->registerMetadata($segmentDelivery, Delivery::class);

        $expectedNumberPeople = new Metadata('expectedNumberPeople', ['label' => \MapasCulturais\i::__('Número previsto de pessoas')]);
        $app->registerMetadata($expectedNumberPeople, Delivery::class);

        $generaterRevenue = new Metadata('generaterRevenue', [
            'label' => \MapasCulturais\i::__('A entrega irá gerar receita?'),
            'type' => 'select',
            'options' => array(
                'true' => \MapasCulturais\i::__('Sim'),
                'false' => \MapasCulturais\i::__('Não'),
            ),
        ]);
        $app->registerMetadata($generaterRevenue, Delivery::class);

        $renevueQtd = new Metadata('renevueQtd', ['label' => \MapasCulturais\i::__('Quantidade')]);
        $app->registerMetadata($renevueQtd, Delivery::class);

        $unitValueForecast = new Metadata('unitValueForecast', ['label' => \MapasCulturais\i::__('Previsão de valor unitário')]);
        $app->registerMetadata($unitValueForecast, Delivery::class);

        $totalValueForecast = new Metadata('totalValueForecast', ['label' => \MapasCulturais\i::__('Previsão de valor total')]);
        $app->registerMetadata($totalValueForecast, Delivery::class);
    }
}