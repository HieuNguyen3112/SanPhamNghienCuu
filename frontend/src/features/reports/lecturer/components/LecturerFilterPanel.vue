<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="overflow-x-auto">
      <div class="flex flex-nowrap items-end gap-3">
        <div class="w-[320px]">
          <label class="text-xs font-medium text-slate-700">Khoa</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100 disabled:bg-slate-50"
              :value="filters.facultyId"
              :disabled="isFacultyLocked"
              @change="updateFacultyId(($event.target as HTMLSelectElement).value)"
            >
              <option v-if="!isFacultyLocked" value="ALL">Tất cả khoa</option>
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
          <label class="text-xs font-medium text-slate-700">Trình độ</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="filters.degreeId"
              @change="updateDegreeId(($event.target as HTMLSelectElement).value)"
            >
              <option value="ALL">Tất cả</option>
              <option
                v-for="degree in degreeOptions"
                :key="degree.id"
                :value="degree.id"
              >
                {{ degree.name }}
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <div class="w-[220px]">
          <label class="text-xs font-medium text-slate-700">Học hàm</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="filters.academicRankId"
              @change="
                updateAcademicRankId(($event.target as HTMLSelectElement).value)
              "
            >
              <option value="ALL">Tất cả</option>
              <option
                v-for="rank in academicRankOptions"
                :key="rank.id"
                :value="rank.id"
              >
                {{ rank.name }}
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <div class="w-[200px]">
          <label class="text-xs font-medium text-slate-700">Giới tính</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="filters.gender"
              @change="updateGender(($event.target as HTMLSelectElement).value)"
            >
              <option value="ALL">Tất cả</option>
              <option
                v-for="gender in genderOptions"
                :key="gender.value"
                :value="gender.value"
              >
                {{ gender.label }}
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <div class="ml-auto w-10">
          <label class="block select-none text-xs font-medium text-transparent">
            Đặt lại
          </label>
          <button
            type="button"
            class="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.99]"
            title="Đặt lại bộ lọc"
            @click="emitComponentEvent('resetRequested')"
          >
            <RotateCcw class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RotateCcw, ChevronDown } from "lucide-vue-next";
import type {
  AcademicRankOption,
  DegreeOption,
  FacultyOption,
  GenderOption,
  LecturerReportFilters,
} from "../lecturerReportTypes";

const props = defineProps<{
  filters: LecturerReportFilters;
  facultyOptions: FacultyOption[];
  degreeOptions: DegreeOption[];
  academicRankOptions: AcademicRankOption[];
  genderOptions: GenderOption[];
  facultyLocked?: boolean;
}>();

const emitComponentEvent = defineEmits<{
  (eventName: "filtersUpdated", filters: LecturerReportFilters): void;
  (eventName: "resetRequested"): void;
}>();

const isFacultyLocked = computed(() => props.facultyLocked ?? false);

function updateFacultyId(value: string) {
  emitComponentEvent("filtersUpdated", {
    ...props.filters,
    facultyId: value === "ALL" ? "ALL" : Number(value),
  });
}

function updateDegreeId(value: string) {
  emitComponentEvent("filtersUpdated", {
    ...props.filters,
    degreeId: value === "ALL" ? "ALL" : Number(value),
  });
}

function updateAcademicRankId(value: string) {
  emitComponentEvent("filtersUpdated", {
    ...props.filters,
    academicRankId: value === "ALL" ? "ALL" : Number(value),
  });
}

function updateGender(value: string) {
  emitComponentEvent("filtersUpdated", {
    ...props.filters,
    gender: value === "ALL" ? "ALL" : value,
  });
}
</script>
