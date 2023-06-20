import template from './../kit-base-list/kit-base-list.html.twig'

const { Mixin, Data: { Criteria } } = Shopware

Shopware.Component.extend('kit-status-list', 'kit-base-list', {
  template,

  created () {
    this.repository = this.repositoryFactory.create('rma_status')
    this.getList()
  },

  computed: {
    createRoute () {
      return { name: 'kit.rma.status' }
    },

    columns () {
      return [
        {
          property: 'name',
          dataIndex: 'name',
          label: 'kit-rma.modules.status.name',
          routerLink: 'kit.rma.status_view',
        },
        {
          property: 'nameExt',
          dataIndex: 'nameExt',
          label: 'kit-rma.modules.status.nameExtLabel'
        },
        {
          property: 'endstate',
          dataIndex: 'endstate',
          label: 'kit-rma.modules.status.endstateLabel'
        },
        {
          property: 'endstateFinal',
          dataIndex: 'endstateFinal',
          label: 'kit-rma.modules.status.endstateFinalLabel'
        },
        {
          property: 'color',
          dataIndex: 'color',
          label: 'kit-rma.modules.status.colorLabel'
        }]
    }
  }
})
