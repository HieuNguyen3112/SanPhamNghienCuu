<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
  >
    <div class="absolute inset-0 bg-slate-900/40" @click="$emit('close')"></div>

    <div
      class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-xl"
    >
      <div
        class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4"
      >
        <div class="min-w-0">
          <div class="text-sm font-semibold text-slate-900">{{ title }}</div>
          <div v-if="subtitle" class="mt-1 text-xs text-slate-500">
            {{ subtitle }}
          </div>
        </div>

        <button
          type="button"
          class="rounded-xl px-2 py-1 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
          @click="$emit('close')"
          aria-label="Close"
        >
          ✕
        </button>
      </div>

      <div class="px-5 py-4">
        <slot />
      </div>

      <div
        class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4"
      >
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
          @click="$emit('close')"
        >
          Hủy
        </button>
        <button
          type="button"
          class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
          :disabled="confirmDisabled"
          @click="$emit('confirm')"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  open: boolean;
  title: string;
  subtitle?: string;
  confirmText: string;
  confirmDisabled: boolean;
}>();

defineEmits<{
  (e: "close"): void;
  (e: "confirm"): void;
}>();
</script>
