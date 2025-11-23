<template>
  <header
    class="flex h-full flex-col items-center justify-center space-y-6 text-center text-white lg:pr-6"
    aria-label="University introduction"
  >
    <!-- Logo HCMUE -->
    <div class="flex items-center justify-center">
      <img
        src="/logo.png"
        alt="Logo Trường Đại học Sư phạm Thành phố Hồ Chí Minh"
        class="h-32 w-auto"
      />
    </div>

    <!-- University name -->
    <div class="mx-auto max-w-2xl">
      <h1
        class="text-3xl font-semibold leading-snug tracking-wide sm:text-2xl lg:text-4xl"
      >
        TRƯỜNG ĐẠI HỌC SƯ PHẠM
        <br />
        THÀNH PHỐ HỒ CHÍ MINH
      </h1>
      <h2
        class="text-2xl text-red-600 font-semibold leading-snug tracking-wide sm:text-2xl lg:text-xl"
      >
        Quản lý công trình nghiên cứu khoa học
      </h2>
    </div>
  </header>
</template>

<script setup lang="ts">
// Static hero component for HCMUE login/landing pages.
</script>
