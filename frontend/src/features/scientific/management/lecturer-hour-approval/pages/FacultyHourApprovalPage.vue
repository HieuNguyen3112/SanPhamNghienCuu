<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Xét duyệt giờ nghiên cứu khoa học cho giảng viên "
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
        :loading="loadingList"
        :faculty-select-disabled="true"
        @update:filter="applyFilter"
        @reset="resetFilter"
      />

      <div class="mt-4">
        <HourApprovalTable
          :rows="rows"
          :loading="loadingList"
          :error="errorList"
          @row-click="openRequestDetail"
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
import { onMounted } from "vue";
import HourApprovalFilterPanel from "@/features/scientific/management/lecturer-hour-approval/components/HourApprovalFilterPanel.vue";
import HourApprovalTable from "@/features/scientific/management/lecturer-hour-approval/components/HourApprovalTable.vue";
import HourApprovalDetailDrawer from "@/features/scientific/management/lecturer-hour-approval/components/HourApprovalDetailDrawer.vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

import type { FacultyOption } from "@/features/scientific/management/lecturer-hour-approval/contracts/hourApproval.contract";
import { buildFacultyDb } from "@/features/scientific/management/lecturer-hour-approval/mock-data/hourApproval.mock";
import { createHourApprovalService } from "@/features/scientific/management/lecturer-hour-approval/services/hourApprovalService";
import { useHourApprovalManagement } from "@/features/scientific/management/lecturer-hour-approval/composables/useHourApprovalManagement";

const facultyOptions: FacultyOption[] = [
  { id: 10, name: "Khoa Công nghệ Thông tin" },
];

const service = createHourApprovalService(buildFacultyDb(10));

const {
  filter,
  rows,
  requestDetail,
  isDetailOpen,

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
} = useHourApprovalManagement(service, {
  facultyId: 10, // BCN khoa cố định khoa
});

onMounted(() => {
  loadRequests();
});
</script>
