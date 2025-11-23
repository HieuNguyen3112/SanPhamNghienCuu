<!-- Học vị – chức danh KH -->
<!-- src/features/profile/pages/ProfileAcademicRankView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">
        Học vị – chức danh khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Thông tin về học vị cao nhất và chức danh khoa học.
      </p>
    </header>

    <section class="rounded-md bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
          Học vị – chức danh
        </h2>
        <button
          class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
        >
          Chỉnh sửa
        </button>
      </div>

      <p class="text-sm text-slate-500">
        (TODO: hiển thị học vị, năm bảo vệ, PGS/GS, năm phong,...)
      </p>
    </section>
  </div>
</template>

<script setup lang="ts"></script>
