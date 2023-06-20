import template from './kit-ticket-new.html.twig'

const {Mixin, Component, Data: {Criteria}} = Shopware

Component.register('kit-ticket-new', {
    template,

    inject: [
        'repositoryFactory', 'KitRmaTicketService'
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
            ticket: null,
            processSuccess: false,
            cases: null,
            isLoading: false,
            orders: [],
            products: [],
            quantity: 100,
            statuses: null,
            selectedSerialNumbers: [],
            serialNumbers: [],
            productNumber: null,
            orderNumber: null,
            productsFromOrder: [],
            customerNumber: null,
            isValidProduct: false
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('rma_ticket')
        this.statusRepository = this.repositoryFactory.create('rma_status')
        this.caseRepository = this.repositoryFactory.create('rma_case')

        this.getTicket()
        this.getStatuses()
        this.getCases()
    },

    computed: {
        productRepository() {
            return this.repositoryFactory.create('product')
        },

        orderRepository() {
            return this.repositoryFactory.create('order')
        },

        shippingInfoRepository() {
            return this.repositoryFactory.create('kit_shipping_info')
        },

        customerRepository() {
            return this.repositoryFactory.create('customer')
        },

        baseCriteria() {
            const criteria = new Criteria()
            criteria.addSorting(Criteria.sort('name'))
            return criteria
        }
    },

    methods: {
        getStatuses() {
            this.statusRepository.search(this.baseCriteria, Shopware.Context.api).then((entity) => {
                this.statuses = entity
            })
        },

        getCases() {
            this.caseRepository.search(this.baseCriteria, Shopware.Context.api).then((entity) => {
                this.cases = entity
            })
        },

        getTicket() {
            this.ticket = this.repository.create(Shopware.Context.api)
        },

        onSelectCustomer(customerId) {
            // Keep the current value when user tries to delete it
            if (!customerId) {
                return
            }

            this.ticket.customerId = customerId
        },

        async fetchProduct() {
            if (this.productNumber) {
                this.isLoading = true
                const criteria = new Criteria()
                criteria.limit = 1

                criteria.addFilter(Criteria.equals('productNumber', this.productNumber))

                await this.productRepository.search(criteria, Shopware.Context.api).then((result) => {
                    let product = result[0]
                    this.productsFromOrder = [{value: product.productNumber, label: product.translated.name}];
                    this.createNotificationInfo({
                        title: this.$root.$tc('global.default.success'),
                        message: 'Product found ' + product.translated.name
                    })
                    this.ticket.productId = product.id
                    this.isLoading = false
                    this.isValidProduct = true
                }).catch(() => {
                    this.isLoading = false
                    this.createNotificationError({
                        title: this.$root.$tc('global.default.error'),
                        message: 'Product not found: ' + this.productNumber
                    })
                })
            }
        },

        async fetchSerialsByProduct(value) {
            if (value) {
                let productId = await this.getProductIdByNumber(value)
                console.log({wait: productId, number: value})
                this.ticket.productId = productId;
                this.isLoading = true
                let criteria = new Criteria()
                criteria.addAssociation('tracking')
                criteria.addFilter(Criteria.equals('orderId', this.ticket.orderId))
                criteria.addFilter(Criteria.equals('productId', this.ticket.productId))
                criteria.limit = 1

                await this.shippingInfoRepository.search(criteria, Shopware.Context.api).then((result) => {
                    const shippingInfo = result[0]
                    const trackingInfo = shippingInfo.tracking
                    trackingInfo.map(item => {
                        if (item.serialNumber) {
                            this.serialNumbers.push({
                                value: item.serialNumber, label: item.serialNumber
                            })
                        }
                    })

                    this.createNotificationInfo({
                        title: this.$root.$tc('global.default.success'),
                        message: 'Found ' + serialNumbers.length + ' items'
                    })

                    this.isLoading = false
                }).catch(() => {
                    this.isLoading = false
                })
            }
        },

        async fetchCustomer() {
            if (this.customerNumber) {
                this.isLoading = true
                let criteria = new Criteria()

                criteria.addFilter(Criteria.equals('customerNumber', this.customerNumber))
                criteria.limit = 1

                await this.customerRepository.search(criteria, Shopware.Context.api).then((result) => {
                    this.isLoading = false
                    let customer = result[0]
                    this.createNotificationInfo({
                        title: this.$root.$tc('global.default.success'),
                        message: `Customer found ${customer.firstName} ${customer.lastName}`
                    })
                    this.onSelectCustomer(customer.id)
                }).catch(() => {
                    this.isLoading = false
                    this.createNotificationError({
                        title: this.$root.$tc('global.default.error'),
                        message: 'Customer not found: ' + this.customerNumber
                    })
                })
            }
        },

        async fetchOrder() {
            if (this.orderNumber) {
                this.isLoading = true
                let criteria = new Criteria()

                criteria.addFilter(Criteria.equals('orderNumber', this.orderNumber))
                criteria.addAssociation('orderCustomer')
                criteria.addAssociation('lineItems')
                criteria.limit = 1

                await this.orderRepository.search(criteria, Shopware.Context.api).then((result) => {
                    this.isLoading = false
                    let order = result[0]
                    this.ticket.orderId = order.id
                    this.productsFromOrder = []
                    order.lineItems.map(lineItem => {
                        this.productsFromOrder.push({value: lineItem.payload.productNumber, label: lineItem.label})
                    })

                    if (order.orderCustomer) {
                        this.onSelectCustomer(order.orderCustomer.customerId)
                        this.customerNumber = order.orderCustomer.customerNumber
                    }

                    this.createNotificationInfo({
                        title: this.$root.$tc('global.default.success'),
                        message: 'Order found'
                    })
                }).catch(() => {
                    this.isLoading = false
                    this.createNotificationError({
                        title: this.$root.$tc('global.default.error'),
                        message: 'Order not found: ' + this.orderNumber
                    })
                })
            }
        },

        onClickSave() {
            this.isLoading = true
            this.ticket.amount = parseInt(this.ticket.amount) || this.serialNumbers.length
            this.ticket.serialNumbers = this.selectedSerialNumbers

            this.KitRmaTicketService.sendPost('ticket', {ticket: this.ticket}).then((response) => {
                this.isLoading = false
                this.processSuccess = true
                this.$router.push({name: 'kit.rma.ticket_view', params: {id: response.id}})
            }).catch((exception) => {
                this.isLoading = false
                this.createNotificationError({
                    title: this.$root.$tc('global.default.error'),
                    message: exception
                })
            })
        },

        getProductIdByNumber(value) {
            const criteria = new Criteria()
            criteria.limit = 1

            criteria.addFilter(Criteria.equals('productNumber', value))
            return this.productRepository.search(criteria, Shopware.Context.api).then((result) => {
                let product = result[0]
                this.productNumber = product.productNumber
                return product.id
            })
        }
    }

})
