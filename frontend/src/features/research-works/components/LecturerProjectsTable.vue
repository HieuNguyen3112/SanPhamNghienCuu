<template>
  <div>
    <!-- Header row -->
    <div
      class="border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"
    >
      <div class="grid grid-cols-12 items-center gap-4">
        <div class="col-span-4">Giảng viên</div>
        <div class="col-span-3">Đơn vị</div>
        <div class="col-span-2 text-right">Tổng công trình</div>
        <div class="col-span-2 text-right">Đã duyệt / Chờ duyệt</div>
        <div class="col-span-1 text-right">Chi tiết</div>
      </div>
    </div>

    <!-- Body -->
    <div v-if="loading" class="px-5 py-6 text-sm text-slate-500">
      Đang tải dữ liệu...
    </div>

    <div v-else-if="errorMessage" class="px-5 py-6 text-sm text-rose-600">
      {{ errorMessage }}
    </div>

    <div v-else-if="!items.length" class="px-5 py-6 text-sm text-slate-500">
      Không có dữ liệu phù hợp.
    </div>

    <div v-else class="divide-y divide-slate-100">
      <div
        v-for="item in items"
        :key="item.lecturer.id"
        class="px-5 py-4 text-sm hover:bg-slate-50"
      >
        <div class="grid grid-cols-12 items-center gap-4">
          <!-- Giảng viên -->
          <div class="col-span-4">
            <div class="font-medium text-slate-900">
              {{ item.lecturer.fullName }}
            </div>
            <div class="text-xs text-slate-500">
              Mã: {{ item.lecturer.code }}
            </div>
          </div>

          <!-- Đơn vị -->
          <div class="col-span-3 text-sm text-slate-700">
            {{ item.lecturer.departmentName }}
          </div>

          <!-- Tổng công trình -->
          <div class="col-span-2 text-right">
            <div class="font-semibold text-slate-900">
              {{ item.totalProjects }}
            </div>
            <div class="text-xs text-slate-500">
              Tổng giờ NCKH: {{ item.totalHours }}
            </div>
          </div>

          <!-- Đã duyệt / chờ duyệt -->
          <div class="col-span-2 text-right text-xs">
            <div class="text-emerald-600">
              Đã duyệt: {{ item.approvedProjects }}
            </div>
            <div class="text-amber-500">
              Chờ duyệt: {{ item.pendingProjects }}
            </div>
            <div v-if="item.rejectedProjects" class="text-rose-500">
              Từ chối: {{ item.rejectedProjects }}
            </div>
          </div>

          <!-- Action -->
          <div class="col-span-1 text-right">
            <button
              type="button"
              class="inline-flex items-center rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-sky-700 hover:bg-sky-50"
              @click="() => emit('view-lecturer', item.lecturer.id)"
            >
              Xem
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div
      class="flex items-center justify-between border-t border-slate-200 px-5 py-3 text-xs text-slate-600"
    >
      <div>
        Hiển thị
        <span class="font-medium"> {{ startIndex }}–{{ endIndex }} </span>
        trong tổng
        <span class="font-medium">
          {{ pagination.totalItems }}
        </span>
        giảng viên
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="pagination.page <= 1 || loading"
          @click="changePage(pagination.page - 1)"
        >
          Trước
        </button>
        <div class="text-xs">
          Trang
          <span class="font-semibold">{{ pagination.page }}</span>
          /
          <span class="font-semibold">{{ totalPages }}</span>
        </div>
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="pagination.page >= totalPages || loading"
          @click="changePage(pagination.page + 1)"
        >
          Sau
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
  LecturerProjectsSummary,
  PaginationState,
} from "@/features/research-works/types";

interface Props {
  items: LecturerProjectsSummary[];
  loading?: boolean;
  errorMessage?: string | null;
  pagination: PaginationState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "change-page", page: number): void;
  (e: "view-lecturer", lecturerId: string): void;
}>();

const totalPages = computed(() => {
  if (!props.pagination.pageSize) return 1;
  return Math.max(
    1,
    Math.ceil(props.pagination.totalItems / props.pagination.pageSize)
  );
});

const startIndex = computed(() => {
  if (!props.pagination.totalItems) return 0;
  return (props.pagination.page - 1) * props.pagination.pageSize + 1;
});

const endIndex = computed(() => {
  return Math.min(
    props.pagination.page * props.pagination.pageSize,
    props.pagination.totalItems
  );
});

function changePage(page: number) {
  emit("change-page", page);
}
</script>
