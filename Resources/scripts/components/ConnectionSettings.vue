<template>
  <form autocomplete="off" @submit.prevent="save">
    <BaseSwitchSection
      v-model="form.ecourier_enabled"
      :title="$t('ecourier.enabled')"
      description=""
    />

    <BaseSwitchSection
      v-model="form.ecourier_auto_send"
      class="mt-4"
      :title="$t('ecourier.auto_send')"
      description=""
    />

    <BaseInputGrid class="mt-8">
      <BaseInputGroup :label="$t('ecourier.api_key')" :help-text="$t('ecourier.api_key_help')">
        <BaseInput v-model="form.ecourier_api_key" type="password" v-bind="noAutofill" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.channel')">
        <BaseSelectInput
          v-model="form.ecourier_channel"
          :options="store.options.channels"
          value-prop="value"
          track-by="label"
        />
      </BaseInputGroup>
    </BaseInputGrid>

    <h6 class="mt-8 text-gray-900 text-base font-medium">
      {{ $t('ecourier.sender_section') }}
    </h6>
    <p class="mt-1 mb-2 text-sm text-gray-500">
      {{ $t('ecourier.sender_address_note') }}
    </p>

    <BaseInputGrid>
      <BaseInputGroup
        :label="$t('ecourier.sender_scheme')"
        :help-text="$t('ecourier.scheme_help')"
      >
        <BaseMultiselect
          v-model="form.ecourier_sender_scheme"
          :options="store.options.identifier_schemes"
          label="label"
          value-prop="value"
          track-by="label"
          :can-clear="false"
          searchable
          placeholder="DK:CVR"
          v-bind="noAutofill"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.sender_id')">
        <BaseInput v-model="form.ecourier_sender_id" v-bind="noAutofill" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.sender_vat_id')">
        <BaseInput v-model="form.ecourier_sender_vat_id" v-bind="noAutofill" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.sender_registration_number')">
        <BaseInput v-model="form.ecourier_sender_registration_number" v-bind="noAutofill" />
      </BaseInputGroup>
    </BaseInputGrid>

    <h6 class="mt-8 text-gray-900 text-base font-medium">
      {{ $t('ecourier.document_section') }}
    </h6>

    <BaseInputGrid class="mt-2">
      <BaseInputGroup :label="$t('ecourier.tax_category')">
        <BaseSelectInput
          v-model="form.ecourier_tax_category"
          :options="store.options.tax_categories"
          value-prop="value"
          track-by="label"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('ecourier.tax_percent')"
        :help-text="$t('ecourier.tax_percent_help')"
      >
        <BaseInput v-model="form.ecourier_tax_percent" type="number" step="0.01" v-bind="noAutofill" />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('ecourier.unit_code')"
        :help-text="$t('ecourier.unit_code_help')"
      >
        <BaseInput v-model="form.ecourier_unit_code" placeholder="C62" v-bind="noAutofill" />
      </BaseInputGroup>
    </BaseInputGrid>

    <h6 class="mt-8 text-gray-900 text-base font-medium">
      {{ $t('ecourier.payment_section') }}
    </h6>

    <BaseInputGrid class="mt-2">
      <BaseInputGroup :label="$t('ecourier.payment_means_code')">
        <BaseSelectInput
          v-model="form.ecourier_payment_means_code"
          :options="store.options.payment_means_codes"
          value-prop="value"
          track-by="label"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.account_scheme')">
        <BaseSelectInput
          v-model="form.ecourier_account_scheme"
          :options="store.options.account_schemes"
          value-prop="value"
          track-by="label"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.account_id')">
        <BaseInput v-model="form.ecourier_account_id" v-bind="noAutofill" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.bank_id')">
        <BaseInput v-model="form.ecourier_bank_id" v-bind="noAutofill" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('ecourier.bank_name')">
        <BaseInput v-model="form.ecourier_bank_name" v-bind="noAutofill" />
      </BaseInputGroup>
    </BaseInputGrid>

    <BaseInputGroup :label="$t('ecourier.payment_terms_note')" class="mt-6">
      <BaseTextarea v-model="form.ecourier_payment_terms_note" rows="3" v-bind="noAutofill" />
    </BaseInputGroup>

    <BaseButton
      :loading="store.isSaving"
      :disabled="store.isSaving"
      class="mt-6"
      type="submit"
    >
      <template #left="slotProps">
        <BaseIcon :class="slotProps.class" name="SaveIcon" />
      </template>
      {{ $t('general.save') }}
    </BaseButton>
  </form>
</template>

<script setup>
import { onMounted, reactive } from 'vue'
import { useEcourierStore } from '../stores/ecourier'
import { noAutofill } from '../support/form-attrs'
import { asBoolean } from '../support/settings'

const store = useEcourierStore()

const form = reactive({
  ecourier_enabled: true,
  ecourier_auto_send: false,
  ecourier_api_key: '',
  ecourier_channel: 'Peppol',
  ecourier_sender_scheme: '',
  ecourier_sender_id: '',
  ecourier_sender_vat_id: '',
  ecourier_sender_registration_number: '',
  ecourier_tax_category: 'S',
  ecourier_tax_percent: '',
  ecourier_unit_code: '',
  ecourier_payment_means_code: '',
  ecourier_account_scheme: 'IBAN',
  ecourier_account_id: '',
  ecourier_bank_id: '',
  ecourier_bank_name: '',
  ecourier_payment_terms_note: '',
})

const BOOLEAN_KEYS = ['ecourier_enabled', 'ecourier_auto_send']

onMounted(async () => {
  await store.fetchSettings()

  Object.keys(form).forEach((key) => {
    const value = store.settings[key]

    if (value === undefined || value === null) {
      return
    }

    form[key] = BOOLEAN_KEYS.includes(key) ? asBoolean(value) : value
  })
})

function save() {
  store.updateSettings({ ...form })
}
</script>
