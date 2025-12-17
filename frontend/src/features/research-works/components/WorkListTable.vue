<!-- src/features/works/components/WorkListTable.vue -->
<template>
  <div
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
  >
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr>
          <th
            class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Tên công trình
          </th>
          <th
            class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Loại
          </th>
          <th
            class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Thời gian
          </th>
          <th
            class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Vai trò
          </th>
          <th
            class="px-4 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Trạng thái
          </th>
          <th
            class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Giờ NCKH
          </th>
        </tr>
      </thead>

      <tbody class="divide-y divide-slate-100">
        <tr v-if="loading">
          <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
            Đang tải dữ liệu công trình...
          </td>
        </tr>

        <tr v-else-if="!items.length">
          <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
            Chưa có công trình nào phù hợp với bộ lọc hiện tại.
          </td>
        </tr>

        <tr
          v-for="work in items"
          v-else
          :key="work.id"
          class="hover:bg-slate-50"
        >
          <td class="px-4 py-2 align-top">
            <p class="text-sm font-medium text-slate-800">
              {{ work.title }}
            </p>
            <p class="mt-0.5 text-xs text-slate-400">Mã: {{ work.id }}</p>
          </td>

          <td class="px-4 py-2 align-top text-sm text-slate-700">
            {{ workTypeLabel(work.workType) }}
          </td>

          <td class="px-4 py-2 align-top text-sm text-slate-700">
            <span v-if="work.startYear && work.endYear">
              {{ work.startYear }} – {{ work.endYear }}
            </span>
            <span v-else-if="work.startYear"> Từ {{ work.startYear }} </span>
            <span v-else class="text-slate-400">—</span>
          </td>

          <td class="px-4 py-2 align-top text-sm text-slate-700">
            {{ roleLabel(work.role) }}
          </td>

          <td class="px-4 py-2 align-top">
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="statusClass(work.status)"
            >
              {{ statusLabel(work.status) }}
            </span>
          </td>

          <td class="px-4 py-2 align-top text-right text-sm text-slate-700">
            <span v-if="work.totalHours != null">
              {{ work.totalHours }}
            </span>
            <span v-else class="text-slate-400">—</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import type { WorkItem, WorkStatus } from "../types";
import type { WorkType } from "@/features/declarations/types";

interface Props {
  items: WorkItem[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
});

function workTypeLabel(type: WorkType): string {
  switch (type) {
    case "ARTICLE":
      return "Bài báo";
    case "PROJECT":
      return "Đề tài";
    case "BOOK":
      return "Sách / Giáo trình";
    case "OTHER":
      return "Khác";
    default:
      return String(type);
  }
}

function roleLabel(role: WorkItem["role"]): string {
  switch (role) {
    case "MAIN":
      return "Chủ trì / Tác giả chính";
    case "CO":
      return "Thành viên / Đồng tác giả";
    default:
      return "";
  }
}

function statusLabel(status: WorkStatus): string {
  switch (status) {
    case "ONGOING":
      return "Đang thực hiện";
    case "COMPLETED":
      return "Đã hoàn thành";
    case "PENDING_APPROVAL":
      return "Chờ duyệt";
    case "DRAFT":
      return "Bản nháp";
    default:
      return String(status);
  }
}

function statusClass(status: WorkStatus): string {
  switch (status) {
    case "ONGOING":
      return "bg-sky-50 text-sky-700 border border-sky-100";
    case "COMPLETED":
      return "bg-emerald-50 text-emerald-700 border border-emerald-100";
    case "PENDING_APPROVAL":
      return "bg-amber-50 text-amber-700 border border-amber-100";
    case "DRAFT":
      return "bg-slate-50 text-slate-600 border border-slate-100";
    default:
      return "bg-slate-50 text-slate-600 border border-slate-100";
  }
}
</script>
