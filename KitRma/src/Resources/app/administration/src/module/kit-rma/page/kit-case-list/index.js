import template from './../kit-base-list/kit-base-list.html.twig'

Shopware.Component.extend('kit-case-list', 'kit-base-list', {
  template,

  created () {
    this.repository = this.repositoryFactory.create('rma_case')
    this.getList()
  },

  computed: {
    detailRoute () {
      return 'kit.rma.case_view'
    },

    createRoute () {
      return { name: 'kit.rma.case' }
    },

    columns () {
      return [
        {
          property: 'name',
          dataIndex: 'name',
          label: 'kit-rma.modules.case.name',
          routerLink: 'kit.rma.case_view',
        }]
    }
  }
})
