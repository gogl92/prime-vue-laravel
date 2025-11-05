<script setup lang="ts">
import { useTemplateRef, onMounted, ref } from 'vue';
import { Head as InertiaHead, Link as InertiaLink } from '@inertiajs/vue3';
import GuestAuthLayout from '@/layouts/GuestAuthLayout.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Message from 'primevue/message';
import { useI18n } from 'vue-i18n';
import { useToast } from 'primevue/usetoast';
import orionService from '@/services/orion';
import { route } from 'ziggy-js';

const props = defineProps<{
  canResetPassword: boolean;
  status?: string;
}>();

const { t } = useI18n();
const toast = useToast();

type InputTextType = InstanceType<typeof InputText> & { $el: HTMLElement };
const emailInput = useTemplateRef<InputTextType>('email-input');

const loginForm = ref({
  email: '',
  password: '',
  remember: false,
});

const errors = ref<{ email?: string; password?: string }>({});
const processing = ref(false);

// Helper to get CSRF token from cookie
const getCsrfToken = (): string | null => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; XSRF-TOKEN=`);
  if (parts.length === 2) {
    const token = parts.pop()?.split(';').shift();
    return token ? decodeURIComponent(token) : null;
  }
  return null;
};

const submit = async () => {
  errors.value = {};
  processing.value = true;

  try {
    // Step 1: Get CSRF cookie first (required by Laravel Sanctum/Fortify)
    await fetch(`${window.location.origin}/sanctum/csrf-cookie`, {
      credentials: 'include',
    });

    // Wait a bit for cookie to be set
    await new Promise(resolve => setTimeout(resolve, 50));

    // Get CSRF token from cookie
    const csrfToken = getCsrfToken();

    // Step 2: Login with Fortify (creates session)
    const loginUrl = `${window.location.origin}/login`;

    const loginHeaders: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrfToken) {
      loginHeaders['X-XSRF-TOKEN'] = csrfToken;
    }

    const response = await fetch(loginUrl, {
      method: 'POST',
      headers: loginHeaders,
      credentials: 'include', // Important for session cookies
      body: JSON.stringify({
        email: loginForm.value.email,
        password: loginForm.value.password,
      }),
    });

    // Check if it's a redirect (Fortify redirects on success)
    if (response.redirected || response.status === 302) {
      // Login successful (redirect detected)
    } else if (!response.ok) {
      const data = await response.json();
      // Handle validation errors
      if (data.errors) {
        errors.value = data.errors;
      } else if (data.message) {
        errors.value.email = data.message;
      }
      processing.value = false;
      return;
    }

    // Step 3: Now get API token for Orion requests
    const tokenResponse = await fetch(`${window.location.origin}/api/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      credentials: 'include',
      body: JSON.stringify({
        email: loginForm.value.email,
        password: loginForm.value.password,
      }),
    });

    if (tokenResponse.ok) {
      const tokenData = await tokenResponse.json();
      if (tokenData.token) {
        localStorage.setItem('auth_token', tokenData.token);
        orionService.setAuthToken(tokenData.token);
      }
    }

    // Show success message
    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Login successful'),
      life: 3000,
    });

    // Small delay to ensure session is persisted
    await new Promise(resolve => setTimeout(resolve, 100));

    // Navigate to dashboard using window.location instead of router.visit
    // This ensures a full page reload with the new session
    window.location.href = route('dashboard');
  } catch (error) {
    console.error('Login error:', error);
    errors.value.email = t('An error occurred during login. Please try again.');
  } finally {
    processing.value = false;
  }
};

onMounted(() => {
  if (emailInput.value) {
    emailInput.value.$el.focus();
  }
});
</script>

<template>
  <InertiaHead :title="t('Log in')" />

  <GuestAuthLayout>
    <template v-if="props.status" #message>
      <Message severity="success" :closable="false" class="shadow-sm">
        {{ props.status }}
      </Message>
    </template>

    <template #title>
      <div class="text-center">{{ t('Log in to your account') }}</div>
    </template>

    <template #subtitle>
      <div class="text-center">{{ t('Enter your email and password below to log in') }}</div>
    </template>

    <form class="space-y-6 sm:space-y-8" @submit.prevent="submit">
      <div class="flex flex-col gap-2">
        <label for="email">{{ t('Email address') }}</label>
        <InputText
          id="email"
          ref="email-input"
          v-model="loginForm.email"
          :invalid="Boolean(errors.email)"
          type="email"
          autocomplete="username"
          required
          fluid
        />
        <Message v-if="errors.email" severity="error" variant="simple" size="small">
          {{ errors.email }}
        </Message>
      </div>

      <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <label for="password">{{ t('Password') }}</label>
          <InertiaLink v-if="props.canResetPassword" :href="route('password.request')">
            <Button class="p-0" variant="link" :label="t('Forgot your password?')" />
          </InertiaLink>
        </div>
        <Password
          v-model="loginForm.password"
          :invalid="Boolean(errors.password)"
          :feedback="false"
          autocomplete="current-password"
          input-id="password"
          toggle-mask
          required
          fluid
        />
        <Message v-if="errors.password" severity="error" variant="simple" size="small">
          {{ errors.password }}
        </Message>
      </div>

      <div>
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <Checkbox v-model="loginForm.remember" class="mr-2" input-id="remember" binary />
            <label for="remember">{{ t('Remember me') }}</label>
          </div>
        </div>
      </div>

      <div>
        <Button :loading="processing" type="submit" :label="t('Log in')" fluid />
      </div>

      <div class="text-center">
        <span class="text-muted-color mr-1">{{ t("Don't have an account?") }}</span>
        <InertiaLink :href="route('register')">
          <Button class="p-0" variant="link" :label="t('Sign up')" />
        </InertiaLink>
      </div>
    </form>
  </GuestAuthLayout>
</template>
