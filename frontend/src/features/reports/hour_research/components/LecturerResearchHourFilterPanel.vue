<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="overflow-x-auto">
      <div class="flex flex-nowrap items-end gap-3">
        <div class="w-[280px]">
          <label class="text-xs font-medium text-slate-700">Chọn khoa</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="selectedFacultyValue"
              @change="
                updateSelectedFacultyId(
                  ($event.target as HTMLSelectElement).value,
                )
              "
            >
              <option value="ALL">Tất cả khoa</option>
              <option
                v-for="faculty in facultyOptions"
                :key="faculty.id"
                :value="faculty.id"
              >
                {{ faculty.name }}
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <div class="w-[220px]">
          <label class="text-xs font-medium text-slate-700">Chọn năm học</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="selectedAcademicYearValue"
              @change="
                updateSelectedAcademicYearId(
                  ($event.target as HTMLSelectElement).value,
                )
              "
            >
              <option value="ALL">Tất cả năm học</option>
              <option
                v-for="academicYear in academicYearOptions"
                :key="academicYear.id"
                :value="academicYear.id"
              >
                {{ academicYear.code }}
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <div class="w-80">
          <label class="text-xs font-medium text-slate-700">
            Trạng thái giờ NCKH
          </label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="selectedStatusValue"
              @change="
                updateSelectedStatus(($event.target as HTMLSelectElement).value)
              "
            >
              <option
                v-for="status in statusOptions"
                :key="status.code"
                :value="status.code"
              >
                {{ status.label }}
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <button
          type="button"
          class="mt-1 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.99]"
          title="Đặt lại bộ lọc"
          @click="resetFilters"
        >
          <RotateCcw class="h-4 w-4" />
          Xóa lọc
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RotateCcw, ChevronDown } from "lucide-vue-next";
import type {
  HourResearchAcademicYearOption,
  HourResearchFacultyOption,
  HourResearchStatusCode,
  HourResearchStatusOption,
} from "../hourResearchReportTypes";

const componentProperties = defineProps<{
  facultyOptions: HourResearchFacultyOption[];
  academicYearOptions: HourResearchAcademicYearOption[];
  statusOptions: HourResearchStatusOption[];
  selectedFacultyId: number | "ALL";
  selectedAcademicYearId: number | "ALL";
  selectedStatus: HourResearchStatusCode | "ALL";
}>();

const componentEvents = defineEmits<{
  (eventName: "update:selectedFacultyId", facultyId: number | "ALL"): void;
  (
    eventName: "update:selectedAcademicYearId",
    academicYearId: number | "ALL",
  ): void;
  (
    eventName: "update:selectedStatus",
    status: HourResearchStatusCode | "ALL",
  ): void;
  (eventName: "resetFilters"): void;
}>();

const selectedFacultyValue = computed(() =>
  componentProperties.selectedFacultyId === "ALL"
    ? "ALL"
    : String(componentProperties.selectedFacultyId),
);

const selectedAcademicYearValue = computed(() =>
  componentProperties.selectedAcademicYearId === "ALL"
    ? "ALL"
    : String(componentProperties.selectedAcademicYearId),
);

const selectedStatusValue = computed(() => componentProperties.selectedStatus);

function updateSelectedFacultyId(value: string): void {
  if (value === "ALL") {
    componentEvents("update:selectedFacultyId", "ALL");
    return;
  }

  const parsed = Number(value);
  componentEvents(
    "update:selectedFacultyId",
    Number.isNaN(parsed) ? "ALL" : parsed,
  );
}

function updateSelectedAcademicYearId(value: string): void {
  if (value === "ALL") {
    componentEvents("update:selectedAcademicYearId", "ALL");
    return;
  }

  const parsed = Number(value);
  componentEvents(
    "update:selectedAcademicYearId",
    Number.isNaN(parsed) ? "ALL" : parsed,
  );
}

function updateSelectedStatus(value: string): void {
  componentEvents("update:selectedStatus", value as HourResearchStatusCode);
}

function resetFilters(): void {
  componentEvents("resetFilters");
}
</script>
