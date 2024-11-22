<?php
/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

 use MapasCulturais\i;

 $this->import('
    entity-field
    entity-file
    mc-card
    v1-embed-tool 
    registration-field-persons
 ')
 ?>
<div class="registration-form">
    <?php $this->applyComponentHook("begin") ?>
    <form v-if="isValid" >
        <mc-card v-for="section in sections" class="registration-form__section">
            <template v-if="section.title" #title>
                {{section.title}}
                <p v-if="section.description">{{section.description}}</p>
            </template>
            <template #content>
                <template v-for="field in section.fields" :key="field.fieldName || field.groupName">
                    <registration-field-persons v-if="field.fieldType == 'persons'" 
                        :registration="registration"
                        :disabled="editableFields ? !editableFields.includes(field.fieldName) : false"
                        :prop="field.fieldName"></registration-field-persons>
                        
                    <entity-field v-else-if="field.fieldName" 
                        :entity="registration" 
                        :prop="field.fieldName" 
                        :disabled="editableFields ? !editableFields.includes(field.fieldName) : false"
                        :field-description="field.description" 
                        :max-length="field.maxSize" 
                        :autosave="60000"
                        description-first
                        :max-options="field?.config?.maxOptions !== undefined && field?.config?.maxOptions !== '' ? Number(field.config.maxOptions) : 0"></entity-field>

                    <entity-file v-else-if="field.groupName" 
                        :entity="registration" 
                        :disabled="editableFields ? !editableFields.includes(field.fieldName) : false"
                        :groupName="field.groupName" 
                        titleModal="<?php i::_e('Adicionar anexo') ?>" 
                        :title="field.title" 
                        editable></entity-file>
                </template>
            </template>
        </mc-card>
    </form>

    <div v-else>
        <entity-field v-if="hasCategory && !registration.category" :entity="registration" prop="category"></entity-field><br>
        <entity-field v-if="hasProponentType && !registration.proponentType" :entity="registration" prop="proponentType"></entity-field><br>
        <entity-field v-if="hasRange && !registration.range" :entity="registration" prop="range"></entity-field><br>
    </div>
    <?php $this->applyComponentHook("end") ?>
</div>