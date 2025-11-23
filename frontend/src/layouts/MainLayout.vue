<!-- src/layouts/MainLayout.vue -->
<template>
  <div class="flex h-screen bg-slate-100">
    <Sidebar
      class="h-full w-64 shrink-0"
      :teacher="teacher"
      :menu-items="menuItems"
      :is-open="isSidebarOpen"
    />

    <div class="flex min-h-screen flex-1 flex-col">
      <Navbar
        class="h-16 flex items-center border-b border-slate-200 bg-rgb(23, 43, 77) px-6 shadow-sm"
        :user-name="teacher.name"
        :user-code="teacher.code"
        @toggle-sidebar="toggleSidebar"
        @change-password="isChangePasswordOpen = true"
      />

      <main class="flex-1 overflow-y-auto bg-slate-50 p-6">
        <RouterView />
      </main>
    </div>

    <!-- Modal đổi mật khẩu -->
    <ChangePasswordModal
      v-model="isChangePasswordOpen"
      @submit="handleChangePassword"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { RouterView } from "vue-router";

import Sidebar from "@/shared/components/layout/Sidebar.vue";
import Navbar from "@/shared/components/layout/Navbar.vue";
import ChangePasswordModal from "@/features/auth/components/ChangePasswordModal.vue";
import { buildMenuForRole } from "@/app/config/menu";

interface Teacher {
  id: string;
  name: string;
  department: string;
  code: string;
  role: string;
  academicTitle: string;
}

const menuItems = computed(() => buildMenuForRole());

const teacher: Teacher = {
  id: "GV001",
  code: "48.01.104.149",
  name: "Nguyễn Quang Vinh",
  department: "Khoa Công nghệ Thông tin",
  role: "Giảng viên",
  academicTitle: "TS.",
};

const isSidebarOpen = ref(true);
const isChangePasswordOpen = ref(false);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

const handleChangePassword = (payload: {
  oldPassword: string;
  newPassword: string;
}) => {
  // TODO: gọi API đổi mật khẩu thật sự
  console.log("Change password", payload);

  // Giả sử thành công:
  isChangePasswordOpen.value = false;
};
</script>
