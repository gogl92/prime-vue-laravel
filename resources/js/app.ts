import '../css/app.css';
import '../css/tailwind.css';
import 'primeicons/primeicons.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, type DefineComponent, h, watch } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { usePrimeVue } from 'primevue/config';

import PrimeVue from 'primevue/config';
import Toast from 'primevue/toast';
import Tooltip from 'primevue/tooltip';
import ToastService from 'primevue/toastservice';
import { useToast } from 'primevue/usetoast';

import { useSiteColorMode } from '@/composables/useSiteColorMode';
import { useAuthToken } from '@/composables/useAuthToken';
import globalPt from '@/theme/global-pt';
import themePreset from '@/theme/noir-preset';
import orionService from '@/services/orion';
import i18n, { primeVueLocales } from '@/i18n';

// Chart.js - Register required components
import {
  Chart as ChartJS,
  Title,
  Tooltip as ChartTooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement,
  LineElement,
  PointElement,
  RadialLinearScale,
  Filler,
} from 'chart.js';

ChartJS.register(
  Title,
  ChartTooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement,
  LineElement,
  PointElement,
  RadialLinearScale,
  Filler
);

const appName = import.meta.env['VITE_APP_NAME'] ?? 'Laravel';

void createInertiaApp({
  title: title => `${title} - ${appName}`,
  resolve: name =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob<DefineComponent>('./pages/**/*.vue')
    ),
  setup({ el, App, props, plugin }) {
    // Initialize Orion service
    void orionService;

    // Site light/dark mode
    const colorMode = useSiteColorMode({ emitAuto: true });

    // Root component with Global Toast
    const Root = {
      setup() {
        // Initialize auth token handling
        useAuthToken();

        // Setup PrimeVue locale switching
        const primeVue = usePrimeVue();
        watch(
          () => i18n.global.locale.value,
          newLocale => {
            primeVue.config.locale = primeVueLocales[newLocale as keyof typeof primeVueLocales];
          }
        );

        // show error toast instead of standard Inertia modal response
        const toast = useToast();
        router.on('invalid', event => {
          const responseBody = event.detail.response?.data;
          if (responseBody?.error_summary && responseBody?.error_detail) {
            event.preventDefault();
            toast.add({
              severity: event.detail.response?.status >= 500 ? 'error' : 'warn',
              summary: responseBody.error_summary,
              detail: responseBody.error_detail,
              life: 5000,
            });
          }
        });

        return () => h('div', [h(App, props), h(Toast, { position: 'bottom-right' })]);
      },
    };

    createApp(Root)
      .use(plugin)
      .use(ZiggyVue)
      .use(i18n)
      .use(PrimeVue, {
        theme: {
          preset: themePreset,
          options: {
            darkModeSelector: '.dark',
            cssLayer: {
              name: 'primevue',
              order: 'theme, base, primevue, utilities',
            },
          },
        },
        pt: globalPt,
        locale: primeVueLocales[i18n.global.locale.value as keyof typeof primeVueLocales],
      })
      .use(ToastService)
      .directive('tooltip', Tooltip)
      .provide('colorMode', colorMode)
      .mount(el);

    // #app content set to hidden by default
    // reduces jumpy initial render from SSR content (unstyled PrimeVue components)
    (el as HTMLElement).style.visibility = 'visible';
  },
  progress: {
    color: 'var(--p-primary-500)',
  },
});
