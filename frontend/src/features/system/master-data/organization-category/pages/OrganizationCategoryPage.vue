<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Danh mục tổ chức"
          subtitle="Quản lý cơ cấu tổ chức phục vụ quản lý giảng viên và công trình NCKH."
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>
      <!-- Tabs card (pill style like screenshot) -->
      <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex gap-2 overflow-x-auto p-1">
          <button
            v-for="tab in tabList"
            :key="tab.key"
            type="button"
            class="inline-flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition"
            :class="tabButtonClass(tab.key)"
            :aria-current="activeTab === tab.key ? 'page' : undefined"
            @click="activeTab = tab.key"
          >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Error -->
      <div
        v-if="error"
        class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-rose-700"
      >
        <div class="text-sm font-semibold">Có lỗi xảy ra</div>
        <div class="mt-1 whitespace-pre-wrap text-xs">{{ error }}</div>
      </div>

      <!-- Tab content -->
      <FacultyTable
        v-if="activeTab === 'faculties'"
        :loading="loading"
        :items="faculties"
        :total-item-count="facultyTotalItemCount"
        :current-page-number="facultyCurrentPageNumber"
        :page-size="facultyPageSize"
        :allow-create="!isFacultyScope"
        :allow-edit="!isFacultyScope"
        v-model:search="facultySearch"
        @create="openCreateFaculty"
        @edit="openEditFaculty"
        @update:currentPageNumber="updateFacultyPage"
        @update:pageSize="updateFacultyPageSize"
      />

      <DepartmentTable
        v-else
        :loading="loading"
        :items="departments"
        :total-item-count="departmentTotalItemCount"
        :current-page-number="departmentCurrentPageNumber"
        :page-size="departmentPageSize"
        :faculty-options="facultyOptions"
        :allow-create="true"
        :allow-edit="true"
        :faculty-select-disabled="isFacultyScope"
        :show-all-faculty-option="!isFacultyScope"
        v-model:search="departmentSearch"
        v-model:faculty-id="departmentFacultyId"
        @create="openCreateDepartment"
        @edit="openEditDepartment"
        @update:currentPageNumber="updateDepartmentPage"
        @update:pageSize="updateDepartmentPageSize"
      />

      <!-- Modals -->
      <FacultyForm
        v-if="!isFacultyScope"
        :open="facultyFormOpen"
        :loading="loading"
        :editing="editingFaculty"
        @close="closeFacultyForm"
        @submit="saveFaculty"
      />

      <DepartmentForm
        :open="departmentFormOpen"
        :loading="loading"
        :editing="editingDepartment"
        :faculty-options="facultyOptions"
        @close="closeDepartmentForm"
        @submit="saveDepartment"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { University, Building2 } from "lucide-vue-next";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import { useUserStore } from "@/app/stores/userStore";

import FacultyTable from "../components/FacultyTable.vue";
import FacultyForm from "../components/FacultyForm.vue";
import DepartmentTable from "../components/DepartmentTable.vue";
import DepartmentForm from "../components/DepartmentForm.vue";
import { useOrganizationCategory } from "../composables/useOrganizationCategory";
import type { TabKey } from "../contracts/organizationCategory.contract";

const userStore = useUserStore();
const scope = userStore.role === "DEPARTMENT_BOARD" ? "FACULTY" : "UNIVERSITY";
const isFacultyScope = computed(() => scope === "FACULTY");

const {
  activeTab,
  loading,
  error,

  faculties,
  facultyOptions,
  departments,

  facultySearch,
  facultyPageSize,
  facultyCurrentPageNumber,
  facultyTotalItemCount,

  departmentSearch,
  departmentFacultyId,
  departmentPageSize,
  departmentCurrentPageNumber,
  departmentTotalItemCount,

  facultyFormOpen,
  editingFaculty,
  openCreateFaculty,
  openEditFaculty,
  closeFacultyForm,
  saveFaculty,

  departmentFormOpen,
  editingDepartment,
  openCreateDepartment,
  openEditDepartment,
  closeDepartmentForm,
  saveDepartment,

  refreshAll,

  updateFacultyPage,
  updateFacultyPageSize,
  updateDepartmentPage,
  updateDepartmentPageSize,
} = useOrganizationCategory(scope);

const tabList = [
  { key: "faculties" as const, label: "Khoa", icon: University },
  {
    key: "departments" as const,
    label: "Đơn vị trực thuộc",
    icon: Building2,
  },
];

function tabButtonClass(key: TabKey) {
  return key === activeTab.value
    ? "border-slate-900 bg-slate-900 text-white"
    : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50";
}

onMounted(() => {
  refreshAll();
});
</script>
