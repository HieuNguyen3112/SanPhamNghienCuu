// File:
src/features/declaration/participation-notifications/components/ParticipationNotificationTable.vue
<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <!-- Top meta -->
    <div
      class="mb-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm text-slate-600">
        Hiển thị
        <span class="font-medium text-slate-900">{{ pagedRows.length }}</span>
        /
        <span class="font-medium text-slate-900">{{ totalItemCount }}</span>
        yêu cầu
      </div>

      <div class="text-sm text-slate-600">
        Trang
        <span class="font-medium text-slate-900">{{ currentPageNumber }}</span>
        /
        <span class="font-medium text-slate-900">{{ totalPages }}</span>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200">
      <div class="max-w-full overflow-x-auto">
        <table class="min-w-[980px] w-full text-left text-sm">
          <thead class="sticky top-0 bg-slate-50 text-xs text-slate-600">
            <tr>
              <th class="px-3 py-2 font-semibold">Công trình</th>
              <th class="px-3 py-2 font-semibold">Loại</th>
              <th class="px-3 py-2 font-semibold">Vai trò của bạn</th>
              <th class="px-3 py-2 font-semibold">Người kê khai</th>
              <th class="px-3 py-2 font-semibold">Ngày yêu cầu</th>
              <th class="px-3 py-2 font-semibold">Trạng thái</th>
              <th class="px-3 py-2 font-semibold text-right">Hành động</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200">
            <tr v-if="loading">
              <td class="px-3 py-6 text-center text-slate-500" colspan="7">
                Đang tải dữ liệu...
              </td>
            </tr>

            <tr
              v-for="row in pagedRows"
              :key="row.id"
              class="cursor-pointer hover:bg-slate-50"
              @click="emit('row-click', row.id)"
            >
              <td class="px-3 py-2">
                <div class="min-w-0">
                  <div class="truncate text-sm font-semibold text-slate-900">
                    {{ row.workTitle }}
                  </div>
                  <div class="mt-0.5 truncate text-xs text-slate-500">
                    {{ row.workShortInfo }}
                  </div>
                </div>
              </td>

              <td class="px-3 py-2">
                <span
                  class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                >
                  {{ workTypeLabel(row.workType) }}
                </span>
              </td>

              <td class="px-3 py-2 text-sm text-slate-700">
                {{ row.yourRole }}
              </td>

              <td class="px-3 py-2 text-sm text-slate-700">
                {{ row.ownerName }}
              </td>

              <td class="px-3 py-2 text-sm text-slate-700">
                {{ formatDateTime(row.requestedAt) }}
              </td>

              <td class="px-3 py-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="statusBadgeClass(row.status)"
                >
                  {{ statusLabel(row.status) }}
                </span>
              </td>

              <td class="px-3 py-2 text-right">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                  title="Xem"
                  @click.stop="emit('row-click', row.id)"
                >
                  <Eye class="h-4 w-4" />
                </button>
              </td>
            </tr>

            <tr v-if="!loading && pagedRows.length === 0">
              <td class="px-3 py-8 text-center text-slate-500" colspan="7">
                Hiện tại bạn không có yêu cầu xác nhận nào.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination (SharedPaginationControls đặt trong table) -->
    <div class="mt-3 flex justify-end">
      <SharedPaginationControls
        :total-item-count="totalItemCount"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        @update:currentPageNumber="emit('update:currentPageNumber', $event)"
        @update:pageSize="emit('update:pageSize', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from "vue";
import { Eye } from "lucide-vue-next";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import { formatBackendDateTimeVi } from "@/shared/utils/backendDateTime";

type WorkType = "ARTICLE" | "PROJECT" | "BOOK" | "CONFERENCE";
type NotificationStatus = "PENDING" | "ACCEPTED" | "REJECTED";

export interface ParticipationNotificationRow {
  id: number;
  workTitle: string;
  workType: WorkType;
  yourRole: string;
  ownerName: string;
  requestedAt: string; // ISO
  status: NotificationStatus;
  workShortInfo: string;
}

const props = defineProps<{
  rows: ParticipationNotificationRow[];
  loading: boolean;

  currentPageNumber: number;
  pageSize: number;
  totalItemCount: number;
  totalPages: number;
}>();

const emit = defineEmits<{
  (e: "row-click", rowId: number): void;
  (e: "update:currentPageNumber", value: number): void;
  (e: "update:pageSize", value: number): void;
}>();

const totalPages = computed(() => {
  return Math.max(1, props.totalPages || 1);
});

const pagedRows = computed(() => {
  return props.rows;
});

watch(
  () => [props.totalItemCount, props.pageSize, props.currentPageNumber],
  () => {
    if (props.currentPageNumber > totalPages.value) {
      emit("update:currentPageNumber", totalPages.value);
    }
    if (props.currentPageNumber < 1) {
      emit("update:currentPageNumber", 1);
    }
  }
);

function workTypeLabel(t: WorkType) {
  if (t === "ARTICLE") return "Bài báo";
  if (t === "PROJECT") return "Đề tài";
  if (t === "BOOK") return "Sách";
  return "Hội thảo";
}

function statusLabel(s: NotificationStatus) {
  if (s === "PENDING") return "Chờ xác nhận";
  if (s === "ACCEPTED") return "Đã xác nhận";
  return "Đã từ chối";
}

function statusBadgeClass(s: NotificationStatus) {
  if (s === "PENDING") return "bg-amber-50 text-amber-700";
  if (s === "ACCEPTED") return "bg-emerald-50 text-emerald-700";
  return "bg-rose-50 text-rose-700";
}

function formatDateTime(iso: string) {
  return formatBackendDateTimeVi(iso);
}
</script>
