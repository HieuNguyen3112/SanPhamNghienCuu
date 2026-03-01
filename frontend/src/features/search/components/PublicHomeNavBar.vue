<template>
  <div class="sticky top-0 z-40 bg-[#234a74] shadow-sm">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-2 md:px-6">
      <!-- Left menu -->
      <nav class="flex items-center gap-1 text-[11px] font-extrabold uppercase tracking-wide md:gap-2">
        <RouterLink :to="{ path: '/' }" :class="navClass(isActiveExact('/'))">TRANG CHỦ</RouterLink>

        <RouterLink :to="{ path: '/giang-vien' }" :class="navClass(isActivePrefix('/giang-vien'))">GIẢNG VIÊN</RouterLink>

        <RouterLink :to="{ path: '/bai-bao-khoa-hoc' }" :class="navClass(isActivePrefix('/bai-bao-khoa-hoc'))">
          BÀI BÁO KHOA HỌC
        </RouterLink>

        <RouterLink :to="{ path: '/de-tai-nghien-cuu' }" :class="navClass(isActivePrefix('/de-tai-nghien-cuu'))">
          ĐỀ TÀI NGHIÊN CỨU
        </RouterLink>

        <RouterLink :to="{ path: '/sach-giao-trinh' }" :class="navClass(isActivePrefix('/sach-giao-trinh'))">
          SÁCH - GIÁO TRÌNH
        </RouterLink>

        <RouterLink :to="{ path: '/hoi-thao-bao-cao-khoa-hoc' }" :class="navClass(isActivePrefix('/hoi-thao-bao-cao-khoa-hoc'))">
          HỘI THẢO - BÁO CÁO KHOA HỌC
        </RouterLink>

        <RouterLink :to="{ path: '/huong-dan-su-dung' }" :class="navClass(isActivePrefix('/huong-dan-su-dung'))">
          HƯỚNG DẪN SỬ DỤNG
        </RouterLink>
      </nav>

      <!-- Right auth -->
      <div class="flex items-center gap-3">
        <RouterLink
          v-if="!isAuthenticated"
          to="/login"
          class="inline-flex h-9 min-w-[112px] items-center justify-center rounded-md bg-[#e11d48] px-5 text-sm font-bold leading-none text-white shadow-sm hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/35"
        >
          Đăng nhập
        </RouterLink>

        <div v-else class="flex items-center gap-2">
          <RouterLink
            to="/profile"
            class="flex h-9 items-center gap-2 rounded-full bg-white/10 px-3 text-sm font-semibold text-white hover:bg-white/15 focus:outline-none"
            title="Trang cá nhân"
          >
            <span
              class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-[11px] font-extrabold text-[#234a74]"
              aria-hidden="true"
            >
              {{ initials || "U" }}
            </span>
            <span class="max-w-[240px] truncate">{{ lecturerName || "Tài khoản" }}</span>
          </RouterLink>

          <button
            type="button"
            class="h-9 rounded-md border border-white/20 bg-white/10 px-3 text-xs font-extrabold text-white hover:bg-white/15 focus:outline-none"
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
    "h-9 rounded-md px-3 text-white hover:bg-white/10 focus:outline-none inline-flex items-center",
    active ? "bg-white/15" : "",
  ].join(" ");
}
</script>