<template>
  <div>
    <p class="mb-4 text-sm text-gray-500">
      {{ $t('ecourier.submissions_description') }}
    </p>

    <BaseEmptyPlaceholder
      v-if="store.submissions.length === 0"
      :title="$t('ecourier.no_submissions')"
      :description="$t('ecourier.submissions_description')"
    />

    <div v-else :class="table.wrap">
      <table :class="table.table">
        <thead>
          <tr>
            <th :class="table.th">{{ $t('ecourier.invoice_number') }}</th>
            <th :class="table.th">{{ $t('ecourier.status') }}</th>
            <th :class="table.th">{{ $t('ecourier.document_id') }}</th>
            <th :class="table.th">{{ $t('ecourier.sent_at') }}</th>
            <th :class="table.th">{{ $t('ecourier.error') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="submission in store.submissions" :key="submission.id">
            <td :class="[table.td, table.compact]">{{ submission.invoice_number }}</td>
            <td :class="[table.td, table.compact]">
              <span :class="[statusClasses, STATUS_COLOURS[submission.status] ?? STATUS_COLOURS.pending]">
                {{ submission.status }}
              </span>
            </td>
            <td :class="[table.td, table.compact]">
              <code class="text-xs">{{ submission.document_id || '—' }}</code>
            </td>
            <td :class="[table.td, table.compact]">{{ formatDate(submission.created_at) }}</td>
            <td
              :class="[
                table.td,
                'min-w-[18rem] whitespace-pre-wrap wrap-anywhere',
                submission.error ? 'text-red-700' : '',
              ]"
              :title="submission.error || ''"
            >
              {{ submission.error || '—' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useEcourierStore } from '../stores/ecourier'
import { table } from '../support/table'

const statusClasses =
  'inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full'

// A submission is pending until eCourier answers, so an unknown status reads as
// pending rather than as a failure.
const STATUS_COLOURS = {
  sent: 'bg-emerald-50 text-emerald-700',
  failed: 'bg-red-50 text-red-700',
  pending: 'bg-gray-100 text-gray-700',
}

const store = useEcourierStore()

/** Timestamps arrive as ISO strings; the seconds and zone only cost width. */
function formatDate(value) {
  if (!value) {
    return '—'
  }

  return String(value).replace('T', ' ').slice(0, 16)
}

onMounted(() => {
  store.fetchSubmissions()
})
</script>
