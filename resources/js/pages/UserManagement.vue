<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { User } from '@/models/User';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';

// Components
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';

import { SortDirection } from '@tailflow/laravel-orion/lib/drivers/default/enums/sortDirection';

// Toast & i18n
const { t } = useI18n();
const toast = useToast();

// State
const loading = ref(false);
const deleting = ref(false);
const users = ref<User[]>([]);
const totalRecords = ref(0);

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
});

// Dialogs
const showDeleteDialog = ref(false);
const userToDelete = ref<User | null>(null);

// Debounced search
const debouncedSearch = debounce(() => {
  pagination.page = 1;
  void loadUsers();
}, 500);

// Methods
const loadUsers = async () => {
  try {
    loading.value = true;

    let query = User.$query().with(['currentCompany', 'currentBranch', 'roles']);

    // Add search if provided
    if (filters.search) {
      query = query.lookFor(filters.search);
    }

    // Sorting
    if (sortField.value) {
      query = query.sortBy(
        sortField.value,
        sortOrder.value === 1 ? SortDirection.Asc : SortDirection.Desc
      );
    }

    // Execute the query with pagination
    const response: unknown =
      filters.search || sortField.value
        ? await query.search(pagination.rows, pagination.page)
        : await query.get(pagination.rows, pagination.page);

    // Handle different response structures
    let actualResponse: User[];
    if (Array.isArray(response)) {
      users.value = response as User[];
      actualResponse = response as User[];
    } else if (
      typeof response === 'object' &&
      response !== null &&
      'data' in response &&
      Array.isArray((response as { data: unknown }).data)
    ) {
      const wrappedResponse = response as { data: User[] };
      users.value = wrappedResponse.data;
      actualResponse = wrappedResponse.data;
    } else {
      console.error('Unexpected response structure:', response);
      users.value = [];
      actualResponse = [];
    }

    // Check if Orion provides pagination metadata
    if (actualResponse[0]?.$response?.data?.meta?.total) {
      totalRecords.value = actualResponse[0].$response.data.meta.total;
    } else {
      // Fallback: estimate total records
      if (filters.search) {
        const countQuery = User.$query().lookFor(filters.search);
        const allResults = await countQuery.get();
        totalRecords.value = allResults.length;
      } else {
        if (users.value.length === pagination.rows) {
          totalRecords.value = (pagination.page - 1) * pagination.rows + users.value.length + 1;
        } else {
          totalRecords.value = (pagination.page - 1) * pagination.rows + users.value.length;
        }
      }
    }
  } catch (error) {
    console.error('Error loading users:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load users'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};


const onPageChange = (event: { first: number; rows: number }) => {
  pagination.page = Math.floor(event.first / event.rows) + 1;
  pagination.rows = event.rows;
  void loadUsers();
};

const onSort = (event: {
  sortField: string | ((item: unknown) => string) | undefined;
  sortOrder: number | null | undefined;
}) => {
  sortField.value = typeof event.sortField === 'function' ? undefined : event.sortField;
  sortOrder.value = event.sortOrder ?? 1;
  pagination.page = 1;
  void loadUsers();
};

const createUser = () => {
  router.visit('/users/create');
};

const editUser = (user: User) => {
  router.visit(`/users/${user.$attributes.id}/edit`);
};

const setupFacialRecognition = (user: User) => {
  router.visit(`/users/${user.$attributes.id}/facial-recognition`);
};

const confirmDelete = (user: User) => {
  userToDelete.value = user;
  showDeleteDialog.value = true;
};

const deleteUser = async () => {
  if (!userToDelete.value) return;

  try {
    deleting.value = true;
    await userToDelete.value.$destroy();

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('User deleted successfully'),
      life: 3000,
    });

    showDeleteDialog.value = false;
    userToDelete.value = null;
    void loadUsers();
  } catch (error) {
    console.error('Error deleting user:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to delete user'),
      life: 3000,
    });
  } finally {
    deleting.value = false;
  }
};

const getRoleSeverity = (roleName: string): string => {
  switch (roleName) {
    case 'super_admin':
      return 'danger';
    case 'admin':
      return 'warn';
    case 'user':
      return 'info';
    default:
      return 'secondary';
  }
};

