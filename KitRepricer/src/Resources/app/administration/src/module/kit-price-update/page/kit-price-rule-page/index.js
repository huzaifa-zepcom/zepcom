import template from './kit-price-rule-page.html.twig'
import './kit-price-rule-page.scss'

const { Mixin } = Shopware
const { EntityCollection, Criteria } = Shopware.Data

Shopware.Component.register('kit-price-rule-page', {
    template,

    inject: ['repositoryFactory', 'KitPriceUpdateApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            showDeleteModal: false,
            manufacturerIds: [],
            supplierIds: [],
            categoryIds: [],
            minPrice: 100,
            minMargin: null,
            gapToCompetitor: null,
            type: 'sink',
            types: [
                {
                    value: 'sink',
                    label: 'Sink'
                }, {
                    value: 'raise',
                    label: 'Raise'
                }
            ],

            isLoading: false,
            exceptionList: [],
            suppliers: [],
            categories: null,
            manufacturers: [],
            updateRegularPrice: false
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        }
    },

    created() {
        this.getCategories()
        this.getManufacturers()
        this.getSuppliers()
        this.getExceptionList()
        this.getBaseRule()
    },

    computed: {
        minMarginLabel() {
            if (this.type === 'sink') {
                return this.$tc('kit-price-update.rules.minimumMargin')
            } else {
                return this.$tc('kit-price-update.rules.comparitiveDifference')
            }
        },

        showForSink() {
            return this.type === 'sink';
        },

        categoryRepository () {
            return this.repositoryFactory.create('category')
        },

        listingCriteria() {
            const criteria = new Criteria(1, 500)

            if (this.term) {
                criteria.setTerm(this.term)
            }

            criteria.addSorting(
                Criteria.sort('name', 'ASC')
            )

            return criteria
        },

        columns() {
            return [
                {property: 'name', label: this.$tc('kit-price-update.rules.generalRule'), rawData: true},
                {property: 'priority', label: this.$tc('kit-price-update.rules.priority'), rawData: true},
                {property: 'type', label: this.$tc('kit-price-update.rules.type'), rawData: true}
            ]

        }
    },

    methods: {

        getCategories () {
            this.categories = new EntityCollection(
                this.categoryRepository.route,
                this.categoryRepository.entityName,
                Shopware.Context.api
            );


            if (this.categoryIds.length <= 0) {
                return Promise.resolve();
            }

            const criteria = new Criteria();
            criteria.setIds(this.categoryIds);

            return this.categoryRepository.search(criteria, Shopware.Context.api).then((categories) => {
                this.categories = categories;
            });
        },

        setCategory (collection) {
            this.categoryIds = collection.getIds()
            this.categories = collection
        },

        getLabel(category) {
            return category.breadcrumb.join(' > ');
        },

        getManufacturers() {
            this.isLoading = true
            const manufacturerRepository = this.repositoryFactory.create('product_manufacturer')
            let manufacturers = []
            manufacturerRepository.search(this.listingCriteria, Shopware.Context.api).then((items) => {
                this.total = items.total
                items.forEach((item) => {
                    if(item.name) {
                        manufacturers.push({
                            value: item.id,
                            label: item.name
                        })
                    }
                })
                this.manufacturers = manufacturers

            }).finally(() => {
                this.isLoading = false
            })
        },

        setManufacturer(item) {
            this.manufacturerIds = item.value
        },

        getSuppliers() {
            this.isLoading = true
            const supplierRepository = this.repositoryFactory.create('kit_supplier')

            let suppliers = []
            supplierRepository.search(this.listingCriteria, Shopware.Context.api).then((items) => {
                this.total = items.total
                items.forEach((item) => {
                    suppliers.push({
                        value: item.supplierId,
                        label: item.name
                    })
                })
                this.suppliers = suppliers

            }).finally(() => {
                this.isLoading = false
            })
        },

        setSupplier(item) {
            this.supplierIds = item.value
        },

        getExceptionList() {
            this.isLoading = true
            this.KitPriceUpdateApiService.sendGet('list').then((response) => {
                this.exceptionList = response.data
            }).finally(() => {
                this.isLoading = false
            })
        },

        showGrowlNotification(type, message) {
            if (type === 'success') {
                this.createNotificationSuccess({
                    title: this.$tc('global.default.success'),
                    message: message
                })
            } else {
                this.createNotificationError({
                    title: this.$tc('global.default.error'),
                    message: message
                })
            }
        },

        getBaseRule(type = 'sink') {
            this.isLoading = true
            this.resetDataAttributes();
            this.KitPriceUpdateApiService.sendGet('get-base', {type: type}).then((response) => {
                let data = response.data
                for (const key in data) {
                    if (this.$data.hasOwnProperty(key)) {
                        this.$set(this, key, data[key])
                    }
                }

                this.categoryIds = data.categoryIds;
                this.getCategories();

            }).catch(() => {
            }).finally(() => {
                this.isLoading = false
            })
        },

        resetDataAttributes() {
            this.categoryIds = []
            this.manufacturerIds = []
            this.supplierIds = []
            this.minPrice = 100
            this.gapToCompetitor = null
            this.updateRegularPrice = false
            this.minMargin = null
        },

        onChangeRule() {
            this.getBaseRule(this.type)
        },

        onEdit(item) {
            this.$router.push({name: 'kit.price.update.detail', params: {id: item.id}})

        },

        onDelete(id) {
            this.showDeleteModal = id
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false
        },

        onConfirmDelete(id) {
            this.showDeleteModal = false

            this.KitPriceUpdateApiService.sendPost('delete-rule', {id: id}).then((response) => {
                this.showGrowlNotification('success', response.message)
                this.getExceptionList()
            }).catch((errorResponse) => {
                this.showGrowlNotification('error', errorResponse.message)
            }).finally(() => {
                this.isLoading = false
            })
        },

        createNew() {
            this.$router.push({name: 'kit.price.update.create'})
        },

        onSave() {
            if (!this.checkRequiredFields()) {
                return
            }

            this.isLoading = true

            let params = {
                manufacturerIds: this.manufacturerIds,
                supplierIds: this.supplierIds,
                categoryIds: this.categoryIds,
                minPrice: this.minPrice,
                minMargin: this.minMargin,
                updateRegularPrice: this.updateRegularPrice,
                gapToCompetitor: this.gapToCompetitor,
                type: this.type
            }

            this.KitPriceUpdateApiService.sendPost('save-base', params).then((response) => {
                this.showGrowlNotification('success', response.message)
            }).catch((errorResponse) => {
                this.showGrowlNotification('error', errorResponse.message)

            }).finally(() => {
                this.isLoading = false
            })
        },

        checkRequiredFields() {
            if (!this.minPrice) {
                let name = this.$tc('kit-price-update.rules.minimumPrice')
                this.showGrowlNotification('error', this.$tc('kit-price-update.general.required', 0, {name: name}))
                return false
            }

            return true
        }

    }
})
