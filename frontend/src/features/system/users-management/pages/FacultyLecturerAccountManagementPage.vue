<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto min-w-0 w-full space-y-4 p-4 md:p-6">
      <!-- Header -->
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <div
          class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
        >
          <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-slate-900">
              Quản lý tài khoản giảng viên (BCN Khoa)
            </div>
            <div class="mt-1 text-sm text-slate-600">
              Phạm vi: giảng viên thuộc khoa của bạn. Bổ nhiệm vai trò BCN khoa
              thực hiện ở cấp trường (Phòng KHCN).
            </div>
          </div>

          <button
            type="button"
            class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 sm:w-auto"
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
        unit-label="Đơn vị"
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
        unit-label="Đơn vị"
        :can-manage-roles="false"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        :total-item-count="totalItems"
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
        :unit-options="unitOptions"
        organization-label="Đơn vị"
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
import LecturerAccountFilterBar from "../components/LecturerAccountFilterBar.vue";
import LecturerAccountTable from "../components/LecturerAccountTable.vue";
import CreateLecturerModal from "../components/CreateLecturerModal.vue";
import EditLecturerModal from "../components/EditLecturerModal.vue";
import AssignRolesModal from "../components/AssignRolesModal.vue";
import DeactivateAccountModal from "../components/DeactivateAccountModal.vue";
import { useLecturerAccountManagement } from "../composables/useLecturerAccountManagement";

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
} = useLecturerAccountManagement({
  scope: "FACULTY",
  // TODO: lấy faculty_unit_id từ auth context (/me)
  faculty_unit_id: 1,
});

function onReset() {
  resetFilter();
  void search();
}
</script>
