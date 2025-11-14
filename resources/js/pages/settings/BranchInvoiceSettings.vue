<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head as InertiaHead, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/UserSettingsLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Message from 'primevue/message';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Dropdown from 'primevue/dropdown';
import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import { useToast } from 'primevue/usetoast';
import { Branch } from '@/models/Branch';
import { Orion } from '@tailflow/laravel-orion/lib/orion';

const { t } = useI18n();
const toast = useToast();
const page = usePage();

const breadcrumbs = [
  { label: t('Settings'), url: '/settings' },
  { label: t('Branch Invoice Settings') },
];

// State
const branches = ref<Branch[]>([]);
const loading = ref(false);
const selectedBranch = ref<Branch | null>(null);
const showSettingsDialog = ref(false);
const settingsLoading = ref(false);
const saving = ref(false);

// Invoice settings form
const invoiceSettings = ref({
  invoicing_provider: 'facturacom',

  // Facturacom
  facturacom_api_url: '',
  facturacom_api_key: '',
  facturacom_secret_key: '',
  facturacom_plugin_key: '',
  facturacom_has_credentials: false,

  // Facturapi
  facturapi_api_url: '',
  facturapi_api_key: '',
  facturapi_has_credentials: false,
});

const providerOptions = [
  { label: 'Facturacom', value: 'facturacom' },
  { label: 'Facturapi', value: 'facturapi' },
];

// Get current user from page props
const currentUser = computed(() => page.props.auth?.user);

// Load branches
const loadBranches = async () => {
  try {
    loading.value = true;

    const companyId = currentUser.value?.current_company_id;

    if (!companyId) {
      toast.add({
        severity: 'warn',
        summary: t('Warning'),
        detail: t('No company selected. Please select a company first.'),
        life: 5000,
      });
      return;
    }

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

// Load invoice settings for a branch
const loadInvoiceSettings = async (branchId: number) => {
  try {
    settingsLoading.value = true;
    const httpClient = Orion.makeHttpClient();
    const response = await httpClient.get(`/branches/${branchId}/invoice-settings`);

    invoiceSettings.value = response.data;
  } catch (error) {
    console.error('Error loading invoice settings:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load invoice settings'),
      life: 3000,
    });
  } finally {
    settingsLoading.value = false;
  }
};

// Open settings dialog
const openSettings = async (branch: Branch) => {
  selectedBranch.value = branch;
  const branchId = branch.$attributes.id;
  if (branchId) {
    await loadInvoiceSettings(branchId);
  }
  showSettingsDialog.value = true;
};

// Save invoice settings
const saveSettings = async () => {
  if (!selectedBranch.value) return;

  try {
    saving.value = true;
    const branchId = selectedBranch.value.$attributes.id;
    if (!branchId) return;

    const httpClient = Orion.makeHttpClient();

    // Prepare data - don't send masked credentials
    const dataToSend: Record<string, string> = {};

    Object.entries(invoiceSettings.value).forEach(([key, value]) => {
      if (typeof value === 'string' && value && value !== '••••••••') {
        dataToSend[key] = value;
      } else if (key === 'invoicing_provider' && value) {
        dataToSend[key] = value as string;
      }
    });

    await httpClient.put(`/branches/${branchId}/invoice-settings`, dataToSend);

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Invoice settings saved successfully'),
      life: 3000,
    });

    showSettingsDialog.value = false;
    await loadBranches();
  } catch (error) {
    console.error('Error saving invoice settings:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to save invoice settings'),
      life: 3000,
    });
  } finally {
    saving.value = false;
  }
};

// Delete credentials for a provider
const deleteCredentials = async (provider: string) => {
  if (!selectedBranch.value) return;

  if (
    !confirm(
      t(
        'Are you sure you want to delete {provider} credentials? This action cannot be undone.',
        { provider: provider.toUpperCase() }
      )
    )
  ) {
    return;
  }

  try {
    const branchId = selectedBranch.value.$attributes.id;
    if (!branchId) return;

    const httpClient = Orion.makeHttpClient();
    await httpClient.delete(`/branches/${branchId}/invoice-settings`, {
      data: { provider },
    });

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Credentials deleted successfully'),
      life: 3000,
    });

    // Reload settings
    await loadInvoiceSettings(branchId);
  } catch (error) {
    console.error('Error deleting credentials:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to delete credentials'),
      life: 3000,
    });
  }
};

// Get configuration status
const getConfigStatus = (hasCredentials: boolean) => {
  return hasCredentials
    ? { severity: 'success', label: t('Configured') }
    : { severity: 'secondary', label: t('Not Configured') };
};

onMounted(() => {
  void loadBranches();
});
</script>

