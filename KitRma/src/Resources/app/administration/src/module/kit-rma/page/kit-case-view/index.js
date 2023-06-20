import template from './kit-case-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.register('kit-case-view', {
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
      businessCase: null,
      fields: [],
      processSuccess: false,
      isLoading: false,
      nextId: 1
    }
  },

  created () {
    this.repository = this.repositoryFactory.create('rma_case')
    this.getCase()
  },

  methods: {
    updateFields ({ field, index }) {
      const fields = this.fields.slice(0, index)

      if (field) {
        fields.push(field)
      }

      this.fields = fields
    },

    removeFields () {
      this.nextId--;
      this.fields.pop()
    },

    updateFreetextFields () {
      this.businessCase.freetext = this.fields
    },

    addField () {
      let field = {
        id: this.nextId++,
        name: '',
        type: '',
        required: true,
        values: '',
        explanation: '',
        dependOnAmount: false
      };

      this.fields.push(field)

    },

    async getCase () {
      this.isLoading = true
      await this.repository
        .get(this.$route.params.id, Shopware.Context.api)
        .then((entity) => {
          this.isLoading = false
          this.businessCase = entity
          this.fields = this.businessCase.freetext
        })
    },

    onClickSave () {
      this.isLoading = true
      this.updateFreetextFields()

      this.repository
        .save(this.businessCase, Shopware.Context.api)
        .then(() => {
          this.getCase()
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
