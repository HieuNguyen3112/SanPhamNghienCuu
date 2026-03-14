<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Quản lý giờ nghiên cứu khoa học theo giảng viên"
          subtitle="Theo dõi và tổng hợp giờ NCKH của giảng viên toàn trường"
          :year-options="yearOptions"
          :selected-year-id="filter.yearId ?? undefined"
          @change-year="applyFilter({ yearId: $event })"
          @exportPdfClicked="onExportPdf"
          @exportExcelClicked="onExportExcel"
        />
      </div>
      <div class="mt-4">
        <HoursFilterPanel
          :filter="filter"
          :year-options="yearOptions"
          @apply-filter="applyFilter"
          @reset="resetFilter"
        >
          <template #extraFilter="{ filter, updateFilter }">
            <div class="w-full">
              <label class="text-xs text-slate-600">Khoa</label>
              <select
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
                :value="filter.facultyId ?? 0"
                @change="
                  updateFilter({
                    facultyId:
                      Number(($event.target as HTMLSelectElement).value) === 0
                        ? null
                        : Number(($event.target as HTMLSelectElement).value),
                  })
                "
              >
                <option :value="0">Tất cả</option>
                <option v-for="f in facultyOptions" :key="f.id" :value="f.id">
                  {{ f.name }}
                </option>
              </select>
            </div>
          </template>
        </HoursFilterPanel>
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
          :current-page-number="currentPageNumber"
          :page-size="pageSize"
          :total-item-count="totalItemCount"
          @view="openDrawer"
          @update:currentPageNumber="updatePage"
          @update:pageSize="updatePageSize"
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
import HoursFilterPanel from "@/features/scientific/management/lecturer-hours-management/components/HoursFilterPanel.vue";
import HoursKpiOverview from "@/features/scientific/management/lecturer-hours-management/components/HoursKpiOverview.vue";
import LecturerHoursTable from "@/features/scientific/management/lecturer-hours-management/components/LecturerHoursTable.vue";
import LecturerHoursDrawer from "@/features/scientific/management/lecturer-hours-management/components/LecturerHoursDrawer.vue";
import { useLecturerHoursManagement } from "@/features/scientific/management/lecturer-hours-management/composables/useLecturerHoursManagement";
import { useExportActionFeedback } from "@/shared/composables/useExportActionFeedback";
import {
  exportHoursExcel,
  exportHoursPdf,
} from "@/features/scientific/management/lecturer-hours-management/services/lecturerHoursExport.service";

const {
  filter,
  overview,

  yearOptions,
  facultyOptions,

  selectedLecturerOverview,
  drawerOpen,
  detailRows,

  totalLecturers,
  hitCount,
  missCount,
  hitRate,

  currentPageNumber,
  pageSize,
  totalItemCount,

  loadingOverview,
  loadingDetail,

  errorOverview,
  errorDetail,

  loadOverview,
  applyFilter,
  resetFilter,
  openDrawer,
  closeDrawer,
  updatePage,
  updatePageSize,
} = useLecturerHoursManagement({
  initialYearId: null,
  initialFacultyId: null,
});

const { runExport } = useExportActionFeedback();

const selectedAcademicYearCode = computed(() => {
  return yearOptions.value.find((y) => y.id === filter.yearId)?.code ?? "";
});

function buildExportParams() {
  return {
    faculty_id: filter.facultyId ?? null,
    academic_year_id: filter.yearId ?? null,
    kpi_status: filter.kpiStatus,
    q: filter.keyword,
  };
}

async function onExportExcel() {
  await runExport("excel", () => exportHoursExcel(buildExportParams()));
}

async function onExportPdf() {
  await runExport("pdf", () => exportHoursPdf(buildExportParams()));
}

onMounted(() => {
  loadOverview();
});
</script>
