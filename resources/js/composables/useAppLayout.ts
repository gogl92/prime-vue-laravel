import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { LayoutGrid, House, Settings, LogOut, FileText, CreditCard, Users } from 'lucide-vue-next';
import { type MenuItem } from '@/types';
import orionService from '@/services/orion';

export function useAppLayout() {
  const currentRoute = computed(() => {
    // Access page.url to trigger re-computation on navigation.
    return route().current();
  });

  // Menu items
  const menuItems = computed<MenuItem[]>(() => [
    {
      label: 'Home',
      lucideIcon: House,
      route: route('welcome'),
      active: currentRoute.value == 'welcome',
    },
    {
      label: 'Dashboard',
      lucideIcon: LayoutGrid,
      route: route('dashboard'),
      active: currentRoute.value == 'dashboard',
    },
    {
      label: 'Invoices',
      lucideIcon: FileText,
      route: route('invoices.example'),
      active: currentRoute.value == 'invoices.example',
    },
    {
      label: 'User Management',
      lucideIcon: Users,
      route: route('users.management'),
      active: currentRoute.value == 'users.management',
    },
    {
      label: 'Stripe Connect',
      lucideIcon: CreditCard,
      route: route('stripe.connect'),
      active: currentRoute.value == 'stripe.connect',
    },
  ]);

  // User menu and logout functionality.
  const logoutForm = useForm({});
  const logout = () => {
    // Clear auth token from localStorage and Orion service
    localStorage.removeItem('auth_token');
    orionService.clearAuth();

    // Perform logout
    logoutForm.post(route('logout'));
  };
  const userMenuItems: MenuItem[] = [
    {
      label: 'Settings',
      route: route('profile.edit'),
      lucideIcon: Settings,
    },
    {
      separator: true,
    },
    {
      label: 'Log out',
      lucideIcon: LogOut,
      command: () => logout(),
    },
  ];

  // Sidebar menu (for both mobile and desktop) - persistent across navigation
  // Initialize based on screen size immediately
  const sidebarOpen = ref(typeof window !== 'undefined' ? window.innerWidth >= 1024 : false);

  if (typeof window !== 'undefined') {
    // Load sidebar state from localStorage on mount
    onMounted(() => {
      // Only load saved state on desktop (lg breakpoint and above)
      // On mobile, always start with sidebar closed
      const isDesktop = window.innerWidth >= 1024; // lg breakpoint

      if (isDesktop) {
        const savedState = localStorage.getItem('sidebar-open');
        if (savedState !== null) {
          sidebarOpen.value = JSON.parse(savedState);
        }
      } else {
        // Mobile: always start closed
        sidebarOpen.value = false;
      }

      // Set up window resize listener
      const windowWidth = ref(window.innerWidth);
      const updateWidth = () => {
        windowWidth.value = window.innerWidth;
        // Auto-close sidebar when switching to mobile
        if (windowWidth.value < 1024) {
          sidebarOpen.value = false;
        }
      };
      window.addEventListener('resize', updateWidth);

      // Cleanup on unmount
      onUnmounted(() => {
        window.removeEventListener('resize', updateWidth);
      });
    });

    // Watch sidebar state and persist to localStorage (only on desktop)
    watch(sidebarOpen, newValue => {
      if (typeof window !== 'undefined' && window.innerWidth >= 1024) {
        localStorage.setItem('sidebar-open', JSON.stringify(newValue));
      }
    });

    // Watch for window resize and force close sidebar on mobile
    if (typeof window !== 'undefined') {
      window.addEventListener('resize', () => {
        if (window.innerWidth < 1024) {
          sidebarOpen.value = false;
        }
      });
    }
  }

  return {
    currentRoute,
    menuItems,
    userMenuItems,
    sidebarOpen,
    logout,
  };
}
