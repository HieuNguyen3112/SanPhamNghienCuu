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
            v-if="logoSrc"
            :src="logoSrc"
            alt="University logo"
            class="max-h-20 w-auto object-contain"
          />
          <div
            v-else
            class="flex h-14 w-full items-center justify-center rounded bg-slate-100 text-xs font-semibold tracking-wide text-slate-500"
          >
            LOGO TRƯỜNG
          </div>
        </div>
      </div>
    </div>

    <!-- THÔNG TIN GIẢNG VIÊN -->
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-4">
      <div class="flex items-center gap-3">
        <div
          class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-300 text-lg font-semibold text-slate-700"
        >
          <span>{{ teacherInitials }}</span>
        </div>

        <div class="flex items-center flex-col">
          <span class="text-sm font-semibold text-slate-900">
            {{ teacher.academicTitle + teacher.name }}
          </span>
          <span class="text-sm text-slate-500">
            {{ teacher.code }}
          </span>
          <span class="text-sm text-slate-500">
            {{ teacher.role }}
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
            >
              •
            </span>
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
import { computed, toRefs } from "vue";
import { useRoute, RouterLink } from "vue-router";

interface Teacher {
  name: string;
  role: string;
  code: string;
  academicTitle: string;
}

interface MenuItem {
  id: string;
  label: string;
  routeName?: string;
}

const props = withDefaults(
  defineProps<{
    teacher: Teacher;
    menuItems: MenuItem[];
    isOpen?: boolean;
  }>(),
  {
    isOpen: true,
  }
);

const { teacher, menuItems, isOpen } = toRefs(props);

// TODO: khi có logo thật:
// import logoReal from "@/assets/images/logo-university.svg";
// const logoSrc = logoReal;
const logoSrc = "/logo.png";

const route = useRoute();

const isActive = (routeName: string) => {
  return route.name === routeName;
};

const teacherInitials = computed(() => {
  const name = teacher.value.name?.trim() ?? "";
  if (!name) return "?";
  const parts = name.split(" ").filter(Boolean);
  const last = parts[parts.length - 1];
  return last?.[0]?.toUpperCase() ?? "?";
});

const currentYear = new Date().getFullYear();
</script>
