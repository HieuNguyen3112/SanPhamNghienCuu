<template>
  <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div
      class="flex flex-col gap-2 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between"
    >
      <div>
        <h2 class="text-sm font-semibold text-slate-900">
          Bảng cảnh báo giảng viên
        </h2>
        <p class="mt-1 text-xs text-slate-600">
          Bảng này hỗ trợ rà soát minh bạch: mức thiếu được tính theo chuẩn yêu
          cầu của từng giảng viên/năm học.
        </p>
      </div>

      <SharedPaginationControls
        displayMode="PAGE_SIZE_ONLY"
        :totalItemCount="lecturerResearchHourWarningEntries.length"
        v-model:currentPageNumber="currentPageNumber"
        v-model:pageSize="pageSize"
        :pageSizeOptionList="[8, 12, 20]"
      />
    </div>

    <div class="overflow-auto">
      <table class="min-w-[980px] w-full border-collapse">
        <thead class="sticky top-0 z-10 bg-slate-50">
          <tr class="border-b border-slate-200">
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Giảng viên
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Khoa
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-semibold text-slate-700"
            >
              Giờ đã thực hiện
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-semibold text-slate-700"
            >
              Chuẩn yêu cầu
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-semibold text-slate-700"
            >
              Giờ còn thiếu
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Mức độ
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-700"
            >
              Trạng thái
            </th>
          </tr>
        </thead>

        <tbody v-if="pagedLecturerResearchHourWarningEntries.length > 0">
          <tr
            v-for="lecturerResearchHourWarningEntry in pagedLecturerResearchHourWarningEntries"
            :key="
              lecturerResearchHourWarningEntry.lecturerIdentifier +
              '-' +
              lecturerResearchHourWarningEntry.academicYear
            "
            class="cursor-pointer border-b border-slate-100 hover:bg-slate-50"
            @click="
              selectLecturerResearchHourWarningEntry(
                lecturerResearchHourWarningEntry
              )
            "
          >
            <td class="px-4 py-3 text-sm font-medium text-slate-900">
              {{ lecturerResearchHourWarningEntry.lecturerDisplayName }}
              <div class="mt-1 text-xs text-slate-500">
                Năm học: {{ lecturerResearchHourWarningEntry.academicYear }}
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              {{ lecturerResearchHourWarningEntry.facultyDisplayName }}
            </td>
            <td class="px-4 py-3 text-right text-sm text-slate-800">
              {{
                formatIntegerValue(
                  lecturerResearchHourWarningEntry.currentLecturerResearchHours
                )
              }}
            </td>
            <td class="px-4 py-3 text-right text-sm text-slate-800">
              {{
                formatIntegerValue(
                  lecturerResearchHourWarningEntry.minimumRequiredResearchHours
                )
              }}
            </td>
            <td
              class="px-4 py-3 text-right text-sm font-semibold text-slate-900"
            >
              {{
                formatIntegerValue(
                  lecturerResearchHourWarningEntry.remainingResearchHoursToMeetStandard
                )
              }}
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
                :class="
                  getSeverityBadgeClassName(lecturerResearchHourWarningEntry)
                "
              >
                {{ getSeverityLabel(lecturerResearchHourWarningEntry) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">Chưa đạt chuẩn</td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="pagedLecturerResearchHourWarningEntries.length === 0"
        class="p-10 text-center"
      >
        <div
          class="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-slate-200 bg-slate-50"
        >
          <svg
            viewBox="0 0 24 24"
            class="h-6 w-6 text-slate-600"
            fill="none"
            stroke="currentColor"
          >
            <path
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M21 21l-5-5M10 18a8 8 0 1 1 0-16a8 8 0 0 1 0 16z"
            />
          </svg>
        </div>
        <div class="mt-3 text-sm font-semibold text-slate-900">
          Không tìm thấy kết quả phù hợp
        </div>
        <div class="mt-1 text-sm text-slate-600">
          Vui lòng điều chỉnh bộ lọc để tiếp tục rà soát.
        </div>
      </div>
    </div>

    <div
      class="flex flex-col gap-2 border-t border-slate-200 p-4 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-xs text-slate-600">
        Hiển thị
        <span class="font-semibold text-slate-900">{{
          pagedLecturerResearchHourWarningEntries.length
        }}</span>
        /
        <span class="font-semibold text-slate-900">{{
          lecturerResearchHourWarningEntries.length
        }}</span>
        bản ghi
      </div>

      <SharedPaginationControls
        displayMode="PAGINATION_ONLY"
        :totalItemCount="totalLecturerResearchHourWarningEntryCount"
        v-model:currentPageNumber="currentPageNumber"
        v-model:pageSize="pageSize"
        :showRecordSummary="true"
        recordSummaryMode="RANGE"
        recordSummaryUnitLabel="bản ghi"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { LecturerResearchHourWarningEntry } from "../lecturerResearchHourWarningModels";
import SharedPaginationControls from "@/shared/components/SharedPaginationControls.vue";

const componentProperties = defineProps<{
  lecturerResearchHourWarningEntries: LecturerResearchHourWarningEntry[];
}>();

const componentEvents = defineEmits<{
  (
    eventName: "lecturerResearchHourWarningEntrySelected",
    lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
  ): void;
}>();

const currentPageNumber = ref<number>(1);
const pageSize = ref<number>(12);

watch(
  () => componentProperties.lecturerResearchHourWarningEntries.length,
  () => (currentPageNumber.value = 1)
);

const pagedLecturerResearchHourWarningEntries = computed<
  LecturerResearchHourWarningEntry[]
>(() => {
  const startIndex = (currentPageNumber.value - 1) * pageSize.value;
  const endIndex = startIndex + pageSize.value;
  return componentProperties.lecturerResearchHourWarningEntries.slice(
    startIndex,
    endIndex
  );
});

function formatIntegerValue(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(Math.round(value));
}

function calculateShortfallRatio(
  lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
): number {
  return (
    lecturerResearchHourWarningEntry.remainingResearchHoursToMeetStandard /
    lecturerResearchHourWarningEntry.minimumRequiredResearchHours
  );
}
const totalLecturerResearchHourWarningEntryCount = computed(() => {
  return componentProperties.lecturerResearchHourWarningEntries.length;
});

function getSeverityLabel(
  lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
): string {
  const shortfallRatio = calculateShortfallRatio(
    lecturerResearchHourWarningEntry
  );
  if (shortfallRatio <= 0.2) return "Thiếu nhẹ";
  if (shortfallRatio <= 0.4) return "Thiếu trung bình";
  return "Thiếu nghiêm trọng";
}

function getSeverityBadgeClassName(
  lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
): string {
  const shortfallRatio = calculateShortfallRatio(
    lecturerResearchHourWarningEntry
  );

  if (shortfallRatio <= 0.2) {
    return "border-amber-200 bg-amber-50 text-amber-900";
  }

  if (shortfallRatio <= 0.4) {
    return "border-orange-200 bg-orange-50 text-orange-900";
  }

  return "border-rose-200 bg-rose-50 text-rose-900";
}

function selectLecturerResearchHourWarningEntry(
  lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
): void {
  componentEvents(
    "lecturerResearchHourWarningEntrySelected",
    lecturerResearchHourWarningEntry
  );
}
</script>
