<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Xét duyệt giờ nghiên cứu khoa học cho giảng viên"
          subtitle="Theo dõi tình hình xét duyệt giờ NCKH của giảng viên trong trường"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <HourApprovalFilterPanel
        :filter="filter"
        :faculty-options="facultyOptions"
        :academic-year-options="academicYearOptions"
        :loading="loadingList || loadingFaculties"
        :faculty-select-disabled="false"
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
        @approve="onApproveSelected"
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

import type {
  AcademicYearOption,
  FacultyOption,
} from "@/features/scientific/management/lecturer-hour-approval/contracts/hourApproval.contract";
import { createUniversityHourApprovalService } from "@/features/scientific/management/lecturer-hour-approval/services/uniHoursApprovals.service";
import { useHourApprovalManagement } from "@/features/scientific/management/lecturer-hour-approval/composables/useHourApprovalManagement";

const facultyOptions = ref<FacultyOption[]>([]);
const academicYearOptions = ref<AcademicYearOption[]>([]);
const loadingFaculties = ref(false);

async function loadFacultyOptions() {
  loadingFaculties.value = true;
  try {
    const { data } = await http.get<{ data: FacultyOption[] }>(
      "/api/lookups/faculties"
    );
    facultyOptions.value = (data.data ?? []).map((item) => ({
      id: item.id,
      name: item.name,
    }));
  } catch (e) {
    facultyOptions.value = [];
  } finally {
    loadingFaculties.value = false;
  }
}

async function loadAcademicYearOptions() {
  try {
    const { data } = await http.get<{
      data: Array<{
        id: number;
        code: string;
        start_date: string;
        end_date: string;
        is_active?: boolean;
        is_current?: boolean;
      }>;
    }>("/api/lookups/academic-years");

    academicYearOptions.value = (data.data ?? []).map((item) => ({
      id: item.id,
      code: item.code,
      startDate: item.start_date,
      endDate: item.end_date,
      isActive: item.is_active ?? false,
      isCurrent: item.is_current ?? false,
    }));
  } catch (_e) {
    academicYearOptions.value = [];
  }
}

const service = createUniversityHourApprovalService();

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
} = useHourApprovalManagement(service);

onMounted(async () => {
  await Promise.all([loadFacultyOptions(), loadAcademicYearOptions()]);
  filter.academicYearId =
    academicYearOptions.value.find((item) => item.isCurrent)?.id ??
    academicYearOptions.value.find((item) => item.isActive)?.id ??
    academicYearOptions.value[0]?.id ??
    null;
  await loadRequests();
});

function onApproveSelected(requestId: number, activityIds: number[]) {
  void approveRequest(requestId, { activityIds });
}
</script>

