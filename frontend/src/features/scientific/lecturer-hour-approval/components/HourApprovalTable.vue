<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div v-if="loading" class="p-4 text-sm text-slate-700">
      Đang tải danh sách…
    </div>

    <div v-else-if="error" class="p-4">
      <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
        <div class="text-sm font-medium text-rose-700">
          Không tải được dữ liệu
        </div>
        <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
          {{ error }}
        </div>
      </div>
    </div>

    <div v-else-if="rows.length === 0" class="p-6 text-center">
      <div class="text-sm font-medium text-slate-900">Không có yêu cầu</div>
      <div class="mt-1 text-xs text-slate-500">
        Thử đổi bộ lọc hoặc khoảng thời gian.
      </div>
    </div>

    <div v-else class="max-h-[560px] overflow-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th>Giảng viên</th>
            <th>Khoa</th>
            <th class="text-right">Số công trình</th>
            <th class="text-right">Tổng giờ đề nghị</th>
            <th>Ngày gửi</th>
            <th class="text-center">Trạng thái</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="row in pagedRows"
            :key="row.requestId"
            class="cursor-pointer hover:bg-slate-50"
            @click="emit('row-click', row.requestId)"
          >
            <td class="px-3 py-2">
              <div class="font-medium text-slate-900">
                {{ row.lecturerFullName }}
              </div>
              <div class="mt-0.5 text-xs text-slate-500">
                {{ row.lecturerCode }}
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">
              {{ row.facultyName }}
            </td>

            <td class="px-3 py-2 text-right font-semibold text-slate-900">
              {{ row.activityCount }}
            </td>

            <td class="px-3 py-2 text-right font-semibold text-slate-900">
              {{ formatHours(row.totalHours) }}h
            </td>

            <td class="px-3 py-2 text-slate-700">
              {{ formatDate(row.submittedAt) }}
            </td>

            <td class="px-3 py-2 text-center">
              <span
                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium ring-1"
                :class="statusPillClass(row.status)"
              >
                <component :is="statusIcon(row.status)" class="h-4 w-4" />
                {{ statusLabel(row.status) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ✅ Dùng SharedPaginationControls của bạn -->
    <div
      v-if="!loading && !error && rows.length > 0"
      class="border-t border-slate-200 px-4 py-3"
    >
      <SharedPaginationControls
        :total-item-count="rows.length"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="yêu cầu"
        container-class-name="w-full"
        @update:currentPageNumber="currentPageNumber = $event"
        @update:pageSize="pageSize = $event"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type {
  HourApprovalRequestSummary,
  HourApprovalRequestStatus,
} from "../contracts/hourApproval.contract";
import { CheckCircle2, Hourglass, XCircle } from "lucide-vue-next";
import SharedPaginationControls from "@/shared/components/SharedPaginationControls.vue";
import { formatDateVietnamese } from "../contracts/hourApproval.contract";

interface Props {
  rows: HourApprovalRequestSummary[];
  loading: boolean;
  error: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: "row-click", requestId: number): void;
}>();

const currentPageNumber = ref(1);
const pageSize = ref(12);

/**
 * ✅ Chỉ reset page khi dataset đổi (do filter/reload)
 * Không reset khi pageSize đổi, vì SharedPaginationControls đã tự clamp + emit page mới.
 */
watch(
  () => props.rows.length,
  () => {
    currentPageNumber.value = 1;
  }
);

const pagedRows = computed(() => {
  const startIndex = (currentPageNumber.value - 1) * pageSize.value;
  return props.rows.slice(startIndex, startIndex + pageSize.value);
});

function formatHours(v: number) {
  return Number.isFinite(v) ? v.toFixed(0) : "0";
}

function formatDate(iso: string) {
  return formatDateVietnamese(iso);
}

function statusLabel(status: HourApprovalRequestStatus) {
  if (status === "pending") return "Chờ duyệt";
  if (status === "approved") return "Đã duyệt";
  return "Từ chối";
}

function statusIcon(status: HourApprovalRequestStatus) {
  if (status === "pending") return Hourglass;
  if (status === "approved") return CheckCircle2;
  return XCircle;
}

function statusPillClass(status: HourApprovalRequestStatus) {
  if (status === "pending") return "bg-amber-50 text-amber-700 ring-amber-200";
  if (status === "approved")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  return "bg-rose-50 text-rose-700 ring-rose-200";
}
</script>
