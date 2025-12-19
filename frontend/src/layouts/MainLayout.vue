<!-- src/layouts/MainLayout.vue -->
<script setup lang="ts">
import { ref } from "vue";
import { RouterView, useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { useLayoutStore } from "@/app/stores/layoutStore";
import Sidebar from "@/shared/components/layout/Sidebar.vue";
import Navbar from "@/shared/components/layout/Navbar.vue";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal.vue";

const userStore = useUserStore();
const router = useRouter();

const layout = useLayoutStore();
const isSidebarOpen = ref(true);
const isChangePasswordOpen = ref(false);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

const handleLogout = async () => {
  await userStore.logout();
  await router.replace("/login");
};
</script>

<template>
  <div class="flex h-screen bg-slate-100">
    <Sidebar
      :is-open="isSidebarOpen"
      :is-collapsed="layout.isSidebarCollapsed"
      @toggle-collapse="layout.toggleSidebarCollapse()"
    />

    <div class="flex flex-col flex-1">
      <Navbar
        @toggle-sidebar="toggleSidebar"
        @change-password="isChangePasswordOpen = true"
        @logout="handleLogout"
      />

      <main class="p-6 flex-1 overflow-y-auto bg-slate-50">
        <RouterView />
      </main>
    </div>

    <ChangePasswordModal v-model="isChangePasswordOpen" />
  </div>
</template>
