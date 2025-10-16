import { createI18n } from 'vue-i18n';
import * as en from '@/i18n/en';
import * as es from '@/i18n/es';
import * as primeVueEn from '@/i18n/primevue/en';
import * as primeVueEs from '@/i18n/primevue/es';

const messages = { es: es.default, en: en.default };

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages,
});

// PrimeVue locale mappings
export const primeVueLocales = {
  en: primeVueEn.default,
  es: primeVueEs.default,
};

export default i18n;
