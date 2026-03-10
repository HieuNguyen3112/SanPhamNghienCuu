<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <PageHeader
            title="Quản lý tài khoản giảng viên"
            subtitle="Theo dõi và quản lý các tài khoản của giảng viên."
            :show-export-pdf="false"
            :show-export-excel="false"
            @exportPdfClicked="() => {}"
            @exportExcelClicked="() => {}"
          />

          <button
            v-if="isFacultyScope"
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
            @click="openCreate"
          >
            + Thêm giảng viên
          </button>
        </div>
      </div>

      <!-- Filter -->
      <LecturerAccountFilterBar
        :filter="filter"
        :unit-options="unitOptions"
        :role-options="roleOptions"
        :loading="loading"
        :result-count="resultCount"
        @update:filter="updateFilter"
        @search="search"
        @reset="onReset"
      />

      <!-- Table -->
      <LecturerAccountTable
        :rows="rows"
        :loading="loading"
        :error="error"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        :total-item-count="totalItems"
        :can-manage-roles="!isFacultyScope"
        @edit="openEdit"
        @roles="openRoles"
        @toggle-status="openDeactivate"
        @update:currentPageNumber="updatePage"
        @update:pageSize="updatePageSize"
      />

      <!-- Modals -->
      <CreateLecturerModal
        :open="createOpen"
        :saving="savingEdit"
        :error="savingError"
        @close="closeAllModals"
        @save="saveCreate"
      />

      <EditLecturerModal
        :open="editOpen"
        :account="selectedAccount"
        :unit-options="unitOptions"
        :saving="savingEdit"
        :error="savingError"
        @close="closeAllModals"
        @save="saveEdit"
      />

      <AssignRolesModal
        v-if="!isFacultyScope"
        :open="rolesOpen"
        :account="selectedAccount"
        :role-options="roleOptions"
        :saving="savingRoles"
        :error="savingError"
        @close="closeAllModals"
        @save="saveRoles"
      />

      <DeactivateAccountModal
        :open="deactivateOpen"
        :account="selectedAccount"
        :saving="savingDeactivate"
        :error="savingError"
        @close="closeAllModals"
        @confirm="confirmToggleStatus"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useUserStore } from "@/app/stores/userStore";
import LecturerAccountFilterBar from "../components/LecturerAccountFilterBar.vue";
import LecturerAccountTable from "../components/LecturerAccountTable.vue";
import CreateLecturerModal from "../components/CreateLecturerModal.vue";
import EditLecturerModal from "../components/EditLecturerModal.vue";
import AssignRolesModal from "../components/AssignRolesModal.vue";
import DeactivateAccountModal from "../components/DeactivateAccountModal.vue";
import { useLecturerAccountManagement } from "../composables/useLecturerAccountManagement";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

const userStore = useUserStore();
const scope = computed(() =>
  userStore.role === "DEPARTMENT_BOARD" ? "FACULTY" : "UNIVERSITY",
);
const isFacultyScope = computed(() => scope.value === "FACULTY");

const {
  filter,
  unitOptions,
  roleOptions,

  rows,
  loading,
  error,
  resultCount,
  currentPageNumber,
  pageSize,
  totalItems,

  createOpen,
  editOpen,
  rolesOpen,
  deactivateOpen,

  selectedAccount,

  savingEdit,
  savingRoles,
  savingDeactivate,
  savingError,

  updateFilter,
  resetFilter,
  search,
  updatePage,
  updatePageSize,

  openCreate,
  openEdit,
  openRoles,
  openDeactivate,
  closeAllModals,

  saveCreate,
  saveEdit,
  saveRoles,
  confirmToggleStatus,
} = useLecturerAccountManagement({ scope: scope.value });

function onReset() {
  resetFilter();
  // reset xong thì search luôn cho đúng UX
  void search({ resetPage: true });
}
</script>
