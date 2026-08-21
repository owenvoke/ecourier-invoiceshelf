import http from '@/scripts/http'
import { useNotificationStore } from '@/scripts/stores/notification'

const { defineStore } = window.pinia

/**
 * All eCourier API calls live here, mirroring how the host's own stores wrap
 * axios. `useNotificationStore(true)` resolves the host's notification store
 * from the shared pinia instance on `window`.
 *
 * The id is the first argument: pinia 3 removed the `defineStore({ id })`
 * single-object form that older InvoiceShelf modules used.
 */
export const useEcourierStore = defineStore('ecourier', {
  state: () => ({
    settings: {},
    options: {
      channels: [],
      identifier_schemes: [],
      tax_categories: [],
      payment_means_codes: [],
      account_schemes: [],
    },
    recipients: [],
    submissions: [],
    isFetching: false,
    isSaving: false,
  }),

  actions: {
    fetchSettings() {
      this.isFetching = true

      return http
        .get('/api/m/ecourier/settings')
        .then((response) => {
          this.settings = response.data.settings ?? {}
          this.options = { ...this.options, ...(response.data.options ?? {}) }

          return response
        })
        .finally(() => {
          this.isFetching = false
        })
    },

    /**
     * Fetches the settings once per page load. The invoice dropdown entry needs
     * to know whether the module is enabled, and reading the settings needs
     * `manage company`, so a rejection is left to the caller to treat as
     * unknown rather than as disabled.
     */
    ensureSettings() {
      if (Object.keys(this.settings).length > 0 || this.isFetching) {
        return Promise.resolve()
      }

      return this.fetchSettings().catch(() => {})
    },

    updateSettings(data) {
      const notificationStore = useNotificationStore(true)
      this.isSaving = true

      return http
        .put('/api/m/ecourier/settings', data)
        .then((response) => {
          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('ecourier.settings_saved'),
          })

          return response
        })
        .catch((error) => {
          this.notifyError(notificationStore, error)

          throw error
        })
        .finally(() => {
          this.isSaving = false
        })
    },

    fetchRecipients() {
      return http.get('/api/m/ecourier/recipients').then((response) => {
        this.recipients = response.data.data ?? []

        return response
      })
    },

    saveRecipient(data) {
      const notificationStore = useNotificationStore(true)
      this.isSaving = true

      return http
        .post('/api/m/ecourier/recipients', data)
        .then((response) => {
          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('ecourier.recipient_saved'),
          })

          return response
        })
        .catch((error) => {
          this.notifyError(notificationStore, error)

          throw error
        })
        .finally(() => {
          this.isSaving = false
        })
    },

    deleteRecipient(customerId) {
      const notificationStore = useNotificationStore(true)

      return http
        .delete(`/api/m/ecourier/recipients/${customerId}`)
        .then((response) => {
          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('ecourier.recipient_removed'),
          })

          return response
        })
        .catch((error) => {
          this.notifyError(notificationStore, error)

          throw error
        })
    },

    fetchSubmissions() {
      return http.get('/api/m/ecourier/submissions').then((response) => {
        this.submissions = response.data.data ?? []

        return response
      })
    },

    sendInvoice(invoiceId, force = false) {
      const notificationStore = useNotificationStore(true)

      return http
        .post(`/api/m/ecourier/invoices/${invoiceId}/send`, { force })
        .then((response) => {
          notificationStore.showNotification({
            type: 'success',
            message: response.data.message,
          })

          return response
        })
        .catch((error) => {
          // `queued: false` is the endpoint saying the invoice has already been
          // sent, which callers turn into an offer to send it again. Reporting
          // it as an error would put that plumbing in front of the user.
          if (error?.response?.data?.queued !== false) {
            this.notifyError(notificationStore, error)
          }

          throw error
        })
    },

    notifyError(notificationStore, error) {
      notificationStore.showNotification({
        type: 'error',
        message: error?.response?.data?.message ?? 'The eCourier request failed.',
      })
    },
  },
})
