<script setup lang="ts">
import { computed, ref } from "vue";
import { RouterView, useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { useLayoutStore } from "@/app/stores/layoutStore";
import Sidebar from "@/shared/components/layout/Sidebar.vue";
import Navbar from "@/shared/components/layout/Navbar.vue";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal.vue";
import { useLogoutFeedback } from "@/features/auth/composables/useLogoutFeedback";

const userStore = useUserStore();
const router = useRouter();
const layout = useLayoutStore();
const { logoutWithFeedback } = useLogoutFeedback("/");

const isSidebarOpen = ref(true);
const isChangePasswordOpen = ref(false);

const userName = computed(() => userStore.currentUser?.name ?? "");
const userCode = computed(
  () => userStore.currentUser?.code ?? userStore.currentUser?.email ?? "",
);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

const handleOpenProfile = async () => {
  if (router.hasRoute("profile.scientific")) {
    await router.push({ name: "profile.scientific" });
    return;
  }

  await router.push("/profile");
};

const handleLogout = async () => {
  await logoutWithFeedback();
};

const handleGoHome = async () => {
  await router.push("/");
};
</script>

<template>
  <div class="flex h-screen bg-slate-100">
    <Sidebar
      :is-open="isSidebarOpen"
      :is-collapsed="layout.isSidebarCollapsed"
      @toggle-collapse="layout.toggleSidebarCollapse()"
    />

    <div class="flex flex-1 flex-col">
      <Navbar
        :user-name="userName"
        :user-code="userCode"
        @toggle-sidebar="toggleSidebar"
        @open-profile="handleOpenProfile"
        @change-password="isChangePasswordOpen = true"
        @logout="handleLogout"
        @go-home="handleGoHome"
      />

      <main class="flex-1 overflow-y-auto bg-slate-50 p-6">
        <RouterView />
      </main>
    </div>

    <ChangePasswordModal v-model="isChangePasswordOpen" />
  </div>
</template>
