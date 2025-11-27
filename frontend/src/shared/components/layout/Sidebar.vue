// src/shared/components/layout/Sidebar.vue
<template>
  <aside
    :class="[
      'fixed inset-y-0 left-0 z-40 flex w-68 flex-col border-r border-slate-200 bg-slate-100 shadow-sm transition-transform duration-200 ease-in-out md:static md:inset-auto md:h-screen md:translate-x-0',
      isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
    ]"
  >
    <!-- LOGO -->
    <div class="border-b border-slate-200 bg-white px-4 py-3">
      <div class="flex items-center justify-center">
        <div class="flex h-16 w-full items-center justify-center">
          <!-- Thay logoSrc bằng import logo thật của bạn -->
          <img
            src="/logo.png"
            alt="University logo"
            class="max-h-20 w-auto object-contain"
          />
        </div>
      </div>
    </div>

    <!-- THÔNG TIN GIẢNG VIÊN -->
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-4">
      <div class="flex items-center gap-6">
        <!-- Avatar chữ cái -->
        <div
          class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-300 text-lg font-semibold text-slate-700"
        >
          <span>{{ teacherInitials }}</span>
        </div>

        <!-- Thông tin -->
        <div class="flex flex-col items-center">
          <span class="text-sm font-semibold text-slate-900">
            {{ teacher.name }}
          </span>
          <span class="text-xs text-slate-500">
            {{ teacher.code }}
          </span>
          <span class="text-xs text-slate-500">
            {{ roleName }}
          </span>
        </div>
      </div>
    </div>

    <!-- MENU -->
    <div class="flex-1 overflow-y-auto px-3 py-4">
      <div class="space-y-1">
        <template v-for="item in menuItems" :key="item.id">
          <!-- HEADER: không có routeName -->
          <p
            v-if="!item.routeName"
            class="mt-4 px-2 text-[16px] font-bold tracking-wide text-slate-500"
          >
            {{ item.label }}
          </p>

          <!-- ITEM: có routeName -->
          <RouterLink
            v-else
            :to="{ name: item.routeName }"
            class="group mt-0.5 flex items-center gap-3 rounded-md px-2 py-2 text-sm text-slate-700 transition-colors"
            :class="
              isActive(item.routeName)
                ? 'bg-white text-slate-900 shadow-sm'
                : 'hover:bg-slate-200'
            "
          >
            <!-- Icon placeholder (có thể thay bằng icon thật) -->
            <span
              class="flex h-4 w-4 items-center justify-center rounded-sm bg-slate-300 text-[10px] text-slate-700"
            ></span>

            <span>{{ item.label }}</span>
          </RouterLink>
        </template>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
      <p class="text-[11px] text-slate-500">
        © {{ currentYear }} Phần mềm quản lý NCKH
      </p>
    </div>
  </aside>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { buildMenuForRole } from "@/app/config/menu";

const props = defineProps<{
  isOpen: boolean;
}>();

// Store & route
const userStore = useUserStore();
const route = useRoute();

// Teacher (null-safe)

const teacher = computed(
  () =>
    userStore.currentUser ?? {
      id: "",
      name: "",
      code: "",
      department: "",
      role: "",
    }
);
const roleLabelMap: Record<string, string> = {
  LECTURER: "Giảng viên",
  DEPARTMENT_BOARD: "Ban chủ nhiệm khoa",
  SCIENCE_OFFICE: "Phòng quản lý khoa học",
};

const roleName = computed(() => {
  const roleUser = userStore.role; //
  return roleUser ? roleLabelMap[roleUser] : "Không xác định";
});

// Menu theo role

const menuItems = computed(() => buildMenuForRole(userStore.role));

// Active route
const isActive = (routeName: string) => route.name === routeName;

// Avatar chữ cái

const teacherInitials = computed(() => {
  const name = teacher.value.name?.trim();
  if (!name) return "?";

  const parts = name.split(" ").filter(Boolean);
  if (!parts.length) return "?";

  const last = parts[parts.length - 1] ?? ""; // fallback to empty string if undefined
  return last.charAt(0).toUpperCase() || "?";
});

// Năm hiện tại
const currentYear = new Date().getFullYear();
</script>
