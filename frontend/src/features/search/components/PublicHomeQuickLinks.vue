<template>
  <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-5">
      <button
        type="button"
        class="h-14 rounded-lg bg-[#0b5fa5] px-4 text-xs font-extrabold uppercase tracking-wide text-white shadow-sm hover:brightness-110 focus:outline-none"
        @click="$emit('jump', 'search')"
      >
        Tra cứu công trình
      </button>

      <button
        type="button"
        class="h-14 rounded-lg bg-[#b0007a] px-4 text-xs font-extrabold uppercase tracking-wide text-white shadow-sm hover:brightness-110 focus:outline-none"
        @click="$emit('jump', 'lecturer')"
      >
        Theo giảng viên
      </button>

      <button
        type="button"
        class="h-14 rounded-lg bg-[#475569] px-4 text-xs font-extrabold uppercase tracking-wide text-white shadow-sm hover:brightness-110 focus:outline-none"
        @click="$emit('jump', 'faculty')"
      >
        Theo khoa
      </button>

      <button
        type="button"
        class="h-14 rounded-lg bg-[#1aa6a8] px-4 text-xs font-extrabold uppercase tracking-wide text-white shadow-sm hover:brightness-110 focus:outline-none"
        @click="$emit('jump', 'year')"
      >
        Theo năm học
      </button>

      <button
        type="button"
        class="h-14 rounded-lg bg-[#f97316] px-4 text-xs font-extrabold uppercase tracking-wide text-white shadow-sm hover:brightness-110 focus:outline-none"
        @click="$emit('jump', 'guide')"
      >
        Hướng dẫn
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineEmits<{
  (e: "jump", key: "search" | "lecturer" | "faculty" | "year" | "guide"): void;
}>();
</script>
