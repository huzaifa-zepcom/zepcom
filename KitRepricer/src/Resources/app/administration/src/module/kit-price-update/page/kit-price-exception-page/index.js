import template from './kit-price-exception-page.html.twig'

const { Mixin } = Shopware
const { EntityCollection, Criteria } = Shopware.Data

Shopware.Component.register('kit-price-exception-page', {
  template,
  inject: ['repositoryFactory', 'KitPriceUpdateApiService'],

  mixins: [
    Mixin.getByName('notification')
  ],

  data () {
    return {
      name: '',
      manufacturerIds: [],
      supplierIds: [],
      categoryIds: [],
      productNumbers: null,
      productName: null,
      productDesc: null,
      minPrice: 100,
      maxPrice: null,
      priority: null,
      enable: true,
      adjustIfInStock: false,
      adjustWithCompetitorInventory: false,
      active: true,
      minMargin: null,
      gapToCompetitor: null,
      position: null,
      ruleId: null,
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
      isProductLoading: false,
      suppliers: [],
      categories: null,
      manufacturers: [],
      excluded: false,
      updateRegularPrice: false
    }
  },

  metaInfo () {
    return {
      title: this.$createTitle()
    }
  },

  created () {
    this.createdComponent();

  },

  watch: {
    '$route.params.id' () {
      this.fetchRule()
    }
  },

  computed: {
    minMarginLabel () {
      if (this.showForSink) {
        return this.$tc('kit-price-update.rules.minimumMargin')
      } else {
        return this.$tc('kit-price-update.rules.comparitiveDifference')
      }
    },

    showForSink () {
      return this.type === 'sink'
    },

    productRepository () {
      return this.repositoryFactory.create('product')
    },

    categoryRepository () {
      return this.repositoryFactory.create('category')
    },

    supplierRepository () {
      return this.repositoryFactory.create('kit_supplier')
    },

    manufacturerRepository () {
      return this.repositoryFactory.create('product_manufacturer')
    },

    listingCriteria () {
      const criteria = new Criteria(1, 500)

      criteria.addSorting(
        Criteria.sort('name', 'ASC')
      )

      return criteria
    },
  },

  methods: {
    createdComponent () {
      this.getManufacturers()
      this.getCategories()
      this.getSuppliers()
      this.fetchRule()
    },

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

    getManufacturers () {
      this.isLoading = true
      let manufacturers = []
      this.manufacturerRepository.search(this.listingCriteria, Shopware.Context.api).then((items) => {
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

    setManufacturer (item) {
      this.manufacturerIds = item.value
    },

    getSuppliers () {
      this.isLoading = true
      let suppliers = []
      this.listingCriteria.setLimit(500);

      this.supplierRepository.search(this.listingCriteria, Shopware.Context.api).then((items) => {
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

    setSupplier (item) {
      this.supplierIds = item.value
    },

    fetchRule () {
      if (this.$route.params.id) {
        this.isLoading = true
        this.ruleId = this.$route.params.id

        this.KitPriceUpdateApiService.sendGet('get-rule', { ruleId: this.ruleId }).then((response) => {
          let data = response.data
          for (const key in data) {
            if (this.$data.hasOwnProperty(key)) {
              this.$set(this, key, data[key])
            }
          }

          this.categoryIds = data.categoryIds;
          this.getCategories();

        }).finally(() => {
          this.isLoading = false
        })
      }
    },

    showGrowlNotification (type, message) {
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

    onCancel () {
      this.$router.push({ name: 'kit.price.update.index' })
    },

    onSave () {
      if (!this.checkRequiredFields()) {
        return
      }

      this.isLoading = true

      let params = {
        name: this.name,
        manufacturerIds: this.manufacturerIds,
        supplierIds: this.supplierIds,
        categoryIds: this.categoryIds,
        productNumbers: this.productNumbers,
        minPrice: this.minPrice,
        productDesc: this.productDesc,
        productName: this.productName,
        excluded: this.excluded,
        maxPrice: this.maxPrice,
        priority: this.priority,
        adjustIfInStock: this.showForSink ? this.adjustIfInStock : false,
        adjustWithCompetitorInventory: this.showForSink ? this.adjustWithCompetitorInventory : false,
        active: this.active,
        minMargin: this.minMargin,
        gapToCompetitor: this.gapToCompetitor,
        position: this.position,
        updateRegularPrice: this.updateRegularPrice,
        type: this.type
      }

      if (this.ruleId) {
        params.id = this.ruleId
      }

      this.KitPriceUpdateApiService.sendPost('save-rule', params).then((response) => {
        this.showGrowlNotification('success', response.message)

        this.$router.push({ name: 'kit.price.update.index' })
      }).catch((errorResponse) => {
        this.showGrowlNotification('error', errorResponse.message)

      }).finally(() => {
        this.isLoading = false
      })
    },

    checkRequiredFields () {
      if (!this.name) {
        let name = this.$tc('kit-price-update.rules.generalRule')
        this.showGrowlNotification('error', this.$tc('kit-price-update.general.required', 0, { name: name }))
        return false
      }

      if (!this.priority) {
        let name = this.$tc('kit-price-update.rules.priority')
        this.showGrowlNotification('error', this.$tc('kit-price-update.general.required', 0, { name: name }))
        return false
      }

      return true
    }
  }
})
