<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-tag-list
    mc-title
');
?>
<section class="mc-accordion">
    <header @click="toggle($event)" :class="{ 'mc-accordion__header--active': active }" class="mc-accordion__header">
        <mc-title tag="h3" class="bold mc-accordion__title">
            <slot name="title"></slot>
        </mc-title>

        <span class="mc-accordion__close">
            <div class="mc-accordion__icon">
                <slot name="icon">
                    <div v-if="withText" class="mc-accordion__icon">
                        <label v-if="active">
                            <?= i::__('Diminuir') ?>
                        </label>
                        <label v-else>
                            <?= i::__('Expandir') ?>
                        </label>
                    </div>
                </slot>
                <mc-icon :name="active ? 'arrowPoint-up' : 'arrowPoint-down'" class="primary__color"></mc-icon>
            </div>
        </span>
    </header>
    <div v-if="active" class="mc-accordion__content">
        <slot name="content"></slot>
    </div>
</section>