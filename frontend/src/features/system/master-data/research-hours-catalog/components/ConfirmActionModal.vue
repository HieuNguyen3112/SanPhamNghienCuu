<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
  >
    <div
      class="absolute inset-0 bg-slate-900/40"
      @click="$emit('cancel')"
    ></div>

    <div
      class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-xl"
    >
      <div class="border-b border-slate-200 px-5 py-4">
        <div class="flex items-start gap-3">
          <div class="mt-0.5 rounded-xl bg-rose-50 p-2 ring-1 ring-rose-200">
            <slot name="icon" />
          </div>
          <div class="min-w-0">
            <div class="text-sm font-semibold text-slate-900">{{ title }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ description }}</div>
          </div>
        </div>
      </div>

      <div class="px-5 py-4">
        <div
          class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700"
        >
          <div class="font-medium text-slate-800">Lưu ý an toàn</div>
          <div class="mt-1">
            Hành động này ảnh hưởng cấu hình hệ thống. Hãy chắc chắn trước khi
            xác nhận.
          </div>
        </div>
      </div>

      <div
        class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4"
      >
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
          @click="$emit('cancel')"
        >
          Hủy
        </button>
        <button
          type="button"
          class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
          :disabled="loading"
          @click="$emit('confirm')"
        >
          {{ loading ? "Đang xử lý..." : confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  open: boolean;
  title: string;
  description: string;
  confirmText: string;
  loading: boolean;
}>();

defineEmits<{
  (e: "cancel"): void;
  (e: "confirm"): void;
}>();
</script>
