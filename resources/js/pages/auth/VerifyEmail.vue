<script setup lang="ts">
import { computed } from 'vue';
import { useForm, Head as InertiaHead } from '@inertiajs/vue3';
import GuestAuthLayout from '@/layouts/GuestAuthLayout.vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
  status?: string;
}>();

const { t } = useI18n();

const sendVerificationForm = useForm({});
const submit = () => {
  sendVerificationForm.post(route('verification.send'));
};
const logoutForm = useForm({});
const logout = () => {
  logoutForm.post(route('logout'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
  <InertiaHead :title="t('Verify email')" />

  <GuestAuthLayout>
    <template #title>
      <div class="text-center">{{ t('Verify email') }}</div>
    </template>

    <template #subtitle>
      <div class="text-center">
        {{ t('Please verify your email address by clicking on the link we just emailed to you.') }}
      </div>
    </template>

    <template v-if="verificationLinkSent" #message>
      <Message severity="success" :closable="false" class="shadow-sm">
        {{ t('A new verification link has been sent to the email address you provided during registration.') }}
      </Message>
    </template>

    <div class="space-y-6 sm:space-y-8">
      <form @submit.prevent="submit">
        <Button
          :loading="sendVerificationForm.processing"
          type="submit"
          :label="t('Resend verification email')"
          fluid
        />
      </form>
      <div class="text-center">
        <Button
          :loading="logoutForm.processing"
          class="p-0"
          variant="link"
          :label="t('Log out')"
          @click="logout"
        />
      </div>
    </div>
  </GuestAuthLayout>
</template>
