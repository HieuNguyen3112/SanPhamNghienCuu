<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
      <!-- Khoa -->
      <div class="md:col-span-3">
        <label class="mb-1 block text-xs font-semibold text-slate-700"
          >Khoa</label
        >
        <div class="relative">
          <select
            class="block w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-800 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
            :value="selectedFacultyIdentifier"
            @change="
              updateSelectedFacultyIdentifier(
                ($event.target as HTMLSelectElement).value
              )
            "
          >
            <option value="ALL_FACULTIES">Tất cả khoa</option>
            <option
              v-for="facultyOption in facultyOptions"
              :key="facultyOption.facultyIdentifier"
              :value="facultyOption.facultyIdentifier"
            >
              {{ facultyOption.facultyDisplayName }}
            </option>
          </select>

          <!-- dropdown icon -->
          <svg
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            viewBox="0 0 24 24"
            fill="none"
          >
            <path
              d="M6 9l6 6 6-6"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
      </div>

      <!-- Năm học -->
      <div class="md:col-span-3">
        <label class="mb-1 block text-xs font-semibold text-slate-700"
          >Năm học</label
        >
        <div class="relative">
          <select
            class="block w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-800 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
            :value="selectedAcademicYear"
            @change="
              updateSelectedAcademicYear(
                ($event.target as HTMLSelectElement).value
              )
            "
          >
            <option value="ALL_ACADEMIC_YEARS">Tất cả năm học</option>
            <option
              v-for="academicYearOption in academicYearOptions"
              :key="academicYearOption"
              :value="academicYearOption"
            >
              {{ academicYearOption }}
            </option>
          </select>

          <svg
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            viewBox="0 0 24 24"
            fill="none"
          >
            <path
              d="M6 9l6 6 6-6"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
      </div>

      <!-- Mức độ thiếu giờ -->
      <div class="md:col-span-4">
        <label class="mb-1 block text-xs font-semibold text-slate-700">
          Mức độ thiếu giờ
        </label>
        <div class="relative">
          <select
            class="block w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-800 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
            :value="selectedResearchHourShortfallSeverityFilterCondition"
            @change="
              updateSelectedResearchHourShortfallSeverityFilterCondition(
                ($event.target as HTMLSelectElement)
                  .value as ResearchHourShortfallSeverityFilterCondition
              )
            "
          >
            <option value="ALL">Tất cả</option>
            <option value="LIGHT">Thiếu nhẹ (≤ 20%)</option>
            <option value="MEDIUM">Thiếu trung bình (20–40%)</option>
            <option value="SEVERE">Thiếu nghiêm trọng (&gt; 40%)</option>
          </select>

          <svg
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
            viewBox="0 0 24 24"
            fill="none"
          >
            <path
              d="M6 9l6 6 6-6"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
      </div>

      <!-- Search button -->
      <div class="md:col-span-2 md:flex md:justify-end">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 md:w-auto"
          @click="resetLecturerResearchHourWarningFilterConditions"
          title="Áp dụng / tìm kiếm theo bộ lọc"
          aria-label="Áp dụng / tìm kiếm theo bộ lọc"
        >
          <RotateCcw class="h-6 w-6 text-slate-700" />
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { RotateCcw } from "lucide-vue-next";

type ResearchHourShortfallSeverityFilterCondition =
  | "ALL"
  | "LIGHT"
  | "MEDIUM"
  | "SEVERE";

interface FacultyOption {
  facultyIdentifier: string;
  facultyDisplayName: string;
}

defineProps<{
  facultyOptions: FacultyOption[];
  academicYearOptions: string[];
  selectedFacultyIdentifier: string;
  selectedAcademicYear: string;
  selectedResearchHourShortfallSeverityFilterCondition: ResearchHourShortfallSeverityFilterCondition;
}>();

const componentEvents = defineEmits<{
  (
    eventName: "update:selectedFacultyIdentifier",
    facultyIdentifier: string
  ): void;
  (eventName: "update:selectedAcademicYear", academicYear: string): void;
  (
    eventName: "update:selectedResearchHourShortfallSeverityFilterCondition",
    selectedResearchHourShortfallSeverityFilterCondition: ResearchHourShortfallSeverityFilterCondition
  ): void;
  (eventName: "resetLecturerResearchHourWarningFilterConditions"): void;
}>();

function updateSelectedFacultyIdentifier(facultyIdentifier: string): void {
  componentEvents("update:selectedFacultyIdentifier", facultyIdentifier);
}

function updateSelectedAcademicYear(academicYear: string): void {
  componentEvents("update:selectedAcademicYear", academicYear);
}

function updateSelectedResearchHourShortfallSeverityFilterCondition(
  selectedResearchHourShortfallSeverityFilterCondition: ResearchHourShortfallSeverityFilterCondition
): void {
  componentEvents(
    "update:selectedResearchHourShortfallSeverityFilterCondition",
    selectedResearchHourShortfallSeverityFilterCondition
  );
}

function resetLecturerResearchHourWarningFilterConditions(): void {
  componentEvents("resetLecturerResearchHourWarningFilterConditions");
}
</script>
