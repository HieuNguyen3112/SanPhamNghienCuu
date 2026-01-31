<!-- src/shared/components/layout/Navbar.vue -->
<template>
  <header
    class="flex h-14 w-full items-center justify-between bg-[#234a74] px-4 text-slate-100 shadow-sm md:h-16 md:px-8"
  >
    <!-- LEFT: toggle (mobile) + title -->
    <div class="flex items-center gap-3">
      <!-- Nút toggle sidebar cho mobile -->
      <button
        type="button"
        class="flex h-9 w-9 items-center justify-center rounded-md border border-white/20 bg-white/10 text-slate-100 shadow-sm hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60 md:hidden"
        @click="emit('toggle-sidebar')"
      >
        <span class="sr-only">Toggle sidebar</span>
        <span class="space-y-1">
          <span class="block h-0.5 w-4 bg-current"></span>
          <span class="block h-0.5 w-4 bg-current"></span>
          <span class="block h-0.5 w-4 bg-current"></span>
        </span>
      </button>

      <!-- Logo + title trường -->
      <div class="flex items-center gap-3">
        <span
          class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-100 md:text-sm"
        >
          {{ "TRƯỜNG ĐẠI HỌC SƯ PHẠM THÀNH PHỐ HỒ CHÍ MINH" }}
        </span>
      </div>
    </div>

    <!-- RIGHT: flag + notification + user avatar + dropdown -->
    <div class="flex items-center gap-4 md:gap-6">
      <!-- Cờ Việt Nam -->
      <!-- <button
        type="button"
        class="flex h-6 items-center rounded-sm border border-white/10 bg-red-600 px-2 shadow-sm hover:bg-red-500"
      >
        <span class="sr-only">Ngôn ngữ: Tiếng Việt</span>
        <span class="text-[10px] font-semibold text-yellow-300">VN</span>
      </button> -->

      <!-- Notification bell -->
      <button
        type="button"
        class="relative flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-slate-100 shadow-sm hover:bg-white/20"
      >
        <span class="sr-only">Thông báo</span>
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.7"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
          <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>

        <span
          v-if="notificationCount > 0"
          class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white"
        >
          {{ notificationCount }}
        </span>
      </button>

      <!-- Avatar + dropdown -->
      <div>
        <button
          ref="avatarBtnRef"
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-300 text-slate-700 shadow-sm hover:bg-slate-200"
          @click="toggleUserMenu"
        >
          <span class="sr-only">Tài khoản</span>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path
              d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5A1.5 1.5 0 0 0 5.5 20h13A1.5 1.5 0 0 0 20 18.5C20 16 16.42 14 12 14Z"
            />
          </svg>
        </button>

        <UserDropdown
          :open="isUserMenuOpen"
          :anchor-el="avatarBtnRef"
          :user-name="userName"
          :user-code="userCode"
          @close="closeUserMenu"
          @open-profile="handleOpenProfile"
          @change-password="handleChangePassword"
          @logout="handleLogout"
        />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import UserDropdown from "@/shared/components/layout/UserDropdown.vue";

const emit = defineEmits<{
  (e: "toggle-sidebar"): void;
  (e: "open-profile"): void;
  (e: "change-password"): void;
  (e: "logout"): void;
}>();

const props = withDefaults(
  defineProps<{
    title?: string;
    notificationCount?: number;
    userName?: string;
    userCode?: string;
  }>(),
  {
    title: "TRƯỜNG ĐẠI HỌC SƯ PHẠM THÀNH PHỐ HỒ CHÍ MINH",
    notificationCount: 0,
    userName: "",
    userCode: "",
  }
);

// TODO: khi có logo thật:
// import logoReal from "@/assets/images/logo-university.svg";
// const logoSrc = logoReal;
// const logoSrc = "" as string;

const isUserMenuOpen = ref(false);
const avatarBtnRef = ref<HTMLElement | null>(null);
const userMenuRef = ref<HTMLElement | null>(null);

const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value;
};

const closeUserMenu = () => {
  isUserMenuOpen.value = false;
};

const handleOpenProfile = () => {
  emit("open-profile");
  closeUserMenu();
};

const handleChangePassword = () => {
  emit("change-password");
  closeUserMenu();
};

const handleLogout = () => {
  emit("logout");
  closeUserMenu();
};

const handleClickOutside = (event: MouseEvent) => {
  if (!isUserMenuOpen.value) return;
  const target = event.target as Node | null;
  if (
    userMenuRef.value &&
    !userMenuRef.value.contains(target) &&
    avatarBtnRef.value &&
    !avatarBtnRef.value.contains(target)
  ) {
    closeUserMenu();
  }
};

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
});

const { notificationCount, userName, userCode } = props;
</script>
