<script setup lang="ts">
import { Head as InertiaHead } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/UserSettingsLayout.vue';
import SelectColorModeButton from '@/components/SelectColorModeButton.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const breadcrumbs = [
  { label: t('Dashboard'), route: route('dashboard') },
  { label: t('Appearance settings') },
];
</script>

<template>
  <InertiaHead :title="t('Appearance settings')" />

  <AppLayout :breadcrumbs>
    <SettingsLayout>
      <Card pt:body:class="max-w-2xl space-y-3" pt:caption:class="space-y-1">
        <template #title>{{ t('Appearance settings') }}</template>
        <template #subtitle>{{ t("Update your account's appearance settings") }}</template>
        <template #content>
          <SelectColorModeButton id="color-mode-selector" />
        </template>
      </Card>
    </SettingsLayout>
  </AppLayout>
</template>
