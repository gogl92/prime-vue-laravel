<script setup lang="ts">
import { ref, useTemplateRef, onMounted } from 'vue';
import { useForm, Head as InertiaHead } from '@inertiajs/vue3';
import GuestAuthLayout from '@/layouts/GuestAuthLayout.vue';
import InputText from 'primevue/inputtext';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const recovery = ref(false);

type InputTextType = InstanceType<typeof InputText> & { $el: HTMLElement };
const codeInput = useTemplateRef<InputTextType>('code-input');

const twoFactorForm = useForm({
  code: '',
  recovery_code: '',
});

const toggleRecovery = () => {
  recovery.value = !recovery.value;
  twoFactorForm.clearErrors();
  twoFactorForm.code = '';
  twoFactorForm.recovery_code = '';
};

const submit = () => {
  twoFactorForm.post('/two-factor-challenge', {
    onFinish: () => {
      twoFactorForm.reset();
    },
  });
};

onMounted(() => {
  if (codeInput.value) {
    codeInput.value.$el.focus();
  }
});
</script>

<template>
  <InertiaHead :title="t('Two-Factor Authentication')" />

  <GuestAuthLayout>
    <template #title>
      <div class="text-center">{{ t('Two-Factor Authentication') }}</div>
    </template>

    <template #subtitle>
      <div class="text-center">
        <template v-if="!recovery">
          {{ t('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
        </template>
        <template v-else>
          {{ t('Please confirm access to your account by entering one of your emergency recovery codes.') }}
        </template>
      </div>
    </template>

    <form class="space-y-6 sm:space-y-8" @submit.prevent="submit">
      <div v-if="!recovery" class="flex flex-col gap-2">
        <label for="code">{{ t('Authentication Code') }}</label>
        <InputText
          id="code"
          ref="code-input"
          v-model="twoFactorForm.code"
          :invalid="Boolean(twoFactorForm.errors?.code)"
          type="text"
          inputmode="numeric"
          autocomplete="one-time-code"
          autofocus
          required
          fluid
        />
        <Message v-if="twoFactorForm.errors?.code" severity="error" variant="simple" size="small">
          {{ twoFactorForm.errors?.code }}
        </Message>
      </div>

      <div v-else class="flex flex-col gap-2">
        <label for="recovery-code">{{ t('Recovery Code') }}</label>
        <InputText
          id="recovery-code"
          v-model="twoFactorForm.recovery_code"
          :invalid="Boolean(twoFactorForm.errors?.recovery_code)"
          type="text"
          autocomplete="off"
          required
          fluid
        />
        <Message
          v-if="twoFactorForm.errors?.recovery_code"
          severity="error"
          variant="simple"
          size="small"
        >
          {{ twoFactorForm.errors?.recovery_code }}
        </Message>
      </div>

      <div>
        <Button :loading="twoFactorForm.processing" type="submit" :label="t('Submit')" fluid />
      </div>

      <div class="text-center">
        <Button
          v-if="!recovery"
          class="p-0"
          variant="link"
          :label="t('Use a recovery code')"
          @click="toggleRecovery"
        />
        <Button
          v-else
          class="p-0"
          variant="link"
          :label="t('Use an authentication code')"
          @click="toggleRecovery"
        />
      </div>
    </form>
  </GuestAuthLayout>
</template>

