<template>
  <div
    class="sticky bottom-0 z-30 -mx-4 border-t border-slate-200 bg-white/90 px-4 py-3 backdrop-blur md:-mx-6 md:px-6"
  >
    <div class="flex items-center justify-between gap-3">
      <div class="text-sm text-slate-700">
        <span class="font-medium text-slate-900">Tổng giờ NCKH đã chọn:</span>
        <span class="ml-1 font-semibold text-slate-900">
          {{ formatHours(selectedHoursTotal) }} giờ
        </span>
      </div>

      <div class="flex items-center gap-3">
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:opacity-50"
          :disabled="selectedCount === 0 || submitting"
          @click="emit('submit')"
        >
          <Check class="h-4 w-4" />
          Gửi yêu cầu xét duyệt giờ NCKH
        </button>
      </div>
    </div>

    <div
      v-if="submitError"
      class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
    >
      {{ submitError }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { Check } from "lucide-vue-next";
import { formatHours } from "../contracts/selectHoursRequest.contract";

defineProps<{
  selectedCount: number;
  selectedHoursTotal: number;
  submitting: boolean;
  submitError: string | null;
}>();

const emit = defineEmits<{
  (e: "submit"): void;
}>();
</script>
