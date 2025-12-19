<template>
  <section class="grid grid-cols-1 gap-3 md:grid-cols-4">
    <div
      v-for="summaryCardDescriptor in summaryCardDescriptors"
      :key="summaryCardDescriptor.title"
      class="group rounded-xl border border-amber-100 bg-amber-50/60 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-xs font-medium text-slate-700">
            {{ summaryCardDescriptor.subtitle }}
          </div>
          <div class="mt-1 text-lg font-semibold tracking-tight text-slate-900">
            {{ summaryCardDescriptor.formattedValue }}
          </div>
          <div class="mt-1 text-sm text-slate-700">
            {{ summaryCardDescriptor.title }}
          </div>
        </div>

        <div
          class="rounded-lg border border-amber-100 bg-white/70 p-2 text-amber-900 group-hover:bg-white"
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
const componentProperties = defineProps<{
  totalLecturersNotMeetingResearchHourStandard: number;
  totalRemainingResearchHoursToMeetStandard: number;
  averageRemainingResearchHoursToMeetStandard: number;
  minimumRequiredResearchHours: number;
}>();

function formatIntegerValue(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(Math.round(value));
}

function formatDecimalValue(value: number): string {
  return new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 2 }).format(
    value
  );
}

const summaryCardDescriptors = [
  {
    title: "Số giảng viên chưa đạt chuẩn",
    subtitle: "Quy mô cần theo dõi",
    formattedValue: formatIntegerValue(
      componentProperties.totalLecturersNotMeetingResearchHourStandard
    ),
    iconPath:
      "M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M12 7a4 4 0 1 1-8 0a4 4 0 0 1 8 0z",
  },
  {
    title: "Tổng số giờ NCKH còn thiếu",
    subtitle: "Khoảng cách so với chuẩn",
    formattedValue: formatIntegerValue(
      componentProperties.totalRemainingResearchHoursToMeetStandard
    ),
    iconPath: "M12 8v4l3 3M21 12a9 9 0 1 1-18 0a9 9 0 0 1 18 0z",
  },
  {
    title: "Giờ thiếu trung bình / giảng viên",
    subtitle: "Mức độ bình quân",
    formattedValue: formatDecimalValue(
      componentProperties.averageRemainingResearchHoursToMeetStandard
    ),
    iconPath: "M4 19V5M8 17V9M12 19v-6M16 17v-8M20 19V7",
  },
  {
    title: "Chuẩn giờ NCKH hiện hành",
    subtitle: "Căn cứ đối soát",
    formattedValue: `${formatIntegerValue(
      componentProperties.minimumRequiredResearchHours
    )} giờ`,
    iconPath: "M20 6L9 17l-5-5",
  },
];
</script>
