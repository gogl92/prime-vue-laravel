<script setup lang="ts">
import { ref, computed, useTemplateRef, onMounted } from 'vue';
import { useForm, usePage, Head as InertiaHead, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Password from 'primevue/password';
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/UserSettingsLayout.vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

const { t } = useI18n();
const breadcrumbs = [
  { label: t('Dashboard'), route: route('dashboard') },
  { label: t('Password settings') },
];

type PasswordInputType = InstanceType<typeof Password> & { $el: HTMLElement };
const currentPasswordInput = useTemplateRef<PasswordInputType>('current-password-input');
const newPasswordInput = useTemplateRef<PasswordInputType>('new-password-input');

const toast = useToast();
const confirm = useConfirm();

// Get user from page props
const page = usePage();
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const user = computed(() => page.props.auth?.user as any);
const twoFactorEnabled = computed(() => user.value?.two_factor_confirmed_at !== null && user.value?.two_factor_confirmed_at !== undefined);

// Update password form
const updatePasswordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

// Two-factor authentication state
const enabling2FA = ref(false);
const confirming2FA = ref(false);
const disabling2FA = ref(false);
const qrCode = ref<string | null>(null);
const secretKey = ref<string | null>(null);
const recoveryCodes = ref<string[]>([]);
const confirmationCode = ref('');
const confirmationError = ref('');
const showPasswordConfirmDialog = ref(false);
const confirmPasswordValue = ref('');
const confirmPasswordError = ref('');

const showSuccessToast = () => {
  toast.add({
    severity: 'success',
    summary: t('Saved'),
    detail: t('Your password has been updated'),
    life: 3000,
  });
};

const updatePassword = () => {
  updatePasswordForm.put('/user/password', {
    preserveScroll: true,
    onSuccess: () => {
      updatePasswordForm.reset();
      showSuccessToast();
    },
    onError: () => {
      if (updatePasswordForm.errors?.password) {
        updatePasswordForm.reset('password', 'password_confirmation');
        if (newPasswordInput.value && newPasswordInput.value?.$el) {
          const newPasswordInputElement = newPasswordInput.value.$el.querySelector('input');
          newPasswordInputElement?.focus();
        }
      }
      if (updatePasswordForm.errors?.current_password) {
        updatePasswordForm.reset('current_password');
        if (currentPasswordInput.value && currentPasswordInput.value?.$el) {
          const currentPasswordInputElement = currentPasswordInput.value.$el.querySelector('input');
          currentPasswordInputElement?.focus();
        }
      }
    },
  });
};

// Two-factor authentication methods
const enable2FA = () => {
  // Show password confirmation dialog
  showPasswordConfirmDialog.value = true;
  confirmPasswordValue.value = '';
  confirmPasswordError.value = '';
};

const proceedWithEnable2FA = async () => {
  if (!confirmPasswordValue.value) {
    confirmPasswordError.value = t('Password is required');
    return;
  }

  enabling2FA.value = true;
  confirmPasswordError.value = '';

  try {
    // First, confirm password
    await axios.post('/user/confirm-password', {
      password: confirmPasswordValue.value,
    });

    // Then enable 2FA
    await axios.post('/user/two-factor-authentication');
    await show2FAQRCode();
    confirming2FA.value = true;
    showPasswordConfirmDialog.value = false;
    confirmPasswordValue.value = '';
  } catch (error) {
    const axiosError = error as { response?: { data?: { message?: string } } };
    const errorMessage = axiosError.response?.data?.message ?? t('Failed to enable two-factor authentication');

    // Check if it's a password confirmation error
    if (errorMessage.toLowerCase().includes('password')) {
      confirmPasswordError.value = errorMessage;
    } else {
      toast.add({
        severity: 'error',
        summary: t('Error'),
        detail: errorMessage,
        life: 3000,
      });
      showPasswordConfirmDialog.value = false;
    }
  } finally {
    enabling2FA.value = false;
  }
};

const cancelPasswordConfirm = () => {
  showPasswordConfirmDialog.value = false;
  confirmPasswordValue.value = '';
  confirmPasswordError.value = '';
};

const show2FAQRCode = async () => {
  try {
    const [qrResponse, secretResponse] = await Promise.all([
      axios.get('/user/two-factor-qr-code'),
      axios.get('/user/two-factor-secret-key'),
    ]);
    qrCode.value = qrResponse.data.svg;
    secretKey.value = secretResponse.data.secretKey;
  } catch {
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to fetch QR code'),
      life: 3000,
    });
  }
};

