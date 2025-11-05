<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head as InertiaHead } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import { Branch } from '@/models/Branch';
import { Orion } from '@tailflow/laravel-orion/lib/orion';

const { t } = useI18n();
const toast = useToast();

const breadcrumbs = [
  { label: t('Settings'), url: '/settings' },
  { label: t('Stripe Connect') },
];

// State
const branches = ref<Branch[]>([]);
const loading = ref(false);
const selectedBranch = ref<Branch | null>(null);
const showOnboardingDialog = ref(false);
const onboardingStatus = ref<{
  hasStripeAccount: boolean;
  onboardingCompleted: boolean;
  stripeAccountId: string | null;
  canAcceptPayments: boolean;
  businessName: string | null;
  taxId: string | null;
} | null>(null);
const statusLoading = ref(false);

// Load branches
const loadBranches = async () => {
  try {
    loading.value = true;
    const response = await Branch.$query().get();
    branches.value = response;
  } catch (error) {
    console.error('Error loading branches:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load branches'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// Load onboarding status
const loadOnboardingStatus = async (branchId: number) => {
  try {
    statusLoading.value = true;
    const httpClient = Orion.makeHttpClient();
    const response = await httpClient.get(`/api/stripe/branches/${branchId}/status`);

    onboardingStatus.value = response.data as {
      hasStripeAccount: boolean;
      onboardingCompleted: boolean;
      stripeAccountId: string | null;
      canAcceptPayments: boolean;
      businessName: string | null;
      taxId: string | null;
    };
  } catch (error) {
    console.error('Error loading onboarding status:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load Stripe status'),
      life: 3000,
    });
  } finally {
    statusLoading.value = false;
  }
};

// Start onboarding
const startOnboarding = async (branch: Branch) => {
  selectedBranch.value = branch;
  await loadOnboardingStatus(branch.$attributes.id!);
  showOnboardingDialog.value = true;
};

// Generate onboarding URL and redirect
const generateOnboardingUrl = async () => {
  if (!selectedBranch.value) return;

  try {
    loading.value = true;
    const branchId = selectedBranch.value.$attributes.id!;

    const returnURL = `${window.location.origin}/settings/stripe/return`;
    const refreshURL = `${window.location.origin}/settings/stripe/refresh`;

    const httpClient = Orion.makeHttpClient();
    const response = await httpClient.post(`/api/stripe/branches/${branchId}/onboarding`, {
      returnURL,
      refreshURL,
    });

    const data = response.data as { url: string };

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Redirecting to Stripe onboarding...'),
      life: 2000,
    });

    // Redirect to Stripe onboarding
    setTimeout(() => {
      window.location.href = data.url;
    }, 1000);
  } catch (error) {
    console.error('Error generating onboarding URL:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to start onboarding process'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// View dashboard
const viewDashboard = async (branch: Branch) => {
  try {
    const branchId = branch.$attributes.id!;

    const httpClient = Orion.makeHttpClient();
    const response = await httpClient.get(`/api/stripe/branches/${branchId}/dashboard`);

    const data = response.data as { url: string };
    window.open(data.url, '_blank');
  } catch (error) {
    console.error('Error getting dashboard URL:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to open Stripe dashboard'),
      life: 3000,
    });
  }
};

// Reset account
const resetAccount = async () => {
  if (!selectedBranch.value) return;

  if (
    !confirm(
      t(
        'Are you sure you want to reset this Stripe account? This will delete the existing account and create a new one.'
      )
    )
  ) {
    return;
  }

  try {
    loading.value = true;
    const branchId = selectedBranch.value.$attributes.id!;

    const returnURL = `${window.location.origin}/settings/stripe/return`;
    const refreshURL = `${window.location.origin}/settings/stripe/refresh`;

    const httpClient = Orion.makeHttpClient();
    const response = await httpClient.post(`/api/stripe/branches/${branchId}/reset`, {
      returnURL,
      refreshURL,
    });

    const data = response.data as { url: string };

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Account reset successfully. Redirecting to onboarding...'),
      life: 2000,
    });

    // Redirect to Stripe onboarding
    setTimeout(() => {
      window.location.href = data.url;
    }, 1000);
  } catch (error) {
    console.error('Error resetting account:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to reset Stripe account'),
      life: 3000,
    });
  } finally {
    loading.value = false;
    showOnboardingDialog.value = false;
  }
};

// Get status severity
const getStatusSeverity = (hasAccount: boolean, completed: boolean) => {
  if (!hasAccount) return 'secondary';
  if (completed) return 'success';
  return 'warn';
};

// Get status label
const getStatusLabel = (hasAccount: boolean, completed: boolean) => {
  if (!hasAccount) return t('Not Connected');
  if (completed) return t('Connected');
  return t('Pending');
};

onMounted(() => {
  void loadBranches();
});
</script>

