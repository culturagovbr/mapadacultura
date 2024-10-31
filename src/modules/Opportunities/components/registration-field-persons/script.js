app.component('registration-field-persons', {
    template: $TEMPLATES['registration-field-persons'],
    
    emits: ['update:registration', 'change', 'save'],

    props: {
        registration: {
            type: Entity,
            required: true,
        },

        prop: {
            type: String,
            required: true,
        },

        autosave: {
            type: Number,
        },
        
        debounce: {
            type: Number,
            default: 0
        },
    },    

    data() {
        let rules = this.registration.$PROPERTIES[this.prop].registrationFieldConfiguration.config || {};
        let required = $DESCRIPTIONS.registration[this.prop].required;

        return {
            rules,
            required,
            areas: $TAXONOMIES.area.terms,
            functions: $TAXONOMIES.funcao.terms,
            races: $DESCRIPTIONS.agent.raca.optionsOrder,
            genders: $DESCRIPTIONS.agent.genero.optionsOrder,
            sexualOrientations: $DESCRIPTIONS.agent.orientacaoSexual.optionsOrder,
            deficiencies: $DESCRIPTIONS.agent.pessoaDeficiente.optionsOrder,
            communities: $DESCRIPTIONS.agent.comunidadesTradicional.optionsOrder,
        };
    },

    watch: {
        change(event, now) {
            clearTimeout(this.__timeout);
            let oldValue = this.entity[this.prop] ? JSON.parse(JSON.stringify(this.entity[this.prop])) : null;
            
            this.__timeout = setTimeout(() => {
                if (this.autosave && (now || JSON.stringify(this.entity[this.prop]) != JSON.stringify(oldValue))) {
                    this.entity.save(now ? 0 : this.autosave).then(() => {
                        this.$emit('save', this.entity);
                    });
                }

            }, now ? 0 : this.debounce);
        },
    },
    
    methods: {
        removePerson(person) {
            const persons = this.registration[this.prop];
            this.registration[this.prop] = persons.filter( (_person) => { 
                return !(_person == person);
            });
            this.save();
        },

        addNewPerson() {
            if (!this.registration[this.prop]) {
                this.registration[this.prop] = [];
            }
 
            this.registration[this.prop].push({
                name: '',
                fullName: '',
                socialName: '',
                cpf: '',
                income: '',
                function: '',
                education: '',
                telephone: '',
                email: '',
                race: '',
                gender: '',
                sexualOrientation: '',
                deficiencies: [],
                comunty: '',
                area: [],
                funcao: [],
            }) 
        },

        save() {
            this.registration.save();
            this.$emit('update:registration', this.registration);
        }
    },
});