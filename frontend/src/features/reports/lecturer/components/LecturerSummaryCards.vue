<template>
  <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
    <div
      v-for="summaryCard in summaryCardDefinitions"
      :key="summaryCard.summaryCardIdentifier"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-[1px] hover:shadow-md"
    >
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="text-xs font-medium text-slate-600">
            {{ summaryCard.summaryCardTitle }}
          </p>
          <p class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
            {{ summaryCard.summaryCardValue }}
          </p>
          <p class="mt-1 text-xs text-slate-500">
            {{ summaryCard.summaryCardDescription }}
          </p>
        </div>

        <div
          class="flex h-10 w-10 items-center justify-center rounded-lg"
          :class="summaryCard.summaryCardIconBackgroundClassName"
        >
          <component :is="summaryCard.summaryCardIconComponent" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const componentProperties = defineProps<{
  totalLecturerCount: number;
  numberOfDoctorLecturers: number;
  numberOfMasterLecturers: number;
  numberOfBachelorLecturers: number;
  numberOfProfessorAndAssociateProfessorLecturers: number;
}>();

const PeopleIcon = {
  name: "PeopleIcon",
  template: `
    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-slate-700">
      <path d="M16 11a4 4 0 1 0-8 0" stroke="currentColor" stroke-width="1.5"/>
      <path d="M4 21a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.5"/>
    </svg>
  `,
};

const DoctorIcon = {
  name: "DoctorIcon",
  template: `
    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-slate-700">
      <path d="M8 21h8a2 2 0 0 0 2-2V9l-4-4H8a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" stroke="currentColor" stroke-width="1.5"/>
      <path d="M14 5v4h4" stroke="currentColor" stroke-width="1.5"/>
      <path d="M12 10v6" stroke="currentColor" stroke-width="1.5"/>
      <path d="M9 13h6" stroke="currentColor" stroke-width="1.5"/>
    </svg>
  `,
};

const MasterIcon = {
  name: "MasterIcon",
  template: `
    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-slate-700">
      <path d="M4 7l8-4 8 4-8 4-8-4Z" stroke="currentColor" stroke-width="1.5"/>
      <path d="M20 7v6" stroke="currentColor" stroke-width="1.5"/>
      <path d="M6 11v6c0 2 3 4 6 4s6-2 6-4v-6" stroke="currentColor" stroke-width="1.5"/>
    </svg>
  `,
};

const BachelorIcon = {
  name: "BachelorIcon",
  template: `
    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-slate-700">
      <path d="M6 3h12v18H6V3Z" stroke="currentColor" stroke-width="1.5"/>
      <path d="M9 7h6M9 11h6M9 15h6" stroke="currentColor" stroke-width="1.5"/>
    </svg>
  `,
};

const AcademicRankIcon = {
  name: "AcademicRankIcon",
  template: `
    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-slate-700">
      <path d="M12 2l3 7h7l-5.5 4 2 7-6.5-4.2L5.5 20l2-7L2 9h7l3-7Z" stroke="currentColor" stroke-width="1.5"/>
    </svg>
  `,
};

const summaryCardDefinitions = computed(() => {
  return [
    {
      summaryCardIdentifier: "totalLecturerCount",
      summaryCardTitle: "Tổng số giảng viên",
      summaryCardValue: componentProperties.totalLecturerCount,
      summaryCardDescription: "Tổng nhân sự giảng dạy theo bộ lọc",
      summaryCardIconComponent: PeopleIcon,
      summaryCardIconBackgroundClassName: "bg-slate-100",
    },
    {
      summaryCardIdentifier: "doctorLecturerCount",
      summaryCardTitle: "Tiến sĩ",
      summaryCardValue: componentProperties.numberOfDoctorLecturers,
      summaryCardDescription: "Trình độ Tiến sĩ",
      summaryCardIconComponent: DoctorIcon,
      summaryCardIconBackgroundClassName: "bg-indigo-50",
    },
    {
      summaryCardIdentifier: "masterLecturerCount",
      summaryCardTitle: "Thạc sĩ",
      summaryCardValue: componentProperties.numberOfMasterLecturers,
      summaryCardDescription: "Trình độ Thạc sĩ",
      summaryCardIconComponent: MasterIcon,
      summaryCardIconBackgroundClassName: "bg-blue-50",
    },
    {
      summaryCardIdentifier: "bachelorLecturerCount",
      summaryCardTitle: "Đại học",
      summaryCardValue: componentProperties.numberOfBachelorLecturers,
      summaryCardDescription: "Trình độ Đại học",
      summaryCardIconComponent: BachelorIcon,
      summaryCardIconBackgroundClassName: "bg-slate-50",
    },
    {
      summaryCardIdentifier: "academicRankLecturerCount",
      summaryCardTitle: "Giáo sư / Phó Giáo sư",
      summaryCardValue:
        componentProperties.numberOfProfessorAndAssociateProfessorLecturers,
      summaryCardDescription: "Nhóm có học hàm",
      summaryCardIconComponent: AcademicRankIcon,
      summaryCardIconBackgroundClassName: "bg-amber-50",
    },
  ] as const;
});
</script>