<template>
  <InertiaHead title="Stripe Connect" />

  <AppLayout :breadcrumbs>
    <Card>
      <template #title>
        <div class="flex justify-between items-center">
          <span>{{ t('Stripe Connect - Branch Management') }}</span>
        </div>
      </template>
      <template #content>
        <Message severity="info" :closable="false" class="mb-4">
          {{
            t(
              'Manage Stripe Connect accounts for your branches. Connect branches to accept payments and manage payouts.'
            )
          }}
        </Message>

        <DataTable
          :value="branches"
          :loading="loading"
          striped-rows
          paginator
          :rows="10"
          :rows-per-page-options="[10, 20, 50]"
          data-key="id"
          filter-display="row"
          :global-filter-fields="['name', 'code', 'email']"
        >
          <Column field="name" :header="t('Branch Name')" sortable>
            <template #body="{ data }">
              <div class="font-semibold">{{ data.$attributes.name }}</div>
              <div class="text-sm text-gray-500">{{ data.$attributes.code }}</div>
            </template>
          </Column>

          <Column field="email" :header="t('Email')" sortable>
            <template #body="{ data }">
              {{ data.$attributes.email }}
            </template>
          </Column>

          <Column field="city" :header="t('Location')" sortable>
            <template #body="{ data }">
              <div v-if="data.$attributes.city">
                {{ data.$attributes.city }}, {{ data.$attributes.state }}
              </div>
              <div v-else class="text-gray-400">-</div>
            </template>
          </Column>

          <Column field="is_stripe_connected" :header="t('Stripe Status')">
            <template #body="{ data }">
              <Tag
                :severity="
                  getStatusSeverity(
                    !!data.$attributes.is_stripe_connected,
                    !!data.$attributes.is_stripe_connected
                  )
                "
              >
                {{
                  getStatusLabel(
                    !!data.$attributes.is_stripe_connected,
                    !!data.$attributes.is_stripe_connected
                  )
                }}
              </Tag>
            </template>
          </Column>

          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  v-if="!data.$attributes.is_stripe_connected"
                  :label="t('Connect')"
                  icon="pi pi-link"
                  size="small"
                  @click="startOnboarding(data)"
                />
                <Button
                  v-else
                  :label="t('Dashboard')"
                  icon="pi pi-external-link"
                  size="small"
                  severity="secondary"
                  outlined
                  @click="viewDashboard(data)"
                />
                <Button
                  v-if="data.$attributes.is_stripe_connected"
                  :label="t('Manage')"
                  icon="pi pi-cog"
                  size="small"
                  severity="info"
                  outlined
                  @click="startOnboarding(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Onboarding Dialog -->
    <Dialog
      v-model:visible="showOnboardingDialog"
      :header="t('Stripe Connect Configuration')"
      :modal="true"
      :style="{ width: '600px' }"
    >
      <div v-if="selectedBranch" class="space-y-4">
        <div class="border-b pb-4">
          <h3 class="font-semibold text-lg">{{ selectedBranch.$attributes.name }}</h3>
          <p class="text-sm text-gray-500">{{ selectedBranch.$attributes.email }}</p>
        </div>

        <div v-if="statusLoading" class="flex justify-center py-8">
          <i class="pi pi-spin pi-spinner text-4xl" />
        </div>

        <div v-else-if="onboardingStatus" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="p-4 border rounded-lg">
              <div class="text-sm text-gray-500">{{ t('Stripe Account') }}</div>
              <div class="font-semibold">
                {{ onboardingStatus.hasStripeAccount ? t('Created') : t('Not Created') }}
              </div>
            </div>

            <div class="p-4 border rounded-lg">
              <div class="text-sm text-gray-500">{{ t('Onboarding') }}</div>
              <div class="font-semibold">
                {{ onboardingStatus.onboardingCompleted ? t('Completed') : t('Pending') }}
              </div>
            </div>

            <div class="p-4 border rounded-lg">
              <div class="text-sm text-gray-500">{{ t('Accept Payments') }}</div>
              <div class="font-semibold">
                {{ onboardingStatus.canAcceptPayments ? t('Yes') : t('No') }}
              </div>
            </div>

            <div class="p-4 border rounded-lg">
              <div class="text-sm text-gray-500">{{ t('Account ID') }}</div>
              <div class="font-mono text-xs truncate">
                {{ onboardingStatus.stripeAccountId || '-' }}
              </div>
            </div>

            <div v-if="onboardingStatus.businessName" class="p-4 border rounded-lg col-span-2">
              <div class="text-sm text-gray-500">{{ t('Business Name') }}</div>
              <div class="font-semibold">
                {{ onboardingStatus.businessName }}
              </div>
            </div>

            <div v-if="onboardingStatus.taxId" class="p-4 border rounded-lg col-span-2">
              <div class="text-sm text-gray-500">{{ t('Tax ID (RFC)') }}</div>
              <div class="font-mono">
                {{ onboardingStatus.taxId }}
              </div>
            </div>
          </div>

          <Message
            v-if="!onboardingStatus.onboardingCompleted"
            severity="warn"
            :closable="false"
          >
            {{
              t(
                'This branch has not completed the Stripe Connect onboarding process. Click "Start Onboarding" to continue.'
              )
            }}
          </Message>

          <Message
            v-else-if="!onboardingStatus.canAcceptPayments"
            severity="warn"
            :closable="false"
          >
            {{
              t('This branch cannot accept payments yet. Additional verification may be required.')
            }}
          </Message>

          <Message v-else severity="success" :closable="false">
            {{ t('This branch is fully configured and can accept payments!') }}
          </Message>

          <div class="flex gap-2 pt-4">
            <Button
              v-if="!onboardingStatus.onboardingCompleted"
              :label="t('Start Onboarding')"
              icon="pi pi-arrow-right"
              :loading="loading"
              @click="generateOnboardingUrl"
            />
            <Button
              v-if="onboardingStatus.hasStripeAccount"
              :label="t('View Dashboard')"
              icon="pi pi-external-link"
              severity="secondary"
              outlined
              @click="viewDashboard(selectedBranch)"
            />
            <Button
              v-if="onboardingStatus.hasStripeAccount"
              :label="t('Reset Account')"
              icon="pi pi-refresh"
              severity="danger"
              outlined
              @click="resetAccount"
            />
          </div>
        </div>
      </div>

      <template #footer>
        <Button
          :label="t('Close')"
          icon="pi pi-times"
          severity="secondary"
          outlined
          @click="showOnboardingDialog = false"
        />
      </template>
    </Dialog>
  </AppLayout>
</template>
