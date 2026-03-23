<template>
  <section class="grid grid-cols-1 gap-3 md:grid-cols-4">
    <div
      v-for="summaryCardDescriptor in summaryCardDescriptors"
      :key="summaryCardDescriptor.title"
      class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-xs font-medium text-slate-600">
            {{ summaryCardDescriptor.subtitle }}
          </div>
          <div class="mt-1 text-lg font-semibold tracking-tight text-slate-900">
            {{ summaryCardDescriptor.formattedValue }}
          </div>
          <div class="mt-1 text-sm text-slate-600">
            {{ summaryCardDescriptor.title }}
          </div>
        </div>

        <div
          class="rounded-lg border border-slate-200 bg-slate-50 p-2 text-slate-700 group-hover:bg-slate-100"
          aria-hidden="true"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
          >
            <path
              :d="summaryCardDescriptor.iconPath"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";

const componentProperties = defineProps<{
  totalLecturerCount: number;
  totalResearchHourCount: number;
  averageResearchHoursPerLecturer: number;
  lecturerMeetingResearchHourStandardPercentage: number;
}>();

function formatIntegerValue(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(Math.round(value));
}

function formatDecimalValue(value: number): string {
  return new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 2 }).format(
    value,
  );
}

const summaryCardDescriptors = computed(() => [
  {
    title: "Tổng số giảng viên",
    subtitle: "Quy mô nhân lực",
    formattedValue: formatIntegerValue(componentProperties.totalLecturerCount),
    iconPath:
      "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3.13a4 4 0 0 1 0 7.75M20 21v-2a4 4 0 0 0-3-3.87M12 7a4 4 0 1 1-8 0a4 4 0 0 1 8 0z",
  },
  {
    title: "Tổng giờ NCKH",
    subtitle: "Khối lượng nghiên cứu",
    formattedValue: formatIntegerValue(
      componentProperties.totalResearchHourCount,
    ),
    iconPath:
      "M9 12h6M9 16h6M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z",
  },
  {
    title: "Giờ NCKH trung bình / giảng viên",
    subtitle: "Mức độ bình quân",
    formattedValue: formatDecimalValue(
      componentProperties.averageResearchHoursPerLecturer,
    ),
    iconPath: "M4 19V5M8 17V9M12 19v-6M16 17v-8M20 19V7",
  },
  {
    title: "Tỉ lệ giảng viên đạt chuẩn (%)",
    subtitle: "Mức độ đáp ứng chuẩn",
    formattedValue: `${formatDecimalValue(
      componentProperties.lecturerMeetingResearchHourStandardPercentage,
    )}%`,
    iconPath: "M20 6L9 17l-5-5",
  },
]);
</script>
