<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
      <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
        <div class="text-xs text-slate-600">Tổng công trình đủ điều kiện</div>
        <div class="mt-1 text-lg font-semibold text-slate-900">
          {{ totalApprovedCount }}
        </div>
      </div>

      <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
        <div class="text-xs text-slate-600">Công trình đã chọn</div>
        <div class="mt-1 text-lg font-semibold text-slate-900">
          {{ selectedCount }}
        </div>
      </div>

      <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
        <div class="text-xs text-slate-600">Tổng giờ duyệt đã chọn</div>
        <div class="mt-1 text-lg font-semibold text-slate-900">
          {{ formatHours(selectedHoursTotal) }} giờ
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { formatHours } from "../contracts/selectHoursRequest.contract";

defineProps<{
  totalApprovedCount: number;
  selectedCount: number;
  selectedHoursTotal: number;
}>();
</script>
