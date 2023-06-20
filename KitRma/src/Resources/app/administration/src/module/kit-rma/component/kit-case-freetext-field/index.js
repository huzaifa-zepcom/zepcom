import template from './kit-case-freetext-field.html.twig';
import './kit-case-freetext-field.scss'

const { Component } = Shopware

Component.register('kit-case-freetext-field', {
  template,

  props: {
    field: {
      type: Object,
      required: true
    },

    index: {
      type: Number,
      required: true
    }
  },

  data() {
    return {
      fieldTypes: [],
      allowedFieldTypes: [
        'selectbox'
      ]
    }

  },

  computed: {
    showValues() {
      return this.allowedFieldTypes.includes(this.field.type)
    }
  },

  created() {
    this.fieldTypes = [
      {label: 'serial'},
      {label: 'text'},
      {label: 'number'},
      {label: 'textarea'},
      {label: 'selectbox'},
      {label: 'checkbox'},
      {label: 'file'},
      {label: 'date'}
    ]
  },

  methods: {
    changeField() {
      this.$emit('field-changed', { field: this.field, index: this.index });
    },

    removeField() {
      this.$emit('field-removed', { field: field, index: this.index });
    },
  }
});
