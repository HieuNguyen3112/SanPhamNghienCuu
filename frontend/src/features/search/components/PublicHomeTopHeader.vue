<template>
  <div class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 sm:py-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <RouterLink
          to="/"
          class="flex min-w-0 items-start gap-3 sm:items-center sm:gap-4"
          aria-label="Về trang chủ"
        >
          <img
            src="/logo.png"
            alt="Logo"
            class="h-12 w-auto shrink-0 object-contain sm:h-14 md:h-16"
          />

          <div class="min-w-0">
            <div
              class="text-sm font-extrabold leading-tight tracking-wide text-slate-900 sm:text-base md:text-lg"
            >
              TRƯỜNG ĐẠI HỌC SƯ PHẠM THÀNH PHỐ HỒ CHÍ MINH
            </div>
            <div
              class="mt-1 text-xs font-semibold leading-snug text-rose-700 sm:text-sm md:text-base"
            >
              Ho Chi Minh City University of Education
            </div>
          </div>
        </RouterLink>

        <div class="flex items-center gap-3 self-start sm:self-auto">
          <span
            class="inline-flex h-5 w-8 items-center justify-center rounded-sm bg-red-600 text-[10px] font-bold text-yellow-200"
          >
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