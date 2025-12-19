<!-- src/features/works/pages/MyWorksView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">Công trình của tôi</h1>
      <p class="mt-1 text-sm text-slate-500">
        Danh sách công trình khoa học mà giảng viên đã và đang thực hiện, được
        tổng hợp từ các kê khai và hệ thống.
      </p>
    </div>

    <!-- Bộ lọc -->
    <div
      class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-end md:justify-between"
    >
      <div class="flex flex-wrap gap-2">
        <button
          v-for="option in statusOptions"
          :key="option.value"
          type="button"
          class="rounded-full px-3 py-1 text-xs font-medium"
          :class="
            statusFilter === option.value
              ? 'bg-sky-600 text-white'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          "
          @click="onChangeStatus(option.value)"
        >
          {{ option.label }}
        </button>
      </div>

      <div class="flex flex-wrap gap-3">
        <div class="w-full md:w-48">
          <label class="block text-xs font-medium text-slate-600">
            Loại công trình
          </label>
          <select
            v-model="workTypeFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="ARTICLE">Bài báo</option>
            <option value="PROJECT">Đề tài</option>
            <option value="BOOK">Sách / Giáo trình</option>
            <option value="OTHER">Khác</option>
          </select>
        </div>

        <div class="w-full md:w-64">
          <label class="block text-xs font-medium text-slate-600">
            Tìm kiếm
          </label>
          <input
            v-model="search"
            type="text"
            placeholder="Nhập tên công trình..."
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            @keyup.enter="loadData"
          />
        </div>

        <button
          type="button"
          class="mt-5 inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
          @click="loadData"
        >
          Lọc
        </button>
      </div>
    </div>

    <!-- Bảng công trình -->
    <WorkListTable :items="items" :loading="loading" />

    <!-- Thông tin phân trang đơn giản -->
    <div
      v-if="!loading && totalItems > 0"
      class="flex items-center justify-between text-xs text-slate-500"
    >
      <p>
        Hiển thị
        <span class="font-medium">
          {{ (page - 1) * pageSize + 1 }} –
          {{ Math.min(page * pageSize, totalItems) }}
        </span>
        trên tổng số
        <span class="font-medium">{{ totalItems }}</span> công trình
      </p>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-md border border-slate-200 px-2 py-1 text-xs hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="page <= 1"
          @click="changePage(page - 1)"
        >
          Trước
        </button>
        <span>
          Trang
          <span class="font-medium">{{ page }}</span>
        </span>
        <button
          type="button"
          class="rounded-md border border-slate-200 px-2 py-1 text-xs hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="page * pageSize >= totalItems"
          @click="changePage(page + 1)"
        >
          Sau
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import WorkListTable from "@/features/scientific/research-works/lecturer/components/LecturerResearchWorkListTable.vue";
import { fetchMyWorks } from "../../api";
import type { WorkStatus, WorkItem } from "../../types";
import type { WorkType } from "@/features/declarations/types";

type StatusFilter = WorkStatus | "ALL";
type WorkTypeFilter = WorkType | "ALL";

const statusOptions: { value: StatusFilter; label: string }[] = [
  { value: "ALL", label: "Tất cả" },
  { value: "ONGOING", label: "Đang thực hiện" },
  { value: "COMPLETED", label: "Đã hoàn thành" },
  { value: "PENDING_APPROVAL", label: "Chờ duyệt" },
  { value: "DRAFT", label: "Bản nháp" },
];

const loading = ref(false);
const items = ref<WorkItem[]>([]);
const statusFilter = ref<StatusFilter>("ALL");
const workTypeFilter = ref<WorkTypeFilter>("ALL");
const search = ref("");

const page = ref(1);
const pageSize = ref(10);
const totalItems = ref(0);

async function loadData() {
  loading.value = true;
  try {
    const res = await fetchMyWorks({
      status: statusFilter.value,
      workType: workTypeFilter.value,
      search: search.value || undefined,
      page: page.value,
      pageSize: pageSize.value,
    });

    items.value = res.items;
    totalItems.value = res.totalItems;
  } finally {
    loading.value = false;
  }
}

function onChangeStatus(value: StatusFilter) {
  statusFilter.value = value;
  page.value = 1;
  loadData();
}

function changePage(newPage: number) {
  page.value = newPage;
  loadData();
}

onMounted(() => {
  loadData();
});
</script>
