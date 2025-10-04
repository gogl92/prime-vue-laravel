<template>
    <AppLayout title="Invoices Example - Orion API">
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
                    Invoices Example - Orion API
                </h1>
                <Button
                    label="Add Invoice"
                    icon="pi pi-plus"
                    @click="showCreateDialog = true"
                    severity="success"
                />
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filters -->
            <Card>
                <template #title>Filters</template>
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
                                showClear
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
                                @click="loadInvoices"
                                :loading="loading"
                            />
                        </div>
                    </div>
                </template>
                <template #content>
                    <DataTable
                        :value="invoices"
                        :loading="loading"
                        :paginator="true"
                        :rows="pagination.rows"
                        :totalRecords="totalRecords"
                        :lazy="true"
                        @page="onPageChange"
                        @sort="onSort"
                        :sortField="sortField"
                        :sortOrder="sortOrder"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                        :rowsPerPageOptions="[10, 20, 50]"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
                        responsiveLayout="scroll"
                    >
                        <Column field="id" header="ID" :sortable="true" style="width: 80px">
                            <template #body="{ data }">
                                <Tag :value="data.$attributes.id" severity="info" />
                            </template>
                        </Column>

                        <Column field="name" header="Name" :sortable="true">
                            <template #body="{ data }">
                                <div class="font-medium">{{ data.$attributes.name }}</div>
                                <div class="text-sm text-surface-500">{{ data.$attributes.email }}</div>
                            </template>
                        </Column>

                        <Column field="phone" header="Phone" :sortable="true">
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-phone text-surface-500"></i>
                                    {{ data.$attributes.phone }}
                                </div>
                            </template>
                        </Column>

                        <Column field="city" header="Location" :sortable="true">
                            <template #body="{ data }">
                                <div>
                                    <div class="font-medium">{{ data.$attributes.city }}, {{ data.$attributes.state }}</div>
                                    <div class="text-sm text-surface-500">{{ data.$attributes.country }} {{ data.$attributes.zip }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="created_at" header="Created" :sortable="true">
                            <template #body="{ data }">
                                <div class="text-sm">
                                    {{ formatDate(data.$attributes.created_at) }}
                                </div>
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 150px">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button
                                        icon="pi pi-pencil"
                                        severity="warning"
                                        text
                                        size="small"
                                        @click="editInvoice(data)"
                                        v-tooltip="'Edit'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        size="small"
                                        @click="confirmDelete(data)"
                                        v-tooltip="'Delete'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>

        <!-- Create/Edit Dialog -->
        <Dialog
            v-model:visible="showCreateDialog"
            :header="editingInvoice ? 'Edit Invoice' : 'Create Invoice'"
            :modal="true"
            :style="{ width: '600px' }"
            appendTo="body"
        >
            <form @submit.prevent="saveInvoice" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Name *</label>
                        <InputText
                            v-model="form.name"
                            placeholder="Enter name"
                            :class="{ 'p-invalid': errors.name }"
                        />
                        <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Email *</label>
                        <InputText
                            v-model="form.email"
                            type="email"
                            placeholder="Enter email"
                            :class="{ 'p-invalid': errors.email }"
                        />
                        <small v-if="errors.email" class="text-red-500">{{ errors.email }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Phone *</label>
                        <InputText
                            v-model="form.phone"
                            placeholder="Enter phone"
                            :class="{ 'p-invalid': errors.phone }"
                        />
                        <small v-if="errors.phone" class="text-red-500">{{ errors.phone }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Address *</label>
                        <InputText
                            v-model="form.address"
                            placeholder="Enter address"
                            :class="{ 'p-invalid': errors.address }"
                        />
                        <small v-if="errors.address" class="text-red-500">{{ errors.address }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">City *</label>
                        <InputText
                            v-model="form.city"
                            placeholder="Enter city"
                            :class="{ 'p-invalid': errors.city }"
                        />
                        <small v-if="errors.city" class="text-red-500">{{ errors.city }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">State *</label>
                        <InputText
                            v-model="form.state"
                            placeholder="Enter state"
                            :class="{ 'p-invalid': errors.state }"
                        />
                        <small v-if="errors.state" class="text-red-500">{{ errors.state }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">ZIP Code *</label>
                        <InputText
                            v-model="form.zip"
                            placeholder="Enter ZIP code"
                            :class="{ 'p-invalid': errors.zip }"
                        />
                        <small v-if="errors.zip" class="text-red-500">{{ errors.zip }}</small>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Country *</label>
                        <Dropdown
                            v-model="form.country"
                            :options="countryOptions"
                            placeholder="Select country"
                            :class="{ 'p-invalid': errors.country }"
                        />
                        <small v-if="errors.country" class="text-red-500">{{ errors.country }}</small>
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
            :modal="true"
            :style="{ width: '400px' }"
            appendTo="body"
        >
            <div class="flex items-center gap-3 mb-4">
                <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
                <span>Are you sure you want to delete this invoice?</span>
            </div>
            <div v-if="invoiceToDelete" class="bg-surface-100 dark:bg-surface-800 p-3 rounded">
                <div class="font-medium">{{ invoiceToDelete.$attributes.name }}</div>
                <div class="text-sm text-surface-500">{{ invoiceToDelete.$attributes.email }}</div>
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
                    @click="deleteInvoice"
                    :loading="deleting"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import AppLayout from '@/layouts/AppLayout.vue'
import orionService from '@/services/orion'
import { Invoice } from '@/models/Invoice'
import { debounce } from 'lodash-es'

// Components
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'

// Toast
const toast = useToast()

// State
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const invoices = ref<Invoice[]>([])
const totalRecords = ref(0)

// Pagination
const pagination = reactive({
    page: 1,
    rows: 20
})

// Sorting
const sortField = ref('id')
const sortOrder = ref(1) // 1 for asc, -1 for desc

// Filters
const filters = reactive({
    search: '',
    city: '',
    country: ''
})

// Dialogs
const showCreateDialog = ref(false)
const showDeleteDialog = ref(false)
const editingInvoice = ref<Invoice | null>(null)
const invoiceToDelete = ref<Invoice | null>(null)

// Form
const form = reactive({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    country: ''
})

const errors = reactive<Record<string, string>>({})

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
    'China'
])

// Debounced search
const debouncedSearch = debounce(() => {
    pagination.page = 1
    loadInvoices()
}, 500)

// Methods
const loadInvoices = async () => {
    try {
        loading.value = true

        let query = Invoice.$query()

        // Add search if provided
        if (filters.search) {
            query = query.lookFor(filters.search)
        }

        // Filter by city
        if (filters.city) {
            // exact match filter
            query = query.where('city', filters.city)
        }

        // Filter by country
        if (filters.country) {
            query = query.where('country', filters.country)
        }

        // Sorting
        if (sortField.value) {
            query = query.orderBy(sortField.value, sortOrder.value === 1 ? 'asc' : 'desc')
        }

        // Get the results
        const response = await query.get()
        invoices.value = response
        totalRecords.value = response.length
    } catch (error) {
        console.error('Error loading invoices:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load invoices',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

const onPageChange = (event: any) => {
    pagination.page = event.page + 1
    pagination.rows = event.rows
    loadInvoices()
}

const onSort = (event: any) => {
    sortField.value = event.sortField
    sortOrder.value = event.sortOrder
    loadInvoices()
}

const editInvoice = (invoice: Invoice) => {
    editingInvoice.value = invoice
    Object.assign(form, invoice.$attributes)
    showCreateDialog.value = true
}

const confirmDelete = (invoice: Invoice) => {
    invoiceToDelete.value = invoice
    showDeleteDialog.value = true
}

const deleteInvoice = async () => {
    if (!invoiceToDelete.value) return

    try {
        deleting.value = true
        await invoiceToDelete.value.$destroy()

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Invoice deleted successfully',
            life: 3000
        })

        showDeleteDialog.value = false
        invoiceToDelete.value = null
        loadInvoices()
    } catch (error) {
        console.error('Error deleting invoice:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete invoice',
            life: 3000
        })
    } finally {
        deleting.value = false
    }
}

const saveInvoice = async () => {
    try {
        saving.value = true
        clearErrors()

        if (editingInvoice.value) {
            // Update existing invoice
            Object.assign(editingInvoice.value.$attributes, form)
            await editingInvoice.value.$save()
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Invoice updated successfully',
                life: 3000
            })
        } else {
            // Create new invoice
            const newInvoice = await Invoice.$query().store(form)
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Invoice created successfully',
                life: 3000
            })
        }

        closeDialog()
        loadInvoices()
    } catch (error: any) {
        console.error('Error saving invoice:', error)

        if (error.response?.data?.errors) {
            Object.assign(errors, error.response.data.errors)
        }

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to save invoice',
            life: 3000
        })
    } finally {
        saving.value = false
    }
}

const closeDialog = () => {
    showCreateDialog.value = false
    editingInvoice.value = null
    clearForm()
    clearErrors()
}

const clearForm = () => {
    Object.assign(form, {
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip: '',
        country: ''
    })
}

const clearErrors = () => {
    Object.keys(errors).forEach(key => {
        delete errors[key as keyof typeof errors]
    })
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
    loadInvoices()
})
</script>
