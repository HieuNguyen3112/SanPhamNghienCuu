<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4">
    <div
      class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
    >
      <div class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <slot
          name="extraFilter"
          :filter="filter"
          :updateFilter="emitUpdateFilter"
        />

        <div>
          <label class="text-xs text-slate-600">Năm học</label>
          <select
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.yearId"
            @change="onChangeYear"
          >
            <option v-for="y in yearOptions" :key="y.id" :value="y.id">
              {{ y.code }}
            </option>
          </select>
        </div>

        <div>
          <label class="text-xs text-slate-600">Trạng thái KPI</label>
          <select
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.kpiStatus"
            @change="onChangeKpiStatus"
          >
            <option value="all">Tất cả</option>
            <option value="hit">Đạt</option>
            <option value="miss">Thiếu</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-slate-600">Tìm giảng viên</label>
          <input
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.keyword"
            placeholder="Mã / Họ tên..."
            @input="onChangeKeyword"
          />
        </div>
      </div>

      <div class="flex justify-end">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 md:w-auto"
          @click="emit('reset')"
          title="Đặt lại bộ lọc"
          aria-label="Đặt lại bộ lọc"
        >
          <RotateCcw class="h-4 w-4 text-slate-700" />Xóa lọc
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RotateCcw } from "lucide-vue-next";
import type { LecturerHoursFilterModel } from "../services/lecturerHoursService";
import type { AcademicYearOption } from "../lecturerHours.contract";

interface Props {
  filter: LecturerHoursFilterModel;
  yearOptions: AcademicYearOption[];
}

interface Emits {
  (e: "apply-filter", nextFilter: Partial<LecturerHoursFilterModel>): void;
  (e: "reset"): void;
}

defineProps<Props>();
const emit = defineEmits<Emits>();

function emitUpdateFilter(partial: Partial<LecturerHoursFilterModel>) {
  emit("apply-filter", partial);
}

function onChangeYear(event: Event) {
  const target = event.target as HTMLSelectElement | null;
  if (!target) return;
  emitUpdateFilter({ yearId: Number(target.value) });
}

function onChangeKpiStatus(event: Event) {
  const target = event.target as HTMLSelectElement | null;
  if (!target) return;
  emitUpdateFilter({
    kpiStatus: target.value as LecturerHoursFilterModel["kpiStatus"],
  });
}

function onChangeKeyword(event: Event) {
  const target = event.target as HTMLInputElement | null;
  if (!target) return;
  emitUpdateFilter({ keyword: target.value });
}
</script>
