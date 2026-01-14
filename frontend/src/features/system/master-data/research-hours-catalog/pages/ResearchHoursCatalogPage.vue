<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <!-- Header -->

      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Danh mục giờ NCKH"
          subtitle=" Cấu hình quy đổi và định mức giờ nghiên cứu khoa học phục vụ tính
            giờ toàn trường"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>
      <!-- Tabs -->
      <ResearchHoursCatalogTabs v-model="activeTab" :tabs="tabs" />

      <!-- Content card per tab -->
      <div class="mt-4">
        <WorkConversionCatalogSection
          v-if="activeTab === 'work_conversion'"
          :vm="workVm"
        />
        <HoursQuotaCatalogSection
          v-else-if="activeTab === 'hours_quota'"
          :vm="quotaVm"
        />
        <AcademicYearPeriodCatalogSection v-else :vm="yearVm" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, computed, ref } from "vue";
import { Repeat, Gauge, CalendarRange } from "lucide-vue-next";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

import ResearchHoursCatalogTabs from "../components/ResearchHoursCatalogTabs.vue";
import WorkConversionCatalogSection from "../components/WorkConversionCatalogSection.vue";
import HoursQuotaCatalogSection from "../components/HoursQuotaCatalogSection.vue";
import AcademicYearPeriodCatalogSection from "../components/AcademicYearPeriodCatalogSection.vue";

import { useWorkConversionCatalog } from "../composables/useWorkConversionCatalog";
import { useHoursQuotaCatalog } from "../composables/useHoursQuotaCatalog";
import { useAcademicYearPeriodCatalog } from "../composables/useAcademicYearPeriodCatalog";

type TabKey = "work_conversion" | "hours_quota" | "academic_year_period";

const activeTab = ref<TabKey>("work_conversion");

const tabs = [
  {
    key: "work_conversion" as const,
    label: "Quy đổi giờ theo công trình",
    icon: Repeat,
  },
  { key: "hours_quota" as const, label: "Định mức giờ NCKH", icon: Gauge },
  {
    key: "academic_year_period" as const,
    label: "Năm học / Đợt tính giờ",
    icon: CalendarRange,
  },
];

const work = useWorkConversionCatalog();
const quota = useHoursQuotaCatalog();
const year = useAcademicYearPeriodCatalog();

onMounted(async () => {
  await Promise.all([work.fetch(), quota.fetch(), year.fetch()]);
});

const workVm = computed(() => ({
  loading: work.loading.value,
  saving: work.saving.value,
  error: work.error.value,

  rows: work.rows.value,
  filter: work.filter,
  page: work.page.value,
  pageSize: work.pageSize.value,
  totalItems: work.totalItems.value,

  academicYearOptions: work.academicYearOptions.value,
  kindOptions: work.kindOptions.value,
  typeOptions: work.typeOptions.value,

  modal: work.modal,
  draft: work.draft,
  draftErrors: work.draftErrors,

  openCreate: work.openCreate,
  openEdit: work.openEdit,
  closeModal: work.closeModal,
  save: work.save,
  setActiveWithConfirm: work.setActiveWithConfirm,
  setPage: work.setPage,
  setPageSize: work.setPageSize,
}));

const quotaVm = computed(() => ({
  loading: quota.loading.value,
  saving: quota.saving.value,
  error: quota.error.value,

  rows: quota.rows.value,
  filter: quota.filter,
  page: quota.page.value,
  pageSize: quota.pageSize.value,
  totalItems: quota.totalItems.value,

  academicYearOptions: quota.academicYearOptions.value,

  modal: quota.modal,
  draft: quota.draft,
  draftErrors: quota.draftErrors,

  openCreate: quota.openCreate,
  openEdit: quota.openEdit,
  closeModal: quota.closeModal,
  save: quota.save,
  setPage: quota.setPage,
  setPageSize: quota.setPageSize,
}));

const yearVm = computed(() => ({
  loading: year.loading.value,
  saving: year.saving.value,
  error: year.error.value,

  rows: year.rows.value,
  filter: year.filter,
  page: year.page.value,
  pageSize: year.pageSize.value,
  totalItems: year.totalItems.value,

  modal: year.modal,
  draft: year.draft,
  draftErrors: year.draftErrors,

  openCreateYear: year.openCreateYear,
  openEditYear: year.openEditYear,
  closeModal: year.closeModal,
  saveYear: year.saveYear,
  setActiveYearWithConfirm: year.setActiveYearWithConfirm,
  setPage: year.setPage,
  setPageSize: year.setPageSize,

  statusLabel: year.statusLabel,
}));
</script>
