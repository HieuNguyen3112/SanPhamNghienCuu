<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div
      class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
    >
      <div class="min-w-0">
        <h2 class="text-sm font-semibold text-slate-900">
          Danh sách công trình
        </h2>
        <p class="mt-1 text-xs text-slate-500">
          Nhấn
          <span class="font-semibold text-slate-700">{{
            tableActionButtonLabel
          }}</span>
          để mở hồ sơ ở ngăn bên phải.
        </p>

        <div
          class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-600"
        >
          <span
            class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 font-semibold text-amber-900"
          >
            Chờ duyệt: {{ totalPendingResearchWorkCount }}
          </span>

          <span class="text-slate-400">•</span>

          <span>
            Tổng:
            <span class="font-semibold text-slate-900">{{
              totalItemCount
            }}</span>
            công trình
          </span>
        </div>
      </div>

      <div class="shrink-0">
        <SharedPaginationControls
          displayMode="PAGE_SIZE_ONLY"
          :totalItemCount="totalItemCount"
          v-model:currentPageNumber="currentPageNumber"
          v-model:pageSize="pageSize"
          :pageSizeOptionList="[6, 8, 12, 20]"
        />
      </div>
    </div>

    <div
      v-if="totalItemCount === 0"
      class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center"
    >
      <div class="text-sm font-semibold text-slate-800">
        Không tìm thấy công trình phù hợp
      </div>
      <div class="mt-1 text-sm text-slate-600">
        Hãy thử điều chỉnh điều kiện lọc hoặc từ khóa tìm kiếm.
      </div>
    </div>

    <div v-else class="mt-4 space-y-3">
      <article
        v-for="researchWorkApprovalEntry in pagedResearchWorkApprovalList"
        :key="researchWorkApprovalEntry.researchWorkIdentifier"
        class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:bg-slate-50/40"
      >
        <div
          class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
        >
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h3 class="truncate text-sm font-semibold text-slate-900">
                {{ researchWorkApprovalEntry.researchWorkTitle }}
              </h3>

              <span
                :class="
                  mapApprovalStatusToBadgeClass(
                    researchWorkApprovalEntry.approvalStatus
                  )
                "
              >
                {{
                  mapApprovalStatusToDisplayName(
                    researchWorkApprovalEntry.approvalStatus
                  )
                }}
              </span>

              <span
                v-if="approvalScopeIdentifier === 'UNIVERSITY_SCOPE'"
                class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700"
              >
                Đã duyệt cấp khoa
              </span>
            </div>

            <div
              class="mt-2 grid grid-cols-1 gap-y-1 text-sm text-slate-700 md:grid-cols-2 md:gap-x-6"
            >
              <div class="min-w-0">
                <span class="text-slate-500">Giảng viên:</span>
                <span class="ml-1 font-medium text-slate-800">{{
                  researchWorkApprovalEntry.submittingLecturerDisplayName
                }}</span>
              </div>

              <div class="min-w-0">
                <span class="text-slate-500">Khoa:</span>
                <span class="ml-1 font-medium text-slate-800">{{
                  researchWorkApprovalEntry.facultyDisplayName
                }}</span>
              </div>

              <div class="min-w-0">
                <span class="text-slate-500">Năm học:</span>
                <span class="ml-1 font-medium text-slate-800">{{
                  researchWorkApprovalEntry.academicYear
                }}</span>
              </div>

              <div class="min-w-0">
                <span class="text-slate-500">Loại:</span>
                <span class="ml-1 font-medium text-slate-800">{{
                  mapResearchWorkTypeToDisplayName(
                    researchWorkApprovalEntry.researchWorkType
                  )
                }}</span>
              </div>
            </div>

            <div class="mt-2 text-xs text-slate-500">
              Minh chứng:
              <span class="font-semibold text-slate-700">{{
                researchWorkApprovalEntry.evidenceAttachmentList.length
              }}</span>
              tệp
              <span class="text-slate-400">•</span>
              Giờ kê khai:
              <span class="font-semibold text-slate-700">{{
                formatIntegerValue(
                  researchWorkApprovalEntry.lecturerDeclaredResearchHours
                )
              }}</span>

              <span
                v-if="approvalScopeIdentifier === 'UNIVERSITY_SCOPE'"
                class="text-slate-400"
                >•</span
              >
              <template v-if="approvalScopeIdentifier === 'UNIVERSITY_SCOPE'">
                Giờ chính thức:
                <span class="font-semibold text-slate-900">{{
                  formatIntegerValue(
                    researchWorkApprovalEntry.officialResearchHours
                  )
                }}</span>
              </template>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
              @click="
                componentEvents(
                  'openResearchWorkDetailDrawer',
                  researchWorkApprovalEntry
                )
              "
            >
              {{ tableActionButtonLabel }}
            </button>
          </div>
        </div>
      </article>

      <div class="pt-2">
        <SharedPaginationControls
          displayMode="PAGINATION_ONLY"
          :totalItemCount="totalItemCount"
          v-model:currentPageNumber="currentPageNumber"
          v-model:pageSize="pageSize"
          :showRecordSummary="true"
          recordSummaryMode="RANGE"
          recordSummaryUnitLabel="bản ghi"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalScopeIdentifier,
} from "../models/researchWorkApprovalModels";
import { useResearchWorkApprovalDisplayMapping } from "../composables/useResearchWorkApprovalDisplayMapping";

const componentProperties = defineProps<{
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;
  researchWorkApprovalList: ResearchWorkApprovalEntry[];
  totalPendingResearchWorkCount: number;
  tableActionButtonLabel: string;
}>();

const componentEvents = defineEmits<{
  (
    eventName: "openResearchWorkDetailDrawer",
    entry: ResearchWorkApprovalEntry
  ): void;
}>();

const displayMapping = useResearchWorkApprovalDisplayMapping({
  approvalScopeIdentifier: componentProperties.approvalScopeIdentifier,
});

const {
  mapResearchWorkTypeToDisplayName,
  mapApprovalStatusToDisplayName,
  mapApprovalStatusToBadgeClass,
  formatIntegerValue,
} = displayMapping;

const currentPageNumber = ref<number>(1);
const pageSize = ref<number>(8);

const totalItemCount = computed<number>(
  () => componentProperties.researchWorkApprovalList.length
);
const totalPageCount = computed<number>(() =>
  Math.max(1, Math.ceil(totalItemCount.value / pageSize.value))
);

watch([totalItemCount, pageSize], () => {
  // WHY: tránh rơi vào trang trống khi filter làm giảm dữ liệu
  if (currentPageNumber.value > totalPageCount.value)
    currentPageNumber.value = totalPageCount.value;
  if (currentPageNumber.value < 1) currentPageNumber.value = 1;
});

const pagedResearchWorkApprovalList = computed<ResearchWorkApprovalEntry[]>(
  () => {
    const startIndex = (currentPageNumber.value - 1) * pageSize.value;
    return componentProperties.researchWorkApprovalList.slice(
      startIndex,
      startIndex + pageSize.value
    );
  }
);

const { approvalScopeIdentifier, tableActionButtonLabel } = componentProperties;
</script>
