<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Invoice } from '@/models/Invoice';
import { Product } from '@/models/Product';
import { Payment } from '@/models/Payment';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';

// Components
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';

import { FilterOperator } from '@tailflow/laravel-orion/lib/drivers/default/enums/filterOperator';
import { SortDirection } from '@tailflow/laravel-orion/lib/drivers/default/enums/sortDirection';

// Toast & i18n
const { t } = useI18n();
const toast = useToast();

// State
const loading = ref(false);
const deleting = ref(false);
const invoices = ref<Invoice[]>([]);
const totalRecords = ref(0);
const expandedRows = ref<Invoice[]>([]);
const invoiceProducts = ref<Record<number, Product[]>>({});
const loadingProducts = ref<Record<number, boolean>>({});
const invoicePayments = ref<Record<number, Payment[]>>({});
const loadingPayments = ref<Record<number, boolean>>({});

// Pagination
const pagination = reactive({
  page: 1,
  rows: 10,
});

// Sorting
const sortField = ref<string | undefined>(undefined);
const sortOrder = ref<number>(1); // 1 for asc, -1 for desc

// Filters
const filters = reactive({
  search: '',
  city: '',
  country: '',
});

// Dialogs
const showDeleteDialog = ref(false);
const invoiceToDelete = ref<Invoice | null>(null);


// Country options
const countryOptions = ref([
  'United States',
  'Canada',
  'United Kingdom',
  'Germany',
  'France',
  'Australia',
  'Japan',
  'Brazil',
  'India',
  'China',
]);

// Debounced search
const debouncedSearch = debounce(() => {
  pagination.page = 1;
  void loadInvoices();
}, 500);

// Methods
const loadInvoices = async () => {
  try {
    loading.value = true;

    let query = Invoice.$query().with(['client', 'issuer', 'products', 'payments']);

    // Add search if provided
    if (filters.search) {
      query = query.lookFor(filters.search);
    }

    // Filter by city
    if (filters.city) {
      query = query.filter('client.city', FilterOperator.Equal, filters.city);
    }

    // Filter by country
    if (filters.country) {
      query = query.filter('client.country', FilterOperator.Equal, filters.country);
    }

    // Sorting
    if (sortField.value) {
      query = query.sortBy(
        sortField.value,
        sortOrder.value === 1 ? SortDirection.Asc : SortDirection.Desc
      );
    }

    // Execute the query with pagination
    // get() and search() methods accept limit and page as parameters
    // Use search() when filtering OR sorting is applied, otherwise use get()
    const response: unknown =
      filters.search || filters.city || filters.country || sortField.value
        ? await query.search(pagination.rows, pagination.page)
        : await query.get(pagination.rows, pagination.page);

    // Get the results
    // console.error('Raw response:', response);
    // console.error('Response type:', typeof response);
    // console.error('Is array:', Array.isArray(response));
    // console.error('Response length:', response?.length);

    // Handle different response structures
    let actualResponse: Invoice[];
    if (Array.isArray(response)) {
      invoices.value = response as Invoice[];
      actualResponse = response as Invoice[];
    } else if (
      typeof response === 'object' &&
      response !== null &&
      'data' in response &&
      Array.isArray((response as { data: unknown }).data)
    ) {
      // If response is wrapped in a data property
      const wrappedResponse = response as { data: Invoice[] };
      invoices.value = wrappedResponse.data;
      actualResponse = wrappedResponse.data;
    } else {
      console.error('Unexpected response structure:', response);
      invoices.value = [];
      actualResponse = [];
    }

    // Check if Orion provides pagination metadata in the response
    if (actualResponse[0]?.$response?.data?.meta?.total) {
      // Use Orion's pagination metadata
      totalRecords.value = actualResponse[0].$response.data.meta.total;
    } else {
      // Fallback: get total count only when filtering
      if (filters.search || filters.city || filters.country) {
        // When filtering, we need accurate count
        let countQuery = Invoice.$query();
        if (filters.search) {
          countQuery = countQuery.lookFor(filters.search);
        }
        if (filters.city) {
          countQuery = countQuery.filter('client.city', FilterOperator.Equal, filters.city);
        }
        if (filters.country) {
          countQuery = countQuery.filter('client.country', FilterOperator.Equal, filters.country);
        }

        // Get count without pagination
        const allResults = await countQuery.get();
        totalRecords.value = allResults.length;
      } else {
        // For unfiltered results, estimate based on current response
        // If current page is full, there might be more records
        if (invoices.value.length === pagination.rows) {
          // Estimate: assume there are more records
          totalRecords.value = (pagination.page - 1) * pagination.rows + invoices.value.length + 1;
        } else {
          // Current page is not full, this is likely the last page
          totalRecords.value = (pagination.page - 1) * pagination.rows + invoices.value.length;
        }
      }
    }
  } catch (error) {
    console.error('Error loading invoices:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load invoices'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const onPageChange = (event: { first: number; rows: number }) => {
  // For lazy loading, PrimeVue passes different event structure
  // event.first: index of first record
  // event.rows: number of rows per page
  pagination.page = Math.floor(event.first / event.rows) + 1;
  pagination.rows = event.rows;
  void loadInvoices();
};

const onSort = (event: {
  sortField: string | ((item: unknown) => string) | undefined;
  sortOrder: number | null | undefined;
}) => {
  sortField.value = typeof event.sortField === 'function' ? undefined : event.sortField;
  sortOrder.value = event.sortOrder ?? 1;
  pagination.page = 1; // Reset to first page when sorting
  void loadInvoices();
};

const editInvoice = (_invoice: Invoice) => {
  // Navigate to edit page (you can implement this later)
  // For now, just show a toast message
  toast.add({
    severity: 'info',
    summary: t('Info'),
    detail: t('Edit functionality will be implemented soon'),
    life: 3000,
  });
};

const confirmDelete = (invoice: Invoice) => {
  invoiceToDelete.value = invoice;
  showDeleteDialog.value = true;
};

const deleteInvoice = async () => {
  if (!invoiceToDelete.value) return;

  try {
    deleting.value = true;
    await invoiceToDelete.value.$destroy();

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Invoice deleted successfully'),
      life: 3000,
    });

    showDeleteDialog.value = false;
    invoiceToDelete.value = null;
    void loadInvoices();
  } catch (error) {
    console.error('Error deleting invoice:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to delete invoice'),
      life: 3000,
    });
  } finally {
    deleting.value = false;
  }
};



