<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <!-- Filter -->
      <GlobalResearchWorkFilterBar
        :filter="filterDraft"
        :loading="loadingList"
        :faculty-options="facultyOptions"
        :work-type-options="workTypeOptions"
        :author-role-options="authorRoleOptions"
        :status-options="statusOptions"
        :management-level-options="managementLevelOptions"
        :academic-year-options="years"
        :lecturer-suggestions="lecturerSuggestions"
        :result-count-text="resultCountText"
        @update:filter="onUpdateFilter"
        @search="search"
        @reset="reset"
      />

      <!-- Table -->
      <GlobalResearchWorkTable
        :rows="rows"
        :loading="loadingList"
        :error="errorList"
        :pagination="pagination"
        @open-detail="openDetail"
        @update:page="onPageChange"
        @update:pageSize="onPageSizeChange"
      />

      <!-- Detail Panel -->
      <GlobalResearchWorkDetailPanel
        :open="detailOpen"
        :detail="detail"
        :loading="loadingDetail"
        :error="errorDetail"
        @close="closeDetail"
        @download="downloadAttachment"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import GlobalResearchWorkFilterBar from "../components/GlobalResearchWorkFilterBar.vue";
import GlobalResearchWorkTable from "../components/GlobalResearchWorkTable.vue";
import GlobalResearchWorkDetailPanel from "../components/GlobalResearchWorkDetailPanel.vue";
import { useGlobalResearchWorkSearch } from "../composables/useGlobalResearchWorkSearch";
import type { GlobalResearchWorkSearchFilter } from "../contracts/globalResearchWorkSearch.contract";

const {
  facultyOptions,
  workTypeOptions,
  authorRoleOptions,
  statusOptions,
  managementLevelOptions,
  years,
  lecturerSuggestions,

  filterDraft,
  rows,
  loadingList,
  errorList,
  pagination,
  resultCountText,

  detailOpen,
  detail,
  loadingDetail,
  errorDetail,

  loadLookups,
  search,
  reset,
  fetchList,
  openDetail,
  closeDetail,
  downloadAttachment,
} = useGlobalResearchWorkSearch();

function onUpdateFilter(partial: Partial<GlobalResearchWorkSearchFilter>) {
  filterDraft.value = { ...filterDraft.value, ...partial };
}

onMounted(() => {
  void (async () => {
    await loadLookups();
    await search();
  })();
});

function onPageChange(page: number) {
  void fetchList(page);
}

function onPageSizeChange(pageSize: number) {
  void fetchList(1, pageSize);
}
</script>
