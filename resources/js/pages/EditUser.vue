<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { User } from '@/models/User';
import { Role } from '@/models/Role';
import { Company } from '@/models/Company';
import { Branch } from '@/models/Branch';
import { useI18n } from 'vue-i18n';

// Components
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Password from 'primevue/password';

// Props
const props = defineProps<{
  userId: number | string;
}>();

// Toast & i18n
const { t } = useI18n();
const toast = useToast();

// State
const loading = ref(false);
const saving = ref(false);
const user = ref<User | null>(null);
const roles = ref<Role[]>([]);
const companies = ref<Company[]>([]);
const branches = ref<Branch[]>([]);

// User form
const userForm = reactive({
  username: '',
  first_name: '',
  last_name: '',
  second_last_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  role_ids: [] as number[],
  current_company_id: null as number | null,
  current_branch_id: null as number | null,
});

// Load user data
const loadUser = async () => {
  try {
    loading.value = true;
    const loadedUser = await User.$query().find(props.userId);
    user.value = loadedUser;

    // Populate form
    if (loadedUser.$attributes) {
      userForm.username = loadedUser.$attributes.username;
      userForm.first_name = loadedUser.$attributes.first_name;
      userForm.last_name = loadedUser.$attributes.last_name;
      userForm.second_last_name = loadedUser.$attributes.second_last_name || '';
      userForm.email = loadedUser.$attributes.email;
      userForm.phone = loadedUser.$attributes.phone || '';
      userForm.current_company_id = loadedUser.$attributes.current_company_id || null;
      userForm.current_branch_id = loadedUser.$attributes.current_branch_id || null;

      // Load roles
      const userRoles = loadedUser.$attributes.roles || loadedUser.$relations?.roles || [];
      userForm.role_ids = userRoles.map((role: any) => role.id || role.$attributes?.id);
    }
  } catch (error) {
    console.error('Error loading user:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to load user'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

// Load data
const loadRoles = async () => {
  try {
    const response = await Role.$query().get();
    roles.value = Array.isArray(response) ? response : [];
  } catch (error) {
    console.error('Error loading roles:', error);
  }
};

const loadCompanies = async () => {
  try {
    const response = await Company.$query().get();
    companies.value = Array.isArray(response) ? response : [];
  } catch (error) {
    console.error('Error loading companies:', error);
  }
};

const loadBranches = async () => {
  try {
    const response = await Branch.$query().get();
    branches.value = Array.isArray(response) ? response : [];
  } catch (error) {
    console.error('Error loading branches:', error);
  }
};

// Actions
const updateUser = async () => {
  if (!user.value) return;

  try {
    saving.value = true;

    // Prepare update data
    const updateData: any = {
      username: userForm.username,
      first_name: userForm.first_name,
      last_name: userForm.last_name,
      second_last_name: userForm.second_last_name,
      email: userForm.email,
      phone: userForm.phone,
      current_company_id: userForm.current_company_id,
      current_branch_id: userForm.current_branch_id,
    };

    // Only include password if it was changed
    if (userForm.password) {
      updateData.password = userForm.password;
      updateData.password_confirmation = userForm.password_confirmation;
    }

    // Update user using Orion
    await user.value.$save(updateData);

    // Update roles if any
    if (userForm.role_ids.length > 0) {
      // TODO: Implement role sync via API
      console.log('Syncing roles:', userForm.role_ids);
    }

    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('User updated successfully'),
      life: 3000,
    });

    // Navigate back to user management
    router.visit('/users');
  } catch (error) {
    console.error('Error updating user:', error);
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to update user'),
      life: 3000,
    });
  } finally {
    saving.value = false;
  }
};

const cancel = () => {
  router.visit('/users');
};

// Lifecycle
onMounted(() => {
  void loadUser();
  void loadRoles();
  void loadCompanies();
  void loadBranches();
});
</script>

<template>
  <AppLayout :title="t('Edit User')">
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-surface-900 dark:text-surface-0">
          {{ t('Edit User') }}
          <span v-if="user" class="text-lg font-normal text-surface-500">
            - {{ user.$attributes.username }}
          </span>
        </h1>
        <div class="flex gap-2">
          <Button
            :label="t('Cancel')"
            icon="pi pi-times"
            severity="secondary"
            @click="cancel"
          />
          <Button
            :label="t('Update')"
            icon="pi pi-check"
            severity="success"
            :loading="saving"
            :disabled="loading"
            @click="updateUser"
          />
        </div>
      </div>

      <!-- Loading State -->
      <Card v-if="loading">
        <template #content>
          <div class="flex justify-center items-center py-8">
            <i class="pi pi-spin pi-spinner text-4xl" />
          </div>
        </template>
      </Card>

      <!-- User Form -->
      <Card v-else>
        <template #title>{{ t('User Information') }}</template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Username -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Username') }} <span class="text-red-500">*</span>
              </label>
              <InputText
                v-model="userForm.username"
                :placeholder="t('Enter username')"
                class="w-full"
              />
            </div>

            <!-- Email -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Email') }} <span class="text-red-500">*</span>
              </label>
              <InputText
                v-model="userForm.email"
                type="email"
                :placeholder="t('Enter email')"
                class="w-full"
              />
            </div>

            <!-- First Name -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('First Name') }} <span class="text-red-500">*</span>
              </label>
              <InputText
                v-model="userForm.first_name"
                :placeholder="t('Enter first name')"
                class="w-full"
              />
            </div>

            <!-- Last Name -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Last Name') }} <span class="text-red-500">*</span>
              </label>
              <InputText
                v-model="userForm.last_name"
                :placeholder="t('Enter last name')"
                class="w-full"
              />
            </div>

            <!-- Second Last Name -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Second Last Name') }}
              </label>
              <InputText
                v-model="userForm.second_last_name"
                :placeholder="t('Enter second last name')"
                class="w-full"
              />
            </div>

            <!-- Phone -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Phone') }}
              </label>
              <InputText
                v-model="userForm.phone"
                :placeholder="t('Enter phone number')"
                class="w-full"
              />
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Password') }}
                <span class="text-surface-500 text-xs">({{ t('Leave empty to keep current') }})</span>
              </label>
              <Password
                v-model="userForm.password"
                :placeholder="t('Enter new password')"
                toggle-mask
                class="w-full"
                :input-class="'w-full'"
              />
            </div>

            <!-- Password Confirmation -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Confirm Password') }}
              </label>
              <Password
                v-model="userForm.password_confirmation"
                :placeholder="t('Confirm new password')"
                toggle-mask
                :feedback="false"
                class="w-full"
                :input-class="'w-full'"
              />
            </div>

            <!-- Roles -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Roles') }}
              </label>
              <MultiSelect
                v-model="userForm.role_ids"
                :options="roles"
                option-label="name"
                option-value="$attributes.id"
                :placeholder="t('Select roles')"
                class="w-full"
              />
            </div>

            <!-- Company -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Company') }}
              </label>
              <Dropdown
                v-model="userForm.current_company_id"
                :options="companies"
                option-label="$attributes.name"
                option-value="$attributes.id"
                :placeholder="t('Select company')"
                class="w-full"
                show-clear
              />
            </div>

            <!-- Branch -->
            <div>
              <label class="block text-sm font-medium mb-2">
                {{ t('Branch') }}
              </label>
              <Dropdown
                v-model="userForm.current_branch_id"
                :options="branches"
                option-label="$attributes.name"
                option-value="$attributes.id"
                :placeholder="t('Select branch')"
                class="w-full"
                show-clear
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>