<template>
  <InertiaHead title="Branch Invoice Settings" />

  <AppLayout :breadcrumbs>
    <SettingsLayout>
      <Card>
        <template #title>
          <div class="flex justify-between items-center">
            <span>{{ t('Branch Invoice Settings') }}</span>
            <Button
              :label="t('Refresh')"
              icon="pi pi-refresh"
              size="small"
              severity="secondary"
              outlined
              :loading="loading"
              @click="loadBranches"
            />
          </div>
        </template>
        <template #content>
          <Message severity="info" :closable="false" class="mb-4">
            {{
              t(
                'Configure Mexican invoicing credentials for each branch. Each branch can use Facturacom or Facturapi to generate CFDI invoices.'
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

          <Column :header="t('Actions')">
            <template #body="{ data }">
              <Button
                :label="t('Configure Invoicing')"
                icon="pi pi-cog"
                size="small"
                @click="openSettings(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Settings Dialog -->
    <Dialog
      v-model:visible="showSettingsDialog"
      :header="t('Invoice Configuration')"
      modal
      :style="{ width: '700px' }"
      :closable="!saving"
    >
      <div v-if="selectedBranch" class="space-y-4">
        <div class="border-b pb-4">
          <h3 class="font-semibold text-lg">{{ selectedBranch.$attributes.name }}</h3>
          <p class="text-sm text-gray-500">{{ selectedBranch.$attributes.email }}</p>
        </div>

        <div v-if="settingsLoading" class="flex justify-center py-8">
          <i class="pi pi-spin pi-spinner text-4xl" />
        </div>

        <div v-else class="space-y-4">
          <!-- Provider Selection -->
          <div>
            <label class="block text-sm font-medium mb-2">{{ t('Invoicing Provider') }}</label>
            <Dropdown
              v-model="invoiceSettings.invoicing_provider"
              :options="providerOptions"
              option-label="label"
              option-value="value"
              :placeholder="t('Select provider')"
              class="w-full"
            />
          </div>

          <!-- Credentials Configuration -->
          <Accordion :multiple="true" class="mt-4">
            <!-- Facturacom -->
            <AccordionPanel value="facturacom">
              <template #header>
                <div class="flex items-center justify-between w-full pr-4">
                  <span class="font-semibold">Facturacom</span>
                  <Tag
                    :severity="getConfigStatus(invoiceSettings.facturacom_has_credentials).severity"
                  >
                    {{ getConfigStatus(invoiceSettings.facturacom_has_credentials).label }}
                  </Tag>
                </div>
              </template>

              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium mb-1">{{ t('API URL') }}</label>
                  <InputText
                    v-model="invoiceSettings.facturacom_api_url"
                    type="url"
                    :placeholder="t('https://factura.com/api')"
                    class="w-full"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium mb-1">{{ t('API Key') }}</label>
                  <Password
                    v-model="invoiceSettings.facturacom_api_key"
                    :placeholder="t('Enter API key')"
                    class="w-full"
                    :feedback="false"
                    toggle-mask
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium mb-1">{{ t('Secret Key') }}</label>
                  <Password
                    v-model="invoiceSettings.facturacom_secret_key"
                    :placeholder="t('Enter secret key')"
                    class="w-full"
                    :feedback="false"
                    toggle-mask
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium mb-1">{{ t('Plugin Key') }}</label>
                  <Password
                    v-model="invoiceSettings.facturacom_plugin_key"
                    :placeholder="t('Enter plugin key')"
                    class="w-full"
                    :feedback="false"
                    toggle-mask
                  />
                </div>

                <Button
                  v-if="invoiceSettings.facturacom_has_credentials"
                  :label="t('Delete Facturacom Credentials')"
                  icon="pi pi-trash"
                  severity="danger"
                  outlined
                  size="small"
                  @click="deleteCredentials('facturacom')"
                />
              </div>
            </AccordionPanel>

            <!-- Facturapi -->
            <AccordionPanel value="facturapi">
              <template #header>
                <div class="flex items-center justify-between w-full pr-4">
                  <span class="font-semibold">Facturapi</span>
                  <Tag
                    :severity="getConfigStatus(invoiceSettings.facturapi_has_credentials).severity"
                  >
                    {{ getConfigStatus(invoiceSettings.facturapi_has_credentials).label }}
                  </Tag>
                </div>
              </template>

              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium mb-1">{{ t('API URL') }}</label>
                  <InputText
                    v-model="invoiceSettings.facturapi_api_url"
                    type="url"
                    :placeholder="t('https://api.facturapi.io')"
                    class="w-full"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium mb-1">{{ t('API Key') }}</label>
                  <Password
                    v-model="invoiceSettings.facturapi_api_key"
                    :placeholder="t('Enter API key')"
                    class="w-full"
                    :feedback="false"
                    toggle-mask
                  />
                </div>

                <Button
                  v-if="invoiceSettings.facturapi_has_credentials"
                  :label="t('Delete Facturapi Credentials')"
                  icon="pi pi-trash"
                  severity="danger"
                  outlined
                  size="small"
                  @click="deleteCredentials('facturapi')"
                />
              </div>
            </AccordionPanel>
          </Accordion>
        </div>
      </div>

      <template #footer>
        <Button
          :label="t('Cancel')"
          icon="pi pi-times"
          severity="secondary"
          outlined
          :disabled="saving"
          @click="showSettingsDialog = false"
        />
        <Button
          :label="t('Save')"
          icon="pi pi-check"
          :loading="saving"
          @click="saveSettings"
        />
      </template>
    </Dialog>
    </SettingsLayout>
  </AppLayout>
</template>
