<template>
  <div class="sticky top-0 z-40 bg-[#234a74] shadow-sm">
    <div
      class="mx-auto flex max-w-6xl items-center justify-between px-4 py-2 md:px-6"
    >
      <!-- Left menu -->
      <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide">
        <button
          type="button"
          class="h-9 rounded-md px-3 text-white hover:bg-white/10 focus:outline-none"
          @click="$emit('go-home')"
        >
          TRANG CHỦ
        </button>

        <button
          type="button"
          class="h-9 rounded-md px-3 text-white hover:bg-white/10 focus:outline-none"
          @click="$emit('go-search')"
        >
          TRA CỨU CÔNG TRÌNH
        </button>

        <span class="mx-1 hidden text-white/60 md:inline">|</span>

        <span class="hidden h-9 items-center rounded-md px-3 text-white/80 md:inline-flex">
          NGÀNH
        </span>
      </nav>

      <!-- Right auth -->
      <div class="flex items-center gap-3">
        <RouterLink
          v-if="!isAuthenticated"
          to="/login"
          class="h-9 rounded-md bg-[#e11d48] px-4 text-xs font-extrabold text-white shadow-sm hover:brightness-110 focus:outline-none"
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
            <span class="max-w-[240px] truncate">
              {{ lecturerName || "Tài khoản" }}
            </span>
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
import { RouterLink } from "vue-router";

defineProps<{
  isAuthenticated: boolean;
  lecturerName: string;
  lecturerCode: string;
  initials: string;
}>();

defineEmits<{
  (e: "go-home"): void;
  (e: "go-search"): void;
  (e: "logout"): void;
}>();
</script>
