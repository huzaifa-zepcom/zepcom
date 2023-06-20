import template from './../kit-base-list/kit-base-list.html.twig'

const { Mixin, Data: { Criteria } } = Shopware

Shopware.Component.extend('kit-address-list', 'kit-base-list', {
  template,

  created () {
    this.repository = this.repositoryFactory.create('rma_address_book')
    this.getList()
  },

  computed: {
    createRoute() {
      return {name: 'kit.rma.address'}
    },

    columns () {
      return [
        {
          property: 'name',
          dataIndex: 'name',
          label: 'kit-rma.modules.address.name',
          routerLink: 'kit.rma.address_view',
        }
      ]
    }
  }
})
