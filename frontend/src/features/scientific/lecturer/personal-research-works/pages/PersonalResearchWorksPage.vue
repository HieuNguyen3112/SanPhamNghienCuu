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
      :rows="currentPageRows"
      :total-item-count="sortedRows.length"
      v-model:current-page-number="currentPageNumber"
      v-model:page-size="pageSize"
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
import { computed, onMounted, ref, watch } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import PersonalResearchWorksStatsCards from "../components/PersonalResearchWorksStatsCards.vue";
import PersonalResearchWorksTable from "../components/PersonalResearchWorksTable.vue";
import WorkDetailDrawer from "../components/WorkDetailDrawer.vue";
import { usePersonalResearchWorks } from "../composables/usePersonalResearchWorks";

type SortKey = "updatedAt" | "title" | "workYear" | "roleName";
type SortOrder = "asc" | "desc";

const {
  stats,
  filterTab,
  rows,

  isDetailOpen,
  selectedWorkDetail,

  loadingList,
  errorList,
  loadingDetail,
  errorDetail,

  loadStats,
  loadWorks,

  // nếu chưa dùng tabs thì bỏ changeTab khỏi destructuring để khỏi warning
  changeTab,
  selectCard,
  openDetail,
  closeDetail,

  goToEditDraft,
  copyFromRejected,

  // nếu chưa dùng nút "kê khai mới" thì bỏ createNewDraft khỏi destructuring để khỏi warning
  createNewDraft,
} = usePersonalResearchWorks();

/** ✅ Sort state nằm ở PAGE */
const sortKey = ref<SortKey>("updatedAt");
const sortOrder = ref<SortOrder>("desc");

function handleSortChange(nextKey: SortKey, nextOrder: SortOrder) {
  sortKey.value = nextKey;
  sortOrder.value = nextOrder;
}

/** ✅ rows từ composable = list đã filter theo tab hiện tại */
const sortedRows = computed(() => {
  const direction = sortOrder.value === "asc" ? 1 : -1;

  return [...rows.value].sort((a, b) => {
    if (sortKey.value === "title") {
      return a.title.localeCompare(b.title) * direction;
    }

    if (sortKey.value === "workYear") {
      const av = a.workYear ?? -Infinity;
      const bv = b.workYear ?? -Infinity;
      return (av - bv) * direction;
    }

    if (sortKey.value === "roleName") {
      const ar = (a.roleName ?? "").toLowerCase();
      const br = (b.roleName ?? "").toLowerCase();
      return ar.localeCompare(br) * direction;
    }

    // updatedAt
    const at = new Date(a.updatedAt).getTime();
    const bt = new Date(b.updatedAt).getTime();
    if (Number.isNaN(at) || Number.isNaN(bt)) {
      return a.updatedAt.localeCompare(b.updatedAt) * direction;
    }
    return (at - bt) * direction;
  });
});

/**
 * ✅ Pagination state nằm ở PAGE
 * Sau khi sort/filter xong mới slice.
 */
const currentPageNumber = ref(1); // 1-based
const pageSize = ref(12);

const currentPageRows = computed(() => {
  const startIndex = (currentPageNumber.value - 1) * pageSize.value;
  return sortedRows.value.slice(startIndex, startIndex + pageSize.value);
});

// Khi đổi tab / đổi pageSize / đổi sort => về trang 1
watch(
  () => [
    filterTab.value,
    pageSize.value,
    sortKey.value,
    sortOrder.value,
    sortedRows.value.length,
  ],
  () => {
    currentPageNumber.value = 1;
  }
);

// Clamp currentPageNumber nếu total page giảm
watch(
  () => sortedRows.value.length,
  () => {
    const totalPages = Math.max(
      1,
      Math.ceil(sortedRows.value.length / pageSize.value)
    );
    if (currentPageNumber.value > totalPages)
      currentPageNumber.value = totalPages;
  }
);

// Nếu bạn chưa dùng changeTab / createNewDraft mà muốn hết warning TS:
void changeTab;
void createNewDraft;

onMounted(async () => {
  await Promise.all([loadStats(), loadWorks()]);
});
</script>