const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount);
};

const formatDateTime = (dateString: string | null | undefined) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
};

const getStatusSeverity = (status: string) => {
  const severityMap: Record<
    string,
    'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' | undefined
  > = {
    completed: 'success',
    pending: 'warn',
    failed: 'danger',
    refunded: 'info',
  };
  return severityMap[status] ?? 'secondary';
};

const getPaymentMethodIcon = (method: string) => {
  const iconMap: Record<string, string> = {
    credit_card: 'pi-credit-card',
    debit_card: 'pi-credit-card',
    paypal: 'pi-paypal',
    bank_transfer: 'pi-building-columns',
    cash: 'pi-money-bill',
    check: 'pi-file',
  };
  return iconMap[method] ?? 'pi-wallet';
};

const onRowExpand = async (event: { data: Invoice }) => {
  const invoiceId = event.data.$attributes.id;

  // console.error('Row expand triggered for invoice ID:', invoiceId);

  if (!invoiceId) {
    // console.error('No invoice ID found');
    return;
  }

  // Check if products are already loaded
  if (invoiceProducts.value[invoiceId]) {
    // console.error('Products already loaded for invoice:', invoiceId);
    return;
  }

  try {
    loadingProducts.value[invoiceId] = true;

    // Check if products are already included in the invoice data
    // Orion stores relations in $relations, not $attributes
    const loadedProducts =
      event.data.$relations?.['products'] ?? event.data.$attributes['products'];
    if (loadedProducts && Array.isArray(loadedProducts) && loadedProducts.length > 0) {
      // console.error('Using products from invoice data:', loadedProducts);
      invoiceProducts.value[invoiceId] = loadedProducts as Product[];
      return;
    }

    // Fallback: Load products using the Orion belongsToMany relationship endpoint
    const products = await Product.$query().filter('invoices.id', FilterOperator.Equal, invoiceId).search();
    // console.error('Products loaded from API:', products);
    invoiceProducts.value[invoiceId] = products;
  } catch (error) {
    console.error('Error loading products:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load products'),
      life: 3000,
    });
  } finally {
    loadingProducts.value[invoiceId] = false;
  }
};

const onTabChange = async (
  event: { index: number; originalEvent: Event },
  invoiceData: Invoice
) => {
  const invoiceId = invoiceData.$attributes.id;

  // If Payments tab is selected (index 1)
  if (event.index === 1) {
    if (!invoiceId) return;
    await loadPayments(invoiceId, { data: invoiceData });
  }
};

