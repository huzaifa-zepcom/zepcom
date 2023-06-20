import template from './sw-order-state-change-modal-assign-mail-template.html.twig'
import './sw-order-state-change-modal-assign-mail-template.scss'

const {Criteria} = Shopware.Data
const {Component} = Shopware

Component.register('sw-order-state-change-modal-assign-mail-template', {
    template,

    inject: ['repositoryFactory', 'systemConfigApiService'],

    props: {
        order: {
            type: Object,
            required: true
        },

        mailTemplatesExist: {
            required: false
        },

        technicalName: {
            type: String,
            required: true
        },
        sendMail: {
            required: true
        }
    },

    data() {
        return {
            userHasSetMailTemplate: false,
            selectedMailTemplateId: null,
            mailTemplates: null,
            mailTemplateTypeIds: null,
            allTechnicalNames: null,
            hasPreviewTemplate: false,
            searchTerm: null
        }
    },

    computed: {
        mailTemplateRepository() {
            return this.repositoryFactory.create('mail_template')
        },

        mailTemplateSalesChannelAssociationRepository() {
            return this.repositoryFactory.create('mail_template_sales_channel')
        },

        getSelectedMailTemplateCriteria() {
            const criteria = new Criteria()
            criteria.addAssociation('mailTemplateType')

            return criteria
        },

        mailTemplateGridColumns() {
            return [
                {
                    property: 'radioButtons',
                    label: null,
                    rawData: true,
                    sortable: false
                },
                {
                    property: 'mailTemplateType.name',
                    label: 'sw-order.assignMailTemplateCard.gridColumnType',
                    rawData: true,
                    sortable: false
                },
                {
                    property: 'description',
                    label: 'sw-order.assignMailTemplateCard.gridColumnDescription',
                    rawData: true,
                    sortable: true
                }
            ]
        }
    },

    created() {
        this.createdComponent()
    },

    methods: {
        onPreviewEmail() {
            this.$emit('on-mail-preview', this.selectedMailTemplateId)
        },

        mailTemplateSelectionCriteria() {
            const criteria = new Criteria()
            criteria.addAssociation('mailTemplateType')

            return criteria
        },

        getSelectedMailTemplate(mailTemplateId) {
            return this.mailTemplateRepository
                .get(
                    mailTemplateId,
                    Shopware.Context.api,
                    this.getSelectedMailTemplateCriteria
                )
        },

        onChangeMailTemplate(mailTemplateId) {
            if (!mailTemplateId) {
                return
            }
            this.selectedMailTemplateId = mailTemplateId
            this.userHasSetMailTemplate = true
        },

        onConfirmNoMail() {
            this.$emit('on-no-mail-confirm')
        },

        onCreateMailTemplate() {
            const closeModal = new Promise((resolve) => {
                resolve(this.$emit('on-create-mail-template'))
            })

            closeModal.then(() => {
                this.$router.push({
                    name: 'sw.mail.template.create'
                })
            })
        },

        fillValues() {
            const searchTerm = this.searchTerm
            const criteria = this.mailTemplateSelectionCriteria().setLimit(10)

            if (this.mailTemplateTypeIds) {
                criteria.addFilter(
                    Criteria.equalsAny('mailTemplateType.id', this.mailTemplateTypeIds)
                )
            }

            if (searchTerm) {
                criteria.addFilter(
                    Criteria.contains('mailTemplateType.name', searchTerm)
                )
            }

            this.mailTemplateRepository.search(criteria, Shopware.Context.api).then((items) => {
                this.total = items.total
                this.mailTemplates = items
                this.isLoading = false
            })
        },

        onColumnSort() {
            this.userHasSetMailTemplate = false
        },

        onSearchTermChange(term) {
            this.searchTerm = term

            this.fillValues()
        },

        async createdComponent() {
            await this.systemConfigApiService.getValues('SuiMailOnOrderChange.config')
                .then(response => {
                    this.mailTemplateTypeIds = response['SuiMailOnOrderChange.config.mailTemplates']
                    this.isLoading = false
                })
            this.fillValues()
        }
    }
})
