<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="overflow-x-auto">
      <div class="flex flex-nowrap items-end gap-3">
        <!-- Khoa -->
        <div class="w-[280px]">
          <label class="text-xs font-medium text-slate-700">Chọn khoa</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
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
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Năm học -->
        <div class="w-[220px]">
          <label class="text-xs font-medium text-slate-700">Chọn năm học</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
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
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Trạng thái -->
        <div class="w-80">
          <label class="text-xs font-medium text-slate-700"
            >Trạng thái giờ NCKH</label
          >
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="selectedResearchHourStatusFilterCondition"
              @change="
                updateSelectedResearchHourStatusFilterCondition(
                  ($event.target as HTMLSelectElement).value as any
                )
              "
            >
              <option value="ALL">Tất cả</option>
              <option value="MEETING_RESEARCH_HOUR_STANDARD">Đạt chuẩn</option>
              <option value="NOT_MEETING_RESEARCH_HOUR_STANDARD">
                Chưa đạt chuẩn
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Reset (same line) -->
        <div class="ml-auto w-10">
          <!-- label ẩn để canh đáy giống các ô select -->
          <label class="block text-xs font-medium text-transparent select-none">
            Đặt lại
          </label>

          <button
            type="button"
            class="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.99]"
            title="Đặt lại bộ lọc"
            @click="resetLecturerResearchHourFilterConditions"
          >
            <RotateCcw class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type {
  FacultyOption,
  ResearchHourStatusFilterCondition,
} from "../lecturerResearchHourModels";
import { RotateCcw, ChevronDown } from "lucide-vue-next";

const componentProperties = defineProps<{
  facultyOptions: FacultyOption[];
  academicYearOptions: string[];
  selectedFacultyIdentifier: string;
  selectedAcademicYear: string;
  selectedResearchHourStatusFilterCondition: ResearchHourStatusFilterCondition;
}>();

const componentEvents = defineEmits<{
  (
    eventName: "update:selectedFacultyIdentifier",
    facultyIdentifier: string
  ): void;
  (eventName: "update:selectedAcademicYear", academicYear: string): void;
  (
    eventName: "update:selectedResearchHourStatusFilterCondition",
    selectedResearchHourStatusFilterCondition: ResearchHourStatusFilterCondition
  ): void;
  (eventName: "resetLecturerResearchHourFilterConditions"): void;
}>();

function updateSelectedFacultyIdentifier(facultyIdentifier: string): void {
  componentEvents("update:selectedFacultyIdentifier", facultyIdentifier);
}

function updateSelectedAcademicYear(academicYear: string): void {
  componentEvents("update:selectedAcademicYear", academicYear);
}

function updateSelectedResearchHourStatusFilterCondition(
  selectedResearchHourStatusFilterCondition: ResearchHourStatusFilterCondition
): void {
  componentEvents(
    "update:selectedResearchHourStatusFilterCondition",
    selectedResearchHourStatusFilterCondition
  );
}

function resetLecturerResearchHourFilterConditions(): void {
  componentEvents("resetLecturerResearchHourFilterConditions");
}
</script>
