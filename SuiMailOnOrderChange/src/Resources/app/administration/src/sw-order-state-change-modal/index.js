import template from './sw-order-state-change-modal.html.twig';

const {Component} = Shopware;

Component.override('sw-order-state-change-modal', {
    template,

    inject: ['SuiOrderStatusApiService'],
    props: {
        order: {
            type: Object,
            required: true
        },

        isLoading: {
            type: Boolean,
            required: true
        },

        mailTemplatesExist: {
            required: false
        },

        technicalName: {
            type: String,
            required: true
        }
    },

    data() {
        return {
            showModal: false,
            assignMailTemplatesOptions: [],
            userCanConfirm: false,
            userHasAssignedMailTemplate: false,
            docIds: [],
            mailTemplateId: null,
            hasPreviewTemplate: false,
            mail: {
                toField: '',
                subject: '',
                content: ''
            },
            sendMail: false
        };
    },

    computed: {
        modalTitle() {
            return this.mailTemplatesExist || this.userHasAssignedMailTemplate ?
                this.$tc('sw-order.documentCard.modalTitle') :
                this.$tc('sw-order.assignMailTemplateCard.cardTitle');
        },
    },

    methods: {
        onCancel() {
            this.$emit('page-leave');
        },

        onDocsConfirm(docIds, sendMail = true) {
            this.sendMail = sendMail;
            this.docIds = docIds;
        },

        async onSendMail() {
            if (this.sendMail) {
                await this.SuiOrderStatusApiService.sendPost('mail', {
                    templateId: this.mailTemplateId,
                    orderId: this.order.id,
                    preview: false,
                    content: this.mail.content,
                    subject: this.mail.subject,
                    toField: this.mail.toField,
                    documentIds: this.docIds
                });
            }

            await this.closeModal();
            this.onNoMailConfirm();
        },

        closeModal() {
            this.hasPreviewTemplate = false;
        },

        async onPreviewEmail(mailTemplateId) {
            this.hasPreviewTemplate = true;
            this.mailTemplateId = mailTemplateId;

            await this.SuiOrderStatusApiService.sendPost('mail', {
                templateId: mailTemplateId,
                orderId: this.order.id,
                preview: true
            }).then(response => {
                this.hasPreviewTemplate = true;
                this.mail = {
                    toField: response.toField,
                    subject: response.subject,
                    content: response.content
                };
            });
        },

        onNoMailConfirm() {
            this.$emit('page-leave-confirm', [], false);
        },

        onAssignMailTemplate() {
            this.userHasAssignedMailTemplate = true;
        }
    }
});
