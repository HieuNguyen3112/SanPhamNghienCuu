<!-- src/features/research-works/pages/LecturerWorksOverviewPage.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-800">
          Quản lý công trình NCKH giảng viên
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Tổng hợp công trình nghiên cứu khoa học theo năm học, phục vụ theo dõi
          và quản lý của BCN khoa / Phòng QLKH.
        </p>
      </div>

      <RouterLink
        :to="{ name: 'works.approvals' }"
        class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 shadow-sm hover:bg-red-100"
      >
        <span class="mr-2 h-2 w-2 rounded-full bg-red-500" />
        Danh sách công trình chờ duyệt
        <span
          v-if="overviewStats?.pendingProjectsCount"
          class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"
        >
          {{ overviewStats.pendingProjectsCount }}
        </span>
      </RouterLink>
    </div>

    <!-- Bộ lọc: component đã render card giống các trang khác -->
    <ProjectsFilterBar
      v-model="filters"
      :scope-options="scopeOptions"
      :year-options="yearOptions"
      :semester-options="semesterOptions"
      :department-options="departmentOptions"
      :loading="loading"
      @apply="handleApplyFilter"
    />

    <!-- Stats -->
    <ProjectsStatsCards :loading="loading" :stats="overviewStats" />

    <!-- Bảng tổng hợp: card + chiều cao cố định, scroll dọc -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="max-h-[70vh] overflow-y-auto">
        <LecturerProjectsTable
          :loading="loading"
          :error-message="errorMessage"
          :items="summaries"
          :pagination="pagination"
          @change-page="handleChangePage"
          @view-lecturer="handleViewLecturer"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";

import ProjectsFilterBar from "@/features/research-works/components/ProjectsFilterBar.vue";
import ProjectsStatsCards from "@/features/research-works/components/ProjectsStatsCards.vue";
import LecturerProjectsTable from "@/features/research-works/components/LecturerProjectsTable.vue";
import type {
  DepartmentOption,
  LecturerProjectsSummary,
  OverviewStats,
  PaginationState,
  ProjectsFilter,
  ScopeOption,
  SelectOption,
} from "@/features/research-works/types";
import {
  fetchDepartmentOptions,
  fetchLecturerProjectsOverview,
  fetchSelectOptions,
} from "@/features/research-works/api";

const loading = ref(false);
const errorMessage = ref<string | null>(null);

const filters = ref<ProjectsFilter>({
  academicYear: "",
  semester: "all",
  scope: "faculty",
  departmentId: "all",
  lecturerName: "",
});

const yearOptions = ref<SelectOption<string>[]>([]);
const semesterOptions = ref<SelectOption<string>[]>([]);
const scopeOptions = ref<ScopeOption[]>([]);
const departmentOptions = ref<DepartmentOption[]>([]);

const pagination = reactive<PaginationState>({
  page: 1,
  pageSize: 10,
  totalItems: 0,
});

const summaries = ref<LecturerProjectsSummary[]>([]);
const overviewStats = ref<OverviewStats | null>(null);

async function loadOptions() {
  const [selects, departments] = await Promise.all([
    fetchSelectOptions(),
    fetchDepartmentOptions(),
  ]);

  yearOptions.value = selects.years;
  semesterOptions.value = selects.semesters;
  scopeOptions.value = selects.scopes;
  departmentOptions.value = departments;

  if (!filters.value.academicYear && yearOptions.value.length > 0) {
    const firstYear = yearOptions.value[0];
    filters.value.academicYear =
      firstYear && typeof firstYear.value === "string" ? firstYear.value : "";
  }
}

async function loadOverview() {
  loading.value = true;
  errorMessage.value = null;

  try {
    const {
      items,
      stats,
      pagination: serverPagination,
    } = await fetchLecturerProjectsOverview({
      filters: filters.value,
      pagination,
    });

    summaries.value = items;
    overviewStats.value = stats;
    pagination.page = serverPagination.page;
    pagination.pageSize = serverPagination.pageSize;
    pagination.totalItems = serverPagination.totalItems;
  } catch (error) {
    console.error(error);
    errorMessage.value = "Không tải được dữ liệu. Vui lòng thử lại.";
  } finally {
    loading.value = false;
  }
}

function handleApplyFilter() {
  pagination.page = 1;
  loadOverview();
}

function handleChangePage(newPage: number) {
  if (newPage === pagination.page) return;
  pagination.page = newPage;
  loadOverview();
}

function handleViewLecturer(lecturerId: string) {
  console.log("View lecturer projects for", lecturerId);
}

onMounted(async () => {
  await loadOptions();
  await loadOverview();
});
</script>
