<template>
  <div class="sticky top-0 z-40 bg-[#234a74] shadow-sm">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-2 md:px-6">
      <!-- Left menu (no scrollbar) -->
      <nav class="flex min-w-0 flex-1 items-center gap-1 overflow-hidden text-[12px] font-normal md:gap-2">
        <RouterLink :to="{ path: '/' }" :class="navClass(isActiveExact('/'))">Trang chủ</RouterLink>

        <RouterLink :to="{ path: '/lecturers' }" :class="navClass(isActivePrefix('/lecturers'))">Giảng viên</RouterLink>

        <RouterLink :to="{ path: '/research-articles' }" :class="navClass(isActivePrefix('/research-articles'))">
          Bài báo khoa học
        </RouterLink>

        <RouterLink :to="{ path: '/research-projects' }" :class="navClass(isActivePrefix('/research-projects'))">
          Đề tài nghiên cứu
        </RouterLink>

        <RouterLink :to="{ path: '/textbooks' }" :class="navClass(isActivePrefix('/textbooks'))">
          Sách - Giáo trình
        </RouterLink>

        <RouterLink
          :to="{ path: '/research-conferences' }"
          :class="navClass(isActivePrefix('/research-conferences'))"
        >
          <span class="hidden lg:inline">Hội thảo - Báo cáo khoa học</span>
          <span class="lg:hidden">Hội thảo - Báo cáo</span>
        </RouterLink>

        <RouterLink :to="{ path: '/user-guide' }" :class="navClass(isActivePrefix('/user-guide'))">
          <span class="hidden lg:inline">Hướng dẫn sử dụng</span>
          <span class="lg:hidden">Hướng dẫn</span>
        </RouterLink>
      </nav>

      <!-- Right auth (always on the far right) -->
      <div class="flex shrink-0 items-center gap-2 whitespace-nowrap">
        <RouterLink
          v-if="!isAuthenticated"
          to="/login"
          class="inline-flex h-9 items-center justify-center rounded-xl bg-[#e11d48] px-4 text-[13px] font-normal text-white hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/35"
        >
          Đăng nhập
        </RouterLink>

        <div v-else class="flex items-center gap-2">
          <RouterLink
            to="/profile"
            class="flex h-9 items-center gap-2 rounded-full bg-white/10 px-3 text-[13px] font-normal text-white hover:bg-white/15 focus:outline-none"
            title="Trang cá nhân"
          >
            <span
              class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-[#234a74]"
              aria-hidden="true"
            >
              {{ initials || "U" }}
            </span>
            <span class="max-w-[180px] truncate">{{ lecturerName || "Tài khoản" }}</span>
          </RouterLink>

          <button
            type="button"
            class="h-9 rounded-xl border border-white/20 bg-white/10 px-3 text-[13px] font-normal text-white hover:bg-white/15 focus:outline-none"
            @click="$emit('logout')"
          >
            Đăng xuất
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink, useRoute } from "vue-router";

defineProps<{
  isAuthenticated: boolean;
  lecturerName: string;
  lecturerCode: string;
  initials: string;
}>();

defineEmits<{
  (e: "logout"): void;
}>();

const route = useRoute();

function isActiveExact(path: string) {
  return route.path === path;
}
function isActivePrefix(prefix: string) {
  return route.path === prefix || route.path.startsWith(prefix + "/");
}
function navClass(active: boolean) {
  return [
    "h-9 whitespace-nowrap rounded-xl px-2.5 text-white/90 hover:bg-white/10 hover:text-white inline-flex items-center transition",
    active ? "bg-white/15 text-white" : "",
  ].join(" ");
}
</script>