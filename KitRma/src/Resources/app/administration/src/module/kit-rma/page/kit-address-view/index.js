import template from './kit-address-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.register('kit-address-view', {
  template,

  inject: [
    'repositoryFactory'
  ],

  mixins: [
    Mixin.getByName('notification')
  ],

  metaInfo () {
    return {
      title: this.$createTitle()
    }
  },

  data () {
    return {
      repository: null,
      address: null,
      processSuccess: false,
      isLoading: false,
      selectedSuppliers: [],
      suppliers: []
    }
  },

  created () {
    this.repository = this.repositoryFactory.create('rma_address_book')
    this.getSuppliers()
    this.getAddress()
  },

  computed: {
    supplierRepository () {
      return this.repositoryFactory.create('kit_supplier')
    }
  },
  methods: {
    getSuppliers () {
      this.isLoading = true
      let criteria = new Criteria(1, 500)
      criteria.addSorting(Criteria.sort('name'))

      return this.supplierRepository.search(criteria, Shopware.Context.api).then((response) => {
        this.isLoading = false
        this.suppliers = response
      })
    },

    getAddress () {
      this.isLoading = true
      this.repository
        .get(this.$route.params.id, Shopware.Context.api)
        .then((entity) => {
          this.isLoading = false
          this.address = entity
          this.selectedSuppliers = entity.suppliers
        })
    },

    onClickSave () {
      this.isLoading = true

      this.address.suppliers = this.selectedSuppliers
      this.repository
        .save(this.address, Shopware.Context.api)
        .then(() => {
          this.getAddress()
          this.isLoading = false
          this.processSuccess = true
        }).catch((exception) => {
        this.isLoading = false
        this.createNotificationError({
          title: this.$root.$tc('global.default.error'),
          message: exception
        })
      })
    },
    saveFinish () {
      this.processSuccess = false
    },
  }
})