const confirm2FA = async () => {
  confirmationError.value = '';
  try {
    await axios.post('/user/confirmed-two-factor-authentication', {
      code: confirmationCode.value,
    });

    // Fetch recovery codes
    const response = await axios.get('/user/two-factor-recovery-codes');
    recoveryCodes.value = response.data;

    // Reload page to update user state
    router.reload();

    toast.add({
      severity: 'success',
      summary: t('Enabled'),
      detail: t('Two-factor authentication has been enabled'),
      life: 3000,
    });
  } catch (error) {
    const axiosError = error as { response?: { data?: { message?: string } } };
    confirmationError.value = axiosError.response?.data?.message ?? t('Invalid authentication code');
  }
};

const regenerateRecoveryCodes = async () => {
  try {
    await axios.post('/user/two-factor-recovery-codes');
    const response = await axios.get('/user/two-factor-recovery-codes');
    recoveryCodes.value = response.data;
    toast.add({
      severity: 'success',
      summary: t('Success'),
      detail: t('Recovery codes have been regenerated'),
      life: 3000,
    });
  } catch {
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to regenerate recovery codes'),
      life: 3000,
    });
  }
};

const copyRecoveryCodes = async () => {
  try {
    const codesText = recoveryCodes.value.join('\n');
    await navigator.clipboard.writeText(codesText);
    toast.add({
      severity: 'success',
      summary: t('Copied'),
      detail: t('Recovery codes copied to clipboard'),
      life: 3000,
    });
  } catch {
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to copy recovery codes'),
      life: 3000,
    });
  }
};

