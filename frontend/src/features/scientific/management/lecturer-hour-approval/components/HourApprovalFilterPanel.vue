<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4">
    <div
      class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between"
    >
      <div class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
        <div>
          <label class="text-xs text-slate-600">Năm học</label>
          <select
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0 disabled:bg-slate-50"
            :disabled="loading"
            :value="academicYearValue"
            @change="onChangeAcademicYear"
          >
            <option value="ALL">Tất cả</option>
            <option
              v-for="year in academicYearOptions"
              :key="year.id"
              :value="year.id"
            >
              {{ year.code }}
            </option>
          </select>
        </div>

        <div>
          <label class="text-xs text-slate-600">Khoa</label>
          <select
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0 disabled:bg-slate-50"
            :disabled="facultySelectDisabled || loading"
            :value="filter.facultyId ?? ''"
            @change="onChangeFaculty"
          >
            <option value="">Tất cả</option>
            <option v-for="f in facultyOptions" :key="f.id" :value="f.id">
              {{ f.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="text-xs text-slate-600">Trạng thái</label>
          <select
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0 disabled:bg-slate-50"
            :disabled="loading"
            :value="filter.status"
            @change="onChangeStatus"
          >
            <option value="all">Tất cả</option>
            <option value="pending">Chờ khoa duyệt</option>
            <option value="need_revision">Cần chỉnh sửa</option>
            <option value="partially_approved">Đã duyệt một phần</option>
            <option value="approved">Đã duyệt</option>
            <option value="rejected">Bị từ chối</option>
          </select>
        </div>

        <div>
          <label class="text-xs text-slate-600">Từ ngày</label>
          <div class="relative mt-1">
            <CalendarRange
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              type="date"
              class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-400 focus:ring-0 disabled:bg-slate-50"
              :disabled="loading"
              :value="filter.submittedFrom ?? ''"
              @change="onChangeFrom"
            />
          </div>
        </div>

        <div>
          <label class="text-xs text-slate-600">Đến ngày</label>
          <div class="relative mt-1">
            <CalendarRange
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              type="date"
              class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-400 focus:ring-0 disabled:bg-slate-50"
              :disabled="loading"
              :value="filter.submittedTo ?? ''"
              @change="onChangeTo"
            />
          </div>
        </div>

        <div>
          <label class="text-xs text-slate-600">Tìm giảng viên</label>
          <div class="relative mt-1">
            <Search
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-400 focus:ring-0 disabled:bg-slate-50"
              :disabled="loading"
              :value="filter.searchText"
              placeholder="Tên / mã giảng viên..."
              @input="onChangeSearch"
            />
          </div>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 md:w-auto disabled:opacity-60"
          :disabled="loading"
          @click="emit('reset')"
          title="Đặt lại"
        >
          <RotateCcw class="h-4 w-4 text-slate-700" />
          <span class="hidden md:inline">Xóa lọc</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
  AcademicYearOption,
  FacultyOption,
  HourApprovalFilter,
} from "../contracts/hourApproval.contract";
import {
  CalendarRange,
  RotateCcw as FilterIcon,
  Search,
} from "lucide-vue-next";

const RotateCcw = FilterIcon;

interface Props {
  filter: HourApprovalFilter;
  facultyOptions: FacultyOption[];
  academicYearOptions: AcademicYearOption[];
  loading: boolean;
  facultySelectDisabled: boolean;
}

const props = defineProps<Props>();
const academicYearValue = computed(() =>
  props.filter.academicYearId == null
    ? "ALL"
    : String(props.filter.academicYearId),
);

const emit = defineEmits<{
  (e: "update:filter", next: HourApprovalFilter): void;
  (e: "reset"): void;
}>();

function patch(next: Partial<HourApprovalFilter>) {
  emit("update:filter", { ...props.filter, ...next });
}

function onChangeFaculty(event: Event) {
  const value = (event.target as HTMLSelectElement).value;
  patch({ facultyId: value ? Number(value) : null });
}

function onChangeAcademicYear(event: Event) {
  const raw = (event.target as HTMLSelectElement).value;
  patch({ academicYearId: raw === "ALL" ? null : Number(raw) });
}

function onChangeStatus(event: Event) {
  patch({
    status: (event.target as HTMLSelectElement)
      .value as HourApprovalFilter["status"],
  });
}

function onChangeFrom(event: Event) {
  const v = (event.target as HTMLInputElement).value;
  patch({ submittedFrom: v ? v : null });
}

function onChangeTo(event: Event) {
  const v = (event.target as HTMLInputElement).value;
  patch({ submittedTo: v ? v : null });
}

function onChangeSearch(event: Event) {
  patch({ searchText: (event.target as HTMLInputElement).value });
}
</script>
