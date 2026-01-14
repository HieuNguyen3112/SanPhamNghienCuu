<template>
  <div class="min-h-screen bg-slate-50">
    <div class="space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Quản lý công trình khoa học cá nhân"
          subtitle="Theo dõi & kê khai các công trình NCKH của bạn"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <GuidanceAlert />

      <div
        v-if="toastMessage"
        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
      >
        {{ toastMessage }}
      </div>

      <HoursSummaryCard
        :total-approved-count="totalApprovedCount"
        :selected-count="selectedCount"
        :selected-hours-total="selectedHoursTotal"
      />

      <WorksFilterPanel
        :filter="filter"
        :loading="loadingList"
        @update:filter="applyFilter"
        @reset="resetFilter"
      />

      <ApprovedWorksTable
        :rows="filteredWorks"
        :selectable-ids="selectableIds"
        :selected-ids="selectedIds"
        :selected-count="selectedCount"
        :selected-hours-total="selectedHoursTotal"
        :submitting="submitting"
        :submit-error="submitError"
        :loading="loadingList"
        :error="errorList"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        :total-item-count="totalItemCount"
        @toggle-row="toggleWorkSelection"
        @toggle-select-all="toggleSelectAll"
        @open-detail="openWorkDetail"
        @submit-request="submitRequest"
        @update:currentPageNumber="updateCurrentPageNumber"
        @update:pageSize="updatePageSize"
      />

      <WorkDetailDrawer
        :open="drawerOpen"
        :detail="workDetail"
        :loading="loadingDetail"
        :error="errorDetail"
        @close="closeWorkDetail"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

import GuidanceAlert from "@/features/scientific/lecturer/personal-hours-declare/components/GuidanceAlert.vue";
import HoursSummaryCard from "@/features/scientific/lecturer/personal-hours-declare/components/HoursSummaryCard.vue";
import WorksFilterPanel from "@/features/scientific/lecturer/personal-hours-declare/components/WorksFilterPanel.vue";
import ApprovedWorksTable from "@/features/scientific/lecturer/personal-hours-declare/components/ApprovedWorksTable.vue";
import WorkDetailDrawer from "@/features/scientific/lecturer/personal-hours-declare/components/WorkDetailDrawer.vue";

import { useSelectHoursRequest } from "@/features/scientific/lecturer/personal-hours-declare/composables/useSelectHoursRequest";

const {
  filteredWorks,
  selectableIds,
  selectedIds,
  filter,

  drawerOpen,
  workDetail,

  loadingList,
  errorList,
  loadingDetail,
  errorDetail,

  submitting,
  submitError,
  toastMessage,

  totalApprovedCount,
  selectedCount,
  selectedHoursTotal,
  currentPageNumber,
  pageSize,
  totalItemCount,

  loadApprovedWorks,
  applyFilter,
  resetFilter,
  updateCurrentPageNumber,
  updatePageSize,

  toggleWorkSelection,
  toggleSelectAll,
  openWorkDetail,
  closeWorkDetail,
  submitRequest,
} = useSelectHoursRequest();

onMounted(() => {
  loadApprovedWorks();
});
</script>
