import template from './sw-cms-el-image-flip.html.twig';
import './sw-cms-el-image-flip.scss';

const { Component, Mixin, Filter } = Shopware;

/**
 * @private since v6.5.0
 * @package content
 */
Component.register('sw-cms-el-image-flip', {
    template,

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    computed: {
        cmsPreview() {
            return 'framework/assets/default/cms/preview_mountain_large.jpg';
        },

        displayModeClass() {
            if (this.element.config.displayMode.value === 'standard') {
                return null;
            }

            return `is--${this.element.config.displayMode.value}`;
        },

        styles() {
            return {
                'min-height': this.element.config.displayMode.value === 'cover' &&
                              this.element.config.minHeight.value &&
                              this.element.config.minHeight.value !== 0 ? this.element.config.minHeight.value : '340px',
            };
        },

        imgStyles() {
            return {
                'align-self': this.element.config.verticalAlign.value || null
            };
        },

        imgStylesFlip() {
            return {
                'align-self': this.element.config.verticalAlign.value || null,
                'display': 'none'
            };
        },

        mediaUrl() {
            const fallBackImageFileName = this.cmsPreview.slice(this.cmsPreview.lastIndexOf('/') + 1);
            const staticFallBackImage = this.assetFilter(`administration/static/img/cms/${fallBackImageFileName}`);
            const elemData = this.element.data.media;
            const elemConfig = this.element.config.media;

            if (elemConfig.source === 'mapped') {
                const demoMedia = this.getDemoValue(elemConfig.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return staticFallBackImage;
            }

            if (elemConfig.source === 'default') {
                // use only the filename
                const fileName = elemConfig.value.slice(elemConfig.value.lastIndexOf('/') + 1);
                return this.assetFilter(`/administration/static/img/cms/${fileName}`);
            }

            if (elemData?.id) {
                return this.element.data.media.url;
            }

            if (elemData?.url) {
                return this.assetFilter(elemConfig.url);
            }

            return staticFallBackImage;
        },

        mediaUrlFlip() {
            const fallBackImageFileName = this.cmsPreview.slice(this.cmsPreview.lastIndexOf('/') + 1);
            const staticFallBackImage = this.assetFilter(`administration/static/img/cms/${fallBackImageFileName}`);
            const elemData = this.element.data.mediaFlip;
            const elemConfig = this.element.config.mediaFlip;

            if (elemConfig.source === 'mapped') {
                const demoMedia = this.getDemoValue(elemConfig.value);

                if (demoMedia?.url) {
                    return demoMedia.url;
                }

                return staticFallBackImage;
            }

            if (elemConfig.source === 'default') {
                // use only the filename
                const fileName = elemConfig.value.slice(elemConfig.value.lastIndexOf('/') + 1);
                return this.assetFilter(`/administration/static/img/cms/${fileName}`);
            }

            if (elemData?.id) {
                return this.element.data.mediaFlip.url;
            }

            if (elemData?.url) {
                return this.assetFilter(elemConfig.url);
            }

            return staticFallBackImage;
        },

        assetFilter() {
            return Filter.getByName('asset');
        },

        mediaConfigValue() {
            return this.element?.config?.sliderItems?.value;
        },
    },

    watch: {
        cmsPageState: {
            deep: true,
            handler() {
                this.$forceUpdate();
            },
        },

        mediaConfigValue(value) {
            const mediaId = this.element?.data?.media?.id;
            const isSourceStatic = this.element?.config?.media?.source === 'static';

            if (isSourceStatic && mediaId && value !== mediaId) {
                this.element.config.media.value = mediaId;
            }
        },

        mediaFlipConfigValue(value) {
            const mediaId = this.element?.data?.mediaFlip?.id;
            const isSourceStatic = this.element?.config?.mediaFlip?.source === 'static';

            if (isSourceStatic && mediaId && value !== mediaId) {
                this.element.config.mediaFlip.value = mediaId;
            }
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('image-flip');
            this.initElementData('image-flip');
        },
    },
});
