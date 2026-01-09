/**
 * uso:
 * 
 * // omitindo o id, pega a entity do Mapas.requestedEntity
 * <mc-entity v-slot='{entity}'>{{entity.id}} - {{entity.name}}</mc-entity>
 * 
 * // passango o id sem select, não faz consulta na api
 * <mc-entity v-slot='{entity}' :type='space' :id='33'>{{entity.id}}</mc-entity>
 * 
 * // passando o id e passando um select, faz a consulta na api
 * <mc-entity v-slot='{entity}' :type='space' :id='33' :select="id,name">{{entity.id}} - {{entity.name}}</mc-entity>
 */
app.component('mc-entity', {
    data() {
        return {
            entity: null,
            loading: true
        }
    },

    props: {
        id: Number,
        type: String,
        select: {
            type: String,
            default: '*'
        },
        scope: String
    },

    computed: {
        // Computed que retorna o ID do tipo "Outros" para spaces
        spaceOtherTypeId() {
            return Utils.getSpaceOtherTypeId();
        },
        
        // Computed que retorna as props do slot condicionalmente
        slotProps() {
            const props = { entity: this.entity };
            
            // Só adiciona spaceOtherTypeId se a entity for do tipo 'space'
            if (this.entity?.__objectType === 'space') {
                props.spaceOtherTypeId = this.spaceOtherTypeId;
            }
            
            return props;
        }
    },

    methods: {
        getDataFromApi() {
            const api = new API(this.type, this.scope || 'default');
            api.findOne(this.id, this.select).then(entity => { 
                this.entity = entity;
                this.loading = false;
            }).catch((error) => {
                console.error(error);
            });
        }
    },

    mounted() {
        if (this.id) {
            this.getDataFromApi();
        } else if($MAPAS.requestedEntity) {
            const entity = $MAPAS.requestedEntity;
            const api = new API(entity['@entityType'], this.scope || 'default');

            this.entity = api.getEntityInstance(entity.id); 
            this.entity.populate(entity);
            this.loading = false;

            globalThis.$entity = this.entity;
        }
    },

    template: $TEMPLATES['mc-entity']
});
