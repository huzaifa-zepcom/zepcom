import template from './kit-price-logs.html.twig'

Shopware.Component.register('kit-price-logs', {
  template,

  inject: ['repositoryFactory', 'KitPriceUpdateApiService'],

  data () {
    return {
      searchTerm: '',
      logsList: [],
      isLoading: false
    }
  },

  metaInfo () {
    return {
      title: this.$createTitle()
    }
  },

  created () {
    this.isLoading = true
    this.getLogs()
  },

  computed: {
    columns () {
      return [
        { property: 'artId', label: this.$tc('kit-price-update.logs.product'), rawData: true },
        { property: 'bestCompetitor', label: this.$tc('kit-price-update.logs.cheapestDealer'), rawData: true },
        { property: 'oldPrice', label: this.$tc('kit-price-update.logs.originalPrice'), rawData: true },
        { property: 'bestPriceWithMargin', label: this.$tc('kit-price-update.logs.optimizedPrice'), rawData: true },
        { property: 'min_price', label: this.$tc('kit-price-update.logs.minPrice'), rawData: true },
        { property: 'new_place', label: this.$tc('kit-price-update.logs.newPlace'), rawData: true },
        { property: 'percentage', label: this.$tc('kit-price-update.logs.margin'), rawData: true },
        { property: 'action', label: this.$tc('kit-price-update.logs.action'), rawData: true },
        { property: 'rulename', label: this.$tc('kit-price-update.logs.rulename'), rawData: true },
        { property: 'created_at', label: this.$tc('kit-price-update.logs.date'), rawData: true }
      ]

    }
  },

  methods: {
    getLogs () {
      let params = {}
      if (this.searchTerm) {
        params = { term: this.searchTerm }
      }

      this.KitPriceUpdateApiService.sendGet('logs', params).then((response) => {
        this.logsList = response.data
      }).finally(() => {
        this.isLoading = false
      })
    }
  }
})
