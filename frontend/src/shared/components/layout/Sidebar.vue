<template>
  <aside
    id="app-sidebar"
    ref="sidebarRef"
    :aria-hidden="!isDesktop && !isDrawerOpen"
    :tabindex="isDesktop ? undefined : -1"
    aria-label="Thanh điều hướng chính"
    class="fixed inset-y-0 left-0 z-50 flex h-full shrink-0 flex-col border-r border-slate-200 bg-slate-100 transition-[width,transform] duration-200 ease-in-out lg:relative lg:inset-auto lg:z-auto lg:h-screen lg:translate-x-0"
    :class="[
      isDrawerOpen || isDesktop ? 'translate-x-0' : '-translate-x-full',
      'w-[82vw] max-w-[20rem]',
      isCollapsedDesktop ? 'lg:w-16' : 'lg:w-[17rem]',
      isDesktop ? 'shadow-sm' : 'shadow-xl',
    ]"
  >
    <div class="relative border-b border-slate-200 bg-white px-4 py-3">
      <button
        type="button"
        class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
        aria-label="Đóng menu điều hướng"
        @click="emit('close-drawer')"
      >
        <span aria-hidden="true" class="text-lg leading-none">×</span>
      </button>

      <RouterLink
        :to="{ name: 'public-home' }"
        aria-label="Về trang chủ"
        class="group flex h-16 w-full items-center justify-center rounded-xl outline-none focus:outline-none focus:ring-0 active:outline-none"
      >
        <img
          src="/logo.png"
          alt="Logo trường đại học"
          class="w-auto object-contain"
          :class="isCollapsedDesktop ? 'max-h-16' : 'max-h-20'"
        />
      </RouterLink>
    </div>

    <div
      class="border-b border-slate-200 bg-slate-50"
      :class="isCollapsedDesktop ? 'px-2 py-4' : 'px-4 py-4'"
    >
      <div
        class="flex items-center"
        :class="isCollapsedDesktop ? 'justify-center' : 'gap-4'"
      >
        <div
          class="flex items-center justify-center rounded-full bg-slate-300 font-semibold text-slate-700"
          :class="
            isCollapsedDesktop ? 'h-12 w-12 text-base' : 'h-14 w-14 text-lg'
          "
        >
          <span>{{ teacherInitials }}</span>
        </div>

        <div v-if="!isCollapsedDesktop" class="min-w-0 flex-1 text-center">
          <span class="block truncate text-sm font-semibold text-slate-900">
            {{ teacher.name }}
          </span>
          <span class="block truncate text-xs text-slate-500">
            {{ teacher.code }}
          </span>
        </div>
      </div>
    </div>

    <div class="relative hidden lg:block">
      <div class="border-b border-slate-200"></div>

      <button
        type="button"
        class="group absolute right-0 top-0 h-full w-4"
        :title="isCollapsedDesktop ? 'Mở rộng menu' : 'Thu gọn menu'"
        @click="emit('toggle-collapse')"
      >
        <span
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1 rounded-md bg-slate-200/70 px-1.5 py-1 text-slate-500 opacity-0 transition group-hover:opacity-100 group-hover:text-slate-900"
        >
          {{ isCollapsedDesktop ? "›" : "‹" }}
        </span>
      </button>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-4 no-scrollbar">
      <div class="space-y-2">
        <div v-for="group in menuGroups" :key="group.header.id">
          <button
            v-if="!isCollapsedDesktop"
            type="button"
            class="mt-2 flex w-full items-center justify-between rounded px-2 py-2 text-[15px] font-bold tracking-wide text-slate-500 transition hover:bg-slate-200"
            @click="toggleGroup(group.header.id)"
          >
            <span>{{ group.header.label }}</span>
            <span class="text-xs">{{
              groupOpen[group.header.id] ? "▾" : "▸"
            }}</span>
          </button>

          <transition name="collapse">
            <div
              v-show="isCollapsedDesktop || groupOpen[group.header.id]"
              class="space-y-1"
            >
              <RouterLink
                v-for="item in group.children"
                :key="item.id"
                :to="{ name: item.routeName }"
                class="group relative mt-0.5 flex items-center rounded-md px-2 py-2 text-sm text-slate-700 transition-colors"
                :class="[
                  isActive(item.routeName!)
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'hover:bg-slate-200',
                  isCollapsedDesktop ? 'justify-center' : 'gap-3',
                ]"
                :title="isCollapsedDesktop ? item.label : undefined"
                @click="handleItemClick"
              >
                <span
                  class="flex h-8 w-8 items-center justify-center rounded-md bg-transparent"
                >
                  <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                  <span v-else class="text-[10px]">•</span>
                </span>

                <span v-if="!isCollapsedDesktop" class="truncate">{{
                  item.label
                }}</span>

                <div
                  v-if="isCollapsedDesktop"
                  class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[11px] text-white opacity-0 shadow-lg transition-opacity duration-150 group-hover:opacity-100"
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

    <div
      class="border-t border-slate-200 bg-slate-50"
      :class="isCollapsedDesktop ? 'px-2 py-3' : 'px-4 py-3'"
    >
      <p v-if="!isCollapsedDesktop" class="text-[11px] text-slate-500">
        © {{ currentYear }} Phần mềm quản lý NCKH
      </p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { buildMenuForRole } from "@/app/config/menu";
import { useSidebarGroups } from "./useSidebarGroups";
import type { MenuItem } from "@/app/config/menu.types";

const props = defineProps<{
  isDesktop: boolean;
  isDrawerOpen: boolean;
  isCollapsed: boolean;
}>();

const emit = defineEmits<{
  (e: "toggle-collapse"): void;
  (e: "navigate"): void;
  (e: "close-drawer"): void;
}>();

const sidebarRef = ref<HTMLElement | null>(null);
const userStore = useUserStore();
const route = useRoute();

const teacher = computed(
  () =>
    userStore.currentUser ?? {
      id: "",
      name: "",
      code: "",
      department: "",
      role: "",
    },
);

const isCollapsedDesktop = computed(() => props.isDesktop && props.isCollapsed);





const menuItems = computed(
  () => buildMenuForRole(userStore.role) as MenuItem[],
);
const { menuGroups, groupOpen, toggleGroup } = useSidebarGroups(
  menuItems,
  route,
);

const isActive = (routeName: string) => route.name === routeName;

const teacherInitials = computed(() => {
  const name = teacher.value.name?.trim();
  if (!name) return "?";
  const parts = name.split(" ").filter(Boolean);
  const last = parts[parts.length - 1] ?? "";
  return last.charAt(0).toUpperCase() || "?";
});

const currentYear = new Date().getFullYear();

const handleItemClick = () => {
  if (!props.isDesktop) {
    emit("navigate");
  }
};

const focusDrawer = () => {
  sidebarRef.value?.focus();
};

defineExpose({ focusDrawer });
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
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  width: 0;
  height: 0;
}
</style>
