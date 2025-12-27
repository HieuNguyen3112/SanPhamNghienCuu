<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <AuditLogFilterBar
        v-model="draftFilters"
        :actors="actors"
        :faculties="faculties"
        :group-options="groupOptions"
        :show-faculty-filter="false"
        :is-loading="isLoading"
        :date-range-invalid="dateRangeInvalid"
        @apply="applyFilters"
        @reset="resetFilters"
      />

      <AuditLogTable
        :entries="pagedEntries"
        :is-loading="isLoading"
        :error="error"
        :page="page"
        :page-size="pageSize"
        :total-items="totalItems"
        @select="openDetail"
        @change-page="setPage"
        @change-page-size="setPageSize"
      />

      <AuditLogDetailDrawer
        :open="!!selectedEntry"
        :entry="selectedEntry"
        @close="closeDetail"
        @view-target="onViewTarget"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import AuditLogFilterBar from "../components/AuditLogFilterBar.vue";
import AuditLogTable from "../components/AuditLogTable.vue";
import AuditLogDetailDrawer from "../components/AuditLogDetailDrawer.vue";
import { useAuditLog } from "../composables/useAuditLog";
import type { AuditTarget } from "../contracts/audit-log.contract";
import {
  AUDIT_GROUP_LABELS,
  FACULTY_ALLOWED_GROUPS,
} from "../contracts/audit-log.contract";

const router = useRouter();

const {
  isLoading,
  error,
  actors,
  faculties,
  draftFilters,
  dateRangeInvalid,
  page,
  pageSize,
  totalItems,
  pagedEntries,
  selectedEntry,
  applyFilters,
  resetFilters,
  openDetail,
  closeDetail,
  setPage,
  setPageSize,
} = useAuditLog("FACULTY");

const groupOptions = computed(() =>
  FACULTY_ALLOWED_GROUPS.map((g) => ({
    value: g,
    label: AUDIT_GROUP_LABELS[g],
  }))
);

function onViewTarget(target: AuditTarget) {
  if (!target.routeName) return;
  try {
    router.push({ name: target.routeName, params: target.routeParams ?? {} });
  } catch {
    // route may not exist in current build
  }
}
</script>
