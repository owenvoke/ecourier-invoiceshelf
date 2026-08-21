<template>
  <div>
    <p class="mb-4 text-sm text-gray-500">
      {{ $t('ecourier.recipients_description') }}
    </p>

    <BaseEmptyPlaceholder
      v-if="store.recipients.length === 0"
      :title="$t('ecourier.no_recipients')"
      :description="$t('ecourier.recipients_description')"
    />

    <div v-else :class="table.wrap">
      <table :class="table.table">
        <thead>
          <tr>
            <th :class="table.th">{{ $t('ecourier.customer') }}</th>
            <th :class="table.th">{{ $t('ecourier.scheme') }}</th>
            <th :class="table.th">{{ $t('ecourier.identifier') }}</th>
            <th :class="table.th">{{ $t('ecourier.country') }}</th>
            <th :class="table.th" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="recipient in store.recipients" :key="recipient.id">
            <td :class="table.td">{{ recipient.customer_name || recipient.customer_id }}</td>
            <td :class="table.td">{{ recipient.scheme }}</td>
            <td :class="table.td"><code class="text-xs">{{ recipient.identifier }}</code></td>
            <td :class="table.td">{{ recipient.country || '—' }}</td>
            <td :class="[table.td, 'text-right', table.compact]">
              <button type="button" class="text-xs font-medium text-primary-500" @click="edit(recipient)">
                {{ $t('general.edit') }}
              </button>
              <button
                type="button"
                class="ml-3 text-xs font-medium text-red-500"
                @click="remove(recipient)"
              >
                {{ $t('general.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <form class="mt-8" autocomplete="off" @submit.prevent="save">
      <h6 class="text-gray-900 text-base font-medium">
        {{ form.id ? $t('ecourier.update_recipient') : $t('ecourier.add_recipient') }}
      </h6>

      <BaseInputGrid class="mt-4">
        <BaseInputGroup :label="$t('ecourier.customer')" required>
          <BaseCustomerSelectInput v-model="form.customer_id" fetch-all v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('ecourier.scheme')"
          :help-text="$t('ecourier.scheme_help')"
          required
        >
          <BaseMultiselect
            v-model="form.scheme"
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

        <BaseInputGroup :label="$t('ecourier.identifier')" required>
          <BaseInput v-model="form.identifier" v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('ecourier.name')">
          <BaseInput v-model="form.name" v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('ecourier.vat_id')">
          <BaseInput v-model="form.vat_id" v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('ecourier.registration_number')">
          <BaseInput v-model="form.registration_number" v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('ecourier.street')">
          <BaseInput v-model="form.street" v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('ecourier.city')">
          <BaseInput v-model="form.city" v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('ecourier.postal_code')">
          <BaseInput v-model="form.postal_code" v-bind="noAutofill" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('ecourier.country')">
          <BaseInput v-model="form.country" placeholder="DK" v-bind="noAutofill" />
        </BaseInputGroup>
      </BaseInputGrid>

      <div class="mt-6 flex gap-3">
        <BaseButton type="submit" :loading="store.isSaving" :disabled="!canSave">
          {{ $t('general.save') }}
        </BaseButton>
        <BaseButton v-if="form.id" variant="primary-outline" @click="reset">
          {{ $t('general.cancel') }}
        </BaseButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import { useEcourierStore } from '../stores/ecourier'
import { noAutofill } from '../support/form-attrs'
import { table } from '../support/table'

const store = useEcourierStore()

const form = reactive(emptyForm())

const canSave = computed(
  () => !!form.customer_id && !!form.scheme && String(form.identifier || '').trim().length > 0
)

function emptyForm() {
  return {
    id: null,
    customer_id: null,
    scheme: '',
    identifier: '',
    name: '',
    vat_id: '',
    registration_number: '',
    street: '',
    city: '',
    postal_code: '',
    country: '',
  }
}

function reset() {
  Object.assign(form, emptyForm())
}

function edit(recipient) {
  Object.assign(form, { ...emptyForm(), ...recipient })
}

async function save() {
  if (!canSave.value) {
    return
  }

  await store.saveRecipient({ ...form })
  reset()
  await store.fetchRecipients()
}

async function remove(recipient) {
  await store.deleteRecipient(recipient.customer_id)
  await store.fetchRecipients()
}

onMounted(() => {
  store.fetchRecipients()

  if (store.options.identifier_schemes.length === 0) {
    store.fetchSettings()
  }
})
</script>