const onRowCollapse = (_event: { data: Invoice }) => {
  // Optionally clear products when row collapses to save memory
  // const invoiceId = event.data.$attributes.id;
  // delete invoiceProducts.value[invoiceId];
};

const loadPayments = async (invoiceId: number, event?: { data: Invoice }) => {
  // Check if payments are already loaded
  if (invoicePayments.value[invoiceId]) {
    return;
  }

  try {
    loadingPayments.value[invoiceId] = true;

    // First check if payments are already included in the invoice data
    if (event?.data?.$relations?.['payments']) {
      const loadedPayments = event.data.$relations['payments'];
      if (Array.isArray(loadedPayments) && loadedPayments.length > 0) {
        invoicePayments.value[invoiceId] = loadedPayments as Payment[];
        return;
      }
    }

    // Fallback: Load payments using the Orion hasMany relationship endpoint
    // The URL pattern will be: /api/invoices/{invoice}/payments
    const payments = await Payment.$query().filter('invoice_id', FilterOperator.Equal, invoiceId).search();
    invoicePayments.value[invoiceId] = payments;
  } catch (error) {
    console.error('Error loading payments:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load payments'),
      life: 3000,
    });
  } finally {
    loadingPayments.value[invoiceId] = false;
  }
};

const getTotalPayments = (invoiceId: number): number => {
  const payments = invoicePayments.value[invoiceId];
  if (!payments || payments.length === 0) return 0;

  return payments.reduce((total, payment) => {
    const amount = payment.$attributes?.amount ?? 0;
    return total + parseFloat(amount.toString());
  }, 0);
};

const selectedInvoices = ref<Invoice[]>([]);

// Lifecycle
onMounted(() => {
  void loadInvoices();
});
</script>

