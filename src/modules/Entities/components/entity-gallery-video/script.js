
app.component('entity-gallery-video', {
    template: $TEMPLATES['entity-gallery-video'],
    
    setup() { 
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('entity-gallery-video')
        return { text }
    },

    created() {
        window.addEventListener('keydown', (e) => {
            if (this.galleryOpen) {
                switch(e.key) {
                    case 'Escape':      this.close();   break;
                    case 'ArrowLeft':   this.prev();    break;
                    case 'ArrowRight':  this.next();    break;
                }            
            }
        });
    },

    data() {
        return {    
            videoList: {},
            galleryOpen: false,
            actualVideoIndex: null,
            actualVideo: {},
            metalist: {},
        }
    },

    props: {
        entity: {
            type: Entity,
            required: true
        },
        title: {
            type: String,
            default: __('title', 'entity-gallery-video')
        },
        editable: {
            type: Boolean,
            default: false
        },
        classes: {
            type: [String, Array, Object],
            required: false
        },

    },

    computed: {
        videos() {
            const items = this.entity.metalists?.videos;
            return Array.isArray(items) ? items : [];
        },

        /**
         * Chave derivada só de dados persistidos (id, value, title).
         * Evita watch profundo que dispararia a cada tecla em metalist.newData.* (popover de edição).
         */
        videosMetalistSyncKey() {
            const items = this.entity.metalists?.videos;
            if (!Array.isArray(items) || items.length === 0) {
                return '';
            }
            return items
                .map((item, index) => {
                    const idPart = item.id != null ? String(item.id) : `i:${index}`;
                    return [idPart, String(item.value ?? ''), String(item.title ?? '')].join('\u001f');
                })
                .join('\u001e');
        },
    },

    watch: {
        videosMetalistSyncKey: {
            handler() {
                this.syncVideoMetalistItems(this.videos);
            },
            immediate: true,
        },
    },

    methods: {
        syncVideoMetalistItems(items) {
            if (!items.length) {
                return;
            }
            items.forEach((content) => {
                const parsed = this.getVideoBasicData(content.value);
                const previous = content.video;
                const unchanged =
                    previous &&
                    previous.provider === parsed.provider &&
                    previous.videoID === parsed.videoID;
                if (!unchanged) {
                    content.video = parsed;
                }
                if (!content.newData) {
                    content.newData = {
                        title: content.title,
                        value: content.value,
                    };
                }
            });
        },

        // separa os dados do vídeo pela URL
        getVideoBasicData(url) {
            try {
                var parsedURL = new URL(url);
                var host = parsedURL.host;
                var provider = '';
                var videoID = '';
                var videoThumbnail = '';

                var ytRegex = /(youtu.*be.*)\/(watch\?v=|embed\/|v|shorts|)(.*?((?=[&#?])|$))/;
                var vmRegex = /(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)(?:[a-zA-Z0-9_\-]+)?/i;

                if (host.indexOf('youtube') != -1 || host.indexOf('youtu.be') != -1) {
                    provider = 'youtube';
                    videoID = parsedURL.href.match(ytRegex)[3];
                    videoThumbnail = 'https://img.youtube.com/vi/'+videoID+'/0.jpg';
                } else if (host.indexOf('vimeo') != -1) {
                    provider = 'vimeo';
                    videoID = parsedURL.href.match(vmRegex)[1];
                    videoThumbnail = 'https://vumbnail.com/'+videoID+'.jpg';
                }

                return {
                    'parsedURL': parsedURL,
                    'provider': provider,
                    'videoID': videoID,
                    'thumbnail': videoThumbnail
                }
            } catch (e) {
                console.error(`erro na galeria - ${e}`);
                return {};
            }
        },
        // Abertura da modal
        open() {
            this.galleryOpen = true;
            if (!document.querySelector('body').classList.contains('galleryOpen'))
                document.querySelector('body').classList.add('galleryOpen');
        },
        // Fechamento da modal
        close() {
            this.galleryOpen = false;
            this.actualVideo = null;
            this.actualVideoIndex = null;
            
            if (document.querySelector('body').classList.contains('galleryOpen'))
                document.querySelector('body').classList.remove('galleryOpen');
        },
        // Abertura da imagem na modal
        openVideo(index) {
            const list = this.entity.metalists?.videos;
            if (!list?.length) {
                return;
            }
            this.actualVideo = list[index];
            this.actualVideoIndex = index;
        },
        // Avança entre os vídeos
        prev() {
            const list = this.entity.metalists?.videos;
            if (!list?.length) {
                return;
            }
            this.actualVideoIndex = (this.actualVideoIndex > 0) ? --this.actualVideoIndex : list.length - 1;
            this.openVideo(this.actualVideoIndex);
        },
        // Recua entre os vídeos
        next() {
            const list = this.entity.metalists?.videos;
            if (!list?.length) {
                return;
            }
            this.actualVideoIndex = (this.actualVideoIndex < list.length - 1) ? ++this.actualVideoIndex : 0;
            this.openVideo(this.actualVideoIndex);
        },
        // Adiciona video na entidade
        async create(popover) {
            if(!this.metalist.value || !this.metalist.title){
                const messages = useMessages();
                messages.error(this.text('preencha todos os campos'));
                return;
            }
            await this.entity.createMetalist('videos', this.metalist);
            popover.close();
        },
        // Salva modificações nos vídeos adicionados
        async save(metalist, popover) {
            if(!metalist.newData.title) {
                const messages = useMessages();
                messages.error(this.text('preencha todos os campos'));
                return;
            }
            metalist.title = metalist.newData.title;
            // Mantém o value original, não permite editar
            
            await metalist.save();
            popover.close();
        }
    },
});