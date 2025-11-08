<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { User } from '@/models/User';
import type { Role } from '@/models/Role';
import type { Company } from '@/models/Company';
import type { Branch } from '@/models/Branch';
import { useI18n } from 'vue-i18n';

// Components
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Button from 'primevue/button';
import FloatLabel from 'primevue/floatlabel';

// Props
interface Props {
  visible: boolean;
  user?: User | null;
  roles: Role[];
  companies: Company[];
  branches: Branch[];
}

const props = defineProps<Props>();

// Emits
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void;
  (e: 'saved'): void;
}>();

// Toast & i18n
const { t } = useI18n();
const toast = useToast();

// State
const loading = ref(false);
const formData = ref({
  first_name: '',
  last_name: '',
  second_last_name: '',
  username: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
  current_company_id: null as number | null,
  current_branch_id: null as number | null,
  roles: [] as string[],
});

const errors = ref<Record<string, string>>({});

// Computed
const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value),
});

const isEditMode = computed(() => !!props.user);

const dialogTitle = computed(() =>
  isEditMode.value ? t('Edit User') : t('Create User')
);

const roleOptions = computed(() =>
  props.roles.map((role) => ({
    label: role.name,
    value: role.name,
  }))
);

const companyOptions = computed(() =>
  props.companies.map((company) => ({
    label: company.name,
    value: company.id,
  }))
);

const branchOptions = computed(() =>
  props.branches.map((branch) => ({
    label: branch.name,
    value: branch.id,
  }))
);

// Methods
const resetForm = () => {
  formData.value = {
    first_name: '',
    last_name: '',
    second_last_name: '',
    username: '',
    phone: '',
    email: '',
    password: '',
    password_confirmation: '',
    current_company_id: null,
    current_branch_id: null,
    roles: [],
  };
  errors.value = {};
};

// Watch for user changes to populate form
watch(
  () => props.user,
  (user) => {
    if (user) {
      formData.value = {
        first_name: user.first_name ?? '',
        last_name: user.last_name ?? '',
        second_last_name: user.second_last_name ?? '',
        username: user.username ?? '',
        phone: user.phone ?? '',
        email: user.email ?? '',
        password: '',
        password_confirmation: '',
        current_company_id: user.current_company_id ?? null,
        current_branch_id: user.current_branch_id ?? null,
        roles: user.roles ? user.roles.map((role: { name: string }) => role.name) : [],
      };
    } else {
      resetForm();
    }
    errors.value = {};
  },
  { immediate: true }
);

const validateForm = (): boolean => {
  errors.value = {};
  let isValid = true;

  if (!formData.value.first_name) {
    errors.value.first_name = t('First name is required');
    isValid = false;
  }

  if (!formData.value.last_name) {
    errors.value.last_name = t('Last name is required');
    isValid = false;
  }

  if (!formData.value.username) {
    errors.value.username = t('Username is required');
    isValid = false;
  }

  if (!formData.value.email) {
    errors.value.email = t('Email is required');
    isValid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.value.email)) {
    errors.value.email = t('Invalid email format');
    isValid = false;
  }

  if (!isEditMode.value) {
    // Password is required when creating a new user
    if (!formData.value.password) {
      errors.value.password = t('Password is required');
      isValid = false;
    } else if (formData.value.password.length < 8) {
      errors.value.password = t('Password must be at least 8 characters');
      isValid = false;
    }

    if (formData.value.password !== formData.value.password_confirmation) {
      errors.value.password_confirmation = t('Passwords do not match');
      isValid = false;
    }
  } else {
    // When editing, only validate password if provided
    if (formData.value.password) {
      if (formData.value.password.length < 8) {
        errors.value.password = t('Password must be at least 8 characters');
        isValid = false;
      }

      if (formData.value.password !== formData.value.password_confirmation) {
        errors.value.password_confirmation = t('Passwords do not match');
        isValid = false;
      }
    }
  }

  return isValid;
};

