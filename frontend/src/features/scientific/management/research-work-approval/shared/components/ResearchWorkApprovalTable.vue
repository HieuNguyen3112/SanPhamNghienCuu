<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <!-- Header -->
    <div
      class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
    >
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="text-sm font-semibold text-slate-900">
            Danh sách công trình
          </h2>

          <span class="text-xs text-slate-300">•</span>

          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span
              class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 font-semibold text-amber-900"
            >
              Chờ duyệt: {{ totalPendingResearchWorkCount }}
            </span>

            <span
              class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-semibold text-slate-700"
            >
              Tổng: {{ totalItemCount }}
            </span>
          </div>
        </div>

        <p class="mt-1 text-xs text-slate-500">
          Nhấn vào một dòng để mở hồ sơ ở ngăn bên phải.
        </p>
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

    <!-- Empty state -->
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

    <!-- List (clickable rows) -->
    <div v-else class="mt-4">
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <article
          v-for="researchWorkApprovalEntry in pagedResearchWorkApprovalList"
          :key="researchWorkApprovalEntry.researchWorkIdentifier"
          role="button"
          tabindex="0"
          class="group cursor-pointer select-none p-4 outline-none transition hover:bg-slate-50/60 focus:bg-slate-50/60 focus:ring-4 focus:ring-slate-100"
          @click="
            componentEvents(
              'openResearchWorkDetailDrawer',
              researchWorkApprovalEntry,
            )
          "
          @keydown.enter.prevent="
            componentEvents(
              'openResearchWorkDetailDrawer',
              researchWorkApprovalEntry,
            )
          "
          @keydown.space.prevent="
            componentEvents(
              'openResearchWorkDetailDrawer',
              researchWorkApprovalEntry,
            )
          "
          aria-label="Mở chi tiết công trình"
        >
          <div
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
          >
            <!-- Left -->
            <div class="min-w-0">
              <!-- Title + badges -->
              <div class="flex flex-wrap items-start gap-2">
                <h3
                  class="min-w-0 flex-1 text-sm font-semibold text-slate-900 line-clamp-2 md:line-clamp-1"
                  :title="researchWorkApprovalEntry.researchWorkTitle"
                >
                  {{ researchWorkApprovalEntry.researchWorkTitle }}
                </h3>

                <span
                  :class="
                    mapApprovalStatusToBadgeClass(
                      researchWorkApprovalEntry.approvalStatus,
                    )
                  "
                >
                  {{
                    mapApprovalStatusToDisplayName(
                      researchWorkApprovalEntry.approvalStatus,
                    )
                  }}
                </span>

                <span
                  v-if="approvalScopeIdentifier === 'UNIVERSITY_SCOPE'"
                  class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700"
                >
                  <svg
                    class="h-3.5 w-3.5 text-slate-500"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M16.704 5.29a1 1 0 010 1.414l-7.2 7.2a1 1 0 01-1.414 0l-3.2-3.2a1 1 0 011.414-1.414l2.493 2.493 6.493-6.493a1 1 0 011.414 0z"
                      clip-rule="evenodd"
                    />
                  </svg>
                  Đã duyệt cấp khoa
                </span>
              </div>

              <!-- Meta grid -->
              <div
                class="mt-2 grid grid-cols-1 gap-y-1 text-sm text-slate-700 sm:grid-cols-2 sm:gap-x-6"
              >
                <div class="min-w-0 truncate">
                  <span class="text-slate-500">Giảng viên:</span>
                  <span class="ml-1 font-medium text-slate-800">
                    {{
                      researchWorkApprovalEntry.submittingLecturerDisplayName
                    }}
                  </span>
                </div>

                <div class="min-w-0 truncate">
                  <span class="text-slate-500">Khoa:</span>
                  <span class="ml-1 font-medium text-slate-800">
                    {{ researchWorkApprovalEntry.facultyDisplayName }}
                  </span>
                </div>

                <div class="min-w-0 truncate">
                  <span class="text-slate-500">Năm học:</span>
                  <span class="ml-1 font-medium text-slate-800">
                    {{ researchWorkApprovalEntry.academicYear }}
                  </span>
                </div>

                <div class="min-w-0 truncate">
                  <span class="text-slate-500">Loại:</span>
                  <span class="ml-1 font-medium text-slate-800">
                    {{
                      mapResearchWorkTypeToDisplayName(
                        researchWorkApprovalEntry.researchWorkType,
                      )
                    }}
                  </span>
                </div>
              </div>

              <!-- Chips summary -->
              <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                <span
                  class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-semibold text-slate-700"
                >
                  Minh chứng:
                  <span class="ml-1 text-slate-900">
                    {{
                      researchWorkApprovalEntry.evidenceAttachmentList.length
                    }}
                  </span>
                </span>

                <span
                  v-if="researchWorkApprovalEntry.hasApproverConflict"
                  class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 font-semibold text-rose-800"
                >
                  Bạn tham gia công trình này
                </span>

                <span
                  class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-semibold text-slate-700"
                >
                  Giờ kê khai:
                  <span class="ml-1 text-slate-900">
                    {{
                      formatIntegerValue(
                        researchWorkApprovalEntry.lecturerDeclaredResearchHours,
                      )
                    }}
                  </span>
                </span>

                <span
                  v-if="approvalScopeIdentifier === 'UNIVERSITY_SCOPE'"
                  class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-semibold text-slate-700"
                >
                  Giờ chính thức:
                  <span class="ml-1 text-slate-900">
                    {{
                      formatIntegerValue(
                        researchWorkApprovalEntry.officialResearchHours,
                      )
                    }}
                  </span>
                </span>
              </div>
            </div>

            <!-- Right: subtle affordance -->
            <div
              class="mt-1 flex shrink-0 items-center justify-end md:mt-0 md:pl-4"
            >
              <div
                class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 group-hover:text-slate-700"
              >
                <span>Mở hồ sơ</span>
                <svg
                  class="h-4 w-4"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div class="pt-3">
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
    entry: ResearchWorkApprovalEntry,
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
  () => componentProperties.researchWorkApprovalList.length,
);
const totalPageCount = computed<number>(() =>
  Math.max(1, Math.ceil(totalItemCount.value / pageSize.value)),
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
      startIndex + pageSize.value,
    );
  },
);

const { approvalScopeIdentifier } = componentProperties;
</script>
