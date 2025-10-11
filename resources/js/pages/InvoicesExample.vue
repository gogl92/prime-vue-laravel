<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/layouts/AppLayout.vue';
import { Invoice } from '@/models/Invoice';
import type { Product } from '@/models/Product';
import { debounce } from 'lodash-es';

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

// Toast
const toast = useToast();

// State
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const invoices = ref<Invoice[]>([]);
const totalRecords = ref(0);
const expandedRows = ref({});
const invoiceProducts = ref<Record<number, Product[]>>({});
const loadingProducts = ref<Record<number, boolean>>({});

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
const showCreateDialog = ref(false);
const showDeleteDialog = ref(false);
const editingInvoice = ref<Invoice | null>(null);
const invoiceToDelete = ref<Invoice | null>(null);

// Form
const form = reactive({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    country: '',
});

const errors = reactive<Record<string, string>>({});

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

        let query = Invoice.$query();

        // Add search if provided
        if (filters.search) {
            query = query.lookFor(filters.search);
        }

        // Filter by city
        if (filters.city) {
            query = query.filter('city', FilterOperator.Equal, filters.city);
        }

        // Filter by country
        if (filters.country) {
            query = query.filter('country', FilterOperator.Equal, filters.country);
        }

        // Sorting
        if (sortField.value) {
            query = query.sortBy(sortField.value, sortOrder.value === 1 ? SortDirection.Asc : SortDirection.Desc);
        }

        // Execute the query with pagination
        // get() and search() methods accept limit and page as parameters
        // Use search() when filtering OR sorting is applied, otherwise use get()
        let response;
        if (filters.search || filters.city || filters.country || sortField.value) {
            response = await query.search(pagination.rows, pagination.page);
        } else {
            response = await query.get(pagination.rows, pagination.page);
        }

        // Get the results
        invoices.value = response;

        // Check if Orion provides pagination metadata in the response
        // Orion returns pagination info in response.$response.meta
        if (response.$response?.meta?.total) {
            // Use Orion's pagination metadata
            totalRecords.value = response.$response.meta.total;
        } else {
            // Fallback: get total count only when filtering
            if (filters.search || filters.city || filters.country) {
                // When filtering, we need accurate count
                let countQuery = Invoice.$query();
                if (filters.search) {
                    countQuery = countQuery.lookFor(filters.search);
                }
                if (filters.city) {
                    countQuery = countQuery.filter('city', FilterOperator.Equal, filters.city);
                }
                if (filters.country) {
                    countQuery = countQuery.filter('country', FilterOperator.Equal, filters.country);
                }

                // Get count without pagination
                const allResults = await countQuery.get();
                totalRecords.value = allResults.length;
            } else {
                // For unfiltered results, estimate based on current response
                // If current page is full, there might be more records
                if (response.length === pagination.rows) {
                    // Estimate: assume there are more records
                    totalRecords.value = (pagination.page - 1) * pagination.rows + response.length + 1;
                } else {
                    // Current page is not full, this is likely the last page
                    totalRecords.value = (pagination.page - 1) * pagination.rows + response.length;
                }
            }
        }
    } catch (error) {
        console.error('Error loading invoices:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load invoices',
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

const onSort = (event: { sortField: string | undefined; sortOrder: number }) => {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    pagination.page = 1; // Reset to first page when sorting
    void loadInvoices();
};

const editInvoice = (invoice: Invoice) => {
    editingInvoice.value = invoice;
    Object.assign(form, invoice.$attributes);
    showCreateDialog.value = true;
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
            summary: 'Success',
            detail: 'Invoice deleted successfully',
            life: 3000,
        });

        showDeleteDialog.value = false;
        invoiceToDelete.value = null;
        void loadInvoices();
    } catch (error) {
        console.error('Error deleting invoice:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete invoice',
            life: 3000,
        });
    } finally {
        deleting.value = false;
    }
};

const saveInvoice = async () => {
    try {
        saving.value = true;
        clearErrors();

        if (editingInvoice.value) {
            // Update existing invoice
            Object.assign(editingInvoice.value.$attributes, form);
            await editingInvoice.value.$save();
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Invoice updated successfully',
                life: 3000,
            });
        } else {
            // Create new invoice
            const newInvoice = await Invoice.$query().store(form);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Invoice ' + newInvoice.$attributes.id + ' created successfully',
                life: 3000,
            });
        }

        closeDialog();
        void loadInvoices();
    } catch (error: unknown) {
        console.error('Error saving invoice:', error);

        const errorObj = error as { response?: { data?: { errors?: Record<string, string>; message?: string } } };
        if (errorObj.response?.data?.errors) {
            Object.assign(errors, errorObj.response.data.errors);
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: errorObj.response?.data?.message ?? 'Failed to save invoice',
            life: 3000,
        });
    } finally {
        saving.value = false;
    }
};

const closeDialog = () => {
    showCreateDialog.value = false;
    editingInvoice.value = null;
    clearForm();
    clearErrors();
};

