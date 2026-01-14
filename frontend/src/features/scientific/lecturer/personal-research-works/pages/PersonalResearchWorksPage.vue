<template>
  <div class="space-y-4 p-4 md:p-6">
    <div
      class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
    >
      <PageHeader
        title="Quản lý công trình khoa học cá nhân"
        subtitle="Theo dõi & kê khai các công trình NCKH của bạn"
        :show-export-pdf="false"
        :show-export-excel="false"
        @exportPdfClicked="() => {}"
        @exportExcelClicked="() => {}"
      />
    </div>

    <PersonalResearchWorksStatsCards
      :stats="stats"
      :active-tab="filterTab"
      @select="selectCard"
    />

    <PersonalResearchWorksTable
      :rows="rows"
      :total-item-count="totalItemCount"
      :current-page-number="currentPageNumber"
      :page-size="pageSize"
      @update:currentPageNumber="setPage"
      @update:pageSize="setPageSize"
      :loading="loadingList"
      :error="errorList"
      :active-tab="filterTab"
      :sort-key="sortKey"
      :sort-order="sortOrder"
      @sort-change="handleSortChange"
      @open-detail="openDetail"
      @edit-draft="goToEditDraft"
      @copy-rejected="copyFromRejected"
    />

    <WorkDetailDrawer
      :open="isDetailOpen"
      :loading="loadingDetail"
      :error="errorDetail"
      :work="selectedWorkDetail"
      @close="closeDetail"
      @edit-draft="goToEditDraft"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import PersonalResearchWorksStatsCards from "../components/PersonalResearchWorksStatsCards.vue";
import PersonalResearchWorksTable from "../components/PersonalResearchWorksTable.vue";
import WorkDetailDrawer from "../components/WorkDetailDrawer.vue";
import { usePersonalResearchWorks } from "../composables/usePersonalResearchWorks";

const {
  stats,
  filterTab,
  rows,
  totalItemCount,

  isDetailOpen,
  selectedWorkDetail,

  loadingList,
  errorList,
  loadingDetail,
  errorDetail,

  loadWorks,

  selectCard,
  openDetail,
  closeDetail,

  goToEditDraft,
  copyFromRejected,

  currentPageNumber,
  pageSize,
  sortKey,
  sortOrder,
  handleSortChange,
  setPage,
  setPageSize,
} = usePersonalResearchWorks();

onMounted(async () => {
  await loadWorks();
});
</script>
