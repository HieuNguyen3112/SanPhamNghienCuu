<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div
      class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
    >
      <div class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <!-- Extra filter slot: nếu page không inject thì slot không render gì => KHÔNG chiếm ô -->
        <slot
          name="extraFilter"
          :filter="filter"
          :updateFilter="emitUpdateFilter"
        />

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-700">
            Niên học
          </label>
          <select
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
            :value="filter.academicYearId ?? ''"
            @change="onAcademicYearChange"
          >
            <option value="">Tất cả</option>
            <option
              v-for="year in academicYearOptions"
              :key="year.id"
              :value="year.id"
            >
              {{ year.code }} <span v-if="year.isActive"> (đang active)</span>
            </option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-700">
            Tìm giảng viên
          </label>
          <input
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
            :value="filter.lecturerName"
            placeholder="Nhập tên giảng viên..."
            @input="onLecturerNameInput"
          />
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-700">
            Trạng thái (chỉ ảnh hưởng COUNT)
          </label>
          <select
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
            :value="filter.statusMode"
            @change="onStatusModeChange"
          >
            <option value="all">Tất cả</option>
            <option value="approved">Chỉ Approved</option>
          </select>
        </div>
      </div>

      <div class="md:col-span-2 md:flex md:justify-end">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 md:w-auto"
          @click="emitReset"
          title="Áp dụng / tìm kiếm theo bộ lọc"
          aria-label="Áp dụng / tìm kiếm theo bộ lọc"
        >
          <RotateCcw class="h-5 w-5 text-slate-700" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  AcademicYearOption,
  FilterState,
} from "../lecturerResearchWork.contracts";
import { RotateCcw } from "lucide-vue-next";

interface FilterPanelProps {
  filter: FilterState;
  academicYearOptions: AcademicYearOption[];
}

interface FilterPanelEmits {
  (e: "update-filter", partial: Partial<FilterState>): void;
  (e: "reset"): void;
}

const props = defineProps<FilterPanelProps>();
const emit = defineEmits<FilterPanelEmits>();

function emitUpdateFilter(partial: Partial<FilterState>) {
  emit("update-filter", partial);
}

function emitReset() {
  emit("reset");
}

function onAcademicYearChange(event: Event) {
  const value = (event.target as HTMLSelectElement).value;
  emitUpdateFilter({ academicYearId: value ? Number(value) : null });
}

function onLecturerNameInput(event: Event) {
  emitUpdateFilter({ lecturerName: (event.target as HTMLInputElement).value });
}

function onStatusModeChange(event: Event) {
  const value = (event.target as HTMLSelectElement)
    .value as FilterState["statusMode"];
  emitUpdateFilter({ statusMode: value });
}
</script>
