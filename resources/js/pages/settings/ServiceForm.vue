<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head as InertiaHead, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import { useToast } from 'primevue/usetoast';
import { Service } from '@/models/Service';
import { Branch } from '@/models/Branch';

const { t } = useI18n();
const toast = useToast();

const props = defineProps<{
  id?: string;
}>();

const isEditing = computed(() => !!props.id);

const breadcrumbs = computed(() => [
  { label: t('Settings'), url: '/settings' },
  { label: t('Services'), url: '/settings/services' },
  { label: isEditing.value ? t('Edit Service') : t('Create Service') },
]);

// State
const branches = ref<Branch[]>([]);
const loading = ref(false);
const saving = ref(false);

// Form state
const formData = ref({
  branch_id: null as number | null,
  name: '',
  description: '',
  price: null as number | null,
  duration: null as number | null,
  sku: '',
  is_active: true,
});

// Load branches
const loadBranches = async () => {
  try {
    const response = await Branch.$query().get();
    branches.value = response;
    
    // Auto-select first branch if only one exists and not editing
    if (branches.value.length === 1 && !isEditing.value) {
      const firstBranchId = branches.value[0]?.$attributes?.id;
      formData.value.branch_id = firstBranchId ?? null;
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

// Load service for editing
const loadService = async () => {
  if (!props.id) return;
  
  try {
    loading.value = true;
    const service = await Service.$query().with(['branch']).find(parseInt(props.id));
    
    if (service) {
      formData.value = {
        branch_id: service.$attributes.branch_id,
        name: service.$attributes.name,
        description: service.$attributes.description || '',
        price: service.$attributes.price,
        duration: service.$attributes.duration ?? null,
        sku: service.$attributes.sku || '',
        is_active: service.$attributes.is_active ?? true,
      };
    }
  } catch (error) {
    console.error('Error loading service:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load service'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// Save service
const saveService = async () => {
  try {
    saving.value = true;

    if (!formData.value.branch_id) {
      toast.add({
        severity: 'error',
        summary: t('Error'),
        detail: t('Please select a branch'),
        life: 3000,
      });
      return;
    }

    if (!formData.value.name) {
      toast.add({
        severity: 'error',
        summary: t('Error'),
        detail: t('Please enter a service name'),
        life: 3000,
      });
      return;
    }

    if (formData.value.price === null || formData.value.price === undefined) {
      toast.add({
        severity: 'error',
        summary: t('Error'),
        detail: t('Please enter a price'),
        life: 3000,
      });
      return;
    }

    if (isEditing.value && props.id) {
      // Update existing service
      const service = await Service.$query().find(parseInt(props.id));
      if (service) {
        await service.$query().update(parseInt(props.id), formData.value);
        toast.add({
          severity: 'success',
          summary: t('Success'),
          detail: t('Service updated successfully'),
          life: 3000,
        });
      }
    } else {
      // Create new service
      await Service.$query().store({
        branch_id: formData.value.branch_id,
        name: formData.value.name,
        description: formData.value.description,
        price: formData.value.price,
        duration: formData.value.duration,
        sku: formData.value.sku,
        is_active: formData.value.is_active,
      });
      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('Service created successfully'),
        life: 3000,
      });
    }

    // Navigate back to services list
    router.visit('/settings/services');
  } catch (error) {
    console.error('Error saving service:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to save service'),
      life: 3000,
    });
  } finally {
    saving.value = false;
  }
};

// Cancel and go back
const cancel = () => {
  router.visit('/settings/services');
};

onMounted(async () => {
  await loadBranches();
  if (isEditing.value) {
    await loadService();
  }
});
</script>

<template>
  <InertiaHead :title="isEditing ? t('Edit Service') : t('Create Service')" />

  <AppLayout :breadcrumbs>
    <div class="max-w-4xl mx-auto">
      <Card>
        <template #title>
          <div class="flex justify-between items-center">
            <span>{{ isEditing ? t('Edit Service') : t('Create Service') }}</span>
          </div>
        </template>
        
        <template #content>
          <div v-if="loading" class="flex justify-center items-center py-8">
            <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
          </div>

          <form v-else @submit.prevent="saveService" class="space-y-6">
            <!-- Branch Selection -->
            <div class="field">
              <label for="branch" class="block mb-2 font-semibold">
                {{ t('Branch') }} <span class="text-red-500">*</span>
              </label>
              <Select
                id="branch"
                v-model="formData.branch_id"
                :options="branches"
                option-label="$attributes.name"
                option-value="$attributes.id"
                :placeholder="t('Select a branch')"
                class="w-full"
                :disabled="isEditing"
              />
              <small class="text-gray-500">
                {{ isEditing ? t('Branch cannot be changed after creation') : t('Select the branch this service belongs to') }}
              </small>
            </div>

            <!-- Service Name -->
            <div class="field">
              <label for="name" class="block mb-2 font-semibold">
                {{ t('Service Name') }} <span class="text-red-500">*</span>
              </label>
              <InputText
                id="name"
                v-model="formData.name"
                :placeholder="t('e.g., Haircut, Oil Change, Consultation')"
                class="w-full"
                required
              />
            </div>

            <!-- Description -->
            <div class="field">
              <label for="description" class="block mb-2 font-semibold">
                {{ t('Description') }}
              </label>
              <Textarea
                id="description"
                v-model="formData.description"
                :placeholder="t('Describe what this service includes')"
                rows="4"
                class="w-full"
              />
            </div>

            <!-- Price and Duration -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="field">
                <label for="price" class="block mb-2 font-semibold">
                  {{ t('Price') }} <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  id="price"
                  v-model="formData.price"
                  mode="decimal"
                  prefix="$"
                  :min="0"
                  :min-fraction-digits="2"
                  :max-fraction-digits="2"
                  class="w-full"
                  fluid
                  required
                />
                <small class="text-gray-500">{{ t('Price in USD') }}</small>
              </div>

              <div class="field">
                <label for="duration" class="block mb-2 font-semibold">
                  {{ t('Duration (minutes)') }}
                </label>
                <InputNumber
                  id="duration"
                  v-model="formData.duration"
                  :placeholder="t('e.g., 60')"
                  :min="1"
                  :use-grouping="false"
                  class="w-full"
                  fluid
                />
                <small class="text-gray-500">{{ t('Approximate service duration') }}</small>
              </div>
            </div>

            <!-- SKU -->
            <div class="field">
              <label for="sku" class="block mb-2 font-semibold">
                {{ t('SKU / Service Code') }}
              </label>
              <InputText
                id="sku"
                v-model="formData.sku"
                :placeholder="t('Optional internal reference code')"
                class="w-full"
              />
              <small class="text-gray-500">{{ t('Optional: Used for internal tracking') }}</small>
            </div>

            <!-- Active Status -->
            <div class="field">
              <div class="flex items-center gap-3">
                <Checkbox
                  id="is_active"
                  v-model="formData.is_active"
                  :binary="true"
                />
                <label for="is_active" class="font-semibold">
                  {{ t('Active') }}
                </label>
              </div>
              <small class="text-gray-500 ml-8">
                {{ t('Inactive services will not be available for booking or purchase') }}
              </small>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t">
              <Button
                type="button"
                :label="t('Cancel')"
                icon="pi pi-times"
                severity="secondary"
                outlined
                @click="cancel"
                :disabled="saving"
              />
              <Button
                type="submit"
                :label="t('Save Service')"
                icon="pi pi-check"
                :loading="saving"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
:deep(.p-inputnumber-input) {
  width: 100%;
}
</style>

