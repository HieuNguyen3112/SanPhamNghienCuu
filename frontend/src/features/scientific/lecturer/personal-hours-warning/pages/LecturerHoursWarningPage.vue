<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full max-w-7xl space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Cảnh báo giờ NCKH"
          subtitle="Cảnh báo sớm để bạn kịp hành động và tránh thiếu giờ NCKH."
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <AlertsHeaderBanner
        :summary="summaryStatus"
        :loading="loadingSummary"
        :error="errorSummary"
      />

      <AlertsFilterBar
        :filter-status="filterStatus"
        :counts="counts"
        @change="changeFilter"
      />

      <AlertsList
        :alerts="filteredAlerts"
        :loading="loadingAlerts"
        :error="errorAlerts"
        @mark-seen="markAsSeen"
      />

      <AlertsActionPanel
        :suggestions="actionSuggestions"
        :loading="loadingSuggestions"
        :error="errorSuggestions"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import AlertsHeaderBanner from "@/features/scientific/lecturer/personal-hours-warning/components/WarningHeaderBanner.vue";
import AlertsFilterBar from "@/features/scientific/lecturer/personal-hours-warning/components/WarningFilterBar.vue";
import AlertsList from "../components/WarningList.vue";
import AlertsActionPanel from "../components/WarningActionPanel.vue";
import { useHoursWarning } from "../composables/useHoursWarning";

const {
  summaryStatus,
  actionSuggestions,

  filterStatus,
  filteredAlerts,
  counts,

  loadingSummary,
  errorSummary,
  loadingAlerts,
  errorAlerts,
  loadingSuggestions,
  errorSuggestions,

  loadAlerts,
  changeFilter,
  markAsSeen,
} = useHoursWarning();

onMounted(() => {
  loadAlerts();
});
</script>