const clearForm = () => {
    Object.assign(form, {
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip: '',
        country: '',
    });
};

const clearErrors = () => {
    Object.keys(errors).forEach(key => {
        delete errors[key as keyof typeof errors];
    });
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

const onRowExpand = async (event: { data: Invoice }) => {
    const invoiceId = event.data.$attributes.id;

    if (!invoiceId) return;

    // Check if products are already loaded
    if (invoiceProducts.value[invoiceId]) {
        return;
    }

    try {
        loadingProducts.value[invoiceId] = true;

        // Load products for this invoice using Orion relationship
        // Using the belongsToMany relationship endpoint
        const products = await Invoice.$query()
            .related('products')
            .get(undefined, undefined, { parentId: invoiceId });

        invoiceProducts.value[invoiceId] = products;
    } catch (error) {
        console.error('Error loading products:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load products',
            life: 3000,
        });
    } finally {
        loadingProducts.value[invoiceId] = false;
    }
};

const onRowCollapse = (_event: { data: Invoice }) => {
    // Optionally clear products when row collapses to save memory
    // const invoiceId = event.data.$attributes.id;
    // delete invoiceProducts.value[invoiceId];
};

// Lifecycle
onMounted(() => {
    void loadInvoices();
});
</script>

<template>
    <AppLayout title="Invoices Example - Orion API">
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                    Invoices Example - Orion API
                </h1>
                <Button
                    label="Add Invoice"
                    icon="pi pi-plus"
                    severity="success"
                    class="p-button-success"
                    @click="showCreateDialog = true"
                />
            </div>
            <!-- Filters -->
            <Card>
                <template #title>
                    Filters
                </template>
                <template #content>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Search</label>
                            <InputText
                                v-model="filters.search"
                                placeholder="Search invoices..."
                                @input="debouncedSearch"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">City</label>
                            <InputText
                                v-model="filters.city"
                                placeholder="Filter by city..."
                                @input="debouncedSearch"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Country</label>
                            <Dropdown
                                v-model="filters.country"
                                :options="countryOptions"
                                placeholder="Select country"
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
                        <span>Invoices ({{ totalRecords }} total)</span>
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
                        :value="invoices"
                        :loading="loading"
                        v-model:expandedRows="expandedRows"
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
                        <Column
                            :expander="true"
                            :header-style="'width: 3rem'"
                        />

                        <Column
                            field="id"
                            header="ID"
                            sortable
                            style="width: 80px"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :value="data.$attributes.id"
                                    severity="info"
                                />
                            </template>
                        </Column>

                        <Column
                            field="name"
                            header="Name"
                            sortable
                        >
                            <template #body="{ data }">
                                <div class="font-medium">
                                    {{ data.$attributes.name }}
                                </div>
                                <div class="text-sm text-surface-500">
                                    {{ data.$attributes.email }}
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="phone"
                            header="Phone"
                            sortable
                        >
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-phone text-surface-500" />
                                    {{ data.$attributes.phone }}
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="city"
                            header="Location"
                            sortable
                        >
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium">
                                        {{ data.$attributes.city }}, {{ data.$attributes.state }}
                                    </div>
                                    <div class="text-sm text-surface-500">
                                        {{ data.$attributes.country }} {{ data.$attributes.zip }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="created_at"
                            header="Created"
                            sortable
                        >
                            <template #body="{ data }">
                                <div class="text-sm">
                                    {{ formatDate(data.$attributes.created_at) }}
                                </div>
                            </template>
                        </Column>

                        <Column
                            header="Actions"
                            style="width: 150px"
                        >
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button
                                        v-tooltip="'Edit'"
                                        icon="pi pi-pencil"
                                        severity="warning"
                                        text
                                        size="small"
                                        class="p-button-sm"
                                        @click="editInvoice(data)"
                                    />
                                    <Button
                                        v-tooltip="'Delete'"
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
                                <TabView>
                                    <TabPanel
                                        value="products"
                                        header="Products"
                                    >
                                        <div v-if="loadingProducts[data.$attributes.id]" class="flex justify-center p-4">
                                            <i class="pi pi-spin pi-spinner text-2xl"></i>
                                        </div>
                                        <DataTable
                                            v-else-if="invoiceProducts[data.$attributes.id]?.length"
                                            :value="invoiceProducts[data.$attributes.id]"
                                            tableStyle="min-width: 50rem"
                                            class="p-datatable-sm"
                                        >
                                            <Column field="id" header="#" style="width: 60px">
                                                <template #body="{ index }">
                                                    {{ index + 1 }}
                                                </template>
                                            </Column>
                                            <Column field="name" header="Product Name" sortable></Column>
                                            <Column field="description" header="Description" sortable>
                                                <template #body="{ data }">
                                                    <div class="max-w-xs truncate" :title="data.$attributes.description">
                                                        {{ data.$attributes.description }}
                                                    </div>
                                                </template>
                                            </Column>
                                            <Column field="sku" header="SKU" sortable></Column>
                                            <Column field="pivot.quantity" header="Quantity" sortable>
                                                <template #body="{ data }">
                                                    {{ data.$attributes.pivot?.quantity || 1 }}
                                                </template>
                                            </Column>
                                            <Column field="pivot.price" header="Unit Price" sortable>
                                                <template #body="{ data }">
                                                    {{ formatCurrency(data.$attributes.pivot?.price || data.$attributes.price) }}
                                                </template>
                                            </Column>
                                            <Column header="Total" sortable>
                                                <template #body="{ data }">
                                                    {{ formatCurrency((data.$attributes.pivot?.quantity || 1) * (data.$attributes.pivot?.price || data.$attributes.price)) }}
                                                </template>
                                            </Column>
                                        </DataTable>
                                        <div v-else class="text-center p-4 text-surface-500">
                                            No products found for this invoice.
                                        </div>
                                    </TabPanel>
                                    <TabPanel
                                        value="payments"
                                        header="Payments"
                                    >
                                        <div class="text-center p-8 text-surface-500">
                                            <i class="pi pi-credit-card text-4xl mb-4"></i>
                                            <p>Payments functionality will be implemented here.</p>
                                        </div>
                                    </TabPanel>
                                </TabView>
                            </div>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Create/Edit Dialog -->
        <Dialog
            v-model:visible="showCreateDialog"
            :header="editingInvoice ? 'Edit Invoice' : 'Create Invoice'"
            modal
            :style="{ width: '600px' }"
            append-to="body"
        >
            <form
                class="space-y-4"
                @submit.prevent="saveInvoice"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Name *</label>
                        <InputText
                            v-model="form.name"
                            placeholder="Enter name"
                            :class="{ 'p-invalid': errors.name }"
                        />
                        <small
                            v-if="errors.name"
                            class="text-red-500"
                        >{{ errors.name }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Email *</label>
                        <InputText
                            v-model="form.email"
                            type="email"
                            placeholder="Enter email"
                            :class="{ 'p-invalid': errors.email }"
                        />
                        <small
                            v-if="errors.email"
                            class="text-red-500"
                        >{{ errors.email }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Phone *</label>
                        <InputText
                            v-model="form.phone"
                            placeholder="Enter phone"
                            :class="{ 'p-invalid': errors.phone }"
                        />
                        <small
                            v-if="errors.phone"
                            class="text-red-500"
                        >{{ errors.phone }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Address *</label>
                        <InputText
                            v-model="form.address"
                            placeholder="Enter address"
                            :class="{ 'p-invalid': errors.address }"
                        />
                        <small
                            v-if="errors.address"
                            class="text-red-500"
                        >{{ errors.address }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">City *</label>
                        <InputText
                            v-model="form.city"
                            placeholder="Enter city"
                            :class="{ 'p-invalid': errors.city }"
                        />
                        <small
                            v-if="errors.city"
                            class="text-red-500"
                        >{{ errors.city }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">State *</label>
                        <InputText
                            v-model="form.state"
                            placeholder="Enter state"
                            :class="{ 'p-invalid': errors.state }"
                        />
                        <small
                            v-if="errors.state"
                            class="text-red-500"
                        >{{ errors.state }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">ZIP Code *</label>
                        <InputText
                            v-model="form.zip"
                            placeholder="Enter ZIP code"
                            :class="{ 'p-invalid': errors.zip }"
                        />
                        <small
                            v-if="errors.zip"
                            class="text-red-500"
                        >{{ errors.zip }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Country *</label>
                        <Dropdown
                            v-model="form.country"
                            :options="countryOptions"
                            placeholder="Select country"
                            :class="{ 'p-invalid': errors.country }"
                        />
                        <small
                            v-if="errors.country"
                            class="text-red-500"
                        >{{ errors.country }}</small>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <Button
                        label="Cancel"
                        severity="secondary"
                        @click="closeDialog"
                    />
                    <Button
                        type="submit"
                        :label="editingInvoice ? 'Update' : 'Create'"
                        :loading="saving"
                    />
                </div>
            </form>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            v-model:visible="showDeleteDialog"
            header="Confirm Delete"
            modal
            :style="{ width: '400px' }"
            append-to="body"
        >
            <div class="flex items-center gap-3 mb-4">
                <i class="pi pi-exclamation-triangle text-orange-500 text-2xl" />
                <span>Are you sure you want to delete this invoice?</span>
            </div>
            <div
                v-if="invoiceToDelete"
                class="bg-surface-100 dark:bg-surface-800 p-3 rounded"
            >
                <div class="font-medium">
                    {{ invoiceToDelete.$attributes.name }}
                </div>
                <div class="text-sm text-surface-500">
                    {{ invoiceToDelete.$attributes.email }}
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    @click="showDeleteDialog = false"
                />
                <Button
                    label="Delete"
                    severity="danger"
                    :loading="deleting"
                    @click="deleteInvoice"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
