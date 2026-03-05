<template>
  <div class="sticky top-0 z-40 bg-[#234a74] shadow-sm">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-2 md:px-6">
      <!-- Left menu -->
      <nav
        class="flex items-center gap-1 overflow-x-auto whitespace-nowrap text-[13px] font-normal tracking-normal md:gap-2"
      >
        <RouterLink :to="{ path: '/' }" :class="navClass(isActiveExact('/'))">Trang chủ</RouterLink>

        <RouterLink :to="{ path: '/giang-vien' }" :class="navClass(isActivePrefix('/giang-vien'))">Giảng viên</RouterLink>

        <RouterLink :to="{ path: '/bai-bao-khoa-hoc' }" :class="navClass(isActivePrefix('/bai-bao-khoa-hoc'))">
          Bài báo khoa học
        </RouterLink>

        <RouterLink :to="{ path: '/de-tai-nghien-cuu' }" :class="navClass(isActivePrefix('/de-tai-nghien-cuu'))">
          Đề tài nghiên cứu
        </RouterLink>

        <RouterLink :to="{ path: '/sach-giao-trinh' }" :class="navClass(isActivePrefix('/sach-giao-trinh'))">
          Sách - Giáo trình
        </RouterLink>

        <RouterLink
          :to="{ path: '/hoi-thao-bao-cao-khoa-hoc' }"
          :class="navClass(isActivePrefix('/hoi-thao-bao-cao-khoa-hoc'))"
        >
          Hội thảo - Báo cáo khoa học
        </RouterLink>

        <RouterLink :to="{ path: '/huong-dan-su-dung' }" :class="navClass(isActivePrefix('/huong-dan-su-dung'))">
          Hướng dẫn sử dụng
        </RouterLink>
      </nav>

      <!-- Right auth -->
      <div class="flex items-center gap-3 whitespace-nowrap">
        <RouterLink
          v-if="!isAuthenticated"
          to="/login"
          class="inline-flex h-9 min-w-[112px] items-center justify-center rounded-xl bg-[#e11d48] px-5 text-[13px] font-normal leading-none text-white shadow-sm hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/35"
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
            <span class="max-w-[240px] truncate">{{ lecturerName || "Tài khoản" }}</span>
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
    "h-9 whitespace-nowrap rounded-xl px-3 text-white/90 hover:bg-white/10 hover:text-white focus:outline-none inline-flex items-center",
    active ? "bg-white/15 text-white" : "",
  ].join(" ");
}
</script>