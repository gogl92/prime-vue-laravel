<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useAppLayout } from '@/composables/useAppLayout';
import { ChevronsUpDown, Menu as MenuIcon } from 'lucide-vue-next';
import ClientOnly from '@/components/ClientOnly.vue';
import Container from '@/components/Container.vue';
import PopupMenuButton from '@/components/PopupMenuButton.vue';
import NavLogoLink from '@/components/NavLogoLink.vue';
import BranchSelector from '@/components/BranchSelector.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import Menubar from '@/components/primevue/menu/Menubar.vue';
import PanelMenu from '@/components/primevue/menu/PanelMenu.vue';
import Breadcrumb from '@/components/primevue/menu/Breadcrumb.vue';
import { type MenuItem } from '@/types';

const props = withDefaults(
  defineProps<{
    breadcrumbs?: MenuItem[];
  }>(),
  {
    breadcrumbs: () => [],
  }
);

const page = usePage();
const { currentRoute, mobileMenuOpen, menuItems, userMenuItems } = useAppLayout();
</script>

<template>
  <div>
    <ClientOnly>
      <Teleport to="body">
        <!-- Mobile drawer menu -->
        <Drawer v-model:visible="mobileMenuOpen" position="right">
          <PanelMenu :model="menuItems" class="mt-1 w-full" />
          <template #footer>
            <PopupMenuButton
              name="mobile-user-menu-dd"
              button-size="large"
              :menu-items="userMenuItems"
              :button-label="page.props.auth.user.name"
            >
              <template #toggleIcon>
                <ChevronsUpDown />
              </template>
            </PopupMenuButton>
          </template>
        </Drawer>
        <ScrollTop
          :button-props="{
            class: 'fixed! right-4! bottom-4! md:right-8! md:bottom-8! z-[1000]!',
            rounded: true,
            raised: true,
          }"
        />
      </Teleport>
    </ClientOnly>
    <div class="min-h-screen">
      <!-- Primary Navigation Menu -->
      <nav class="dynamic-bg shadow-sm">
        <Container>
          <Menubar
            :key="currentRoute"
            :model="menuItems"
            pt:root:class="px-0 py-0 border-0 rounded-none bg-transparent h-[var(--header-height)]!"
            pt:button:class="hidden"
          >
            <template #start>
              <div class="shrink-0 flex items-center mr-5">
                <NavLogoLink />
              </div>
            </template>
            <template #end>
              <!-- Branch Selector (All screen sizes) -->
              <div class="flex items-center gap-3 mr-4">
                <BranchSelector />
              </div>

              <!-- User Dropdown Menu -->
              <div class="hidden lg:flex">
                <PopupMenuButton
                  name="desktop-user-menu-dd"
                  button-variant="text"
                  fixed-position="right"
                  :menu-items="userMenuItems"
                  :button-label="page.props.auth.user.name"
                />
              </div>

              <!-- Mobile Menu Toggle -->
              <div class="flex lg:hidden">
                <Button severity="secondary" class="p-1!" text @click="mobileMenuOpen = true">
                  <template #icon>
                    <MenuIcon class="size-6!" />
                  </template>
                </Button>
              </div>
            </template>
          </Menubar>
        </Container>
      </nav>

      <main>
        <Container vertical>
          <!-- Session-based Flash Messages -->
          <FlashMessages />

          <!-- Breadcrumbs -->
          <Breadcrumb v-if="props.breadcrumbs.length" :model="props.breadcrumbs" />

          <!-- Page Content -->
          <slot />
        </Container>
      </main>
    </div>
  </div>
</template>