const downloadRecoveryCodes = () => {
  try {
    const codesText = recoveryCodes.value.join('\n');
    const blob = new Blob([codesText], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `recovery-codes-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    toast.add({
      severity: 'success',
      summary: t('Downloaded'),
      detail: t('Recovery codes downloaded'),
      life: 3000,
    });
  } catch {
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to download recovery codes'),
      life: 3000,
    });
  }
};

const show2FARecoveryCodes = async () => {
  try {
    const response = await axios.get('/user/two-factor-recovery-codes');
    recoveryCodes.value = response.data;
  } catch {
    toast.add({
      severity: 'error',
      summary: t('Error'),
      detail: t('Failed to fetch recovery codes'),
      life: 3000,
    });
  }
};

const disable2FA = () => {
  confirm.require({
    message: t('Are you sure you want to disable two-factor authentication?'),
    header: t('Disable Two-Factor Authentication'),
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: t('Cancel'),
    acceptLabel: t('Disable'),
    accept: () => {
      void (async () => {
        disabling2FA.value = true;
        try {
          await axios.delete('/user/two-factor-authentication');
          router.reload();
          toast.add({
            severity: 'success',
            summary: t('Disabled'),
            detail: t('Two-factor authentication has been disabled'),
            life: 3000,
          });
          confirming2FA.value = false;
          qrCode.value = null;
          secretKey.value = null;
          recoveryCodes.value = [];
          confirmationCode.value = '';
        } catch {
          toast.add({
            severity: 'error',
            summary: t('Error'),
            detail: t('Failed to disable two-factor authentication'),
            life: 3000,
          });
        } finally {
          disabling2FA.value = false;
        }
      })();
    },
  });
};

const cancelSetup = async () => {
  try {
    await axios.delete('/user/two-factor-authentication');
    confirming2FA.value = false;
    qrCode.value = null;
    secretKey.value = null;
    confirmationCode.value = '';
    confirmationError.value = '';
  } catch {
    // Silent fail - just reset UI
    confirming2FA.value = false;
    qrCode.value = null;
    secretKey.value = null;
  }
};

onMounted(() => {
  if (twoFactorEnabled.value) {
    void show2FARecoveryCodes();
  }
});
</script>

<template>
  <InertiaHead :title="t('Password settings')" />

  <AppLayout :breadcrumbs>
    <SettingsLayout>
      <!-- Update Password Card -->
      <Card pt:body:class="max-w-2xl space-y-3" pt:caption:class="space-y-1">
        <template #title>{{ t('Update password') }}</template>
        <template #subtitle>
          {{ t('Ensure your account is using a long, random password to stay secure') }}
        </template>
        <template #content>
          <form class="space-y-6" @submit.prevent="updatePassword">
            <div class="flex flex-col gap-2">
              <label for="current-password">{{ t('Current password') }}</label>
              <Password
                ref="current-password-input"
                v-model="updatePasswordForm.current_password"
                :invalid="Boolean(updatePasswordForm.errors?.current_password)"
                :feedback="false"
                autocomplete="current-password"
                input-id="current-password"
                toggle-mask
                required
                fluid
              />
              <Message
                v-if="updatePasswordForm.errors?.current_password"
                severity="error"
                variant="simple"
                size="small"
              >
                {{ updatePasswordForm.errors?.current_password }}
              </Message>
            </div>
            <div class="flex flex-col gap-2">
              <label for="password">{{ t('New password') }}</label>
              <Password
                ref="new-password-input"
                v-model="updatePasswordForm.password"
                :invalid="Boolean(updatePasswordForm.errors?.password)"
                autocomplete="new-password"
                input-id="password"
                toggle-mask
                required
                fluid
              />
              <Message
                v-if="updatePasswordForm.errors?.password"
                severity="error"
                variant="simple"
                size="small"
              >
                {{ updatePasswordForm.errors?.password }}
              </Message>
            </div>
            <div class="flex flex-col gap-2">
              <label for="password-confirmation">{{ t('Confirm pew password') }}</label>
              <Password
                v-model="updatePasswordForm.password_confirmation"
                :invalid="Boolean(updatePasswordForm.errors?.password_confirmation)"
                :feedback="false"
                autocomplete="confirm-password"
                input-id="password-confirmation"
                toggle-mask
                required
                fluid
              />
              <Message
                v-if="updatePasswordForm.errors?.password_confirmation"
                severity="error"
                variant="simple"
                size="small"
              >
                {{ updatePasswordForm.errors?.password_confirmation }}
              </Message>
            </div>
            <Button :loading="updatePasswordForm.processing" type="submit" :label="t('Save password')" />
          </form>
        </template>
      </Card>

      <!-- Two-Factor Authentication Card -->
      <Card class="mt-6" pt:body:class="max-w-2xl space-y-3" pt:caption:class="space-y-1">
        <template #title>{{ t('Two-Factor Authentication') }}</template>
        <template #subtitle>
          {{ t('Add additional security to your account using two-factor authentication') }}
        </template>
        <template #content>
          <div class="space-y-6">
            <!-- 2FA Not Enabled -->
            <div v-if="!twoFactorEnabled && !confirming2FA">
              <p class="text-muted-color mb-4">
                {{ t('When two-factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.') }}
              </p>
              <Button
                :loading="enabling2FA"
                :label="t('Enable Two-Factor Authentication')"
                icon="pi pi-shield"
                @click="enable2FA"
              />
            </div>

            <!-- 2FA Setup - Confirming -->
            <div v-if="confirming2FA && !twoFactorEnabled" class="space-y-4">
              <Message severity="info" :closable="false">
                {{ t('To finish enabling two-factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key and provide the generated OTP code.') }}
              </Message>

              <!-- QR Code -->
              <div v-if="qrCode" class="flex flex-col items-center gap-4 p-4 bg-surface-50 dark:bg-surface-900 rounded-lg">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div v-html="qrCode"></div>
                <div v-if="secretKey" class="text-sm">
                  <p class="font-semibold mb-2">{{ t('Setup Key') }}:</p>
                  <code class="bg-surface-100 dark:bg-surface-800 px-3 py-2 rounded">{{ secretKey }}</code>
                </div>
              </div>

              <!-- Confirmation Code Input -->
              <div class="flex flex-col gap-2">
                <label for="confirmation-code">{{ t('Authentication Code') }}</label>
                <InputText
                  id="confirmation-code"
                  v-model="confirmationCode"
                  :invalid="Boolean(confirmationError)"
                  type="text"
                  inputmode="numeric"
                  autocomplete="one-time-code"
                  placeholder="000000"
                  fluid
                />
                <Message v-if="confirmationError" severity="error" variant="simple" size="small">
                  {{ confirmationError }}
                </Message>
              </div>

              <div class="flex gap-2">
                <Button
                  :label="t('Confirm')"
                  icon="pi pi-check"
                  @click="confirm2FA"
                />
                <Button
                  :label="t('Cancel')"
                  severity="secondary"
                  outlined
                  @click="cancelSetup"
                />
              </div>
            </div>

            <!-- 2FA Enabled -->
            <div v-if="twoFactorEnabled" class="space-y-4">
              <Message severity="success" :closable="false">
                {{ t('You have enabled two-factor authentication.') }}
              </Message>

              <!-- Recovery Codes -->
              <div v-if="recoveryCodes.length > 0" class="space-y-3">
                <div class="flex items-start gap-2">
                  <i class="pi pi-info-circle text-primary mt-1"></i>
                  <p class="text-sm text-muted-color">
                    {{ t('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two-factor authentication device is lost.') }}
                  </p>
                </div>

                <div class="bg-surface-50 dark:bg-surface-900 p-4 rounded-lg">
                  <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                    <div v-for="code in recoveryCodes" :key="code" class="p-2 bg-surface-0 dark:bg-surface-950 rounded">
                      {{ code }}
                    </div>
                  </div>
                </div>

                <div class="flex flex-wrap gap-2">
                  <Button
                    :label="t('Copy Codes')"
                    icon="pi pi-copy"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="copyRecoveryCodes"
                  />
                  <Button
                    :label="t('Download Codes')"
                    icon="pi pi-download"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="downloadRecoveryCodes"
                  />
                  <Button
                    :label="t('Regenerate Recovery Codes')"
                    icon="pi pi-refresh"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="regenerateRecoveryCodes"
                  />
                </div>
              </div>

              <div class="pt-4 border-t border-surface-200 dark:border-surface-700">
                <Button
                  :loading="disabling2FA"
                  :label="t('Disable Two-Factor Authentication')"
                  icon="pi pi-times"
                  severity="danger"
                  @click="disable2FA"
                />
              </div>
            </div>
          </div>
        </template>
      </Card>
    </SettingsLayout>
  </AppLayout>

  <!-- Confirmation Dialog -->
  <ConfirmDialog />

  <!-- Password Confirmation Dialog for 2FA -->
  <Dialog
    v-model:visible="showPasswordConfirmDialog"
    :header="t('Confirm Password')"
    :modal="true"
    :closable="true"
    :style="{ width: '450px' }"
  >
    <div class="space-y-4">
      <p class="text-muted-color">
        {{ t('Please confirm your password to enable two-factor authentication.') }}
      </p>
      <div class="flex flex-col gap-2">
        <label for="confirm-password-input">{{ t('Password') }}</label>
        <Password
          id="confirm-password-input"
          v-model="confirmPasswordValue"
          :invalid="Boolean(confirmPasswordError)"
          :feedback="false"
          autocomplete="current-password"
          toggle-mask
          autofocus
          fluid
          @keyup.enter="proceedWithEnable2FA"
        />
        <Message v-if="confirmPasswordError" severity="error" variant="simple" size="small">
          {{ confirmPasswordError }}
        </Message>
      </div>
    </div>
    <template #footer>
      <Button
        :label="t('Cancel')"
        severity="secondary"
        outlined
        @click="cancelPasswordConfirm"
      />
      <Button
        :label="t('Confirm')"
        :loading="enabling2FA"
        @click="proceedWithEnable2FA"
      />
    </template>
  </Dialog>
</template>
