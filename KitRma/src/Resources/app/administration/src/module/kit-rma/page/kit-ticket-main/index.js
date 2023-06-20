import template from './kit-ticket-main.html.twig'

Shopware.Component.register('kit-ticket-main', {
  template,

  data () {
    return {
      isLoading: false,
      active: 'list'
    }
  },

  metaInfo () {
    return {
      title: this.$createTitle()
    }
  },

  computed: {

    rmaModules () {
      let ticket = [
        {
          name: 'ticket-new',
          label: 'kit-rma.modules.ticket.new',
          to: 'kit.rma.ticket',
          icon: 'default-documentation-paper-pencil',
        },
        {
          name: 'ticket-list',
          label: 'kit-rma.modules.ticket.list',
          to: 'kit.rma.ticket_list',
          icon: 'default-text-list',
        }
      ];

      let cases = [
        {
          name: 'case-new',
          label: 'kit-rma.modules.case.new',
          to: 'kit.rma.case',
          icon: 'default-web-bug',
        },
        {
          name: 'case-list',
          label: 'kit-rma.modules.case.list',
          to: 'kit.rma.case_list',
          icon: 'default-text-list',
        }
      ];

      let status = [
        {
          name: 'status-new',
          label: 'kit-rma.modules.status.new',
          to: 'kit.rma.status',
          icon: 'default-action-tags',
        },
        {
          name: 'status-list',
          label: 'kit-rma.modules.status.list',
          to: 'kit.rma.status_list',
          icon: 'default-text-list',
        }
      ];

      let texts = [
        {
          name: 'text-new',
          label: 'kit-rma.modules.text.new',
          to: 'kit.rma.text',
          icon: 'default-action-tags',
        },
        {
          name: 'text-list',
          label: 'kit-rma.modules.text.list',
          to: 'kit.rma.text_list',
          icon: 'default-text-list',
        }
      ];

      let address = [
        {
          name: 'address-new',
          label: 'kit-rma.modules.address.new',
          to: 'kit.rma.address',
          icon: 'default-location-marker',
        },
        {
          name: 'address-list',
          label: 'kit-rma.modules.address.list',
          to: 'kit.rma.address_list',
          icon: 'default-text-list',
        }
      ];

      return {
        ticket: ticket,
        cases: cases,
        status: status,
        texts: texts,
        address: address
      }
    },

    ticketColumns () {
      return [{
        property: 'rmaNumber',
        dataIndex: 'rmaNumber',
        label: 'kit-rma.ticket.rmaNumber',
        routerLink: 'kit.rma.view',
      }, {
        property: 'createdAt',
        label: 'kit-rma.ticket.date',
        primary: true
      }, {
        property: 'case.name',
        label: 'kit-rma.ticket.case'
      }, {
        property: 'status.name',
        label: 'kit-rma.ticket.status'
      }, {
        property: 'customer.firstName',
        dataIndex: 'customer.firstName,customer.lastName',
        label: 'kit-rma.ticket.customer'
      }, {
        property: 'order.orderNumber',
        label: 'kit-rma.ticket.orderNumber'
      }, {
        property: 'product.translated.name',
        dataIndex: 'product.translated.name,product.productNumber',
        label: 'kit-rma.ticket.product'
      }
      ]
    }
  }
})
