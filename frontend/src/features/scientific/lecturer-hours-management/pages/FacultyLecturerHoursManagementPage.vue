<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Quản lý giờ nghiên cứu khoa học theo giảng viên"
          subtitle="Theo dõi tình hình thực hiện giờ NCKH của giảng viên trong khoa"
          :year-options="yearOptions"
          :selected-year-id="filter.yearId"
          @change-year="applyFilter({ yearId: $event })"
          @export-excel="onExportExcel"
          @export-pdf="onExportPdf"
        />
      </div>
      <div class="mt-4">
        <HoursFilterPanel
          :filter="filter"
          :year-options="yearOptions"
          @apply-filter="applyFilter"
          @reset="resetFilter"
        />
      </div>

      <div class="mt-4">
        <HoursKpiOverview
          :total-lecturers="totalLecturers"
          :hit-count="hitCount"
          :miss-count="missCount"
          :hit-rate="hitRate"
        />
      </div>

      <div class="mt-4">
        <LecturerHoursTable
          :overview="overview"
          :loading="loadingOverview"
          :error="errorOverview"
          @view="openDrawer"
        />
      </div>

      <LecturerHoursDrawer
        :open="drawerOpen"
        :lecturer="selectedLecturerOverview"
        :academic-year-code="selectedAcademicYearCode"
        :detail-rows="detailRows"
        :loading-detail="loadingDetail"
        :error-detail="errorDetail"
        @close="closeDrawer"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import HoursFilterPanel from "@/features/scientific/lecturer-hours-management/components/HoursFilterPanel.vue";
import HoursKpiOverview from "@/features/scientific/lecturer-hours-management/components/HoursKpiOverview.vue";
import LecturerHoursTable from "@/features/scientific/lecturer-hours-management/components/LecturerHoursTable.vue";
import LecturerHoursDrawer from "@/features/scientific/lecturer-hours-management/components/LecturerHoursDrawer.vue";
import { useLecturerHoursManagement } from "@/features/scientific/lecturer-hours-management/composables/useLecturerHoursManagement";
import { mockAcademicYears } from "@/features/scientific/lecturer-hours-management/mock-data/lecturerHoursCatalog.mock";

const yearOptions = mockAcademicYears;

// Faculty scope: ví dụ khoa CNTT id=10
const facultyScopeId = 10;

const initialYearId =
  yearOptions.find((y) => y.isActive)?.id ?? yearOptions[0]?.id ?? 1;

const {
  filter,
  overview,

  selectedLecturerOverview,
  drawerOpen,
  detailRows,

  totalLecturers,
  hitCount,
  missCount,
  hitRate,

  loadingOverview,
  loadingDetail,

  errorOverview,
  errorDetail,

  loadOverview,
  applyFilter,
  resetFilter,
  openDrawer,
  closeDrawer,
} = useLecturerHoursManagement({
  initialYearId,
  initialFacultyId: facultyScopeId,
});

const selectedAcademicYearCode = computed(() => {
  return yearOptions.find((y) => y.id === filter.yearId)?.code ?? "—";
});

function onExportExcel() {
  // UI only
  // eslint-disable-next-line no-console
  console.log("Export Excel (UI only)");
}

function onExportPdf() {
  // UI only
  // eslint-disable-next-line no-console
  console.log("Export PDF (UI only)");
}

onMounted(() => {
  loadOverview();
});
</script>
