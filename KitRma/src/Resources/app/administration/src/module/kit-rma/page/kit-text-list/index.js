import template from './../kit-base-list/kit-base-list.html.twig'

const { Mixin, Data: { Criteria } } = Shopware

Shopware.Component.extend('kit-text-list', 'kit-base-list', {
  template,

  created () {
    this.repository = this.repositoryFactory.create('rma_text')
    this.getList()
  },

  computed: {
    createRoute () {
      return { name: 'kit.rma.text' }
    },
    columns () {
      return [
        {
          property: 'name',
          dataIndex: 'name',
          label: 'kit-rma.modules.text.name',
          routerLink: 'kit.rma.text_view',
        }
      ]
    }
  }
})
