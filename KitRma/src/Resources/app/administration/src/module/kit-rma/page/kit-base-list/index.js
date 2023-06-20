import template from './kit-base-list.html.twig'

const { Mixin, Data: { Criteria } } = Shopware

Shopware.Component.register('kit-base-list', {
  template,

  inject: ['repositoryFactory'],

  mixins: [
    Mixin.getByName('notification'),
    Mixin.getByName('listing')
  ],

  data () {
    return {
      isLoading: false,
      list: null,
      repository: null,
      sortBy: 'name',
      naturalSorting: true,
      total: 0

    }
  },

  metaInfo () {
    return {
      title: this.$createTitle()
    }
  },

  computed: {
    detailRoute () {
      return ''
    },

    createRoute () {
      return { name: '' }
    },

    createRouteText () {
      return this.$tc('kit-rma.general.create');
    },

    columns () {
      return []
    }
  },

  methods: {
    getList () {
      let criteria = new Criteria()
      criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection))

      this.isLoading = true
      return this.repository.search(criteria, Shopware.Context.api).then((response) => {
        this.isLoading = false
        this.list = response
        this.total = response.total
      })
    }
  }
})
