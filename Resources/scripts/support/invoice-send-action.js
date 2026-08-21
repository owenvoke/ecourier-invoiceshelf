import abilities from '@/scripts/admin/stub/abilities'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useEcourierStore } from '../stores/ecourier'
import { asBoolean } from './settings'

/**
 * Adds a "Send via eCourier" entry to the actions dropdown on an invoice.
 *
 * InvoiceShelf 2.x has no extension slots: `window.InvoiceShelf.booting()`
 * hands a module the Vue app and the router, and every host view is a fixed
 * template. The invoice page's dropdown is built from hardcoded
 * `BaseDropdownItem`s, so the only way into it is the DOM. Headless UI mounts
 * the menu's items when it opens and unmounts them again when it closes, which
 * is why this observes rather than injects once: it waits for a menu to appear
 * in the page header while an invoice is open, then appends an entry styled
 * like the host's own items.
 *
 * This is the only part of the module that depends on the host's markup. If a
 * later release restructures the invoice header the entry stops appearing and
 * nothing else changes — the settings screen and the send endpoint are
 * independent of it.
 */

const INVOICE_VIEW = 'invoices.view'
const MARKER = 'data-ecourier-send'

// heroicons v2 `paper-airplane`, outline: the icon the host puts on its own
// send actions, at the size `BaseDropdownItem` gives its icons.
const ICON =
  '<svg class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500" fill="none" ' +
  'viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">' +
  '<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 ' +
  '59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>'

export function installInvoiceSendAction(router) {
  let observer = null

  router.afterEach((to) => {
    if (to.name !== INVOICE_VIEW) {
      observer?.disconnect()
      observer = null

      return
    }

    // Warm the settings cache while the page loads so `shouldOffer()` can hide
    // the entry on companies that have the module switched off.
    useEcourierStore().ensureSettings()

    if (observer) {
      return
    }

    observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType !== Node.ELEMENT_NODE) {
            return
          }

          const menu = node.matches('[role="menu"]')
            ? node
            : node.querySelector('[role="menu"]')

          if (menu && isPageHeaderMenu(menu)) {
            inject(menu, router)
          }
        })
      })
    })

    observer.observe(document.body, { childList: true, subtree: true })
  })
}

/**
 * `BasePageHeader` renders the title and the actions as the two children of one
 * flex row, so the heading locates the row and the row's last child is the
 * actions area. Anchoring on that keeps the sidebar's own dropdowns out.
 */
function isPageHeaderMenu(menu) {
  const heading = document.querySelector('h3.text-2xl.font-bold')
  const row = heading?.parentElement?.parentElement
  const actions = row?.lastElementChild

  return !!actions && actions !== row.firstElementChild && actions.contains(menu)
}

function inject(menu, router) {
  if (!shouldOffer() || menu.querySelector(`[${MARKER}]`)) {
    return
  }

  // `BaseDropdown` wraps the items it is given in a single padded div.
  const items = menu.firstElementChild ?? menu
  const entry = document.createElement('a')

  entry.href = '#'
  entry.setAttribute(MARKER, 'true')
  entry.setAttribute('role', 'menuitem')
  // The same classes `BaseDropdownItem` renders. Hover stands in for the
  // Headless UI `active` state this entry cannot have, being a plain element.
  entry.className =
    'group flex items-center px-4 py-2 text-sm font-normal text-gray-700 whitespace-normal cursor-pointer hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100 focus:text-gray-900'
  entry.innerHTML = ICON
  entry.append(window.i18n.global.t('ecourier.send_action'))
  entry.addEventListener('click', (event) => send(event, router))

  // The host's dropdown ends with Delete, so the entry goes above it rather
  // than below the destructive action. Appending is the fallback if the menu
  // ever comes up empty.
  items.insertBefore(entry, items.lastElementChild)
}

function shouldOffer() {
  if (!useUserStore(true).hasAbilities(abilities.SEND_INVOICE)) {
    return false
  }

  // Reading the settings needs `manage company`, so an admin who may send
  // invoices but cannot see the settings gets the entry and learns from the
  // response instead.
  return asBoolean(useEcourierStore().settings.ecourier_enabled, true)
}

async function send(event, router) {
  event.preventDefault()
  dismiss(event.currentTarget.closest('[role="menu"]'))

  const store = useEcourierStore()
  const invoiceId = router.currentRoute.value.params.id

  try {
    await store.sendInvoice(invoiceId)
  } catch (error) {
    // A refusal carrying `queued: false` means the invoice has already gone to
    // eCourier. That is an answer rather than a failure, so the store leaves it
    // unreported and it becomes the offer to send again.
    if (error?.response?.data?.queued !== false) {
      return
    }

    const { global } = window.i18n

    const confirmed = await useDialogStore(true).openDialog({
      title: global.t('ecourier.resend_title'),
      message: global.t('ecourier.resend_message'),
      yesLabel: global.t('general.ok'),
      noLabel: global.t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })

    if (confirmed) {
      await store.sendInvoice(invoiceId, true).catch(() => {})
    }
  }
}

/** The entry is not a Headless UI `MenuItem`, so closing is on us. */
function dismiss(menu) {
  menu?.dispatchEvent(
    new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
  )
}
