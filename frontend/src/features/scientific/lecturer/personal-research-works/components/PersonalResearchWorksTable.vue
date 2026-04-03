<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="p-6 text-sm text-slate-600">
      Đang tải danh sách...
    </div>

    <div v-else-if="error" class="p-6 text-sm text-rose-700">
      <div class="font-semibold">Không tải được danh sách</div>
      <div class="mt-1 opacity-90">{{ error }}</div>
    </div>

    <div v-else-if="rows.length === 0" class="p-6 text-sm text-slate-600">
      Không có công trình nào trong trạng thái này.
    </div>

    <div
      v-else
      class="personal-research-works-table-scroll max-h-[min(70vh,560px)] overflow-x-auto overflow-y-scroll overscroll-contain"
    >
      <table class="w-full min-w-[980px] text-left text-sm">
        <thead
          class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"
        >
          <tr>
            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">
              <button
                type="button"
                class="inline-flex items-center gap-2 hover:text-slate-900"
                :aria-sort="ariaSort('title')"
                @click="requestSort('title')"
                title="Sắp xếp theo tên"
              >
                Tên công trình
                <span class="text-xs text-slate-400">{{
                  sortIcon("title")
                }}</span>
              </button>
            </th>

            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">
              Loại công trình
            </th>

            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">
              <button
                type="button"
                class="inline-flex items-center gap-2 hover:text-slate-900"
                :aria-sort="ariaSort('roleName')"
                @click="requestSort('roleName')"
                title="Sắp xếp theo vai trò"
              >
                Vai trò
                <span class="text-xs text-slate-400">{{
                  sortIcon("roleName")
                }}</span>
              </button>
            </th>

            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">
              <button
                type="button"
                class="inline-flex items-center gap-2 hover:text-slate-900"
                :aria-sort="ariaSort('workYear')"
                @click="requestSort('workYear')"
                title="Sắp xếp theo năm"
              >
                Năm
                <span class="text-xs text-slate-400">{{
                  sortIcon("workYear")
                }}</span>
              </button>
            </th>

            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3 text-right">
              Giờ quy đổi
            </th>
            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">Trạng thái</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="row in rows"
            :key="row.activityId"
            class="cursor-pointer hover:bg-slate-50 focus-within:bg-slate-50"
            tabindex="0"
            @click="emit('open-detail', row.activityId)"
            @keydown.enter.prevent="emit('open-detail', row.activityId)"
            @keydown.space.prevent="emit('open-detail', row.activityId)"
          >
            <td class="px-4 py-3">
              <div
                class="max-w-[420px] truncate font-semibold text-slate-900 hover:underline"
              >
                {{ row.title }}
              </div>
              <div class="mt-1 text-xs text-slate-500">
                {{ row.activityCode }}
                <span class="px-1 text-slate-300">•</span>
                {{ row.academicYearCode }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">
              <div class="font-medium">{{ row.kindName }}</div>
              <div v-if="row.typeName" class="mt-0.5 text-xs text-slate-500">
                {{ row.typeName }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">{{ row.roleName ?? "—" }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.workYear ?? "—" }}</td>

            <td class="px-4 py-3 text-right tabular-nums text-slate-900">
              <span v-if="row.statusCode === 'approved'">{{
                row.lecturerHours ?? "—"
              }}</span>
              <span v-else class="text-slate-400">—</span>
            </td>

            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                :class="statusBadgeClass(row.statusCode)"
              >
                {{ row.statusName }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-200 px-4 py-3">
      <PaginationControl
        :total-item-count="totalItemCount"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        @update:currentPageNumber="(n) => emit('update:currentPageNumber', n)"
        @update:pageSize="(s) => emit('update:pageSize', s)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  PersonalWorkRow,
  PersonalWorkStatusCode,
} from "../contracts/personalResearchWorksContracts";

import PaginationControl from "@/shared/components/layout/SharedPaginationControls.vue";

type SortKey = "updatedAt" | "title" | "workYear" | "roleName";
type SortOrder = "asc" | "desc";
type AriaSort = "none" | "ascending" | "descending";

const props = defineProps<{
  rows: PersonalWorkRow[];
  totalItemCount: number;
  currentPageNumber: number;
  pageSize: number;
  loading: boolean;
  error: string | null;
  activeTab: string;
  sortKey?: SortKey;
  sortOrder?: SortOrder;
}>();

const emit = defineEmits<{
  (e: "open-detail", workId: number): void;
  (e: "update:currentPageNumber", value: number): void;
  (e: "update:pageSize", value: number): void;
  (e: "sort-change", sortKey: SortKey, sortOrder: SortOrder): void;
}>();

function requestSort(key: SortKey) {
  const currentKey: SortKey = props.sortKey ?? "updatedAt";
  const currentOrder: SortOrder = props.sortOrder ?? "desc";

  if (currentKey === key) {
    emit("sort-change", key, currentOrder === "asc" ? "desc" : "asc");
    return;
  }

  const defaultOrderByKey: Record<SortKey, SortOrder> = {
    updatedAt: "desc",
    title: "asc",
    roleName: "asc",
    workYear: "desc",
  };
  emit("sort-change", key, defaultOrderByKey[key]);
}

function sortIcon(key: SortKey): string {
  const currentKey: SortKey = props.sortKey ?? "updatedAt";
  const currentOrder: SortOrder = props.sortOrder ?? "desc";
  if (currentKey !== key) return "↕";
  return currentOrder === "asc" ? "↑" : "↓";
}

function ariaSort(key: SortKey): AriaSort {
  const currentKey: SortKey = props.sortKey ?? "updatedAt";
  const currentOrder: SortOrder = props.sortOrder ?? "desc";
  if (currentKey !== key) return "none";
  return currentOrder === "asc" ? "ascending" : "descending";
}

function statusBadgeClass(statusCode: PersonalWorkStatusCode): string {
  switch (statusCode) {
    case "approved":
      return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
    case "pending_member_confirm":
    case "pending_faculty_review":
      return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    case "need_revision":
      return "bg-sky-50 text-sky-700 ring-1 ring-sky-200";
    case "member_rejected":
    case "rejected":
      return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
    case "draft":
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
    default:
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
  }
}
</script>

<style scoped>
.personal-research-works-table-scroll {
  scrollbar-gutter: stable both-edges;
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 #e2e8f0;
}

.personal-research-works-table-scroll::-webkit-scrollbar {
  width: 12px;
  height: 12px;
}

.personal-research-works-table-scroll::-webkit-scrollbar-track {
  background: #e2e8f0;
  border-radius: 9999px;
}

.personal-research-works-table-scroll::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border: 2px solid #e2e8f0;
  border-radius: 9999px;
}

.personal-research-works-table-scroll::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
</style>
