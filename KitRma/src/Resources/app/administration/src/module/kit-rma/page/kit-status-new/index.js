import template from './../kit-status-view/kit-status-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.extend('kit-status-new', 'kit-status-view', {
  template,

  methods: {
    getStatus() {
      this.status = this.repository.create(Shopware.Context.api)
    },

    onClickSave () {
      this.isLoading = true

      this.repository
        .save(this.status, Shopware.Context.api)
        .then(() => {
          this.isLoading = false
          this.$router.push({ name: 'kit.rma.status_view', params: { id: this.status.id } })
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
