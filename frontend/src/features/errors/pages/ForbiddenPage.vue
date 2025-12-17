<template>
  <div class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="max-w-md w-full rounded-lg bg-white p-8 shadow text-center space-y-3">
      <h1 class="text-3xl font-semibold text-slate-900">403</h1>
      <p class="text-slate-600">Ban khong co quyen truy cap trang nay.</p>
      <RouterLink
        to="/"
        class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
      >
        Ve trang chu
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";
</script>
