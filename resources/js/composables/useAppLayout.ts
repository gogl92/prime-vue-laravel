import { ref, computed, onMounted, onUnmounted, watchEffect } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { LayoutGrid, House, Settings, LogOut, FileText } from 'lucide-vue-next';
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

    // Mobile menu
    const mobileMenuOpen = ref(false);
    if (typeof window !== 'undefined') {
        const windowWidth = ref(window.innerWidth);
        const updateWidth = () => {
            windowWidth.value = window.innerWidth;
        };
        onMounted(() => {
            window.addEventListener('resize', updateWidth);
        });
        onUnmounted(() => {
            window.removeEventListener('resize', updateWidth);
        });
        watchEffect(() => {
            if (windowWidth.value > 1024) {
                mobileMenuOpen.value = false;
            }
        });
    }

    return {
        currentRoute,
        menuItems,
        userMenuItems,
        mobileMenuOpen,
        logout,
    };
}
