<!-- Thông tin liên hệ -->
<!-- src/features/profile/pages/ProfileContactView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Thông tin liên hệ</h1>
      <p class="mt-1 text-sm text-slate-500">
        Thông tin liên hệ cá nhân và cơ quan của giảng viên.
      </p>
    </header>

    <section class="rounded-md bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
          Hồ sơ cá nhân - Thông tin liên hệ
        </h2>
        <button
          class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
        >
          Chỉnh sửa
        </button>
      </div>

      <!-- TODO: thay bằng dữ liệu thật -->
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">Email</p>
          <p class="mt-1 text-sm text-slate-800">vinh@example.edu.vn</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide text-slate-400">
            Số điện thoại
          </p>
          <p class="mt-1 text-sm text-slate-800">0900 000 000</p>
        </div>
        <div class="md:col-span-2">
          <p class="text-xs uppercase tracking-wide text-slate-400">
            Địa chỉ làm việc
          </p>
          <p class="mt-1 text-sm text-slate-800">Trường ĐH Sư phạm TP.HCM</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
// sau này bind dữ liệu từ API / store vào
</script>
