<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-notification
    opportunity-phase-publish-date-config
    mc-alert
');
?>
<mc-card>
    <div class="grid-12 opportunity-phase-list-evaluation">
        <div class="col-6">
            <h4 class="bold"><?php i::_e("Status das inscrições") ?></h4>
            <!-- <p><?= i::__("Status da avaliação:") ?> <strong>Em andamento</strong></p> -->
            <p v-if="entity.summary.registrations"><?= i::__("Quantidade inscrições:") ?> <strong>{{entity.summary.registrations}}</strong> <?php i::_e('inscrições') ?></p>
            <p v-if="entity.summary.evaluated"><?= i::__("Quantidade de inscrições <strong>avaliadas</strong>:") ?> <strong>{{entity.summary.evaluated}}</strong> <?php i::_e('inscrições') ?></p>
            <p v-if="entity.summary.Approved"><?= i::__("Quantidade de inscrições <strong>selecionadas</strong>:") ?> <strong>{{entity.summary.Approved}}</strong> <?php i::_e('inscrições') ?></p>
            <p v-if="entity.summary.Waitlist"><?= i::__("Quantidade de inscrições <strong>suplentes</strong>:") ?> <strong>{{entity.summary.Waitlist}}</strong> <?php i::_e('inscrições') ?></p>
            <p v-if="entity.summary.Invalid"><?= i::__("Quantidade de inscrições <strong>inválidas</strong>:") ?> <strong>{{entity.summary.Invalid}}</strong> <?php i::_e('inscrições') ?></p>
            <p v-if="entity.summary.Pending"><?= i::__("Quantidade de inscrições <strong>pendentes</strong>:") ?> <strong>{{entity.summary.Pending}}</strong> <?php i::_e('inscrições') ?></p>
        </div>
        <div class="col-6">
            <h4 class="bold"><?php i::_e("Status das avaliações") ?></h4>
            <p v-for="(value, label) in entity.summary.evaluations"><?= i::__("Quantidade de inscrições") ?> <strong>{{label.toLowerCase()}}</strong>: <strong>{{value}}</strong> <?php i::_e('inscrições') ?></p>
        </div>
        <mc-alert v-if="!publishedRegistrations" class="col-12" type="success">
                <?= i::__('A aplicação dos resultados nas inscrições já foi iniciado. Acesse a <strong>lista de inscrições da fase</strong> para continuar ou concluir o processo.') ?>
        </mc-alert>
        <div class="opportunity-phase-list-evaluation__line col-12"></div>
        <div class="col-6 opportunity-phase-list-evaluation_action--center">
            <mc-link :entity="entity.opportunity" class="opportunity-phase-list-data-collection_action--button" icon="external" route="registrations" right-icon>
              <?= i::__("Lista de inscrições da fase") ?>
            </mc-link>
        </div>
        <div class="col-6 opportunity-phase-list-evaluation_action--center">
            <mc-link route="opportunity/opportunityEvaluations" :params="[entity.opportunity.id]" class="opportunity-phase-list-data-collection_action--button" icon="external" right-icon>
              <?= i::__("Lista de avaliações") ?>
            </mc-link>
        </div>
        <div class="opportunity-phase-list-evaluation__line col-12"></div>
        <opportunity-phase-publish-date-config :phase="entity.opportunity" :phases="phases" hide-datepicker></opportunity-phase-publish-date-config>
    </div>
</mc-card>