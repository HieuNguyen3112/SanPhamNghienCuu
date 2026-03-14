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
import {
  resolveApiErrorMessage,
  useActionFeedback,
} from "@/shared/composables/useActionFeedback";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

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
const { runWithFeedback } = useActionFeedback();
const { runPageLoad } = usePageLoadFeedback();

async function loadCatalogDataWithFeedback() {
  await runPageLoad(
    () => Promise.all([work.fetch(), quota.fetch(), year.fetch()]),
    {
      loading: {
        title: "Đang tải danh mục giờ NCKH",
        message: "Hệ thống đang chuẩn bị dữ liệu cấu hình giờ nghiên cứu...",
      },
      onError: (error) => console.error(error),
    },
  );
}

onMounted(() => {
  void loadCatalogDataWithFeedback();
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
  isProjectDraft: work.isProjectDraft.value,

  openCreate: work.openCreate,
  openEdit: work.openEdit,
  closeModal: work.closeModal,
  save: saveWorkConversion,
  setActiveWithConfirm: setWorkConversionActiveWithFeedback,
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
  save: saveQuota,
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
  saveYear: saveAcademicYear,
  setActiveYearWithConfirm: setAcademicYearActiveWithFeedback,
  setPage: year.setPage,
  setPageSize: year.setPageSize,

  statusLabel: year.statusLabel,
}));

function resolveActionErrorMessage(error: unknown, fallback: string) {
  return resolveApiErrorMessage(error, fallback);
}

async function saveWorkConversion() {
  const isCreate = work.modal.mode === "create";
  await runWithFeedback(() => work.save(), {
    loading: {
      title: "Đang xử lý",
      message: "Hệ thống đang cập nhật quy đổi giờ...",
    },
    success: {
      title: "Thành công",
      message: isCreate
        ? "Thêm quy đổi giờ thành công."
        : "Cập nhật quy đổi giờ thành công.",
    },
    error: {
      title: "Có lỗi xảy ra",
      message: (error) =>
        resolveActionErrorMessage(error, "Thao tác thất bại. Vui lòng thử lại."),
    },
    rethrow: false,
  });
}

async function setWorkConversionActiveWithFeedback(id: number, isActive: boolean) {
  await runWithFeedback(() => work.setActiveWithConfirm(id, isActive), {
    loading: {
      title: "Đang xử lý",
      message: "Đang cập nhật trạng thái quy đổi giờ...",
    },
    success: {
      title: "Thành công",
      message: isActive
        ? "Đã áp dụng quy đổi giờ thành công."
        : "Ngừng áp dụng quy đổi giờ thành công.",
    },
    error: {
      title: "Có lỗi xảy ra",
      message: (error) =>
        resolveActionErrorMessage(error, "Thao tác thất bại. Vui lòng thử lại."),
    },
    rethrow: false,
  });
}

async function saveQuota() {
  const isCreate = quota.modal.mode === "create";
  await runWithFeedback(() => quota.save(), {
    loading: {
      title: "Đang xử lý",
      message: "Hệ thống đang cập nhật định mức giờ...",
    },
    success: {
      title: "Thành công",
      message: isCreate
        ? "Thêm định mức giờ NCKH thành công."
        : "Cập nhật định mức giờ NCKH thành công.",
    },
    error: {
      title: "Có lỗi xảy ra",
      message: (error) =>
        resolveActionErrorMessage(error, "Thao tác thất bại. Vui lòng thử lại."),
    },
    rethrow: false,
  });
}

async function saveAcademicYear() {
  const isCreate = year.modal.mode === "create";
  await runWithFeedback(() => year.saveYear(), {
    loading: {
      title: "Đang xử lý",
      message: "Hệ thống đang cập nhật năm học...",
    },
    success: {
      title: "Thành công",
      message: isCreate
        ? "Thêm năm học thành công."
        : "Cập nhật năm học thành công.",
    },
    error: {
      title: "Có lỗi xảy ra",
      message: (error) =>
        resolveActionErrorMessage(error, "Thao tác thất bại. Vui lòng thử lại."),
    },
    rethrow: false,
  });
}

async function setAcademicYearActiveWithFeedback(id: number) {
  await runWithFeedback(() => year.setActiveYearWithConfirm(id), {
    loading: {
      title: "Đang xử lý",
      message: "Đang cập nhật năm học áp dụng...",
    },
    success: {
      title: "Thành công",
      message: "Đặt năm học đang áp dụng thành công.",
    },
    error: {
      title: "Có lỗi xảy ra",
      message: (error) =>
        resolveActionErrorMessage(error, "Thao tác thất bại. Vui lòng thử lại."),
    },
    rethrow: false,
  });
}
</script>
