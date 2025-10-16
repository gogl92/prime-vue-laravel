<script setup lang="ts">
import { useTemplateRef, onMounted } from 'vue';
import { useForm, Head as InertiaHead, Link as InertiaLink } from '@inertiajs/vue3';
import GuestAuthLayout from '@/layouts/GuestAuthLayout.vue';
import InputText from 'primevue/inputtext';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
  status?: string;
}>();

const { t } = useI18n();

type InputTextType = InstanceType<typeof InputText> & { $el: HTMLElement };
const emailInput = useTemplateRef<InputTextType>('email-input');

const forgotPasswordForm = useForm({
  email: '',
});

const submit = () => {
  forgotPasswordForm.post(route('password.email'));
};

onMounted(() => {
  if (emailInput.value) {
    emailInput.value.$el.focus();
  }
});
</script>

<template>
  <InertiaHead :title="t('Forgot password')" />

  <GuestAuthLayout>
    <template v-if="props.status" #message>
      <Message severity="success" :closable="false" class="shadow-sm">
        {{ props.status }}
      </Message>
    </template>

    <template #title>
      <div class="text-center">{{ t('Forgot password') }}</div>
    </template>

    <template #subtitle>
      <div class="text-center">{{ t('Enter your email address to receive a password reset link') }}</div>
    </template>

    <form class="space-y-6 sm:space-y-8" @submit.prevent="submit">
      <div class="flex flex-col gap-2">
        <label for="email">{{ t('Email address') }}</label>
        <InputText
          id="email"
          ref="email-input"
          v-model="forgotPasswordForm.email"
          :invalid="Boolean(forgotPasswordForm.errors?.email)"
          type="email"
          autocomplete="username"
          required
          fluid
        />
        <Message
          v-if="forgotPasswordForm.errors?.email"
          severity="error"
          variant="simple"
          size="small"
        >
          {{ forgotPasswordForm.errors?.email }}
        </Message>
      </div>

      <div>
        <Button
          :loading="forgotPasswordForm.processing"
          type="submit"
          :label="t('Email password reset link')"
          fluid
        />
      </div>

      <div class="text-center">
        <span class="text-muted-color mr-1">{{ t('Or, return to') }}</span>
        <InertiaLink :href="route('login')">
          <Button class="p-0" variant="link" :label="t('log in')" />
        </InertiaLink>
      </div>
    </form>
  </GuestAuthLayout>
</template>
