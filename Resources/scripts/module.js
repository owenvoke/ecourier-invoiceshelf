import EcourierSetting from './views/EcourierSetting.vue'
import moduleLocales from '~/locales/locales'
import { installInvoiceSendAction } from './support/invoice-send-action'
import '../css/module.css'

window.InvoiceShelf.booting((app, router) => {
  window.InvoiceShelf.addMessages(moduleLocales)

  // Mounted under the host's settings route, matching the sidebar entry the
  // service provider registers. `isOwner` mirrors the menu's owner_only flag.
  router.addRoute('settings', {
    path: 'ecourier',
    name: 'ecourier.settings',
    meta: { isOwner: true },
    component: EcourierSetting,
  })

  installInvoiceSendAction(router)
})
