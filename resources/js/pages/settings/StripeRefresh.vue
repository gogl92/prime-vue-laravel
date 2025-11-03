<script setup lang="ts">
import { onMounted } from 'vue';
import { Head as InertiaHead, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Message from 'primevue/message';

const { t } = useI18n();

const breadcrumbs = [
  { label: t('Settings'), url: '/settings' },
  { label: t('Stripe Connect'), url: '/settings/stripe-connect' },
  { label: t('Refresh Onboarding') },
];

const goToStripeConnect = () => {
  router.visit(route('stripe.connect'));
};

onMounted(() => {
  // Auto-redirect after 3 seconds
  setTimeout(() => {
    goToStripeConnect();
  }, 3000);
});
</script>

<template>
  <InertiaHead title="Stripe Onboarding Refresh" />

  <AppLayout :breadcrumbs>
    <Card>
      <template #title>
        <div class="flex items-center gap-3">
          <i class="pi pi-refresh text-blue-500 text-4xl" />
          <span>{{ t('Continue Stripe Connect Onboarding') }}</span>
        </div>
      </template>
      <template #content>
        <div class="space-y-6">
          <Message severity="info" :closable="false">
            {{
              t(
                'You need to complete additional steps to finish your Stripe Connect onboarding.'
              )
            }}
          </Message>

          <div class="space-y-4">
            <h3 class="font-semibold text-lg">{{ t('Why am I seeing this?') }}</h3>
            <p class="text-gray-700 dark:text-gray-300">
              {{
                t(
                  'Stripe requires some additional information or verification to complete your account setup. This is a normal part of the onboarding process to ensure compliance and security.'
                )
              }}
            </p>

            <h3 class="font-semibold text-lg mt-6">{{ t('What should I do?') }}</h3>
            <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
              <li>{{ t('Return to the Stripe Connect settings page') }}</li>
              <li>{{ t('Click "Start Onboarding" to continue where you left off') }}</li>
              <li>{{ t('Complete any remaining verification steps') }}</li>
            </ul>
          </div>

          <div class="pt-4">
            <Button
              :label="t('Go to Stripe Connect Settings')"
              icon="pi pi-arrow-right"
              icon-pos="right"
              @click="goToStripeConnect"
            />
          </div>

          <div class="text-sm text-gray-500">
            {{ t('You will be automatically redirected in 3 seconds...') }}
          </div>
        </div>
      </template>
    </Card>
  </AppLayout>
</template>
