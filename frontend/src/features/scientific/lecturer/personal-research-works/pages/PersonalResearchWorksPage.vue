<template>
  <div class="space-y-4 p-4 md:p-6">
    <div
      class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
    >
      <PageHeader
        title="Quản lý công trình khoa học cá nhân"
        subtitle="Theo dõi và kê khai các công trình NCKH của bạn"
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

    <div
      v-if="stats.rejectedCount > 0 || filterTab === 'rejected'"
      class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900"
    >
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <div class="font-semibold text-sky-950">
            Công trình bị khoa trả về được xử lý trong mục này
          </div>
          <p class="mt-1 leading-6">
            Thông báo workflow ở biểu tượng chuông chỉ giúp điều hướng. Chủ nhiệm và các thành viên đã chấp nhận
            tham gia đều xem lý do từ chối và mở lại công trình tại tab
            <span class="font-semibold">Bị từ chối</span>.
          </p>
        </div>
        <button
          v-if="filterTab !== 'rejected'"
          type="button"
          class="inline-flex items-center justify-center rounded-xl border border-sky-300 bg-white px-3 py-2 font-semibold text-sky-900 transition hover:border-sky-400 hover:bg-sky-100"
          @click="selectCard('rejected')"
        >
          Xem công trình bị từ chối ({{ stats.rejectedCount }})
        </button>
      </div>
    </div>

    <div
      v-if="noticeMessage"
      :class="[
        'rounded-2xl border px-4 py-3 text-sm',
        noticeTone === 'success'
          ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
          : noticeTone === 'error'
          ? 'border-rose-200 bg-rose-50 text-rose-700'
          : 'border-blue-200 bg-blue-50 text-blue-700',
      ]"
    >
      {{ noticeMessage }}
    </div>

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
    />

    <WorkDetailDrawer
      :open="isDetailOpen"
      :loading="loadingDetail"
      :error="errorDetail"
      :work="selectedWorkDetail"
      @close="closeDetail"
      @edit-draft="goToEditDraft"
      @reinvite-member="reinviteMember"
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
  noticeMessage,
  noticeTone,

  bootstrap,

  selectCard,
  openDetail,
  closeDetail,

  goToEditDraft,
  reinviteMember,

  currentPageNumber,
  pageSize,
  sortKey,
  sortOrder,
  handleSortChange,
  setPage,
  setPageSize,
} = usePersonalResearchWorks();

onMounted(async () => {
  await bootstrap();
});
</script>
