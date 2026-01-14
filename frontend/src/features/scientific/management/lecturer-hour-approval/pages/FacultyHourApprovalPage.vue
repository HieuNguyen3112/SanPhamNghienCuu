<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Xét duyệt giờ nghiên cứu khoa học cho giảng viên"
          subtitle="Theo dõi tình hình xét duyệt giờ NCKH của giảng viên trong khoa"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <HourApprovalFilterPanel
        :filter="filter"
        :faculty-options="facultyOptions"
        :loading="loadingList || loadingLookups"
        :faculty-select-disabled="true"
        @update:filter="applyFilter"
        @reset="resetFilter"
      />

      <div class="mt-4">
        <HourApprovalTable
          :rows="rows"
          :loading="loadingList"
          :error="errorList"
          :current-page-number="page"
          :page-size="perPage"
          :total-item-count="total"
          @row-click="openRequestDetail"
          @update:currentPageNumber="updatePage"
          @update:pageSize="updatePageSize"
        />
      </div>

      <HourApprovalDetailDrawer
        :open="isDetailOpen"
        :detail="requestDetail"
        :loading-detail="loadingDetail"
        :error-detail="errorDetail"
        :loading-approve="loadingApprove"
        :loading-reject="loadingReject"
        :error-approve="errorApprove"
        :error-reject="errorReject"
        @close="closeRequestDetail"
        @approve="approveRequest"
        @reject="rejectRequest"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import HourApprovalFilterPanel from "@/features/scientific/management/lecturer-hour-approval/components/HourApprovalFilterPanel.vue";
import HourApprovalTable from "@/features/scientific/management/lecturer-hour-approval/components/HourApprovalTable.vue";
import HourApprovalDetailDrawer from "@/features/scientific/management/lecturer-hour-approval/components/HourApprovalDetailDrawer.vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import http from "@/lib/http";

import type { FacultyOption } from "@/features/scientific/management/lecturer-hour-approval/contracts/hourApproval.contract";
import { createFacultyHourApprovalService } from "@/features/scientific/management/lecturer-hour-approval/services/facultyHoursApprovals.service";
import { useHourApprovalManagement } from "@/features/scientific/management/lecturer-hour-approval/composables/useHourApprovalManagement";

const facultyOptions = ref<FacultyOption[]>([]);
const loadingLookups = ref(false);

async function loadLookups() {
  loadingLookups.value = true;
  try {
    const { data } = await http.get<{ data: { faculties: FacultyOption[] } }>(
      "/api/faculty/hours/approvals/lookups"
    );
    facultyOptions.value = (data.data?.faculties ?? []).map((item) => ({
      id: item.id,
      name: item.name,
    }));
  } catch (e) {
    facultyOptions.value = [];
  } finally {
    loadingLookups.value = false;
  }
}

const service = createFacultyHourApprovalService();

const {
  filter,
  rows,
  requestDetail,
  isDetailOpen,

  page,
  perPage,
  total,

  loadingList,
  loadingDetail,
  loadingApprove,
  loadingReject,

  errorList,
  errorDetail,
  errorApprove,
  errorReject,

  loadRequests,
  applyFilter,
  resetFilter,
  openRequestDetail,
  closeRequestDetail,
  approveRequest,
  rejectRequest,
  updatePage,
  updatePageSize,
} = useHourApprovalManagement(service, {
  facultyId: null,
});

onMounted(async () => {
  await loadLookups();
  if (!filter.facultyId && facultyOptions.value[0]?.id) {
    filter.facultyId = facultyOptions.value[0].id;
  }
  await loadRequests();
});
</script>
