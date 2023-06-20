import template from './kit-ticket-view.html.twig'
import './kit-ticket-view.scss'

const {Mixin, Component, Data: {Criteria}} = Shopware

Component.register('kit-ticket-view', {
    template,

    inject: [
        'repositoryFactory',
        'KitRmaTicketService'
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        }
    },

    data() {
        return {
            repository: null,
            statusRepository: null,
            caseRepository: null,
            userRepository: null,
            supplierRepository: null,
            addressRepository: null,
            textRepository: null,
            historyRepository: null,
            ticket: null,
            oldTicket: null,
            processSuccess: false,
            statuses: null,
            cases: null,
            suppliers: [],
            users: null,
            texts: null,
            history: [],
            selectedText: null,
            freetextfields: [],
            files: [],
            attachments: [],
            documentView: [],
            isLoading: false,
            checkedPdf: false,
            allowPdf: false,
            freetext: [],
            active: 'general',
            customerCriteria: null,
            deleteHistory: false,
            datepickerConfig: {
                enableTime: false,
                altFormat: 'd.m.Y'
            },
            messageTypes: [
                {label: 'External', value: 'external'},
                {label: 'Internal', value: 'internal'}
            ],
            pdfFileName: 'Warenbegleitschein anhängen'
        }
    },

    created() {
        this.createdComponent()
    },

    watch: {
        files() {
            this.documentView = this.files.filter((file) => {
                return file.media !== null && file.media !== undefined
            })
        }
    },
    methods: {
        async createdComponent() {
            this.customerCriteria = new Criteria()
            this.customerCriteria.addSorting(Criteria.sort('firstName'))

            this.repository = this.repositoryFactory.create('rma_ticket')
            this.statusRepository = this.repositoryFactory.create('rma_status')
            this.caseRepository = this.repositoryFactory.create('rma_case')
            this.userRepository = this.repositoryFactory.create('user')
            this.supplierRepository = this.repositoryFactory.create('kit_supplier')
            this.addressRepository = this.repositoryFactory.create('rma_address_book')
            this.textRepository = this.repositoryFactory.create('rma_text')
            this.historyRepository = this.repositoryFactory.create('rma_ticket_history')

            this.getStatuses()
            this.getCases()
            this.getTexts()
            this.getSuppliers()

            await this.getTicket()
        },

        getTicket() {
            this.checkedPdf = false
            this.selectedText = null
            this.isLoading = true
            let criteria = new Criteria()
            criteria.addAssociation('case')
            criteria.addAssociation('customer')
            criteria.addAssociation('product')
            criteria.addAssociation('supplier')
            criteria.addAssociation('status')
            criteria.addAssociation('order')
            criteria.addAssociation('user')

            this.repository.get(this.$route.params.id, Shopware.Context.api, criteria).then((entity) => {
                this.ticket = entity
                this.ticket.message = ''
                this.ticket.message_type = 'external'
                if (!this.ticket.customerEmail) {
                    this.ticket.customerEmail = this.ticket.customer ? this.ticket.customer.email : ''
                }
                this.files = []
                this.attachments = []
                this.freetext = []

                // Clone old state of ticket so we can retrieve previous status/values in the controller
                this.oldTicket = JSON.parse(JSON.stringify(this.ticket))
                this.isLoading = false

                this.getHistory()
                this.generateFreeTextFields()
                this.initializeFiles()
                this.initializeAttachments()

            })
        },

        initializeFiles() {
            let me = this
            this.files = []

            // If files are available, we parse them and show them as media
            const files = this.parseAttachments(this.ticket.files)
            files.then(response => {
                me.files = response

                let min = response.length
                // We need max of 4 files, we if there are any files already in the system, we loop the remaining
                // so the user can add more files later on.
                for (let i = min; i < 4; i++) {
                    me.files.push({
                        id: 'file-' + i,
                        name: 'Anhang',
                        mediaId: null
                    })
                }
            })
        },

        initializeAttachments() {
            // We create an attachments loop, which is needed for the history attachments, which will be sent to the user
            // or customer depending on the message type. We do not need to re-store this from the database because we
            // are saving it with the history object.
            for (let i = 100; i < 104; i++) {
                this.attachments.push({
                    id: 'attachment-' + i,
                    name: 'Anhang',
                    mediaId: null
                })
            }
        },

        generateFreeTextFields() {
            this.freetext = []
            // Ticket content is the additional free-text information that we require from the user based on the type
            // of business case
            if (this.ticket.case) {
                this.freetextfields = this.ticket.case.freetext
                for (let i = 0; i < this.freetextfields.length; i++) {
                    let field = this.freetextfields[i]
                    if (field.type === 'file') {
                        continue
                    }
                    field.value = null
                    if (Array.isArray(this.ticket.ticketContent) && this.ticket.ticketContent.length > 0) {
                        // If we have freetext value saved, then use that
                        let storedField = this.ticket.ticketContent[i]
                        if (storedField !== undefined) {
                            if (storedField.type === field.type) {
                                field.value = storedField.value
                            }
                        }
                    }

                    let values = field.values
                    if (!Array.isArray(values) && field.type === 'selectbox') {
                        field.values = values.split(',').map(item => item.trim())
                    }

                    field.label = field.name

                    // If the freetext field is dependant on the amount, then we create a loop which will create a clone of
                    // the existing free-text object, except the ID will be different to make it unique.
                    if (field.dependOnAmount) {
                        for (let j = 1; j <= this.ticket.amount; j++) {
                            let duplicate = JSON.parse(JSON.stringify(field))
                            duplicate.id += 99 + j
                            duplicate.label += ' ' + j
                            let temp = null
                            if (Array.isArray(this.oldTicket.ticketContent)) {
                                temp = this.oldTicket.ticketContent.find(item => item.label === duplicate.label)
                            }
                            if (temp) {
                                duplicate = temp
                            }
                            if (duplicate.type === 'checkbox') {
                                duplicate.value = duplicate.value || duplicate.value === 'on'
                            }
                            let values = duplicate.values
                            if (!Array.isArray(values) && duplicate.type === 'selectbox') {
                                duplicate.values = values.split(',').map(item => item.trim())
                            }
                            if (duplicate.type === 'date') {
                                if (duplicate.value !== null && duplicate.value !== undefined) {
                                    let dateArray = duplicate.value.split('.')
                                    // For older formats using dd.yy.year
                                    if (dateArray.length === 3) {
                                        let valuedate = new Date(Date.UTC(parseInt(dateArray[2]), parseInt(dateArray[1]) - 1, parseInt(dateArray[0]), 0, 0, 0))
                                        duplicate.value = valuedate.toISOString()
                                    }
                                }
                            }
                            this.freetext.push(duplicate)
                        }
                    } else {
                        let temp = null
                        if (Array.isArray(this.oldTicket.ticketContent)) {
                            temp = this.oldTicket.ticketContent.find(item => item.label === field.label)
                        }
                        if (temp) {
                            field = temp
                        }
                        if (field.type === 'checkbox') {
                            field.value = field.value || field.value === 'on'
                        }
                        // selectbox has comma separated values so we parse that to show in dropdown.
                        let values = field.values
                        if (!Array.isArray(values) && field.type === 'selectbox') {
                            field.values = values.split(',').map(item => item.trim())
                        }
                        if (field.type === 'date') {
                            if (field.value !== null && field.value !== undefined) {
                                let dateArray = field.value.split('.')
                                // For older formats using dd.yy.year
                                if (dateArray.length === 3) {
                                    let valuedate = new Date(Date.UTC(parseInt(dateArray[2]), parseInt(dateArray[1]) - 1, parseInt(dateArray[0]), 0, 0, 0))
                                    field.value = valuedate.toISOString()
                                }
                            }
                        }
                        this.freetext.push(field)
                    }
                }
            }
        },

        getStatuses() {
            this.isLoading = true
            this.baseCriteria.setLimit(500)
            this.statusRepository.search(this.baseCriteria, Shopware.Context.api).then((entity) => {
                this.isLoading = false
                this.statuses = entity
            })
        },

        getCases() {
            this.isLoading = true
            this.baseCriteria.setLimit(500)
            this.caseRepository.search(this.baseCriteria, Shopware.Context.api).then((entity) => {
                this.isLoading = false
                this.cases = entity
            })
        },

        getTexts() {
            this.isLoading = true
            this.baseCriteria.setLimit(500)
            this.textRepository.search(this.baseCriteria, Shopware.Context.api).then((entity) => {
                this.isLoading = false
                this.texts = entity
            })
        },

        getUsers() {
            this.isLoading = true
            this.userRepository.search(this.baseCriteria, Shopware.Context.api).then((entity) => {
                this.isLoading = false
                this.users = entity
            })
        },

        getSuppliers() {
            this.isLoading = true
            let criteria = new Criteria(1, 500)
            criteria.addSorting(Criteria.sort('name'))

            this.supplierRepository.search(criteria, Shopware.Context.api).then((response) => {
                this.isLoading = false
                this.suppliers = response
            })
        },

        onSnippetSelected(selected) {
            if (!selected) {
                return
            }

            let rmaText = this.texts.find((item) => {
                return item.id === selected
            })

            // append new snippet in existing content
            this.ticket.message += rmaText.description + '\n'
        },

        onSupplierSelected(item) {
            if (item === null) {
                return
            }

            let criteria = new Criteria()
            criteria.addFilter(
                Criteria.contains('suppliers', item)
            )
            this.isLoading = true
            this.addressRepository.search(criteria, Shopware.Context.api).then((entity) => {
                this.isLoading = false
                this.ticket.deliveryAddress = entity.first() !== null ? entity.first().address : ''
            }).finally(() => {
                this.isLoading = false
            })

        },

        onClickSave() {
            for (let i = 0; i < this.freetext.length; i++) {
                let ticketFreeText = this.freetext[i]
                // we have setup fields that are required on admin side, and customer side.
                // since we are in the admin context, we check for the fields that are required on admin.
                if (ticketFreeText.requiredAdmin) {
                    if (ticketFreeText.value === null || ticketFreeText.value === undefined || ticketFreeText.value === '') {
                        this.createNotificationError({
                            title: this.$root.$tc('global.default.error'),
                            message: ticketFreeText.label + ' required!'
                        })
                        return
                    }
                }
            }

            if (!this.ticket.supplierId) {
                this.createNotificationError({
                    title: this.$root.$tc('global.default.error'),
                    message:  'Supplier is required!'
                })
                return
            }

            this.isLoading = true
            this.ticket.ticketContent = this.freetext
            const files = this.files.filter((file) => {
                return file.mediaId !== null
            })

            const attachments = this.attachments.filter((file) => {
                return file.mediaId !== null
            })

            this.ticket.files = files
            this.ticket.attachments = attachments

            this.KitRmaTicketService.sendPost('ticket', {old: this.oldTicket, ticket: this.ticket}).then(() => {
                this.isLoading = false
                this.processSuccess = true
                this.getTicket()
            }).catch((exception) => {
                this.isLoading = false
                this.createNotificationError({
                    title: this.$root.$tc('global.default.error'),
                    message: exception
                })
            })
        },

        setMediaItem({targetId}, type, name = '') {
            this.mediaRepository.get(targetId, Shopware.Context.api).then(updatedMedia => {
                if (updatedMedia !== null) {
                    let relatedOption = this.files.find((option) => option.id === type)
                    if (relatedOption === undefined) {
                        relatedOption = this.attachments.find((option) => option.id === type)
                    }

                    relatedOption.mediaId = updatedMedia.id
                    relatedOption.name = name.length > 0 ? name : updatedMedia.fileName
                    relatedOption.filename = updatedMedia.fileName
                    relatedOption.media = updatedMedia
                }
            })
        },

        onDropMedia(mediaItem, type) {
            this.setMediaItem({targetId: mediaItem.id}, type)
        },

        onUnlinkLogo(mediaId, id) {
            // first check if the media exists in the files array.
            let relatedOption = this.files.find((option) => option.id === id)

            // if it does not exist, we check in the attachments array.
            if (relatedOption === undefined) {
                relatedOption = this.attachments.find((option) => option.id === id)
            }

            this.mediaRepository.delete(relatedOption.mediaId, Shopware.Context.api).then((response) => {
                relatedOption.mediaId = null
                relatedOption.name = 'Anhang'
                relatedOption.filename = null
                relatedOption.media = null
                if (mediaId === this.pdfMediaId) {
                    this.pdfMediaId = null
                }

                // this.hasWgb();
            })
        },

        onCaseChange(caseId) {
            this.isLoading = true
            this.ticket.case = this.cases.find((item) => item.id === caseId)
            this.ticket.ticketContent = null
            this.generateFreeTextFields()
            this.initializeFiles()
            this.isLoading = false
        },

        saveFinish() {
            this.processSuccess = false
        },

        getHistory() {
            this.isLoading = true
            let criteria = new Criteria()
            criteria.addAssociation('user')
            criteria.addFilter(Criteria.equals('ticketId', this.$route.params.id))
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'))

            this.historyRepository.search(criteria, Shopware.Context.api).then((entity) => {

                this.history = []

                entity.forEach((item) => {

                    let kitMessage
                    let customerMessage

                    if (item.sender === 'CUSTOMER') {
                        kitMessage = null
                        customerMessage = item.message
                    } else {
                        kitMessage = item.message
                        customerMessage = null
                    }

                    item.customerMessage = customerMessage
                    item.kitMessage = kitMessage
                    const files = this.parseAttachments(item.attachment)
                    files.then(response => {
                        item.attachment = response
                    })

                    this.history.push(item)
                })
            }).finally(() => {
                this.isLoading = false
            })
        },

        closeModal() {
            this.isLoading = false
            this.deleteHistory = false
        },

        showModalDelete(item) {
            this.deleteHistory = item.id
        },

        onConfirmDelete(id) {
            this.isLoading = true
            this.deleteHistory = false
            this.historyRepository.delete(id, Shopware.Context.api).then(() => {
                this.getHistory()
            })
        },

        async parseAttachments(files) {
            let temp = []
            if (files !== null && files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i]
                    await this.mediaRepository.get(file.mediaId, Shopware.Context.api).then((updatedMedia) => {
                        if (updatedMedia !== null) {
                            file.id = file.mediaId
                            file.url = updatedMedia.url
                            file.media = updatedMedia
                            temp.push(file)
                        }
                    })
                }
            }

            return temp
        },

        createPDF(value) {
            if (value) {
                this.generatePdfDocument()
            } else {
                const file = this.attachments.find((file) => {
                    return file.mediaId === this.pdfMediaId
                })

                if (file) {
                    this.onUnlinkLogo(this.pdfMediaId, file.id)
                }
            }
        },

        generatePdfDocument() {
            this.isLoading = true
            this.KitRmaTicketService.sendPost('pdf', {rmaNumber: this.ticket.rmaNumber}).then((response) => {
                const emptyAttachment = this.attachments.find((file) => {
                    return file.mediaId === null
                })

                if (emptyAttachment) {
                    this.mediaRepository.get(response.mediaId, Shopware.Context.api).then(updatedMedia => {
                        if (updatedMedia !== null) {
                            this.pdfMediaId = response.mediaId
                            emptyAttachment.mediaId = updatedMedia.id
                            emptyAttachment.name = this.pdfFileName
                            emptyAttachment.filename = updatedMedia.fileName
                            emptyAttachment.media = updatedMedia
                            this.allowPdf = false
                        }
                    })
                }

                this.isLoading = false
            }).catch((exception) => {
                this.checkedPdf = false
                this.isLoading = false
                this.createNotificationError({
                    title: this.$root.$tc('global.default.error'),
                    message: exception.response.data.errors[0].detail
                })
            })
        },

        hasWgb() {
            const files = this.attachments.filter((file) => {
                return file.name === this.pdfFileName
            })

            this.allowPdf = true
            this.checkedPdf = false

            if (files.length > 0) {
                this.checkedPdf = true
                this.allowPdf = false
            }

            return files.length > 0
        }
    },

    computed: {
        // only allow pdf generation if following conditions are met.
        allowWgb() {
            return this.ticket.supplierRmaNumber && this.ticket.supplierRmaNumber.trim() && this.hasProduct
        },

        mediaRepository() {
            return this.repositoryFactory.create('media')
        },

        historyCount() {
            return this.history.length > 0
        },

        freetextCount() {
            return this.freetext.length > 0
        },

        baseCriteria() {
            const criteria = new Criteria()
            criteria.addSorting(Criteria.sort('name'))
            return criteria
        },

        userCriteria() {
            const criteria = new Criteria()
            criteria.addSorting(Criteria.sort('firstName'))
            criteria.addSorting(Criteria.sort('lastName'))
            return criteria
        },

        hasProduct() {
            return (this.ticket.product || (this.ticket.productName && this.ticket.productName.trim() && this.ticket.productNumber && this.ticket.productNumber.trim()))
        }
    }
})
