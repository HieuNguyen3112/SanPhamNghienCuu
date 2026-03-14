<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Duyệt giờ NCKH theo khoa"
          subtitle="Khoa duyệt giờ là bước cuối cùng của quy trình duyệt giờ NCKH"
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
        :loading="loadingList || loadingLookups"
        :faculty-select-disabled="true"
        @update:filter="applyFilter"
        @reset="resetFilterWithDefaults"
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
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

import type {
  AcademicYearOption,
  FacultyOption,
} from "@/features/scientific/management/lecturer-hour-approval/contracts/hourApproval.contract";
import { createFacultyHourApprovalService } from "@/features/scientific/management/lecturer-hour-approval/services/facultyHoursApprovals.service";
import { useHourApprovalManagement } from "@/features/scientific/management/lecturer-hour-approval/composables/useHourApprovalManagement";

const facultyOptions = ref<FacultyOption[]>([]);
const academicYearOptions = ref<AcademicYearOption[]>([]);
const currentAcademicYearId = ref<number | null>(null);
const loadingLookups = ref(false);
const { runPageLoad } = usePageLoadFeedback();

function resolveDefaultAcademicYearId(): number | null {
  return (
    academicYearOptions.value.find((item) => item.isActive)?.id ??
    academicYearOptions.value.find((item) => item.isCurrent)?.id ??
    currentAcademicYearId.value ??
    academicYearOptions.value[0]?.id ??
    null
  );
}

async function loadLookups() {
  loadingLookups.value = true;
  try {
    const { data } = await http.get<{
      data: {
        faculties: FacultyOption[];
        academic_years?: Array<{
          id: number;
          code: string;
          start_date: string;
          end_date: string;
          is_active?: boolean;
          is_current?: boolean;
        }>;
        current_academic_year_id?: number | null;
      };
    }>("/api/faculty/hours/approvals/lookups");
    facultyOptions.value = (data.data?.faculties ?? []).map((item) => ({
      id: item.id,
      name: item.name,
    }));
    academicYearOptions.value = (data.data?.academic_years ?? []).map(
      (item) => ({
        id: item.id,
        code: item.code,
        startDate: item.start_date,
        endDate: item.end_date,
        isActive: item.is_active ?? false,
        isCurrent: item.is_current ?? false,
      }),
    );
    currentAcademicYearId.value = data.data?.current_academic_year_id ?? null;
  } catch (_e) {
    facultyOptions.value = [];
    academicYearOptions.value = [];
    currentAcademicYearId.value = null;
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
  openRequestDetail,
  closeRequestDetail,
  approveRequest,
  rejectRequest,
  updatePage,
  updatePageSize,
} = useHourApprovalManagement(service, {
  facultyId: null,
  academicYearId: null,
  status: "pending",
});

onMounted(async () => {
  await runPageLoad(
    async () => {
      await loadLookups();
      if (!filter.facultyId && facultyOptions.value[0]?.id) {
        filter.facultyId = facultyOptions.value[0].id;
      }
      if (!filter.academicYearId) {
        filter.academicYearId = resolveDefaultAcademicYearId();
      }
      await loadRequests({ withFeedback: false });
    },
    {
      loading: {
        title: "Đang khởi tạo xét duyệt giờ NCKH",
        message: "Hệ thống đang chuẩn bị dữ liệu xét duyệt giờ nghiên cứu khoa học...",
      },
    },
  );
});

async function resetFilterWithDefaults() {
  await applyFilter({
    facultyId: facultyOptions.value[0]?.id ?? null,
    academicYearId: resolveDefaultAcademicYearId(),
    status: "pending",
    submittedFrom: null,
    submittedTo: null,
    searchText: "",
  });
}

function onApproveSelected(requestId: number, activityIds: number[]) {
  void approveRequest(requestId, { activityIds });
}
</script>
