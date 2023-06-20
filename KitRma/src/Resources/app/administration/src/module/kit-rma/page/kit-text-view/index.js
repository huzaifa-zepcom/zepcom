import template from './kit-text-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.register('kit-text-view', {
  template,

  inject: [
    'repositoryFactory'
  ],

  mixins: [
    Mixin.getByName('notification')
  ],

  metaInfo () {
    return {
      title: this.$createTitle()
    }
  },

  data () {
    return {
      repository: null,
      text: null,
      processSuccess: false,
      isLoading: false,
      placeholders: [
        { label: 'Customer name', value: '{customer_name}' },
        { label: 'Product name', value: '{product_name}' },
        { label: 'Order number', value: '{ordernumber}' },
        { label: 'RMA Number', value: '{rma_id}' },
        { label: 'Supplier RMA-ID', value: '{supplier_rma_number}' },
        { label: 'Direct Link', value: '{deeplink}' },
        { label: 'Status', value: '{status_name}' },
        { label: 'Article number', value: '{articleordernumber}' },
        { label: 'Return Address', value: '{supplier_address}' },
        { label: 'Manufacturer Name', value: '{manufacturer_name}' },
        { label: 'Warranty Support', value: '{warranty_support}' },
        { label: 'Warranty Info', value: '{warranty_info}' },
        { label: 'Support Hotline', value: '{warranty_hotline}' },
      ],
      placeholderText: ''
    }
  },

  created () {
    this.repository = this.repositoryFactory.create('rma_text')
    this.getSnippet()
  },

  methods: {
    getSnippet () {
      this.isLoading = true
      this.repository
        .get(this.$route.params.id, Shopware.Context.api)
        .then((entity) => {
          this.isLoading = false
          this.text = entity
        })
    },

    onClickSave () {
      this.isLoading = true

      this.repository
        .save(this.text, Shopware.Context.api)
        .then(() => {
          this.getSnippet()
          this.isLoading = false
          this.processSuccess = true
        }).catch((exception) => {
        this.isLoading = false
        this.createNotificationError({
          title: this.$root.$tc('global.default.error'),
          message: exception
        })
      })
    },

    insertPlaceholder (value) {
      if(value === null) {
        return;
      }

      value += ' ';
      const textArea = document.getElementById('sw-field--text-description');
      let startPos = textArea.selectionStart,
        // get cursor's position:
        endPos = textArea.selectionEnd,
        cursorPos = startPos,
        tmpStr = textArea.value;

      // insert:
      this.text.description = tmpStr.substring(0, startPos) + value + tmpStr.substring(endPos, tmpStr.length);

      // move cursor:
      setTimeout(() => {
        cursorPos += value.length;
        textArea.selectionStart = textArea.selectionEnd = cursorPos;
      }, 10);
    },

    saveFinish () {
      this.processSuccess = false
    },
  }
})
