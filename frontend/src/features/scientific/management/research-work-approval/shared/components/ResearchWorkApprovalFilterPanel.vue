<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
      <!-- Năm học -->
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-700"
          >Năm học</label
        >
        <div class="relative">
          <select
            class="block w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-800 shadow-sm focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
            :value="selectedAcademicYear"
            @change="
              componentEvents(
                'update:selectedAcademicYear',
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

      <!-- Khoa / Đơn vị (optional) -->
      <div v-if="isDepartmentFilterVisible" class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-700"
          >Khoa / Đơn vị</label
        >
        <div class="relative">
          <select
            class="block w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-800 shadow-sm focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
            :value="selectedDepartmentIdentifier"
            @change="
              componentEvents(
                'update:selectedDepartmentIdentifier',
                ($event.target as HTMLSelectElement).value
              )
            "
          >
            <option
              v-for="departmentOption in departmentOptions"
              :key="departmentOption.departmentIdentifier"
              :value="departmentOption.departmentIdentifier"
            >
              {{ departmentOption.departmentDisplayName }}
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

      <!-- Loại công trình -->
      <div
        :class="isDepartmentFilterVisible ? 'md:col-span-2' : 'md:col-span-2'"
      >
        <label class="mb-1 block text-xs font-semibold text-slate-700"
          >Loại công trình</label
        >
        <div class="relative">
          <select
            class="block w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-800 shadow-sm focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
            :value="selectedResearchWorkType"
            @change="
              componentEvents(
                'update:selectedResearchWorkType',
                ($event.target as HTMLSelectElement).value as any
              )
            "
          >
            <option value="ALL_RESEARCH_WORK_TYPES">
              Tất cả loại công trình
            </option>
            <option
              v-for="researchWorkTypeOption in researchWorkTypeOptions"
              :key="researchWorkTypeOption"
              :value="researchWorkTypeOption"
            >
              {{ mapResearchWorkTypeToDisplayName(researchWorkTypeOption) }}
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

      <!-- Trạng thái -->
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-700"
          >Trạng thái duyệt</label
        >
        <div class="relative">
          <select
            class="block w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-800 shadow-sm focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
            :value="selectedApprovalStatus"
            @change="
              componentEvents(
                'update:selectedApprovalStatus',
                ($event.target as HTMLSelectElement).value as any
              )
            "
          >
            <option
              v-for="approvalStatusOption in approvalStatusOptionList"
              :key="approvalStatusOption.value"
              :value="approvalStatusOption.value"
            >
              {{ approvalStatusOption.label }}
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

      <!-- Keyword -->
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-700"
          >Từ khóa</label
        >
        <input
          class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-4 focus:ring-slate-100"
          :value="selectedLecturerOrResearchWorkKeyword"
          @input="
            componentEvents(
              'update:selectedLecturerOrResearchWorkKeyword',
              ($event.target as HTMLInputElement).value
            )
          "
          placeholder="Giảng viên / công trình"
        />
      </div>
      <!-- Reset -->
      <div class="md:col-span-2 md:flex md:justify-end">
        <button
          type="button"
          class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 md:w-auto"
          @click="componentEvents('resetFilterConditions')"
          title="Xóa lọc"
          aria-label="Xóa lọc"
        >
          <RotateCcw class="h-6 w-6 text-slate-700" />
        </button>
      </div>
    </div>

    <!--
    <p class="mt-3 text-xs leading-relaxed text-slate-500">
      {{ filterPanelHelperText }}
    </p>
    -->
  </section>
</template>

<script setup lang="ts">
import { toRefs } from "vue";
import type { ResearchWorkType } from "../models/researchWorkApprovalModels";
import { RotateCcw } from "lucide-vue-next";

const props = defineProps<{
  isDepartmentFilterVisible: boolean;

  academicYearOptions: string[];
  departmentOptions: {
    departmentIdentifier: string;
    departmentDisplayName: string;
  }[];
  researchWorkTypeOptions: ResearchWorkType[];
  approvalStatusOptionList: { value: string; label: string }[];

  selectedAcademicYear: string;
  selectedDepartmentIdentifier: string;
  selectedResearchWorkType: ResearchWorkType | "ALL_RESEARCH_WORK_TYPES";
  selectedApprovalStatus: string;
  selectedLecturerOrResearchWorkKeyword: string;

  filterPanelHelperText: string;
}>();

const componentEvents = defineEmits<{
  (eventName: "update:selectedAcademicYear", value: string): void;
  (eventName: "update:selectedDepartmentIdentifier", value: string): void;
  (
    eventName: "update:selectedResearchWorkType",
    value: ResearchWorkType | "ALL_RESEARCH_WORK_TYPES"
  ): void;
  (eventName: "update:selectedApprovalStatus", value: string): void;
  (
    eventName: "update:selectedLecturerOrResearchWorkKeyword",
    value: string
  ): void;
  (eventName: "resetFilterConditions"): void;
}>();

function mapResearchWorkTypeToDisplayName(
  researchWorkType: ResearchWorkType
): string {
  const mapping: Record<ResearchWorkType, string> = {
    JOURNAL_ARTICLE: "Bài báo",
    RESEARCH_PROJECT: "Đề tài",
    CONFERENCE_PROCEEDING: "Hội thảo",
    BOOK_CHAPTER: "Chương sách",
    STUDENT_SUPERVISION: "Hướng dẫn sinh viên",
  };
  return mapping[researchWorkType];
}

const {
  isDepartmentFilterVisible,
  academicYearOptions,
  departmentOptions,
  researchWorkTypeOptions,
  approvalStatusOptionList,
  selectedAcademicYear,
  selectedDepartmentIdentifier,
  selectedResearchWorkType,
  selectedApprovalStatus,
  selectedLecturerOrResearchWorkKeyword,
} = toRefs(props);
</script>
