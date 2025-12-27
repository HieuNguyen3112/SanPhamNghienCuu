// File:
src/features/lecturer-hours-overview/pages/LecturerHoursOverviewPage.vue
<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Tổng quan giờ NCKH cá nhân"
          subtitle="Thống kê giờ NCKH của bạn theo năm học và theo dõi lịch sử các đợt xét duyệt"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <HoursOverviewSection
        :overview="overview"
        :loading="loadingOverview"
        :error="errorOverview"
      />

      <HoursDistributionSection
        :overview="overview"
        :distribution="distribution"
        :loading="loadingDistribution"
        :error="errorDistribution"
      />

      <HoursBatchHistorySection
        :batches="batches"
        :loading="loadingBatches"
        :error="errorBatches"
        :detail-open="detailOpen"
        :detail="selectedBatchDetail"
        :loading-detail="loadingBatchDetail"
        :error-detail="errorBatchDetail"
        @open-detail="openBatchDetail"
        @close-detail="closeBatchDetail"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import HoursOverviewSection from "../components/HoursOverviewSection.vue";
import HoursDistributionSection from "../components/HoursDistributionSection.vue";
import HoursBatchHistorySection from "../components/HoursBatchHistorySection.vue";
import { useLecturerHoursOverview } from "../composables/useHoursOverview";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

const {
  overview,
  distribution,
  batches,

  detailOpen,
  selectedBatchDetail,

  loadingOverview,
  errorOverview,
  loadingDistribution,
  errorDistribution,
  loadingBatches,
  errorBatches,
  loadingBatchDetail,
  errorBatchDetail,

  loadOverview,
  loadDistribution,
  loadBatches,
  openBatchDetail,
  closeBatchDetail,
} = useLecturerHoursOverview();

onMounted(async () => {
  await Promise.all([loadOverview(), loadDistribution(), loadBatches()]);
});
</script>
