import template from './../kit-text-view/kit-text-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.extend('kit-text-new', 'kit-text-view', {
  template,

  methods: {
    getSnippet() {
      this.text = this.repository.create(Shopware.Context.api)
      this.text.type = 'A'
    },

    onClickSave () {
      this.isLoading = true

      this.repository
        .save(this.text, Shopware.Context.api)
        .then(() => {
          this.isLoading = false
          this.$router.push({ name: 'kit.rma.text_view', params: { id: this.text.id } })
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
