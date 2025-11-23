<!-- # Màn hình hồ sơ khoa học sẽ hiển thị các thông tin người dùng có thể export ra file cv  -->
<!-- src/features/profile/pages/ProfileScientificView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Hồ sơ khoa học</h1>
      <p class="mt-1 text-sm text-slate-500">
        Tổng quan thông tin hồ sơ khoa học của giảng viên: liên hệ, công tác,
        đào tạo, lĩnh vực nghiên cứu, học vị – chức danh và trình độ ngoại ngữ.
      </p>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="rounded-md bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800">Thông tin liên hệ</h2>
        <p class="mt-1 text-xs text-slate-500">
          Email, số điện thoại, địa chỉ làm việc...
        </p>
        <button
          class="mt-3 rounded bg-[#234a74] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1b3a5a]"
          @click="$router.push({ name: 'profile.contact' })"
        >
          Xem chi tiết
        </button>
      </div>

      <!-- Bạn có thể nhân bản card này cho các phần khác -->
      <div class="rounded-md bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800">Quá trình công tác</h2>
        <p class="mt-1 text-xs text-slate-500">
          Các đơn vị, chức vụ đã và đang công tác.
        </p>
        <button
          class="mt-3 rounded bg-[#234a74] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#1b3a5a]"
          @click="$router.push({ name: 'profile.workHistory' })"
        >
          Xem chi tiết
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// tạm thời chưa cần logic gì, sau này sẽ gọi API ở đây
</script>
