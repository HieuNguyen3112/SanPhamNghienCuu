<template>
  <div class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-4 md:px-6">
      <div class="flex items-center justify-between gap-4">
        <RouterLink to="/" class="flex items-center gap-4" aria-label="Về trang chủ">
          <img
            src="/logo.png"
            alt="Logo"
            class="h-14 w-auto object-contain"
          />

          <div class="min-w-0">
            <div class="truncate text-lg font-extrabold tracking-wide text-slate-900">
              TRƯỜNG ĐẠI HỌC SƯ PHẠM THÀNH PHỐ HỒ CHÍ MINH
            </div>
            <div class="truncate text-base font-semibold text-rose-700">
              Ho Chi Minh City University of Education
            </div>
          </div>
        </RouterLink>

        <div class="hidden items-center gap-3 md:flex">
          <span class="inline-flex h-5 w-8 items-center justify-center rounded-sm bg-red-600 text-[10px] font-bold text-yellow-200">
            VN
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";
</script>
