import template from './kit-status-view.html.twig'

const { Mixin, Component, Data: { Criteria } } = Shopware

Component.register('kit-status-view', {
  template,

  inject: [
    'repositoryFactory'
  ],

  mixins: [
    Mixin.getByName('notification')
  ],

  metaInfo() {
    return {
      title: this.$createTitle()
    };
  },

  data() {
    return {
      repository: null,
      status: null,
      processSuccess: false,
      isLoading: false
    };
  },

  created() {
    this.repository = this.repositoryFactory.create('rma_status');
    this.getStatus();
  },

  methods: {
    getStatus() {
      console.log({id: this.$route.params.id});
      this.isLoading = true
      this.repository
        .get(this.$route.params.id, Shopware.Context.api)
        .then((entity) => {
          this.isLoading = false
          this.status = entity;
        });
    },

    onClickSave() {
      this.isLoading = true;

      this.repository
        .save(this.status, Shopware.Context.api)
        .then(() => {
          this.getStatus();
          this.isLoading = false;
          this.processSuccess = true
        }).catch((exception) => {
        this.isLoading = false;
        this.createNotificationError({
          title: this.$root.$tc('global.default.error'),
          message: exception
        });
      });
    },
    saveFinish() {
      this.processSuccess = false;
    },
  }
});
