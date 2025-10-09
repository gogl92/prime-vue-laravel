<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, Menu as MenuIcon } from 'lucide-vue-next';
import { useAppLayout } from '@/composables/useAppLayout';
import ClientOnly from '@/components/ClientOnly.vue';
import Container from '@/components/Container.vue';
import PopupMenuButton from '@/components/PopupMenuButton.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import NavLogoLink from '@/components/NavLogoLink.vue';
import PanelMenu from '@/components/primevue/menu/PanelMenu.vue';
import Breadcrumb from '@/components/primevue/menu/Breadcrumb.vue';
import { type MenuItem } from '@/types';

const props = withDefaults(defineProps<{
    breadcrumbs?: MenuItem[],
}>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const {
    sidebarOpen,
    menuItems,
    userMenuItems,
} = useAppLayout();

// Mobile detection - initialize immediately
const isMobile = ref(typeof window !== 'undefined' ? window.innerWidth < 1024 : false);

onMounted(() => {
    const updateMobileState = () => {
        isMobile.value = window.innerWidth < 1024;
        console.log('Mobile state updated:', isMobile.value, 'Sidebar open:', sidebarOpen.value);

        // Force close sidebar on mobile
        if (isMobile.value) {
            sidebarOpen.value = false;
        }
    };

    // Update immediately on mount
    updateMobileState();
    window.addEventListener('resize', updateMobileState);

    onUnmounted(() => {
        window.removeEventListener('resize', updateMobileState);
    });
});

// Watch for theme changes and ensure sidebar is closed on mobile
watch(() => page.props.auth, () => {
    if (isMobile.value) {
        sidebarOpen.value = false;
    }
}, { deep: true });
</script>

<template>
    <div class="h-screen flex flex-col">
        <ClientOnly>
            <Teleport to="body">
                <ScrollTop
                    :button-props="{ class: 'fixed! right-4! bottom-4! md:right-8! md:bottom-8! z-[1000]!', rounded: true, raised: true }"
                />
            </Teleport>
        </ClientOnly>

        <!-- Top Navbar (Always visible) -->
        <header class="fixed top-0 left-0 right-0 z-50 dynamic-bg shadow-sm border-b dynamic-border">
            <nav class="flex justify-between items-center">
                <Container class="grow">
                    <div class="flex justify-between items-center gap-4 py-3">
                        <div class="flex items-center gap-3">
                            <!-- Hamburger Menu Button -->
                            <Button
                                severity="secondary"
                                text
                                class="!p-2"
                                @click="sidebarOpen = !sidebarOpen"
                            >
                                <template #icon>
                                    <MenuIcon class="size-6!" />
                                </template>
                            </Button>
                            <!-- App Name - Right of hamburger menu -->
                            <div class="flex items-center gap-2">
                                <NavLogoLink />
                            </div>
                        </div>
                        <!-- Right side of navbar - space for future elements -->
                        <div class="flex items-center gap-2">
                            <!-- User menu on mobile -->
                            <div class="lg:hidden">
                                <PopupMenuButton
                                    name="mobile-top-user-menu"
                                    button-size="small"
                                    :menu-items="userMenuItems"
                                    :button-label="page.props.auth.user.name"
                                >
                                    <template #toggleIcon>
                                        <ChevronsUpDown class="size-4" />
                                    </template>
                                </PopupMenuButton>
                            </div>
                            <!-- Additional navbar items can be added here -->
                        </div>
                    </div>
                </Container>
            </nav>
        </header>

        <div class="flex-1 pt-[60px]">
            <!-- Mobile/Desktop Sidebar (Collapsible) -->
            <aside
                class="w-[18rem] fixed overflow-y-auto overflow-x-hidden dynamic-bg border-r dynamic-border transition-transform duration-300 top-[60px] bottom-0 lg:block z-30"
                :class="[
                    // Mobile: always slides from left, desktop: slides from left when toggled
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                ]"
            >
                <div class="w-full h-full flex flex-col justify-between p-4">
                    <div class="space-y-6">
                        <div>
                            <PanelMenu
                                :model="menuItems"
                                class="mt-1 w-full"
                            />
                        </div>
                    </div>
                    <div>
                        <PopupMenuButton
                            name="sidebar-user-menu-dd"
                            :menu-items="userMenuItems"
                            :button-label="page.props.auth.user.name"
                        >
                            <template #toggleIcon>
                                <ChevronsUpDown />
                            </template>
                        </PopupMenuButton>
                    </div>
                </div>
            </aside>

            <!-- Mobile overlay when sidebar is open - only show on mobile when sidebar is actually open -->
            <div
                v-if="sidebarOpen && isMobile"
                class="fixed inset-0 bg-black bg-opacity-50 z-20"
                @click="sidebarOpen = false"
            />

            <!-- Scrollable Content -->
            <main
                class="flex flex-col h-full transition-all duration-300"
                :class="[
                    sidebarOpen ? 'lg:pl-[18rem]' : 'lg:pl-0'
                ]"
            >
                <Container
                    vertical
                    fluid
                >
                    <!-- Session-based Flash Messages -->
                    <FlashMessages />

                    <!-- Breadcrumbs -->
                    <Breadcrumb
                        v-if="props.breadcrumbs.length"
                        :model="props.breadcrumbs"
                    />

                    <!-- Page Content -->
                    <slot />
                </Container>
            </main>
        </div>
    </div>
</template>