// Lifecycle
onMounted(() => {
  void loadUsers();
});
</script>

<template>
  <AppLayout :title="t('User Management')">
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
          {{ t('User Management') }}
        </h1>
        <Button
          :label="t('Create User')"
          icon="pi pi-plus"
          severity="success"
          class="p-button-success"
          @click="createUser"
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
                :placeholder="t('Search users...')"
                @input="debouncedSearch"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Data Table -->
      <Card>
        <template #title>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span>{{ t('Users') }} ({{ totalRecords }} {{ t('total') }})</span>
            </div>
            <div class="flex items-center gap-2">
              <Button
                icon="pi pi-refresh"
                severity="secondary"
                text
                :loading="loading"
                class="p-button-sm"
                @click="loadUsers"
              />
            </div>
          </div>
        </template>
        <template #content>
          <DataTable
            :value="users"
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
            data-key="$attributes.id"
            @page="onPageChange"
            @sort="onSort"
          >
            <Column field="username" :header="t('Username')" sortable style="min-width: 150px">
              <template #body="{ data }">
                {{ data.$attributes.username }}
              </template>
            </Column>
            <Column field="first_name" :header="t('First Name')" sortable style="min-width: 150px">
              <template #body="{ data }">
                {{ data.$attributes.first_name }}
              </template>
            </Column>
            <Column field="last_name" :header="t('Last Name')" sortable style="min-width: 150px">
              <template #body="{ data }">
                {{ data.$attributes.last_name }}
              </template>
            </Column>
            <Column field="email" :header="t('Email')" sortable style="min-width: 200px">
              <template #body="{ data }">
                {{ data.$attributes.email }}
              </template>
            </Column>
            <Column field="phone" :header="t('Phone')" style="min-width: 150px">
              <template #body="{ data }">
                {{ data.$attributes.phone || '-' }}
              </template>
            </Column>
            <Column :header="t('Roles')" style="min-width: 150px">
              <template #body="{ data }">
                <div class="flex gap-1 flex-wrap">
                  <Tag
                    v-for="role in (data.$attributes.roles || data.$relations?.roles || [])"
                    :key="role.id"
                    :value="role.name"
                    :severity="getRoleSeverity(role.name)"
                  />
                </div>
              </template>
            </Column>
            <Column :header="t('Company')" style="min-width: 150px">
              <template #body="{ data }">
                {{ data.$attributes.currentCompany?.name || data.$relations?.currentCompany?.name || '-' }}
              </template>
            </Column>
            <Column :header="t('Branch')" style="min-width: 150px">
              <template #body="{ data }">
                {{ data.$attributes.currentBranch?.name || data.$relations?.currentBranch?.name || '-' }}
              </template>
            </Column>
            <Column :header="t('Actions')" style="width: 150px">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button
                    v-tooltip="t('Facial Recognition')"
                    icon="pi pi-user"
                    severity="info"
                    text
                    size="small"
                    class="p-button-sm"
                    @click="setupFacialRecognition(data)"
                  />
                  <Button
                    v-tooltip="t('Edit')"
                    icon="pi pi-pencil"
                    severity="warning"
                    text
                    size="small"
                    class="p-button-sm"
                    @click="editUser(data)"
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
        <span v-if="userToDelete">
          {{ t('Are you sure you want to delete user') }}
          <b>{{ userToDelete.$attributes.username }}</b>?
        </span>
      </div>
      <div v-if="userToDelete" class="bg-surface-100 dark:bg-surface-800 p-3 rounded">
        <div class="font-medium">
          {{ userToDelete.$attributes.username }} ({{ userToDelete.$attributes.email }})
        </div>
        <div class="text-sm text-surface-500">
          {{ userToDelete.$attributes.first_name }} {{ userToDelete.$attributes.last_name }}
        </div>
      </div>

      <template #footer>
        <Button :label="t('Cancel')" severity="secondary" @click="showDeleteDialog = false" />
        <Button :label="t('Delete')" severity="danger" :loading="deleting" @click="deleteUser" />
      </template>
    </Dialog>
  </AppLayout>
</template>
