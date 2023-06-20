import template from './sw-cms-el-config-image-flip.html.twig';
import './sw-cms-el-config-image-flip.scss';

const { Component, Mixin } = Shopware;

/**
 * @private since v6.5.0
 * @package content
 */
Component.register('sw-cms-el-config-image-flip', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('cms-element'),
    ],

    data() {
        return {
            mediaModalIsOpen: false,
            mediaFlipModalIsOpen: false,
            initialFolderId: null,
        };
    },

    computed: {
        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        uploadTag() {
            return `cms-element-media-config-${this.element.id}`;
        },

        uploadTagFlip() {
            return `cms-element-media-config-flip-${this.element.id}`;
        },

        previewSource() {
            if (this.element.data && this.element.data.media && this.element.data.media.id) {
                return this.element.data.media;
            }

            return this.element.config.media.value;
        },

        previewSourceFlip() {
            if (this.element.data && this.element.data.mediaFlip && this.element.data.mediaFlip.id) {
                return this.element.data.mediaFlip;
            }

            return this.element.config.mediaFlip.value;
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.initElementConfig('image-flip');
        },

        async onImageUpload({ targetId }) {
            const mediaEntity = await this.mediaRepository.get(targetId);

            this.element.config.media.value = mediaEntity.id;
            this.element.config.media.source = 'static';

            this.updateElementData(mediaEntity);

            this.$emit('element-update', this.element);
        },

        async onImageUploadFlip({ targetId }) {
            const mediaEntity = await this.mediaRepository.get(targetId);

            this.element.config.mediaFlip.value = mediaEntity.id;
            this.element.config.mediaFlip.source = 'static';

            this.updateElementData(mediaEntity);

            this.$emit('element-update', this.element);
        },

        onImageRemove() {
            this.element.config.media.value = null;

            this.updateElementData();

            this.$emit('element-update', this.element);
        },

        onImageRemoveFlip() {
            this.element.config.mediaFlip.value = null;

            this.updateElementDataFlip();

            this.$emit('element-update', this.element);
        },

        onCloseModal() {
            this.mediaModalIsOpen = false;
        },

        onCloseModalFlip() {
            this.mediaFlipModalIsOpen = false;
        },

        onSelectionChanges(mediaEntity) {
            const media = mediaEntity[0];
            this.element.config.media.value = media.id;
            this.element.config.media.source = 'static';

            this.updateElementData(media);

            this.$emit('element-update', this.element);
        },

        onSelectionChangesFlip(mediaEntity) {
            const media = mediaEntity[0];
            this.element.config.mediaFlip.value = media.id;
            this.element.config.mediaFlip.source = 'static';

            this.updateElementDataFlip(media);

            this.$emit('element-update', this.element);
        },

        updateElementData(media = null) {
            const mediaId = media === null ? null : media.id;
            if (!this.element.data) {
                this.$set(this.element, 'data', { mediaId, media });
            } else {
                this.$set(this.element.data, 'mediaId', mediaId);
                this.$set(this.element.data, 'media', media);
            }
        },

        updateElementDataFlip(media = null) {
            const mediaId = media === null ? null : media.id;
            if (!this.element.data) {
                this.$set(this.element, 'data', { 'mediaFlipId': mediaId, 'mediaFlip': media });
            } else {
                this.$set(this.element.data, 'mediaFlipId', mediaId);
                this.$set(this.element.data, 'mediaFlip', media);
            }
        },

        onOpenMediaModal() {
            this.mediaModalIsOpen = true;
        },

        onOpenMediaFlipModal() {
            this.mediaFlipModalIsOpen = true;
        },

        onChangeMinHeight(value) {
            this.element.config.minHeight.value = value === null ? '' : value;

            this.$emit('element-update', this.element);
        },

        onChangeDisplayMode(value) {
            if (value === 'cover') {
                this.element.config.verticalAlign.value = null;
            }

            this.$emit('element-update', this.element);
        },
    },
});
