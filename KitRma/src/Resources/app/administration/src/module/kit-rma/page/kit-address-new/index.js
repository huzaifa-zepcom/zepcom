import template from './../kit-address-view/kit-address-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.extend('kit-address-new', 'kit-address-view', {
  template,

  methods: {
    getAddress() {
      this.address = this.repository.create(Shopware.Context.api)
    },

    onClickSave () {
      this.isLoading = true

      this.address.suppliers = this.selectedSuppliers
      this.repository
        .save(this.address, Shopware.Context.api)
        .then(() => {
          this.isLoading = false
          this.$router.push({ name: 'kit.rma.address_view', params: { id: this.address.id } })
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
