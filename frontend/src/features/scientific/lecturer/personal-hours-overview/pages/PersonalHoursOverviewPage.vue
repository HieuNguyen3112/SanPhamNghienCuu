<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Tổng quan giờ NCKH cá nhân"
          subtitle="Thống kê giờ NCKH theo năm học và theo dõi lịch sử các đợt xét duyệt"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />

        <div class="mt-4 flex flex-wrap items-center gap-3">
          <label
            for="hours-scope-select"
            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Phạm vi thống kê
          </label>
          <select
            id="hours-scope-select"
            class="min-w-[220px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none ring-0 transition focus:border-slate-400"
            :value="selectedScopeValue"
            :disabled="loadingAcademicYears"
            @change="onScopeChange"
          >
            <option v-for="option in scopeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
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
  scopeOptions,
  selectedScopeValue,

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
  loadingAcademicYears,

  initialize,
  changeScope,
  openBatchDetail,
  closeBatchDetail,
} = useLecturerHoursOverview();

async function onScopeChange(event: Event) {
  const target = event.target as HTMLSelectElement | null;
  if (!target) return;
  await changeScope(target.value);
}

onMounted(async () => {
  await initialize();
});
</script>