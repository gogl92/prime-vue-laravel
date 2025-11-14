<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head as InertiaHead, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog from 'primevue/confirmdialog';
import { Service } from '@/models/Service';

const { t } = useI18n();
const toast = useToast();
const confirm = useConfirm();

const breadcrumbs = [
  { label: t('Settings'), url: '/settings' },
  { label: t('Services') },
];

// State
const services = ref<Service[]>([]);
const loading = ref(false);

// Load services
const loadServices = async () => {
  try {
    loading.value = true;
    const response = await Service.$query().with(['branch']).get();
    services.value = response;
  } catch (error) {
    console.error('Error loading services:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load services'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// Navigate to create page
const createService = () => {
  router.visit('/settings/services/create');
};

// Navigate to edit page
const editService = (service: Service) => {
  router.visit(`/settings/services/${service.$attributes.id}/edit`);
};

// Delete service
const deleteService = (service: Service) => {
  confirm.require({
    message: t('Are you sure you want to delete this service? This action cannot be undone.'),
    header: t('Confirm Deletion'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        loading.value = true;
        await service.$destroy();
        toast.add({
          severity: 'success',
          summary: t('Success'),
          detail: t('Service deleted successfully'),
          life: 3000,
        });
        await loadServices();
      } catch (error) {
        console.error('Error deleting service:', error);
        toast.add({
          severity: 'error',
          summary: t('Error'),
          detail: t('Failed to delete service'),
          life: 3000,
        });
      } finally {
        loading.value = false;
      }
    },
  });
};

// Format price
const formatPrice = (value: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

// Format duration
const formatDuration = (minutes: number | null) => {
  if (!minutes) return '-';
  if (minutes < 60) return `${minutes} min`;
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
};

onMounted(async () => {
  await loadServices();
});
</script>

<template>
  <InertiaHead title="Services" />

  <AppLayout :breadcrumbs>
    <ConfirmDialog />
    
    <Card>
      <template #title>
        <div class="flex justify-between items-center">
          <span>{{ t('Services Management') }}</span>
          <div class="flex gap-2">
            <Button
              :label="t('Refresh')"
              icon="pi pi-refresh"
              size="small"
              severity="secondary"
              outlined
              :loading="loading"
              @click="loadServices"
            />
            <Button
              :label="t('Add Service')"
              icon="pi pi-plus"
              size="small"
              @click="createService"
            />
          </div>
        </div>
      </template>
      
      <template #content>
        <DataTable
          :value="services"
          :loading="loading"
          striped-rows
          paginator
          :rows="10"
          :rows-per-page-options="[10, 20, 50]"
          data-key="id"
          filter-display="row"
        >
          <Column field="name" :header="t('Name')" sortable>
            <template #body="{ data }">
              <div class="font-semibold">{{ data.$attributes.name }}</div>
              <div v-if="data.$attributes.sku" class="text-sm text-gray-500">
                SKU: {{ data.$attributes.sku }}
              </div>
            </template>
          </Column>

          <Column field="description" :header="t('Description')">
            <template #body="{ data }">
              <div class="max-w-md truncate">
                {{ data.$attributes.description || '-' }}
              </div>
            </template>
          </Column>

          <Column field="price" :header="t('Price')" sortable>
            <template #body="{ data }">
              {{ formatPrice(data.$attributes.price) }}
            </template>
          </Column>

          <Column field="duration" :header="t('Duration')" sortable>
            <template #body="{ data }">
              {{ formatDuration(data.$attributes.duration) }}
            </template>
          </Column>

          <Column field="branch_id" :header="t('Branch')">
            <template #body="{ data }">
              <div v-if="data.$relations?.branch">
                {{ data.$relations.branch.$attributes.name }}
              </div>
              <div v-else class="text-gray-400">-</div>
            </template>
          </Column>

          <Column field="is_active" :header="t('Status')">
            <template #body="{ data }">
              <Tag :severity="data.$attributes.is_active ? 'success' : 'secondary'">
                {{ data.$attributes.is_active ? t('Active') : t('Inactive') }}
              </Tag>
            </template>
          </Column>

          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  icon="pi pi-pencil"
                  size="small"
                  severity="info"
                  outlined
                  @click="editService(data)"
                />
                <Button
                  icon="pi pi-trash"
                  size="small"
                  severity="danger"
                  outlined
                  @click="deleteService(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </AppLayout>
</template>

