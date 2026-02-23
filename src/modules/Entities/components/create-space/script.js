app.component('create-space', {
    template: $TEMPLATES['create-space'],
    emits: ['create'],

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('create-space')
        return { text }
    },

    created() {
        this.iterationFields();
        var stat = 'publish';
    },

    data() {
        return {
            entity: null,
            fields: [],
        }
    },

    props: {
        editable: {
            type: Boolean,
            default: true
        },
    },

    computed: {
        areaErrors() {
            return this.entity?.__validationErrors?.['term-area'];
        },
        areaClasses() {
            return this.areaErrors ? 'field error' : 'field';
        },
        modalTitle() {
            if (this.entity?.id) {
                if (this.entity.status == 1) {
                    return __('espaçoCriado', 'create-space');
                } else {
                    return __('criarRascunho', 'create-space');
                }
            } else {
                return __('criarEspaço', 'create-space');

            }
        },
    },

    watch: {
        // Monitora mudanças no campo 'type' e limpa o campo 'informarQualOutroTipoDeEspaco' 
        // quando o tipo não for mais "Outros"
        'entity.type': {
            handler(newValue, oldValue) {
                if (!this.entity) return;
                
                const otherTypeId = Utils.getSpaceOtherTypeId();
                const newTypeId = this.getTypeId(newValue);
                const oldTypeId = this.getTypeId(oldValue);
                
                // Se mudou de "Outros" para outro tipo, limpa o campo
                if (oldTypeId === otherTypeId && newTypeId !== otherTypeId) {
                    this.clearOutroTipoDeEspaco();
                } 
                // Se o tipo não é "Outros" mas o campo ainda tem valor, limpa também
                else if (newTypeId !== otherTypeId && this.entity.informarQualOutroTipoDeEspaco) {
                    this.clearOutroTipoDeEspaco();
                }
            },
            immediate: false
        }
    },

    methods: {
        /**
         * Normaliza o valor do tipo para sempre retornar o ID como número
         * @param {object|number|string} type - O tipo pode ser objeto {id: 2040}, número 2040 ou string "2040"
         * @returns {number|null} O ID do tipo como número, ou null se não houver
         */
        getTypeId(type) {
            if (!type) return null;
            // Se for objeto, pega o id
            if (typeof type === 'object' && type.id !== undefined) {
                return Number(type.id);
            }
            // Se for número ou string, converte para número
            return Number(type);
        },

        iterationFields() {
            let skip = [
                'createTimestamp',
                'id',
                'location',
                'name',
                'shortDescription',
                'status',
                'type',
                '_type',
                'userId',
            ];
            Object.keys($DESCRIPTIONS.space).forEach((item) => {
                if (!skip.includes(item) && $DESCRIPTIONS.space[item].required) {
                    this.fields.push(item);
                }
            })
        },
        createEntity() {
            this.entity = Vue.ref(new Entity('space'));
            this.entity.type = 1;
            this.entity.terms = { area: [] }

            this.entity.removeOptions = [
                'Ponto de Cultura',
            ];
        },
        createDraft(modal) {
            this.entity.status = 0;
            this.save(modal);
        },
        createPublic(modal) {
            //lançar dois eventos
            this.entity.status = 1;
            this.save(modal);
        },
        save(modal) {
            modal.loading(true);
            this.entity.save().then((response) => {
                this.$emit('create', response)
                modal.loading(false);
                Utils.pushEntityToList(this.entity);

            }).catch((e) => {
                modal.loading(false);
            });
        },

        destroyEntity() {
            // para o conteúdo da modal não sumir antes dela fechar
            setTimeout(() => this.entity = null, 200);
        },

        /**
         * Limpa o campo 'informarQualOutroTipoDeEspaco' e seus erros de validação
         * quando o tipo do espaço não é mais "Outros" (2040)
         */
        clearOutroTipoDeEspaco() {
            if (this.entity) {
                this.entity.informarQualOutroTipoDeEspaco = '';
                
                if (this.entity.__validationErrors && this.entity.__validationErrors.informarQualOutroTipoDeEspaco) {
                    delete this.entity.__validationErrors.informarQualOutroTipoDeEspaco;
                }
            }
        }
    },
});
