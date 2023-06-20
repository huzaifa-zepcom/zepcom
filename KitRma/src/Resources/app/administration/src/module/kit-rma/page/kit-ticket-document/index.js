import template from './kit-ticket-document.html.twig';

Shopware.Component.register('kit-ticket-document', {
  template,

  props: {
    files: {
      required: true,
    },
  },

  data() {
    return {
      isLoading: false,
      sortBy: 'createdAt',
      sortDirectiion: 'DESC',
      total: 0,
    };
  },

  metaInfo() {
    return {
      title: this.$createTitle(),
    };
  },

  computed: {
    columns() {
      return this.getTicketColumns();
    },

    locale() {
      return this.$root.$i18n.locale;
    },
  },

  methods: {
    getTicketColumns() {
      return [
        {
          property: 'name',
          dataIndex: 'name',
          label: 'File Name',
        },
        {
          property: 'url',
          dataIndex: 'url',
          label: 'URL',
        },
        {
          property: 'media.fileExtension',
          dataIndex: 'fileExtension',
          label: 'Type',
        },
        {
          property: 'uploaded',
          dataIndex: 'uploaded',
          label: 'Uploaded At',
        },
      ];
    },
  },
});
