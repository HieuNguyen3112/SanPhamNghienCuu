<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div
        class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-lg"
      >
        <div
          class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3"
        >
          <div class="min-w-0">
            <div class="truncate text-sm font-semibold text-slate-900">
              {{ title }}
            </div>
            <div v-if="subtitle" class="mt-0.5 text-xs text-slate-500">
              {{ subtitle }}
            </div>
          </div>

          <button
            type="button"
            class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-50 hover:text-slate-900"
            @click="emit('close')"
            title="Đóng"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="px-4 py-4">
          <slot />
        </div>

        <div
          class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3"
        >
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            :disabled="submitting"
            @click="emit('close')"
          >
            Hủy
          </button>
          <button
            type="button"
            class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
            :disabled="submitting"
            @click="emit('submit')"
          >
            {{ submitting ? "Đang lưu..." : "Lưu" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { X } from "lucide-vue-next";

defineProps<{
  open: boolean;
  title: string;
  subtitle?: string;
  submitting: boolean;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "submit"): void;
}>();
</script>
