<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Quản lý tài khoản giảng viên "
          subtitle="Theo dõi và quản lý các tài khoản của giảng viên "
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <!-- Toast -->
      <div
        v-if="toastMessage"
        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
      >
        {{ toastMessage }}
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
        @edit="openEdit"
        @roles="openRoles"
        @toggle-status="openDeactivate"
      />

      <!-- Modals -->
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
import EditLecturerModal from "../components/EditLecturerModal.vue";
import AssignRolesModal from "../components/AssignRolesModal.vue";
import DeactivateAccountModal from "../components/DeactivateAccountModal.vue";
import { useLecturerAccountManagement } from "../composables/useLecturerAccountManagement";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

const {
  filter,
  unitOptions,
  roleOptions,

  rows,
  loading,
  error,
  resultCount,

  toastMessage,

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

  openEdit,
  openRoles,
  openDeactivate,
  closeAllModals,

  saveEdit,
  saveRoles,
  confirmToggleStatus,
} = useLecturerAccountManagement();

function onReset() {
  resetFilter();
  // reset xong thì search luôn cho đúng UX
  void search();
}
</script>