const saveUser = async () => {
  if (!validateForm()) {
    return;
  }

  try {
    loading.value = true;

    // Prepare data to send
    const dataToSend: Record<string, unknown> = {
      first_name: formData.value.first_name,
      last_name: formData.value.last_name,
      second_last_name: formData.value.second_last_name || null,
      username: formData.value.username,
      phone: formData.value.phone || null,
      email: formData.value.email,
      current_company_id: formData.value.current_company_id,
      current_branch_id: formData.value.current_branch_id,
      roles: formData.value.roles,
    };

    // Only include password if provided
    if (formData.value.password) {
      dataToSend.password = formData.value.password;
      dataToSend.password_confirmation = formData.value.password_confirmation;
    }

    if (isEditMode.value && props.user) {
      // Update existing user
      const user = props.user;
      Object.assign(user, dataToSend);
      await user.$save();

      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('User updated successfully'),
        life: 3000,
      });
    } else {
      // Create new user
      const user = new User(dataToSend);
      await user.$save();

      toast.add({
        severity: 'success',
        summary: t('Success'),
        detail: t('User created successfully'),
        life: 3000,
      });
    }

    resetForm();
    emit('saved');
  } catch (error: unknown) {
    console.error('Error saving user:', error);

    // Handle validation errors from API
    if (error && typeof error === 'object' && 'response' in error) {
      const response = error.response as {
        data?: { errors?: Record<string, string[]> };
      };
      if (response.data?.errors) {
        Object.keys(response.data.errors).forEach((key) => {
          const errorMessages = response.data.errors?.[key];
          if (errorMessages) {
            errors.value[key] = errorMessages[0];
          }
        });
      }
    }

    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to save user'),
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

const closeDialog = () => {
  resetForm();
  dialogVisible.value = false;
};
</script>

<template>
  <Dialog
    v-model:visible="dialogVisible"
    :header="dialogTitle"
    :modal="true"
    :closable="true"
    :style="{ width: '800px' }"
    @hide="resetForm"
  >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
      <!-- First Name -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <InputText
            id="first_name"
            v-model="formData.first_name"
            class="w-full"
            :class="{ 'p-invalid': errors.first_name }"
          />
          <label for="first_name">{{ t('First Name') }} *</label>
        </FloatLabel>
        <small v-if="errors.first_name" class="text-red-500">{{
          errors.first_name
        }}</small>
      </div>

      <!-- Last Name -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <InputText
            id="last_name"
            v-model="formData.last_name"
            class="w-full"
            :class="{ 'p-invalid': errors.last_name }"
          />
          <label for="last_name">{{ t('Last Name') }} *</label>
        </FloatLabel>
        <small v-if="errors.last_name" class="text-red-500">{{
          errors.last_name
        }}</small>
      </div>

      <!-- Second Last Name -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <InputText
            id="second_last_name"
            v-model="formData.second_last_name"
            class="w-full"
          />
          <label for="second_last_name">{{ t('Second Last Name') }}</label>
        </FloatLabel>
      </div>

      <!-- Username -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <InputText
            id="username"
            v-model="formData.username"
            class="w-full"
            :class="{ 'p-invalid': errors.username }"
          />
          <label for="username">{{ t('Username') }} *</label>
        </FloatLabel>
        <small v-if="errors.username" class="text-red-500">{{
          errors.username
        }}</small>
      </div>

      <!-- Email -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <InputText
            id="email"
            v-model="formData.email"
            type="email"
            class="w-full"
            :class="{ 'p-invalid': errors.email }"
          />
          <label for="email">{{ t('Email') }} *</label>
        </FloatLabel>
        <small v-if="errors.email" class="text-red-500">{{ errors.email }}</small>
      </div>

      <!-- Phone -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <InputText id="phone" v-model="formData.phone" class="w-full" />
          <label for="phone">{{ t('Phone') }}</label>
        </FloatLabel>
      </div>

      <!-- Password -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <Password
            id="password"
            v-model="formData.password"
            class="w-full"
            input-class="w-full"
            :class="{ 'p-invalid': errors.password }"
            feedback
            toggle-mask
            :strong-regex="
              /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
            "
          />
          <label for="password">{{
            isEditMode ? t('New Password (leave blank to keep current)') : t('Password') + ' *'
          }}</label>
        </FloatLabel>
        <small v-if="errors.password" class="text-red-500">{{
          errors.password
        }}</small>
      </div>

      <!-- Password Confirmation -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <Password
            id="password_confirmation"
            v-model="formData.password_confirmation"
            class="w-full"
            input-class="w-full"
            :class="{ 'p-invalid': errors.password_confirmation }"
            toggle-mask
          />
          <label for="password_confirmation">{{ t('Confirm Password') }}</label>
        </FloatLabel>
        <small v-if="errors.password_confirmation" class="text-red-500">{{
          errors.password_confirmation
        }}</small>
      </div>

      <!-- Roles -->
      <div class="flex flex-col gap-2 col-span-2">
        <FloatLabel>
          <MultiSelect
            id="roles"
            v-model="formData.roles"
            :options="roleOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('Select Roles')"
            class="w-full"
            display="chip"
          />
          <label for="roles">{{ t('Roles') }}</label>
        </FloatLabel>
      </div>

      <!-- Company -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <Select
            id="company"
            v-model="formData.current_company_id"
            :options="companyOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('Select Company')"
            class="w-full"
          />
          <label for="company">{{ t('Company') }}</label>
        </FloatLabel>
      </div>

      <!-- Branch -->
      <div class="flex flex-col gap-2">
        <FloatLabel>
          <Select
            id="branch"
            v-model="formData.current_branch_id"
            :options="branchOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('Select Branch')"
            class="w-full"
          />
          <label for="branch">{{ t('Branch') }}</label>
        </FloatLabel>
      </div>
    </div>

    <template #footer>
      <Button :label="t('Cancel')" icon="pi pi-times" severity="secondary" @click="closeDialog" />
      <Button
        :label="t('Save')"
        icon="pi pi-check"
        severity="success"
        :loading="loading"
        @click="saveUser"
      />
    </template>
  </Dialog>
</template>
