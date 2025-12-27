<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Quản lý công trình khoa học theo giảng viên"
          subtitle="Theo dõi công trình khoa học của giảng viên toàn trường"
          :show-export-pdf="true"
          :show-export-excel="true"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <div class="space-y-4">
        <LecturerResearchWorkFilterPanel
          :filter="filter"
          :academic-year-options="academicYearOptions"
          @update-filter="updateFilter"
          @reset="resetFilter"
        >
          <template
            #extraFilter="{
              filter: slotFilter,
              updateFilter: slotUpdateFilter,
            }"
          >
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700">
                Khoa
              </label>
              <select
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:outline-none"
                :value="slotFilter.facultyId ?? ''"
                @change="(e) => onFacultyChange(e, slotUpdateFilter)"
              >
                <option value="">Tất cả</option>
                <option
                  v-for="faculty in facultyOptions"
                  :key="faculty.id"
                  :value="faculty.id"
                >
                  {{ faculty.name }}
                </option>
              </select>
            </div>
          </template>
        </LecturerResearchWorkFilterPanel>

        <LecturerResearchWorkSummaryTable
          :overview-items="overviewItems"
          :is-loading="isOverviewLoading"
          :error-message="overviewError"
          @open-lecturer="openLecturerDrawer"
        />
      </div>

      <LecturerApprovedResearchWorkDrawer
        :is-open="isLecturerDrawerOpen"
        :lecturer-overview="selectedLecturerOverview"
        :approved-items="approvedItems"
        :is-loading="isApprovedLoading"
        :error-message="approvedError"
        @close="closeLecturerDrawer"
        @open-detail="openDetail"
      />

      <ApprovedResearchWorkDetailDrawer
        :is-open="isDetailDrawerOpen"
        :detail="detail"
        :is-loading="isDetailLoading"
        :error-message="detailError"
        @back="backToList"
        @close="closeLecturerDrawer"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import LecturerResearchWorkFilterPanel from "@/features/scientific/management/research-work-management/components/LecturerResearchWorkFilterPanel.vue";
import LecturerResearchWorkSummaryTable from "@/features/scientific/management/research-work-management/components/LecturerResearchWorkSummaryTable.vue";
import LecturerApprovedResearchWorkDrawer from "@/features/scientific/management/research-work-management/components/LecturerApprovedResearchWorkDrawer.vue";
import ApprovedResearchWorkDetailDrawer from "@/features/scientific/management/research-work-management/components/ApprovedResearchWorkDetailDrawer.vue";
import type { FilterState } from "@/features/scientific/management/research-work-management/lecturerResearchWork.contracts";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

import { createLecturerResearchWorkClient } from "@/features/scientific/management/research-work-management/api/lecturerResearchWork.client";
import { useLecturerResearchWorkManagement } from "@/features/scientific/management/research-work-management/composables/useLecturerResearchWork";

const client = createLecturerResearchWorkClient({ scope: "university" });
const {
  filter,
  facultyOptions,
  academicYearOptions,

  overviewItems,
  isOverviewLoading,
  overviewError,

  selectedLecturerOverview,
  isLecturerDrawerOpen,
  isDetailDrawerOpen,

  approvedItems,
  isApprovedLoading,
  approvedError,

  detail,
  isDetailLoading,
  detailError,

  updateFilter,
  resetFilter,
  openLecturerDrawer,
  closeLecturerDrawer,
  openDetail,
  backToList,
} = useLecturerResearchWorkManagement({ client });

function onFacultyChange(
  event: Event,
  slotUpdateFilter: (partial: Partial<FilterState>) => void
) {
  const value = (event.target as HTMLSelectElement).value;
  slotUpdateFilter({ facultyId: value ? Number(value) : null });
}
</script>
