<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div v-if="loading" class="p-4 text-sm text-slate-700">
      Đang tải danh sách...
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
      <div class="text-sm font-medium text-slate-900">
        Không có công trình đủ điều kiện
      </div>
      <div class="mt-1 text-xs text-slate-500">
        Chỉ hiển thị công trình đã được khoa duyệt nội dung.
      </div>
    </div>

    <div v-else class="max-h-[560px] overflow-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th class="w-12">
              <input
                ref="selectAllCheckbox"
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-0"
                :disabled="selectableIds.length === 0"
                :checked="allSelectableSelected"
                @change="onToggleSelectAll"
              />
            </th>
            <th>Tên công trình</th>
            <th>Loại</th>
            <th>Vai trò</th>
            <th class="text-right">Giờ duyệt</th>
            <th class="text-center">Trạng thái duyệt giờ</th>
            <th class="text-left">Bước tiếp theo</th>
            <th class="w-12 text-right"></th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="row in rows"
            :key="row.activityId"
            class="hover:bg-slate-50"
          >
            <td class="px-3 py-2">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-0 disabled:opacity-50"
                :disabled="isCheckboxDisabled(row)"
                :checked="selectedIdSet.has(row.activityId)"
                @change="onToggleRow(row, $event)"
              />
            </td>

            <td class="px-3 py-2">
              <button
                type="button"
                class="text-left font-medium text-slate-900 hover:underline"
                @click="emit('open-detail', row.activityId)"
              >
                {{ row.title }}
              </button>
              <div class="mt-0.5 text-xs text-slate-500">
                {{ row.activityCode }} <span class="px-1">•</span>
                {{ row.academicYearCode }}
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">{{ row.kindName }}</td>
            <td class="px-3 py-2 text-slate-700">{{ row.memberRoleName }}</td>

            <td class="px-3 py-2 text-right font-semibold text-slate-900">
              {{ formatHours(row.effectiveHoursDisplay) }}
            </td>

            <td class="px-3 py-2 text-center">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                :class="hoursPillClass(row)"
              >
                {{ hoursStatusLabel(row) }}
              </span>
            </td>

            <td class="px-3 py-2">
              <div class="text-xs text-slate-700">
                {{ row.nextActionText ?? "—" }}
              </div>
              <div
                v-if="
                  row.hoursRequestState === 'hours_rejected' ||
                  row.hoursRequestState === 'hours_need_revision'
                "
                class="mt-1 text-xs text-rose-700"
              >
                <div>
                  {{
                    row.hoursRequestState === "hours_need_revision"
                      ? "Yêu cầu chỉnh sửa"
                      : "Lý do từ chối"
                  }}:
                  {{ hoursRejectReasonLabel(normalizedReasonCode(row)) }}
                </div>
                <div v-if="rejectionReasonText(row)">
                  {{ rejectionReasonText(row) }}
                </div>
                <div
                  v-if="row.hoursRequestState === 'hours_need_revision'"
                  class="mt-1 text-blue-700"
                >
                  {{ hoursRejectReasonActionHint(normalizedReasonCode(row)) }}
                </div>
              </div>
              <div
                v-if="validationMessages(row).length > 0"
                class="mt-1 space-y-1 text-xs text-amber-700"
              >
                <div
                  v-for="message in validationMessages(row)"
                  :key="`${row.activityId}-${message}`"
                >
                  {{ message }}
                </div>
              </div>
            </td>

            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                title="Xem chi tiết"
                @click="emit('open-detail', row.activityId)"
              >
                <Eye class="h-4 w-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="!loading && !error && rows.length > 0"
      class="border-t border-slate-200 px-4 py-3"
    >
      <SharedPaginationControls
        :total-item-count="totalItemCount"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="công trình"
        container-class-name="w-full"
        @update:currentPageNumber="emit('update:currentPageNumber', $event)"
        @update:pageSize="emit('update:pageSize', $event)"
      />
    </div>

    <div class="border-t border-slate-200 bg-slate-50/40 px-4 py-3">
      <div
        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="text-sm text-slate-700">
          <span class="font-medium text-slate-900">Tổng giờ đã chọn:</span>
          <span class="ml-1 font-semibold text-slate-900">
            {{ formatHours(selectedHoursTotal) }} giờ
          </span>
          <span class="ml-2 text-xs text-slate-500"
            >( {{ selectedCount }} công trình )</span
          >
        </div>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:opacity-50"
          :disabled="selectedCount === 0 || submitting"
          @click="emit('submit-request')"
        >
          <Check class="h-4 w-4" />
          {{ submitActionLabel }}
        </button>
      </div>

      <div
        v-if="submitError"
        class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
      >
        {{ submitError }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import { Check, Eye } from "lucide-vue-next";
import type { ApprovedWorkRow } from "../contracts/selectHoursRequest.contract";
import {
  formatHours,
  hoursRejectReasonActionHint,
  hoursRejectReasonLabel,
  isWorkEligibleForSubmit,
  normalizeHoursRejectReasonCode,
} from "../contracts/selectHoursRequest.contract";

const props = defineProps<{
  rows: ApprovedWorkRow[];
  selectableIds: number[];
  selectedIds: number[];
  selectedCount: number;
  selectedHoursTotal: number;
  submitting: boolean;
  submitError: string | null;
  submitItemErrorsByActivityId: Record<number, string[]>;
  loading: boolean;
  error: string | null;
  currentPageNumber: number;
  pageSize: number;
  totalItemCount: number;
}>();

const emit = defineEmits<{
  (
    e: "toggle-row",
    payload: { activityId: number; nextChecked: boolean },
  ): void;
  (
    e: "toggle-select-all",
    payload: { selectableIds: number[]; nextChecked: boolean },
  ): void;
  (e: "open-detail", activityId: number): void;
  (e: "submit-request"): void;
  (e: "update:currentPageNumber", value: number): void;
  (e: "update:pageSize", value: number): void;
}>();

const selectedIdSet = computed(() => new Set(props.selectedIds));

const selectedSelectableCount = computed(() => {
  const selected = selectedIdSet.value;
  return props.selectableIds.filter((id) => selected.has(id)).length;
});

const allSelectableSelected = computed(() => {
  return (
    props.selectableIds.length > 0 &&
    selectedSelectableCount.value === props.selectableIds.length
  );
});

const someSelectableSelected = computed(() => {
  return (
    selectedSelectableCount.value > 0 &&
    selectedSelectableCount.value < props.selectableIds.length
  );
});

const submitActionLabel = computed(() => {
  if (props.selectedIds.length === 0) {
    return "Gửi duyệt giờ";
  }

  const selectedRows = props.rows.filter((row) =>
    selectedIdSet.value.has(row.activityId),
  );
  const onlyNeedRevisionRows =
    selectedRows.length > 0 &&
    selectedRows.every(
      (row) => row.hoursRequestState === "hours_need_revision",
    );

  return onlyNeedRevisionRows ? "Chỉnh sửa và gửi lại" : "Gửi duyệt giờ";
});

const selectAllCheckbox = ref<HTMLInputElement | null>(null);
watch(
  () => someSelectableSelected.value,
  () => {
    if (!selectAllCheckbox.value) return;
    selectAllCheckbox.value.indeterminate = someSelectableSelected.value;
  },
  { immediate: true },
);

function onToggleSelectAll(event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  emit("toggle-select-all", {
    selectableIds: props.selectableIds,
    nextChecked: checked,
  });
}

function onToggleRow(row: ApprovedWorkRow, event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  emit("toggle-row", { activityId: row.activityId, nextChecked: checked });
}

function isCheckboxDisabled(row: ApprovedWorkRow) {
  return !isWorkEligibleForSubmit(row);
}

function validationMessages(row: ApprovedWorkRow): string[] {
  return props.submitItemErrorsByActivityId[row.activityId] ?? [];
}

function normalizedReasonCode(row: ApprovedWorkRow) {
  return normalizeHoursRejectReasonCode(row.hoursRejectionReasonCode);
}

function rejectionReasonText(row: ApprovedWorkRow): string | null {
  return row.hoursRejectionReasonDetail ?? row.hoursRejectionReason ?? null;
}

function hoursStatusLabel(row: ApprovedWorkRow) {
  if (row.hoursRequestState === "hours_approved") return "Đã duyệt";
  if (row.hoursRequestState === "hours_pending_faculty")
    return "Chờ khoa duyệt";
  if (row.hoursRequestState === "hours_need_revision") return "Cần chỉnh sửa";
  if (row.hoursRequestState === "hours_rejected") return "Bị từ chối";
  return "Chưa gửi duyệt";
}

function hoursPillClass(row: ApprovedWorkRow) {
  if (row.hoursRequestState === "hours_approved")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (row.hoursRequestState === "hours_pending_faculty")
    return "bg-amber-50 text-amber-700 ring-amber-200";
  if (row.hoursRequestState === "hours_need_revision")
    return "bg-blue-50 text-blue-700 ring-blue-200";
  if (row.hoursRequestState === "hours_rejected")
    return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-slate-50 text-slate-700 ring-slate-200";
}
</script>
