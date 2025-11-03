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
  { label: t('Onboarding Complete') },
];

const goToStripeConnect = () => {
  router.visit(route('stripe.connect'));
};

onMounted(() => {
  // Auto-redirect after 5 seconds
  setTimeout(() => {
    goToStripeConnect();
  }, 5000);
});
</script>

<template>
  <InertiaHead title="Stripe Onboarding Complete" />

  <AppLayout :breadcrumbs>
    <Card>
      <template #title>
        <div class="flex items-center gap-3">
          <i class="pi pi-check-circle text-green-500 text-4xl" />
          <span>{{ t('Stripe Connect Onboarding Complete') }}</span>
        </div>
      </template>
      <template #content>
        <div class="space-y-6">
          <Message severity="success" :closable="false">
            {{
              t(
                'Congratulations! You have successfully completed the Stripe Connect onboarding process.'
              )
            }}
          </Message>

          <div class="space-y-4">
            <h3 class="font-semibold text-lg">{{ t('What happens next?') }}</h3>
            <ul class="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
              <li>{{ t('Your branch can now accept payments through Stripe') }}</li>
              <li>{{ t('Payouts will be processed weekly on Fridays') }}</li>
              <li>{{ t('You can access your Stripe dashboard from the Stripe Connect page') }}</li>
              <li>{{ t('Monitor your transactions and payouts in real-time') }}</li>
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
            {{ t('You will be automatically redirected in 5 seconds...') }}
          </div>
        </div>
      </template>
    </Card>
  </AppLayout>
</template>
