import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
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

    // Sidebar menu (for both mobile and desktop) - persistent across navigation
    const sidebarOpen = ref(false);

    if (typeof window !== 'undefined') {
        // Load sidebar state from localStorage on mount
        onMounted(() => {
            const savedState = localStorage.getItem('sidebar-open');
            if (savedState !== null) {
                sidebarOpen.value = JSON.parse(savedState);
            }

            // Set up window resize listener
            const windowWidth = ref(window.innerWidth);
            const updateWidth = () => {
                windowWidth.value = window.innerWidth;
            };
            window.addEventListener('resize', updateWidth);

            // Cleanup on unmount
            onUnmounted(() => {
                window.removeEventListener('resize', updateWidth);
            });
        });

        // Watch sidebar state and persist to localStorage
        watch(sidebarOpen, (newValue) => {
            localStorage.setItem('sidebar-open', JSON.stringify(newValue));
        });
    }

    return {
        currentRoute,
        menuItems,
        userMenuItems,
        sidebarOpen,
        logout,
    };
}
