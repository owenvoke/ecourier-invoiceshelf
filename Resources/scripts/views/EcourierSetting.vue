<template>
  <BaseSettingCard
    :title="$t('ecourier.settings_title')"
    :description="$t('ecourier.settings_description')"
  >
    <BaseTabGroup class="pb-0 sm:pb-6">
      <BaseTab tab-panel-container="pt-4 mt-px" :title="$t('ecourier.tab_connection')">
        <ConnectionSettings />
      </BaseTab>

      <BaseTab tab-panel-container="pt-4 mt-px" :title="$t('ecourier.tab_recipients')">
        <RecipientsTab />
      </BaseTab>

      <BaseTab tab-panel-container="pt-4 mt-px" :title="$t('ecourier.tab_submissions')">
        <SubmissionsTab />
      </BaseTab>
    </BaseTabGroup>
  </BaseSettingCard>
</template>

<script setup>
import ConnectionSettings from '../components/ConnectionSettings.vue'
import RecipientsTab from '../components/RecipientsTab.vue'
import SubmissionsTab from '../components/SubmissionsTab.vue'
</script>
