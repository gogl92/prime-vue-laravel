<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head as InertiaHead, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import MultiSelect from 'primevue/multiselect';
import ColorPicker from 'primevue/colorpicker';
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import { PaymentGateway } from '@/models/PaymentGateway';
import { Branch } from '@/models/Branch';
import { Product } from '@/models/Product';
import { Service } from '@/models/Service';
import { Orion } from '@tailflow/laravel-orion/lib/orion';

const { t } = useI18n();
const toast = useToast();
const page = usePage();

const breadcrumbs = [
  { label: t('Settings'), url: '/settings' },
  { label: t('Payment Gateways') },
];

// State
const branches = ref<Branch[]>([]);
const gateways = ref<PaymentGateway[]>([]);
const products = ref<Product[]>([]);
const services = ref<Service[]>([]);
const loading = ref(false);
const showDialog = ref(false);
const isEditing = ref(false);
const currentGateway = ref<PaymentGateway | null>(null);
const selectedBranch = ref<Branch | null>(null);

// Form state
const formData = ref({
  branch_id: null as number | null,
  is_enabled: true,
  slug: '',
  business_name: '',
  logo_url: '',
  primary_color: '3B82F6',
  secondary_color: '1E40AF',
  available_product_ids: [] as number[],
  available_service_ids: [] as number[],
  available_subscription_ids: [] as number[],
  terms_and_conditions: '',
  success_message: 'Thank you for your purchase!',
});

// Get current user from page props
const currentUser = computed(() => page.props.auth?.user);

// Get checkout URL for a gateway
const getCheckoutUrl = (gateway: PaymentGateway) => {
  return `${window.location.origin}/checkout/${gateway.$attributes.slug}`;
};

// Load all data
const loadBranches = async () => {
  try {
    // Request both paymentGateway and stripeAccountMapping
    // stripeAccountMapping is needed for is_stripe_connected attribute
    const response = await Branch.$query()
      .with(['paymentGateway', 'stripeAccountMapping'])
      .get();
    branches.value = response;
    
    // Debug: log first branch to check if is_stripe_connected is populated
    if (response.length > 0 && response[0]) {
      console.log('[PaymentGatewaySettings] First branch:', response[0].$attributes);
      console.log('[PaymentGatewaySettings] is_stripe_connected:', response[0].$attributes.is_stripe_connected);
    }
  } catch (error) {
    console.error('Error loading branches:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load branches'),
      life: 3000,
    });
  }
};

