<template>
  <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
      <div class="md:col-span-4">
        <label class="text-xs font-medium text-slate-700">Chọn khoa</label>
        <select
          class="mt-1 w-full rounded-lg border-slate-200 bg-white text-sm text-slate-800 focus:border-slate-400 focus:ring-0"
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
      </div>

      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-700">Chọn năm học</label>
        <select
          class="mt-1 w-full rounded-lg border-slate-200 bg-white text-sm text-slate-800 focus:border-slate-400 focus:ring-0"
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
      </div>

      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-700"
          >Trạng thái giờ NCKH</label
        >
        <select
          class="mt-1 w-full rounded-lg border-slate-200 bg-white text-sm text-slate-800 focus:border-slate-400 focus:ring-0"
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
      </div>

      <div class="md:col-span-2 md:flex md:justify-end">
        <button
          type="button"
          class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 md:w-auto"
          @click="resetLecturerResearchHourFilterConditions"
        >
          Đặt lại bộ lọc
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type {
  FacultyOption,
  ResearchHourStatusFilterCondition,
} from "../lecturerResearchHourModels";

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
  // Vì đây là “điều kiện lọc” do trang cha quản lý, component con chỉ phát sự kiện để tránh rối luồng.
  componentEvents("resetLecturerResearchHourFilterConditions");
}
</script>