<template>
  <AppLayout :title="t('Invoices Example - Orion API')">
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
          {{ t('Invoices Example - Orion API') }}
        </h1>
        <Button
          :label="t('Add Invoice')"
          icon="pi pi-plus"
          severity="success"
          class="p-button-success"
          @click="router.visit('/invoices/create')"
        />
      </div>
      <!-- Filters -->
      <Card>
        <template #title>{{ t('Filters') }}</template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">{{ t('Search') }}</label>
              <InputText
                v-model="filters.search"
                :placeholder="t('Search invoices...')"
                @input="debouncedSearch"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">{{ t('City') }}</label>
              <InputText
                v-model="filters.city"
                :placeholder="t('Filter by city...')"
                @input="debouncedSearch"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">{{ t('Country') }}</label>
              <Dropdown
                v-model="filters.country"
                :options="countryOptions"
                :placeholder="t('Select country')"
                show-clear
                @change="loadInvoices"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Data Table -->
      <Card>
        <template #title>
          <div class="flex items-center justify-between">
            <span>{{ t('Invoices') }} ({{ totalRecords }} {{ t('total') }})</span>
            <div class="flex items-center gap-2">
              <Button
                icon="pi pi-refresh"
                severity="secondary"
                text
                :loading="loading"
                class="p-button-sm"
                @click="loadInvoices"
              />
            </div>
          </div>
        </template>
        <template #content>
          <DataTable
            v-model:expanded-rows="expandedRows"
            :value="invoices"
            :loading="loading"
            paginator
            :rows="pagination.rows"
            :first="(pagination.page - 1) * pagination.rows"
            :total-records="totalRecords"
            lazy
            :sort-field="sortField"
            :sort-order="sortOrder"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rows-per-page-options="[10, 20, 50]"
            current-page-report-template="Showing {first} to {last} of {totalRecords} entries"
            responsive-layout="scroll"
            @page="onPageChange"
            @sort="onSort"
            @row-expand="onRowExpand"
            @row-collapse="onRowCollapse"
          >
          
            <Column>
              <Checkbox v-model="selectedInvoices" binary />
            </Column>
            
            <Column expander :header-style="{ width: '3rem' }" />

            <Column field="id" :header="t('ID')" sortable style="width: 80px">
              <template #body="{ data }">
                <Tag :value="data.$attributes.id" severity="info" />
              </template>
            </Column>

            <Column field="client.name" :header="t('Name')" sortable>
              <template #body="{ data }">
                <div class="font-medium">
                  {{ data.$attributes.client?.name }}
                </div>
                <div class="text-sm text-surface-500">
                  {{ data.$attributes.client?.email }}
                </div>
              </template>
            </Column>

            <Column field="client.phone" :header="t('Phone')" sortable>
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <i class="pi pi-phone text-surface-500" />
                  {{ data.$attributes.client?.phone }}
                </div>
              </template>
            </Column>

            <Column field="client.city" :header="t('Location')" sortable>
              <template #body="{ data }">
                <div>
                  <div class="font-medium">
                    {{ data.$attributes.client?.city }}, {{ data.$attributes.client?.state }}
                  </div>
                  <div class="text-sm text-surface-500">
                    {{ data.$attributes.client?.country }} {{ data.$attributes.client?.zip }}
                  </div>
                </div>
              </template>
            </Column>

            <Column field="created_at" :header="t('Created')" sortable>
              <template #body="{ data }">
                <div class="text-sm">
                  {{ formatDate(data.$attributes.created_at) }}
                </div>
              </template>
            </Column>

            <Column :header="t('Actions')" style="width: 150px">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button
                    v-tooltip="t('Edit')"
                    icon="pi pi-pencil"
                    severity="warning"
                    text
                    size="small"
                    class="p-button-sm"
                    @click="editInvoice(data)"
                  />
                  <Button
                    v-tooltip="t('Delete')"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    size="small"
                    class="p-button-sm"
                    @click="confirmDelete(data)"
                  />
                </div>
              </template>
            </Column>

            <template #expansion="{ data }">
              <div class="p-4 border-t bg-surface-50 dark:bg-surface-800">
                <TabView @tab-change="event => onTabChange(event, data)">
                  <TabPanel value="products" :header="t('Products')">
                    <div
                      v-if="loadingProducts[data.$attributes.id]"
                      class="flex justify-center p-4"
                    >
                      <i class="pi pi-spin pi-spinner text-2xl" />
                    </div>
                    <DataTable
                      v-else-if="invoiceProducts[data.$attributes.id]?.length"
                      :value="invoiceProducts[data.$attributes.id]"
                      :table-style="{ minWidth: '50rem' }"
                      class="p-datatable-sm"
                    >
                      <Column>
                        <Checkbox v-model="selectedInvoices" binary />
                      </Column>
                      <Column field="id" :header="t('#')" style="width: 60px">
                        <template #body="{ index }">
                          {{ index + 1 }}
                        </template>
                      </Column>
                      <Column :header="t('Product Name')" sortable>
                        <template #body="{ data: productData }">
                          {{ productData.$attributes?.name || productData.name }}
                        </template>
                      </Column>
                      <Column :header="t('Description')" sortable>
                        <template #body="{ data: productData }">
                          <div
                            class="max-w-xs truncate"
                            :title="productData.$attributes?.description || productData.description"
                          >
                            {{ productData.$attributes?.description || productData.description }}
                          </div>
                        </template>
                      </Column>
                      <Column :header="t('SKU')" sortable>
                        <template #body="{ data: productData }">
                          {{ productData.$attributes?.sku || productData.sku }}
                        </template>
                      </Column>
                      <Column :header="t('Quantity')" sortable>
                        <template #body="{ data: productData }">
                          {{
                            productData.$attributes?.pivot?.quantity ||
                            productData.pivot?.quantity ||
                            1
                          }}
                        </template>
                      </Column>
                      <Column :header="t('Unit Price')" sortable>
                        <template #body="{ data: productData }">
                          {{
                            formatCurrency(
                              productData.$attributes?.pivot?.price ||
                                productData.pivot?.price ||
                                productData.$attributes?.price ||
                                productData.price
                            )
                          }}
                        </template>
                      </Column>
                      <Column :header="t('Total')" sortable>
                        <template #body="{ data: productData }">
                          {{
                            formatCurrency(
                              (productData.$attributes?.pivot?.quantity ||
                                productData.pivot?.quantity ||
                                1) *
                                (productData.$attributes?.pivot?.price ||
                                  productData.pivot?.price ||
                                  productData.$attributes?.price ||
                                  productData.price)
                            )
                          }}
                        </template>
                      </Column>
                    </DataTable>
                    <div v-else class="text-center p-4 text-surface-500">
                      {{ t('No products found for this invoice.') }}
                    </div>
                  </TabPanel>
                  <TabPanel value="payments" :header="t('Payments')">
                    <div
                      v-if="loadingPayments[data.$attributes.id]"
                      class="flex justify-center p-4"
                    >
                      <i class="pi pi-spin pi-spinner text-2xl" />
                    </div>
                    <div v-else-if="invoicePayments[data.$attributes.id]?.length">
                      <DataTable
                        :value="invoicePayments[data.$attributes.id]"
                        :table-style="{ minWidth: '50rem' }"
                        class="p-datatable-sm"
                      >
                        <Column field="id" :header="t('#')" style="width: 80px">
                          <template #body="{ data: paymentData }">
                            <Tag
                              :value="paymentData.$attributes?.id || paymentData.id"
                              severity="info"
                            />
                          </template>
                        </Column>
                        <Column :header="t('Amount')" sortable>
                          <template #body="{ data: paymentData }">
                            <div class="font-semibold text-lg">
                              {{
                                formatCurrency(
                                  paymentData.$attributes?.amount || paymentData.amount
                                )
                              }}
                            </div>
                          </template>
                        </Column>
                        <Column :header="t('Payment Method')" sortable>
                          <template #body="{ data: paymentData }">
                            <div class="flex items-center gap-2">
                              <i
                                class="pi"
                                :class="
                                  getPaymentMethodIcon(
                                    paymentData.$attributes?.payment_method ||
                                      paymentData.payment_method
                                  )
                                "
                              />
                              <span class="capitalize">
                                {{
                                  (
                                    paymentData.$attributes?.payment_method ||
                                    paymentData.payment_method
                                  ).replace('_', ' ')
                                }}
                              </span>
                            </div>
                          </template>
                        </Column>
                        <Column :header="t('Status')" sortable>
                          <template #body="{ data: paymentData }">
                            <Tag
                              :value="
                                (
                                  paymentData.$attributes?.status || paymentData.status
                                ).toUpperCase()
                              "
                              :severity="
                                getStatusSeverity(
                                  paymentData.$attributes?.status || paymentData.status
                                )
                              "
                            />
                          </template>
                        </Column>
                        <Column :header="t('Transaction ID')" sortable>
                          <template #body="{ data: paymentData }">
                            <div class="text-sm font-mono text-surface-600 dark:text-surface-400">
                              {{
                                paymentData.$attributes?.transaction_id ||
                                paymentData.transaction_id ||
                                'N/A'
                              }}
                            </div>
                          </template>
                        </Column>
                        <Column :header="t('Paid At')" sortable>
                          <template #body="{ data: paymentData }">
                            <div class="text-sm">
                              {{
                                formatDateTime(
                                  paymentData.$attributes?.paid_at || paymentData.paid_at
                                )
                              }}
                            </div>
                          </template>
                        </Column>
                        <Column :header="t('Notes')">
                          <template #body="{ data: paymentData }">
                            <div
                              class="max-w-xs truncate text-sm text-surface-600 dark:text-surface-400"
                              :title="paymentData.$attributes?.notes || paymentData.notes"
                            >
                              {{ paymentData.$attributes?.notes || paymentData.notes || '-' }}
                            </div>
                          </template>
                        </Column>
                      </DataTable>
                      <div
                        class="mt-4 p-4 bg-surface-100 dark:bg-surface-900 rounded-lg flex justify-between items-center"
                      >
                        <div class="text-lg font-semibold">{{ t('Total Payments:') }}</div>
                        <div class="text-2xl font-bold text-primary">
                          {{ formatCurrency(getTotalPayments(data.$attributes.id)) }}
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center p-8 text-surface-500">
                      <i class="pi pi-credit-card text-4xl mb-4 block" />
                      <p>{{ t('No payments found for this invoice.') }}</p>
                    </div>
                  </TabPanel>
                </TabView>
              </div>
            </template>
          </DataTable>
        </template>
      </Card>
    </div>

    <!-- Delete Confirmation Dialog -->
    <Dialog
      v-model:visible="showDeleteDialog"
      :header="t('Confirm Delete')"
      modal
      :style="{ width: '400px' }"
      append-to="body"
    >
      <div class="flex items-center gap-3 mb-4">
        <i class="pi pi-exclamation-triangle text-orange-500 text-2xl" />
        <span>{{ t('Are you sure you want to delete this invoice?') }}</span>
      </div>
      <div v-if="invoiceToDelete" class="bg-surface-100 dark:bg-surface-800 p-3 rounded">
        <div class="font-medium">
          {{ invoiceToDelete.$attributes.client?.name }}
        </div>
        <div class="text-sm text-surface-500">
          {{ invoiceToDelete.$attributes.client?.email }}
        </div>
      </div>

      <template #footer>
        <Button :label="t('Cancel')" severity="secondary" @click="showDeleteDialog = false" />
        <Button :label="t('Delete')" severity="danger" :loading="deleting" @click="deleteInvoice" />
      </template>
    </Dialog>
  </AppLayout>
</template>
