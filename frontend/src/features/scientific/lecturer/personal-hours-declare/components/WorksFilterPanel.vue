<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4">
    <div
      class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between"
    >
      <div class="grid w-full grid-cols-1 gap-3 md:grid-cols-2 lg:max-w-3xl">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-700">
            Trạng thái duyệt giờ
          </label>
          <select
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.hoursMode"
            @change="onChangeHoursMode"
          >
            <option value="all">Tất cả</option>
            <option value="not_submitted">Chưa duyệt giờ</option>
            <option value="pending">Chờ duyệt giờ</option>
            <option value="approved">Đã duyệt giờ</option>
            <option value="rejected">Bị từ chối</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-700">
            Tìm công trình
          </label>
          <div class="relative">
            <Search
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-400 focus:ring-0"
              :value="filter.keyword"
              placeholder="Mã (RA-...) / tên công trình..."
              @input="onChangeKeyword"
            />
          </div>
        </div>
      </div>

      <div class="flex w-full justify-end md:w-auto">
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="emit('reset')"
        >
          <RotateCcw class="h-4 w-4" />
          Đặt lại
        </button>
      </div>
    </div>

    <div
      class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700"
    >
      <span class="font-medium text-slate-900">Lưu ý:</span>
      Danh sách chỉ gồm công trình đã được duyệt nội dung đầy đủ (Khoa + Trường).
      Bộ lọc chỉ áp dụng cho trạng thái duyệt giờ.
    </div>
  </div>
</template>

<script setup lang="ts">
import { RotateCcw, Search } from "lucide-vue-next";
import type { WorksFilterState } from "../contracts/selectHoursRequest.contract";

defineProps<{
  filter: WorksFilterState;
  loading: boolean;
}>();

const emit = defineEmits<{
  (e: "update:filter", partial: Partial<WorksFilterState>): void;
  (e: "reset"): void;
}>();

function onChangeHoursMode(event: Event) {
  const value = (event.target as HTMLSelectElement)
    .value as WorksFilterState["hoursMode"];
  emit("update:filter", { hoursMode: value });
}

function onChangeKeyword(event: Event) {
  emit("update:filter", { keyword: (event.target as HTMLInputElement).value });
}
</script>
