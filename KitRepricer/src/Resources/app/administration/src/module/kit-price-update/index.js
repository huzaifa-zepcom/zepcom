import './page/kit-price-rule-page'
import './page/kit-price-exception-page'
import './page/kit-price-logs'

import deDE from './snippet/de-DE.json'
import enGB from './snippet/en-GB.json'

Shopware.Module.register('kit-price-update', {
  type: 'plugin',
  name: 'KitRepricer',
  title: 'kit-price-update.general.mainMenuItemGeneral',
  description: 'kit-price-update.general.description',
  icon: 'default-symbol-euro',

  snippets: {
    'de-DE': deDE,
    'en-GB': enGB
  },

  settingsItem: [
    {
      name: 'kit-price-update',
      to: 'kit.price.update.index',
      label: 'kit-price-update.general.mainMenuItemGeneral',
      group: 'plugins',
      icon: 'default-symbol-euro'
    }
  ],

  routes: {
    index: {
      component: 'kit-price-rule-page',
      path: 'index',
      meta: {
        parentPath: 'sw.settings.index'
      }
    },
    create: {
      component: 'kit-price-exception-page',
      path: 'create',
      meta: {
        parentPath: 'kit.price.update.index'
      }
    },

    detail: {
      component: 'kit-price-exception-page',
      path: 'detail/:id?',
      props: {
        default: (route) => ({ ruleId: route.params.id })
      },
      meta: {
        parentPath: 'kit.price.update.index'
      }
    },

    logs: {
      component: 'kit-price-logs',
      path: 'logs',
      meta: {
        parentPath: 'kit.price.update.index'
      }
    },
  }
})