const loadGateways = async () => {
  try {
    loading.value = true;
    const response = await PaymentGateway.$query().with(['branch']).get();
    gateways.value = response;
  } catch (error) {
    console.error('Error loading gateways:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load payment gateways'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const loadProducts = async () => {
  try {
    const response = await Product.$query().get();
    products.value = response;
  } catch (error) {
    console.error('Error loading products:', error);
  }
};

const loadServices = async () => {
  try {
    const response = await Service.$query().get();
    services.value = response;
  } catch (error) {
    console.error('Error loading services:', error);
  }
};

// Open create dialog for a branch
const openCreateDialog = (branch: Branch) => {
  isEditing.value = false;
  currentGateway.value = null;
  selectedBranch.value = branch;
  resetForm();
  formData.value.branch_id = branch.$attributes.id ?? null;
  formData.value.business_name = branch.$attributes.name;
  generateSlug();
  showDialog.value = true;
};

// Open edit dialog
const openEditDialog = (gateway: PaymentGateway) => {
  isEditing.value = true;
  currentGateway.value = gateway;
  selectedBranch.value = branches.value.find(b => b.$attributes.id === gateway.$attributes.branch_id) || null;
  formData.value = {
    branch_id: gateway.$attributes.branch_id,
    is_enabled: gateway.$attributes.is_enabled ?? true,
    slug: gateway.$attributes.slug,
    business_name: gateway.$attributes.business_name || '',
    logo_url: gateway.$attributes.logo_url || '',
    primary_color: gateway.$attributes.primary_color?.replace('#', '') || '3B82F6',
    secondary_color: gateway.$attributes.secondary_color?.replace('#', '') || '1E40AF',
    available_product_ids: gateway.$attributes.available_product_ids || [],
    available_service_ids: gateway.$attributes.available_service_ids || [],
    available_subscription_ids: gateway.$attributes.available_subscription_ids || [],
    terms_and_conditions: gateway.$attributes.terms_and_conditions || '',
    success_message: gateway.$attributes.success_message || 'Thank you for your purchase!',
  };
  showDialog.value = true;
};

// Reset form
const resetForm = () => {
  formData.value = {
    branch_id: null,
    is_enabled: true,
    slug: '',
    business_name: '',
    logo_url: '',
    primary_color: '3B82F6',
    secondary_color: '1E40AF',
    available_product_ids: [],
    available_service_ids: [],
    available_subscription_ids: [],
    terms_and_conditions: '',
    success_message: 'Thank you for your purchase!',
  };
};

// Generate slug
const generateSlug = async () => {
  try {
    const httpClient = Orion.makeHttpClient();
    const response = await httpClient.post('/payment-gateways/generate-slug', {
      business_name: formData.value.business_name || selectedBranch.value?.$attributes.name,
    });
    formData.value.slug = response.data['slug'] as unknown as string;
  } catch (error) {
    console.error('Error generating slug:', error);
  }
};

// Save gateway
const saveGateway = async () => {
  try {
    loading.value = true;

    if (!formData.value.branch_id) {
      toast.add({
        severity: 'error',
        summary: t('Error'),
        detail: t('Please select a branch'),
        life: 3000,
      });
      return;
    }

    // Prepare data with hex color codes
    const dataToSave = {
      ...formData.value,
      primary_color: '#' + formData.value.primary_color.replace('#', ''),
      secondary_color: '#' + formData.value.secondary_color.replace('#', ''),
    };

    if (isEditing.value && currentGateway.value) {
      // Update existing gateway
      await currentGateway.value.$query().update(currentGateway.value.$attributes.id as number, dataToSave);
      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('Payment gateway updated successfully'),
        life: 3000,
      });
    } else {
      // Create new gateway
      const gateway = new PaymentGateway();
      await gateway.$query().store(dataToSave);
      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('Payment gateway created successfully'),
        life: 3000,
      });
    }

    showDialog.value = false;
    await loadGateways();
    await loadBranches();
  } catch (error) {
    console.error('Error saving gateway:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to save payment gateway'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// Delete gateway
const deleteGateway = async (gateway: PaymentGateway) => {
  if (!confirm(t('Are you sure you want to delete this payment gateway?'))) {
    return;
  }

  try {
    loading.value = true;
    await gateway.$destroy();
    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Payment gateway deleted successfully'),
      life: 3000,
    });
    await loadGateways();
    await loadBranches();
  } catch (error) {
    console.error('Error deleting gateway:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to delete payment gateway'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// Test gateway
const testGateway = (gateway: PaymentGateway) => {
  const url = getCheckoutUrl(gateway);
  window.open(url, '_blank');
};

// Copy checkout URL
const copyCheckoutUrl = (gateway: PaymentGateway) => {
  const url = getCheckoutUrl(gateway);
  navigator.clipboard.writeText(url);
  toast.add({
    severity: 'success',
    summary: t('Copied'),
    detail: t('Checkout URL copied to clipboard'),
    life: 2000,
  });
};

// Check if branch has gateway
const branchHasGateway = (branchId: number) => {
  return gateways.value.some(g => g.$attributes.branch_id === branchId);
};

// Get gateway for branch
const getGatewayForBranch = (branchId: number) => {
  return gateways.value.find(g => g.$attributes.branch_id === branchId);
};

onMounted(async () => {
  await Promise.all([
    loadBranches(),
    loadGateways(),
    loadProducts(),
    loadServices(),
  ]);
});
</script>

<template>
  <InertiaHead title="Payment Gateways" />

  <AppLayout :breadcrumbs>
    <Card>
      <template #title>
        <div class="flex justify-between items-center">
          <span>{{ t('Payment Gateway Management') }}</span>
          <Button
            :label="t('Refresh')"
            icon="pi pi-refresh"
            size="small"
            severity="secondary"
            outlined
            :loading="loading"
            @click="loadGateways"
          />
        </div>
      </template>
      <template #content>
        <Message severity="info" :closable="false" class="mb-4">
          {{
            t(
              'Configure payment gateways for your branches to allow customers to purchase products and services online.'
            )
          }}
        </Message>

        <DataTable
          :value="branches"
          :loading="loading"
          striped-rows
          data-key="id"
        >
          <Column field="name" :header="t('Branch')">
            <template #body="{ data }">
              <div class="font-semibold">{{ data.$attributes.name }}</div>
              <div class="text-sm text-gray-500">{{ data.$attributes.code }}</div>
            </template>
          </Column>

          <Column field="is_stripe_connected" :header="t('Stripe Status')">
            <template #body="{ data }">
              <Tag :severity="data.$attributes.is_stripe_connected ? 'success' : 'warn'">
                {{ data.$attributes.is_stripe_connected ? t('Connected') : t('Not Connected') }}
              </Tag>
            </template>
          </Column>

          <Column :header="t('Gateway Status')">
            <template #body="{ data }">
              <Tag
                v-if="branchHasGateway(data.$attributes.id)"
                :severity="getGatewayForBranch(data.$attributes.id)?.$attributes.is_enabled ? 'success' : 'secondary'"
              >
                {{
                  getGatewayForBranch(data.$attributes.id)?.$attributes.is_enabled
                    ? t('Enabled')
                    : t('Disabled')
                }}
              </Tag>
              <Tag v-else severity="secondary">{{ t('Not Configured') }}</Tag>
            </template>
          </Column>

          <Column :header="t('Checkout URL')">
            <template #body="{ data }">
              <div v-if="branchHasGateway(data.$attributes.id)" class="flex items-center gap-2">
                <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                  /checkout/{{ getGatewayForBranch(data.$attributes.id)?.$attributes.slug }}
                </code>
                <Button
                  icon="pi pi-copy"
                  size="small"
                  text
                  @click="copyCheckoutUrl(getGatewayForBranch(data.$attributes.id) as PaymentGateway)"
                />
              </div>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  v-if="!branchHasGateway(data.$attributes.id)"
                  :label="t('Setup Gateway')"
                  icon="pi pi-plus"
                  size="small"
                  @click="openCreateDialog(data)"
                />
                <template v-else>
                  <Button
                    :label="t('Configure')"
                    icon="pi pi-cog"
                    size="small"
                    severity="info"
                    outlined
                    @click="openEditDialog(getGatewayForBranch(data.$attributes.id) as PaymentGateway)"
                  />
                  <Button
                    :label="t('Test')"
                    icon="pi pi-external-link"
                    size="small"
                    severity="success"
                    outlined
                    @click="testGateway(getGatewayForBranch(data.$attributes.id) as PaymentGateway)"
                  />
                  <Button
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    outlined
                    @click="deleteGateway(getGatewayForBranch(data.$attributes.id) as PaymentGateway)"
                  />
                </template>
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Configure Gateway Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? t('Configure Payment Gateway') : t('Setup Payment Gateway')"
      modal
      :style="{ width: '800px' }"
      :maximizable="true"
    >
      <div class="space-y-4">
        <Message severity="info" :closable="false">
          {{
            t(
              'Configure your payment gateway to accept online payments from customers.'
            )
          }}
        </Message>

        <Message 
          v-if="selectedBranch && !selectedBranch.$attributes.is_stripe_connected" 
          severity="warn" 
          :closable="false"
        >
          {{
            t(
              'Note: This branch does not have Stripe Connect configured. You can still set up a payment gateway, but payments may not be processed until Stripe Connect is configured.'
            )
          }}
        </Message>

        <div class="field">
          <label class="block mb-2 font-semibold">{{ t('Branch') }}</label>
          <div class="p-3 bg-gray-50 rounded">
            {{ selectedBranch?.$attributes.name || '-' }}
          </div>
        </div>

        <div class="field flex items-center gap-2">
          <input
            id="is_enabled"
            v-model="formData.is_enabled"
            type="checkbox"
            class="h-4 w-4"
          />
          <label for="is_enabled" class="font-semibold">{{ t('Enable Payment Gateway') }}</label>
        </div>

        <div class="field">
          <label for="business_name" class="block mb-2">{{ t('Business Name') }}</label>
          <InputText
            id="business_name"
            v-model="formData.business_name"
            :placeholder="t('Enter business name')"
            class="w-full"
          />
        </div>

        <div class="field">
          <label for="slug" class="block mb-2">{{ t('URL Slug') }}</label>
          <div class="flex gap-2">
            <InputText
              id="slug"
              v-model="formData.slug"
              :placeholder="t('Enter slug')"
              class="flex-1"
              readonly
            />
            <Button
              :label="t('Generate')"
              icon="pi pi-refresh"
              size="small"
              @click="generateSlug"
            />
          </div>
          <small class="text-gray-500">{{ getCheckoutUrl({ $attributes: { slug: formData.slug } } as any) }}</small>
        </div>

        <div class="field">
          <label for="logo_url" class="block mb-2">{{ t('Logo URL') }}</label>
          <InputText
            id="logo_url"
            v-model="formData.logo_url"
            :placeholder="t('Enter logo URL')"
            class="w-full"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label for="primary_color" class="block mb-2">{{ t('Primary Color') }}</label>
            <div class="flex gap-2 items-center">
              <ColorPicker v-model="formData.primary_color" class="w-10" />
              <InputText
                id="primary_color"
                v-model="formData.primary_color"
                :placeholder="t('e.g., 3B82F6')"
                class="flex-1"
              />
            </div>
          </div>

          <div class="field">
            <label for="secondary_color" class="block mb-2">{{ t('Secondary Color') }}</label>
            <div class="flex gap-2 items-center">
              <ColorPicker v-model="formData.secondary_color" class="w-10" />
              <InputText
                id="secondary_color"
                v-model="formData.secondary_color"
                :placeholder="t('e.g., 1E40AF')"
                class="flex-1"
              />
            </div>
          </div>
        </div>

        <div class="field">
          <label for="products" class="block mb-2">{{ t('Available Products') }}</label>
          <MultiSelect
            id="products"
            v-model="formData.available_product_ids"
            :options="products"
            option-label="$attributes.name"
            option-value="$attributes.id"
            :placeholder="t('Select products')"
            class="w-full"
            display="chip"
          />
        </div>

        <div class="field">
          <label for="services" class="block mb-2">{{ t('Available Services') }}</label>
          <MultiSelect
            id="services"
            v-model="formData.available_service_ids"
            :options="services"
            option-label="$attributes.name"
            option-value="$attributes.id"
            :placeholder="t('Select services')"
            class="w-full"
            display="chip"
          />
        </div>

        <div class="field">
          <label for="terms" class="block mb-2">{{ t('Terms and Conditions') }}</label>
          <Textarea
            id="terms"
            v-model="formData.terms_and_conditions"
            :placeholder="t('Enter terms and conditions')"
            rows="4"
            class="w-full"
          />
        </div>

        <div class="field">
          <label for="success_message" class="block mb-2">{{ t('Success Message') }}</label>
          <Textarea
            id="success_message"
            v-model="formData.success_message"
            :placeholder="t('Enter success message')"
            rows="3"
            class="w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button
          :label="t('Cancel')"
          icon="pi pi-times"
          severity="secondary"
          outlined
          @click="showDialog = false"
        />
        <Button
          :label="t('Save')"
          icon="pi pi-check"
          :loading="loading"
          @click="saveGateway"
        />
      </template>
    </Dialog>
  </AppLayout>
</template>

