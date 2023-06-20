import template from './../kit-case-view/kit-case-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.extend('kit-case-new', 'kit-case-view', {
  template,

  methods: {
    getCase() {
      this.businessCase = this.repository.create(Shopware.Context.api)
    },

    onClickSave () {
      this.isLoading = true
      this.updateFreetextFields()

      this.repository
        .save(this.businessCase, Shopware.Context.api)
        .then(() => {
          this.isLoading = false
          this.$router.push({ name: 'kit.rma.case_view', params: { id: this.businessCase.id } })
        }).catch((exception) => {
        this.isLoading = false

        this.createNotificationError({
          title: this.$root.$tc('global.default.error'),
          message: exception
        })
      })
    }
  }
});
