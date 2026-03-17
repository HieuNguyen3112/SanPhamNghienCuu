<template>
  <aside
    :class="[
      'fixed inset-y-0 left-0 z-40 flex flex-col border-r border-slate-200 bg-slate-100 shadow-sm transition-all duration-200 ease-in-out md:static md:inset-auto md:h-screen md:translate-x-0',
      isCollapsed ? 'w-16' : 'w-68',
      isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
    ]"
  >
    <!-- LOGO + NÚT MŨI TÊN -->
  <div class="relative border-b border-slate-200 bg-white px-4 py-3">
    <RouterLink
      :to="{ name: 'public-home' }"
      aria-label="Về trang chủ"
      class="group flex h-16 w-full items-center justify-center rounded-xl outline-none focus:outline-none focus:ring-0 active:outline-none"
    >
    <img
      src="/logo.png"
      alt="University logo"
      class="max-h-20 w-auto object-contain"
    />
    </RouterLink>
  </div>

    <!-- THÔNG TIN GIẢNG VIÊN -->
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-4">
      <div
        class="flex items-center"
        :class="isCollapsed ? 'justify-center' : 'gap-6'"
      >
        <!-- Avatar chữ cái -->
        <div
          class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-300 text-lg font-semibold text-slate-700"
        >
          <span>{{ teacherInitials }}</span>
        </div>

        <!-- Thông tin (ẩn khi collapsed) -->
        <div v-show="!isCollapsed" class="flex flex-col items-center">
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
    <!-- NÚT THU GỌN (tròn, nửa trong nửa ngoài) -->
    <div class="relative">
      <div class="border-b border-slate-200"></div>

      <button
        type="button"
        class="group absolute right-0 top-0 hidden h-full w-3 md:block"
        @click="emit('toggle-collapse')"
        :title="isCollapsed ? 'Mở rộng menu' : 'Thu gọn menu'"
        style="z-index: 9999"
      >
        <span
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1 rounded-md px-1.5 py-1 text-slate-300 opacity-0 group-hover:opacity-100 group-hover:text-slate-900 group-hover:bg-slate-200/70 transition"
        >
          {{ isCollapsed ? "›" : "‹" }}
        </span>
      </button>
    </div>

    <!-- MENU -->
    <div class="flex-1 overflow-y-auto px-3 py-4 no-scrollbar">
      <div class="space-y-2">
        <div v-for="g in menuGroups" :key="g.header.id">
          <!-- GROUP HEADER: ẩn khi sidebar collapsed -->
          <button
            v-if="!isCollapsed"
            type="button"
            class="mt-2 flex w-full items-center justify-between rounded px-2 py-2 text-[16px] font-bold tracking-wide text-slate-500 hover:bg-slate-200"
            @click="toggleGroup(g.header.id)"
          >
            <span>{{ g.header.label }}</span>
            <span class="text-xs">{{
              groupOpen[g.header.id] ? "▾" : "▸"
            }}</span>
          </button>

          <!-- GROUP ITEMS:
               - collapsed: luôn hiện icon
               - expanded: theo groupOpen -->
          <transition name="collapse">
            <div
              v-show="isCollapsed || groupOpen[g.header.id]"
              class="space-y-1"
            >
              <RouterLink
                v-for="item in g.children"
                :key="item.id"
                :to="{ name: item.routeName }"
                class="group relative mt-0.5 flex items-center rounded-md px-2 py-2 text-sm text-slate-700 transition-colors"
                :class="[
                        isActive(item.routeName!)
                          ? 'bg-white text-slate-900 shadow-sm'
                          : 'hover:bg-slate-200',
                        isCollapsed ? 'justify-center' : 'gap-3',
                      ]"
              >
                <!-- ICON -->
                <span
                  class="flex h-8 w-8 items-center justify-center rounded-md bg-transparent"
                >
                  <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                  <span v-else class="text-[10px]">•</span>
                </span>

                <!-- LABEL (ẩn khi collapsed) -->
                <span v-show="!isCollapsed">{{ item.label }}</span>

                <!-- TOOLTIP (chỉ khi collapsed) -->
                <div
                  v-if="isCollapsed"
                  class="pointer-events-none absolute left-full top-1/2 z-99999 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[11px] text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100"
                >
                  {{ item.label }}
                  <span
                    class="absolute -left-1 top-1/2 h-2 w-2 -translate-y-1/2 rotate-45 bg-slate-900"
                  ></span>
                </div>
              </RouterLink>
            </div>
          </transition>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
      <p v-show="!isCollapsed" class="text-[11px] text-slate-500">
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
import { useSidebarGroups } from "./useSidebarGroups";
import type { MenuItem } from "@/app/config/menu.types";
defineProps<{
  isOpen: boolean; // mở/đóng cho mobile drawer
  isCollapsed: boolean; // thu gọn/mở rộng cho desktop
}>();

const emit = defineEmits<{
  (e: "toggle-collapse"): void;
}>();

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
  const roleUser = userStore.role;
  return roleUser ? roleLabelMap[roleUser] : "Không xác định";
});

// Menu items
const menuItems = computed(
  () => buildMenuForRole(userStore.role) as MenuItem[]
);
const { menuGroups, groupOpen, toggleGroup } = useSidebarGroups(
  menuItems,
  route
);

// Active route
const isActive = (routeName: string) => route.name === routeName;

// Avatar chữ cái
const teacherInitials = computed(() => {
  const name = teacher.value.name?.trim();
  if (!name) return "?";
  const parts = name.split(" ").filter(Boolean);
  const last = parts[parts.length - 1] ?? "";
  return last.charAt(0).toUpperCase() || "?";
});

const currentYear = new Date().getFullYear();
</script>

<style scoped>
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.18s ease;
}
.collapse-enter-from,
.collapse-leave-to {
  max-height: 0;
  opacity: 0;
  overflow: hidden;
}
.collapse-enter-to,
.collapse-leave-from {
  max-height: 1000px;
  opacity: 1;
  overflow: hidden;
}
.no-scrollbar {
  -ms-overflow-style: none; /* IE/Edge cũ */
  scrollbar-width: none; /* Firefox */
}

.no-scrollbar::-webkit-scrollbar {
  width: 0;
  height: 0;
}
</style>
