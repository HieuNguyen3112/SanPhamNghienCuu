<template>
  <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div
      class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3"
    >
      <div>
        <h2 class="text-sm font-semibold text-slate-900">{{ title }}</h2>
        <p v-if="subtitle" class="mt-0.5 text-xs text-slate-500">
          {{ subtitle }}
        </p>
      </div>
      <div v-if="$slots.actions" class="shrink-0">
        <slot name="actions" />
      </div>
    </div>

    <div class="p-4">
      <slot />
    </div>
  </section>
</template>

<script setup lang="ts">
defineProps<{
  title: string;
  subtitle?: string;
}>();
</script>
