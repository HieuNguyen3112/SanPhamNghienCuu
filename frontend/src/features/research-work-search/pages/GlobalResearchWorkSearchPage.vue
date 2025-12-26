<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <!-- Filter -->
      <GlobalResearchWorkFilterBar
        :filter="filterDraft"
        :loading="loadingList"
        :faculty-options="facultyOptions"
        :year-options="years"
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
        @open-detail="openDetail"
      />

      <!-- Detail Panel -->
      <GlobalResearchWorkDetailPanel
        :open="detailOpen"
        :detail="detail"
        :loading="loadingDetail"
        :error="errorDetail"
        @close="closeDetail"
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
  lecturerSuggestions,
  years,

  filterDraft,
  rows,
  loadingList,
  errorList,
  resultCountText,

  detailOpen,
  detail,
  loadingDetail,
  errorDetail,

  search,
  reset,
  openDetail,
  closeDetail,
} = useGlobalResearchWorkSearch();

function onUpdateFilter(partial: Partial<GlobalResearchWorkSearchFilter>) {
  filterDraft.value = { ...filterDraft.value, ...partial };
}

onMounted(() => {
  // load initial with default filter (approved)
  void search();
});
</script>
