<script setup lang="ts">
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from "vue";
import { RouterView, useRoute, useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import type { UserRole } from "@/app/stores/userStore";
import { useLayoutStore } from "@/app/stores/layoutStore";
import Sidebar from "@/shared/components/layout/Sidebar.vue";
import Navbar from "@/shared/components/layout/Navbar.vue";
import RoleSwitchConfirmModal from "@/shared/components/layout/RoleSwitchConfirmModal.vue";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal.vue";
import { useLogoutFeedback } from "@/features/auth/composables/useLogoutFeedback";

const DESKTOP_BREAKPOINT = "(min-width: 1024px)";

const userStore = useUserStore();
const route = useRoute();
const router = useRouter();
const layout = useLayoutStore();
const { logoutWithFeedback } = useLogoutFeedback("/");

const sidebarRef = ref<InstanceType<typeof Sidebar> | null>(null);
const desktopMediaQuery = ref<MediaQueryList | null>(null);
const isDesktop = ref(false);
const isMobileSidebarOpen = ref(false);
const isChangePasswordOpen = ref(false);
const isRoleSwitchDialogOpen = ref(false);
const roleSwitchPending = ref<UserRole | null>(null);
const switchingRole = ref(false);

const userName = computed(() => userStore.currentUser?.name ?? "");
const userCode = computed(
  () => userStore.currentUser?.code ?? userStore.currentUser?.email ?? "",
);

const isMobileDrawerOpen = computed(
  () => !isDesktop.value && isMobileSidebarOpen.value,
);

const closeMobileSidebar = () => {
  isMobileSidebarOpen.value = false;
};

const toggleSidebar = () => {
  if (isDesktop.value) {
    return;
  }

  isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
};

const handleOpenProfile = async () => {
  if (userStore.role !== "LECTURER") {
    await router.push(resolveRoleLandingTarget(userStore.role ?? null));
    return;
  }

  if (router.hasRoute("profile.scientific")) {
    await router.push({ name: "profile.scientific" });
    return;
  }

  await router.push("/declarations/gateway");
};

const handleLogout = async () => {
  await logoutWithFeedback();
};

const handleGoHome = async () => {
  await router.push("/");
};

const resolveRoleLandingTarget = (role: UserRole | null) => {
  if (role === "LECTURER") {
    if (router.hasRoute("declarations.gateway")) {
      return { name: "declarations.gateway" };
    }
    if (router.hasRoute("profile.scientific")) {
      return { name: "profile.scientific" };
    }
    return { path: "/profile" };
  }

  if (role === "DEPARTMENT_BOARD") {
    if (router.hasRoute("works.facapprovals")) {
      return { name: "works.facapprovals" };
    }
    if (router.hasRoute("user.manager")) {
      return { name: "user.manager" };
    }
    return { path: "/works/facapprovals" };
  }

  if (role === "SCIENCE_OFFICE") {
    if (router.hasRoute("works.uniapprovals")) {
      return { name: "works.uniapprovals" };
    }
    if (router.hasRoute("user.manager")) {
      return { name: "user.manager" };
    }
    return { path: "/works/uniapprovals" };
  }

  return { path: "/" };
};

const handleChangeRole = async (role: UserRole) => {
  if (!userStore.currentUser) {
    return;
  }

  if (userStore.role === role) {
    return;
  }

  roleSwitchPending.value = role;
  isRoleSwitchDialogOpen.value = true;
};

const cancelRoleSwitch = () => {
  if (switchingRole.value) return;
  isRoleSwitchDialogOpen.value = false;
  roleSwitchPending.value = null;
};

const confirmRoleSwitch = async () => {
  if (switchingRole.value) return;

  const nextRole = roleSwitchPending.value;
  if (!nextRole) {
    cancelRoleSwitch();
    return;
  }

  switchingRole.value = true;
  try {
    await userStore.switchRole(nextRole);

    // Always reset to role landing page to avoid staying in stale/over-scoped screens.
    await router.replace(resolveRoleLandingTarget(nextRole));

    isRoleSwitchDialogOpen.value = false;
    roleSwitchPending.value = null;
  } catch (error) {
    console.error(error);
  } finally {
    switchingRole.value = false;
  }
};

const syncDesktopState = (matchesDesktop: boolean) => {
  isDesktop.value = matchesDesktop;

  if (matchesDesktop) {
    closeMobileSidebar();
  }
};

const handleMediaChange = (event: MediaQueryListEvent) => {
  syncDesktopState(event.matches);
};

const handleEsc = (event: KeyboardEvent) => {
  if (event.key !== "Escape") {
    return;
  }

  closeMobileSidebar();
};

watch(isMobileDrawerOpen, async (isOpen) => {
  document.body.style.overflow = isOpen ? "hidden" : "";
  document.documentElement.style.overflow = isOpen ? "hidden" : "";

  if (!isOpen) {
    return;
  }

  await nextTick();
  sidebarRef.value?.focusDrawer();
});

watch(
  () => route.fullPath,
  () => {
    closeMobileSidebar();
  },
);

onMounted(() => {
  const mediaQuery = window.matchMedia(DESKTOP_BREAKPOINT);
  desktopMediaQuery.value = mediaQuery;
  syncDesktopState(mediaQuery.matches);

  if (typeof mediaQuery.addEventListener === "function") {
    mediaQuery.addEventListener("change", handleMediaChange);
  } else {
    mediaQuery.addListener(handleMediaChange);
  }

  document.addEventListener("keydown", handleEsc);
});

onBeforeUnmount(() => {
  document.body.style.overflow = "";
  document.documentElement.style.overflow = "";
  document.removeEventListener("keydown", handleEsc);

  const mediaQuery = desktopMediaQuery.value;
  if (!mediaQuery) {
    return;
  }

  if (typeof mediaQuery.removeEventListener === "function") {
    mediaQuery.removeEventListener("change", handleMediaChange);
  } else {
    mediaQuery.removeListener(handleMediaChange);
  }
});
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-slate-100">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <button
        v-if="isMobileDrawerOpen"
        type="button"
        class="fixed inset-0 z-40 bg-slate-950/45 lg:hidden"
        aria-label="Đóng menu điều hướng"
        @click="closeMobileSidebar"
      ></button>
    </Transition>

    <Sidebar
      ref="sidebarRef"
      :is-desktop="isDesktop"
      :is-drawer-open="isMobileDrawerOpen"
      :is-collapsed="layout.isSidebarCollapsed"
      @toggle-collapse="layout.toggleSidebarCollapse()"
      @navigate="closeMobileSidebar"
      @close-drawer="closeMobileSidebar"
    />

    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
      <Navbar
        :user-name="userName"
        :user-code="userCode"
        :current-role="userStore.role"
        :available-roles="userStore.currentUser?.roles ?? []"
        :is-desktop="isDesktop"
        :is-sidebar-drawer-open="isMobileDrawerOpen"
        @toggle-sidebar="toggleSidebar"
        @open-profile="handleOpenProfile"
        @change-password="isChangePasswordOpen = true"
        @change-role="handleChangeRole"
        @logout="handleLogout"
        @go-home="handleGoHome"
      />

      <main
        class="min-w-0 flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-5 lg:p-6"
      >
        <RouterView />
      </main>
    </div>

    <ChangePasswordModal v-model="isChangePasswordOpen" />
    <RoleSwitchConfirmModal
      :open="isRoleSwitchDialogOpen"
      :loading="switchingRole"
      :current-role="userStore.role"
      :next-role="roleSwitchPending"
      @cancel="cancelRoleSwitch"
      @confirm="confirmRoleSwitch"
    />
  </div>
</template>
