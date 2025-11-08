<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/layouts/AppLayout.vue';
import { User } from '@/models/User';
import { Role } from '@/models/Role';
import { Company } from '@/models/Company';
import { Branch } from '@/models/Branch';
import { debounce } from 'lodash-es';
import { useI18n } from 'vue-i18n';
import UserFormDialog from '@/components/UserFormDialog.vue';

// Components
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

import { SortDirection } from '@tailflow/laravel-orion/lib/drivers/default/enums/sortDirection';

// Toast & i18n
const { t } = useI18n();
const toast = useToast();

// State
const loading = ref(false);
const deleting = ref(false);
const users = ref<User[]>([]);
const totalRecords = ref(0);
const roles = ref<Role[]>([]);
const companies = ref<Company[]>([]);
const branches = ref<Branch[]>([]);

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
const showUserDialog = ref(false);
const userToDelete = ref<User | null>(null);
const userToEdit = ref<User | null>(null);

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

    // Handle different response structures and extract attributes
    let actualResponse: User[];
    if (Array.isArray(response)) {
      users.value = response.map((user: any) => user.$attributes || user);
      actualResponse = response as User[];
    } else if (
      typeof response === 'object' &&
      response !== null &&
      'data' in response &&
      Array.isArray((response as { data: unknown }).data)
    ) {
      const wrappedResponse = response as { data: any[] };
      users.value = wrappedResponse.data.map((user: any) => user.$attributes || user);
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

const loadRoles = async () => {
  try {
    const response = await Role.$query().get();
    roles.value = Array.isArray(response)
      ? response.map((role: any) => role.$attributes || role)
      : [];
  } catch (error) {
    console.error('Error loading roles:', error);
  }
};

const loadCompanies = async () => {
  try {
    const response = await Company.$query().get();
    companies.value = Array.isArray(response)
      ? response.map((company: any) => company.$attributes || company)
      : [];
  } catch (error) {
    console.error('Error loading companies:', error);
  }
};

const loadBranches = async () => {
  try {
    const response = await Branch.$query().get();
    branches.value = Array.isArray(response)
      ? response.map((branch: any) => branch.$attributes || branch)
      : [];
  } catch (error) {
    console.error('Error loading branches:', error);
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
  userToEdit.value = null;
  showUserDialog.value = true;
};

const editUser = (user: User) => {
  userToEdit.value = user;
  showUserDialog.value = true;
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

const onUserSaved = () => {
  showUserDialog.value = false;
  void loadUsers();
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
  void loadRoles();
  void loadCompanies();
  void loadBranches();
});
</script>

<template>
  <AppLayout :title="t('User Management')">
    <div class="container mx-auto px-4 py-8">
      <Card>
        <template #title>
          <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold">{{ t('User Management') }}</h1>
            <Button
              :label="t('Create User')"
              icon="pi pi-plus"
              severity="success"
              @click="createUser"
            />
          </div>
        </template>
        <template #content>
          <!-- Search -->
          <div class="mb-4">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText
                v-model="filters.search"
                :placeholder="t('Search users...')"
                class="w-full"
                @input="debouncedSearch"
              />
            </IconField>
          </div>

          <!-- DataTable -->
          <DataTable
            :value="users"
            :loading="loading"
            :total-records="totalRecords"
            :rows="pagination.rows"
            :first="(pagination.page - 1) * pagination.rows"
            lazy
            paginator
            data-key="id"
            striped-rows
            :rows-per-page-options="[5, 10, 20, 50]"
            :sort-field="sortField"
            :sort-order="sortOrder"
            @page="onPageChange"
            @sort="onSort"
          >
            <Column field="username" :header="t('Username')" sortable />
            <Column field="first_name" :header="t('First Name')" sortable />
            <Column field="last_name" :header="t('Last Name')" sortable />
            <Column field="email" :header="t('Email')" sortable />
            <Column field="phone" :header="t('Phone')" />
            <Column :header="t('Roles')">
              <template #body="{ data }">
                <div class="flex gap-1 flex-wrap">
                  <Tag
                    v-for="role in data.roles"
                    :key="role.id"
                    :value="role.name"
                    :severity="getRoleSeverity(role.name)"
                  />
                </div>
              </template>
            </Column>
            <Column field="currentCompany.name" :header="t('Company')" />
            <Column field="currentBranch.name" :header="t('Branch')" />
            <Column :header="t('Actions')" style="min-width: 12rem">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-pencil"
                    severity="info"
                    size="small"
                    @click="editUser(data)"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    size="small"
                    @click="confirmDelete(data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Delete Confirmation Dialog -->
      <Dialog
        v-model:visible="showDeleteDialog"
        :header="t('Confirm Delete')"
        modal
        closable
        :style="{ width: '450px' }"
      >
        <div class="flex items-center gap-3">
          <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
          <span v-if="userToDelete">
            {{ t('Are you sure you want to delete user') }}
            <b>{{ userToDelete.username }}</b>?
          </span>
        </div>
        <template #footer>
          <Button
            :label="t('Cancel')"
            icon="pi pi-times"
            severity="secondary"
            @click="showDeleteDialog = false"
          />
          <Button
            :label="t('Delete')"
            icon="pi pi-check"
            severity="danger"
            :loading="deleting"
            @click="deleteUser"
          />
        </template>
      </Dialog>

      <!-- User Form Dialog -->
      <UserFormDialog
        v-model:visible="showUserDialog"
        :user="userToEdit"
        :roles="roles"
        :companies="companies"
        :branches="branches"
        @saved="onUserSaved"
      />
    </div>
  </AppLayout>
</template>
