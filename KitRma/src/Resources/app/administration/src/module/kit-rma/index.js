import './component/kit-case-freetext-field'

import './page/kit-ticket-main'
import './page/kit-base-list'

import './page/kit-ticket-view'
import './page/kit-ticket-new'
import './page/kit-ticket-list'
import './page/kit-ticket-document'

import './page/kit-status-view'
import './page/kit-status-new'
import './page/kit-status-list'

import './page/kit-case-view'
import './page/kit-case-new'
import './page/kit-case-list'

import './page/kit-text-view'
import './page/kit-text-new'
import './page/kit-text-list'

import './page/kit-address-view'
import './page/kit-address-new'
import './page/kit-address-list'

import deDE from './snippet/de-DE.json'
import enGB from './snippet/en-GB.json'

Shopware.Module.register('kit-rma', {
  type: 'plugin',
  name: 'KitRMa',
  title: 'kit-rma.general.moduleTitle',
  description: 'kit-rma.general.description',
  icon: 'default-web-bug',

  snippets: {
    'de-DE': deDE,
    'en-GB': enGB
  },

  settingsItem: [
    {
      name: 'kit-rma',
      to: 'kit.rma.index',
      label: 'kit-rma.general.moduleTitle',
      group: 'plugins',
      icon: 'default-web-bug'
    }
  ],

  routes: {

    index: {
      component: 'kit-ticket-main',
      path: 'index',
      meta: {
        parentPath: 'sw.settings.index'
      }
    },

    ticket: {
      component: 'kit-ticket-new',
      path: 'ticket',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    ticket_view: {
      component: 'kit-ticket-view',
      path: 'ticket/view/:id',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    ticket_list: {
      component: 'kit-ticket-list',
      path: 'tickets',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    status: {
      component: 'kit-status-new',
      path: 'status',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    status_view: {
      component: 'kit-status-view',
      path: 'status/view/:id',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    status_list: {
      component: 'kit-status-list',
      path: 'statuses',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    case: {
      component: 'kit-case-new',
      path: 'case',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    case_view: {
      component: 'kit-case-view',
      path: 'case/view/:id',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    case_list: {
      component: 'kit-case-list',
      path: 'cases',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    text: {
      component: 'kit-text-new',
      path: 'text',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    text_view: {
      component: 'kit-text-view',
      path: 'text/view/:id',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    text_list: {
      component: 'kit-text-list',
      path: 'texts',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    address: {
      component: 'kit-address-new',
      path: 'address',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    address_view: {
      component: 'kit-address-view',
      path: 'address/view/:id',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

    address_list: {
      component: 'kit-address-list',
      path: 'addresses',
      meta: {
        parentPath: 'kit.rma.index'
      }
    },

  }
})
