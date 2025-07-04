app.component('registration-status', {
    template: $TEMPLATES['registration-status'],

    props: {
        registration: {
            type: Entity,
            required: true
        },

        phase: {
            type: Entity,
            required: true
        }
    },
    
    setup(props, { slots }) {
        const hasSlot = name => !!slots[name];
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('registration-status');
        return { text, hasSlot }
    },

    methods: {
		formatNote(note) {
			note = parseFloat(note);
			return note.toLocaleString($MAPAS.config.locale);
		},
		verifyState(registration) {
            switch (registration.status) {
                case 10:
                case 1:
                    return 'success__color';
                    
                case 2 : 
                case 0 : 
				case 3 : 
                    return 'danger__color';
                    
				case 8 : 
                    return 'warning__color';

                case null:
                default:
                    return '';
            }
        },
        showResults(phase) {
            const types = ['qualification', 'technical', 'documentary'];
            return types.includes(phase.type) || phase.publishEvaluationDetails;
        },
    }
});
