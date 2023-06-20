import template from './kit-ticket-list.html.twig';
import './kit-ticket-list.scss';

const {Mixin, Data: {Criteria}} = Shopware;

Shopware.Component.register('kit-ticket-list', {
  template,

  inject: ['repositoryFactory'],

  mixins: [
    Mixin.getByName('notification'),
    Mixin.getByName('listing'),
  ],

  data() {
    return {
      isLoading: false,
      list: null,
      repository: null,
      sortBy: 'createdAt',
      sortDirectiion: 'DESC',
      naturalSorting: true,
      filterData: {'open': true},
      total: 0,

    };
  },

  metaInfo() {
    return {
      title: this.$createTitle(),
    };
  },

  created() {
    this.getList();
  },

  methods: {
    onlyOpenCases() {
      if (this.filterData['open']) {
        this.filterData['status'] = null;
      }

      this.onSelect();
    },

    onSelect() {
      if (this.filterData['status']) {
        this.filterData['open'] = null;
      }

      this.getList();

    },

    filterChange(e) {
      if (!e.target.value) {
        return;
      }

      this.getList();
    },

    getList() {
      this.repository = this.repositoryFactory.create('rma_ticket');
      this.isLoading = true;

      this.repository.search(this.baseCriteria, Shopware.Context.api).then((result) => {
        this.list = result;
        this.total = result.total;
        this.isLoading = false;
      }).catch(() => {
        this.isLoading = false;
      });
    },

    getTicketColumns() {
      return [
        {
          property: 'rmaNumber',
          dataIndex: 'rmaNumber',
          label: 'kit-rma.modules.ticket.rmaNumber',
          routerLink: 'kit.rma.ticket_view',
        },
        {
          property: 'createdAt',
          dataIndex: 'createdAt',
          label: 'kit-rma.modules.ticket.createdAt',
        },
        {
          property: 'caseName',
          dataIndex: 'caseName',
          label: 'kit-rma.modules.ticket.case',
        },
        {
          property: 'statusName',
          dataIndex: 'status.name',
          label: 'kit-rma.modules.ticket.status',
        },
        {
          property: 'productName',
          dataIndex: 'productName',
          label: 'kit-rma.modules.ticket.product',
        },
        {
          property: 'productNumber',
          dataIndex: 'productNumber',
          label: 'kit-rma.modules.ticket.productNumber',
        },
        {
          property: 'customerName',
          dataIndex: 'customerName',
          label: 'kit-rma.modules.ticket.customer',
        },
        {
          property: 'order.orderNumber',
          dataIndex: 'order.orderNumber',
          label: 'kit-rma.modules.ticket.order',
        },
        {
          property: 'user.name',
          dataIndex: 'user.name',
          label: 'kit-rma.modules.ticket.user',
        },
      ];
    },

    resetFilter() {
      this.filterData = [];
      this.getList();
    },

    onEnter(e) {
      if (e.keyCode === 13) {
        this.getList();
      }
    },

    getCaseLabel(item) {
      return item === undefined ? '-' : item.name;
    },

    getProductLabel(item) {
      return item.product ? item.product.name : item.productName;
    },

    getProductNumber(item) {
      return item.product ? item.product.productNumber : item.productNumber;
    },

    getCustomerLabel(item) {
      return item === undefined ? '-' : (item.company ? item.company : item.firstName + ' ' + item.lastName);
    },
  },

  computed: {
    entityCriteria() {
      const criteria = new Criteria();
      criteria.addSorting(Criteria.sort('name'));
      return criteria;
    },

    baseCriteria() {
      const criteria = new Criteria();
      criteria.addAssociation('case');
      criteria.addAssociation('customer');
      criteria.addAssociation('product');
      criteria.addAssociation('supplier');
      criteria.addAssociation('status');
      criteria.addAssociation('order');
      criteria.addAssociation('user');
      criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));
      criteria.addFilter(Criteria.not(
          'AND',
          [Criteria.equals('rmaNumber', null)],
      ));

      if (this.filterData) {
        if (this.filterData['status']) {
          criteria.addFilter(Criteria.equals('statusId', this.filterData['status']));
        } else if (this.filterData['open']) {
          criteria.addFilter(Criteria.equals('status.endstate', false));
        }
        if (this.filterData['order']) {
          criteria.addFilter(Criteria.equals('order.orderNumber', this.filterData['order']));
        }
        if (this.filterData['customer']) {
          criteria.addFilter(Criteria.equals('customer.customerNumber', this.filterData['customer']));
        }
        if (this.filterData['product']) {
          criteria.addFilter(Criteria.multi('OR', [
            Criteria.contains('product.name', this.filterData['product']),
            Criteria.contains('productName', this.filterData['product']),
          ]));
        }
        if (this.filterData['serial']) {
          criteria.addFilter(Criteria.contains('ticketContent', this.filterData['serial']));
        }
        if (this.filterData['ticket']) {
          if (this.filterData['ticket'].includes('*')) {
            let rma = this.filterData['ticket'].replace('*', '').trim();
            criteria.addFilter(Criteria.contains('rmaNumber', rma));
          } else {
            criteria.addFilter(Criteria.equals('rmaNumber', this.filterData['ticket']));
          }
        }
        if (this.filterData['supplier']) {
          criteria.addFilter(Criteria.equals('supplierId', this.filterData['supplier']));
        }
        if (this.filterData['supplierRmaNumber']) {
          criteria.addFilter(Criteria.equals('supplierRmaNumber', this.filterData['supplierRmaNumber']));
        }
        if (this.filterData['case']) {
          criteria.addFilter(Criteria.equals('caseId', this.filterData['case']));
        }
        if (this.filterData['user']) {
          criteria.addFilter(Criteria.equals('userId', this.filterData['user']));
        }
      }
      criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection, this.naturalSorting));

      return criteria;
    },

    createRouteText() {
      return this.$tc('kit-rma.general.create');
    },

    createRoute() {
      return {name: 'kit.rma.ticket'};
    },

    columns() {
      return this.getTicketColumns();
    },
  },
});
