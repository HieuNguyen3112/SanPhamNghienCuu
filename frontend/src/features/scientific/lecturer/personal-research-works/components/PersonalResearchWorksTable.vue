<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="p-6 text-sm text-slate-600">Đang tải danh sách...</div>

    <div v-else-if="error" class="p-6 text-sm text-rose-700">
      <div class="font-semibold">Không tải được danh sách</div>
      <div class="mt-1 opacity-90">{{ error }}</div>
    </div>

    <div v-else-if="rows.length === 0" class="p-6 text-sm text-slate-600">
      Không có công trình nào trong trạng thái này.
    </div>

    <div v-else class="max-h-[560px] overflow-auto">
      <table class="w-full min-w-[980px] text-left text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600">
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
                <span class="text-xs text-slate-400">{{ sortIcon("title") }}</span>
              </button>
            </th>

            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">Loại công trình</th>

            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">
              <button
                type="button"
                class="inline-flex items-center gap-2 hover:text-slate-900"
                :aria-sort="ariaSort('roleName')"
                @click="requestSort('roleName')"
                title="Sắp xếp theo vai trò"
              >
                Vai trò
                <span class="text-xs text-slate-400">{{ sortIcon("roleName") }}</span>
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
                <span class="text-xs text-slate-400">{{ sortIcon("workYear") }}</span>
              </button>
            </th>

            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3 text-right">Giờ quy đổi</th>
            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">Trạng thái</th>
            <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3 text-right">Hành động</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in rows" :key="row.activityId" class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <button type="button" class="text-left" @click="emit('open-detail', row.activityId)">
                <div class="max-w-[420px] truncate font-semibold text-slate-900 hover:underline">
                  {{ row.title }}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ row.activityCode }}
                  <span class="px-1 text-slate-300">•</span>
                  {{ row.academicYearCode }}
                </div>
              </button>
            </td>

            <td class="px-4 py-3 text-slate-700">
              <div class="font-medium">{{ row.kindName }}</div>
              <div v-if="row.typeName" class="mt-0.5 text-xs text-slate-500">{{ row.typeName }}</div>
            </td>

            <td class="px-4 py-3 text-slate-700">{{ row.roleName ?? "—" }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.workYear ?? "—" }}</td>

            <td class="px-4 py-3 text-right tabular-nums text-slate-900">
              <span v-if="row.statusCode === 'approved'">{{ row.lecturerHours ?? "—" }}</span>
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

            <td class="px-4 py-3">
              <div class="flex justify-end">
                <WorkRowActionsMenu
                  :open="openMenuWorkId === row.activityId"
                  :activity-id="row.activityId"
                  :status-code="row.statusCode"
                  :actions="row.actions"
                  @toggle="(nextOpen) => setMenuOpen(row.activityId, nextOpen)"
                  @close="closeMenu"
                  @view="(id) => emit('open-detail', id)"
                  @edit-draft="(id) => emit('edit-draft', id)"
                  @copy-rejected="(id) => emit('copy-rejected', id)"
                  @reinvite="(id) => emit('reinvite', id)"
                />
              </div>
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
import { ref, watch } from "vue";
import type {
  PersonalWorkRow,
  PersonalWorkStatusCode,
} from "../contracts/personalResearchWorksContracts";

import PaginationControl from "@/shared/components/layout/SharedPaginationControls.vue";
import WorkRowActionsMenu from "./WorkRowActionsMenu.vue";

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
  (e: "edit-draft", workId: number): void;
  (e: "copy-rejected", workId: number): void;
  (e: "reinvite", workId: number): void;
  (e: "update:currentPageNumber", value: number): void;
  (e: "update:pageSize", value: number): void;
  (e: "sort-change", sortKey: SortKey, sortOrder: SortOrder): void;
}>();

const openMenuWorkId = ref<number | null>(null);

function setMenuOpen(workId: number, nextOpen: boolean) {
  openMenuWorkId.value = nextOpen ? workId : null;
}

function closeMenu() {
  openMenuWorkId.value = null;
}

watch(
  () => [
    props.activeTab,
    props.currentPageNumber,
    props.pageSize,
    props.rows.length,
    props.sortKey,
    props.sortOrder,
  ],
  () => {
    openMenuWorkId.value = null;
  }
);

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
    case "submitted":
      return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
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
