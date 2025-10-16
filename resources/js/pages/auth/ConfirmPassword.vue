<script setup lang="ts">
import { useForm, Head as InertiaHead } from '@inertiajs/vue3';
import GuestAuthLayout from '@/layouts/GuestAuthLayout.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const form = useForm({
  password: '',
});

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <InertiaHead :title="t('Confirm password')" />

  <GuestAuthLayout>
    <template #title>
      <div class="text-center">{{ t('Confirm your password') }}</div>
    </template>

    <template #subtitle>
      <div class="text-center">
        {{ t('This is a secure area of the application. Please confirm your password before continuing.') }}
      </div>
    </template>

    <form @submit.prevent="submit">
      <div class="space-y-6 sm:space-y-8">
        <div class="flex flex-col gap-2">
          <label for="password">{{ t('Password') }}</label>
          <Password
            v-model="form.password"
            :invalid="Boolean(form.errors?.password)"
            :feedback="false"
            autocomplete="current-password"
            input-id="password"
            toggle-mask
            required
            fluid
          />
          <Message v-if="form.errors?.password" severity="error" variant="simple" size="small">
            {{ form.errors?.password }}
          </Message>
        </div>

        <div class="flex justify-end items-center">
          <Button :loading="form.processing" type="submit" :label="t('Confirm password')" />
        </div>
      </div>
    </form>
  </GuestAuthLayout>
</template>
