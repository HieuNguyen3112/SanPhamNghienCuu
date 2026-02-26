<template>
  <teleport to="body">
    <transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-slate-900/20"
        @click="emit('close')"
      />
    </transition>

    <transition
      enter-active-class="transition-transform duration-250 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[520px] lg:w-[680px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 px-4 py-4 lg:px-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <User class="h-5 w-5 text-slate-500" />
                  <h3 class="truncate text-base font-semibold text-slate-900">
                    {{ detail?.lecturerFullName ?? "Chi tiết yêu cầu" }}
                  </h3>
                </div>
                <div
                  class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600"
                >
                  <span class="inline-flex items-center gap-1">
                    <BookOpen class="h-4 w-4 text-slate-400" />
                    {{ detail?.facultyName ?? "—" }}
                  </span>
                  <span class="inline-flex items-center gap-1">
                    <Clock class="h-4 w-4 text-slate-400" />
                    {{ detail ? formatDateTime(detail.submittedAt) : "—" }}
                  </span>
                </div>
              </div>

              <button
                type="button"
                class="h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <div v-if="detail" class="mt-3 grid grid-cols-2 gap-2">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Tổng giờ đề nghị</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                  {{ formatHours(detail.totalHours) }} giờ
                </div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Số công trình</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                  {{ detail.activityCount }}
                </div>
              </div>
            </div>
          </div>

          <div class="flex-1 overflow-auto px-4 py-4 lg:px-5">
            <div v-if="loadingDetail" class="text-sm text-slate-700">Đang tải chi tiết...</div>

            <div
              v-else-if="errorDetail"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4"
            >
              <div class="text-sm font-medium text-rose-700">Không tải được chi tiết</div>
              <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
                {{ errorDetail }}
              </div>
            </div>

            <template v-else-if="detail">
              <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="text-sm font-semibold text-slate-900">Danh sách công trình trong yêu cầu</div>
                <span class="text-xs text-slate-500">Theo dõi giờ quy đổi và minh chứng</span>
              </div>

              <div
                v-if="canAct"
                class="mb-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
              >
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                      :checked="isAllPendingSelected"
                      @change="toggleSelectAll"
                    />
                    <span class="font-medium text-slate-800">Chọn tất cả mục chờ duyệt</span>
                  </label>

                  <div class="text-xs text-slate-600">
                    Đã chọn: <span class="font-semibold text-slate-900">{{ selectedPendingCount }}</span>
                    / {{ pendingItems.length }} công trình
                    <span class="mx-1 text-slate-400">•</span>
                    Tổng giờ mục đã chọn:
                    <span class="font-semibold text-slate-900">{{ formatHours(selectedPendingTotalHours) }} giờ</span>
                  </div>
                </div>
              </div>

              <div class="space-y-3">
                <article
                  v-for="item in detail.items"
                  :key="item.activityId"
                  class="rounded-xl border border-slate-200 p-3"
                >
                  <div class="flex items-start gap-3">
                    <label
                      class="mt-0.5 inline-flex items-center"
                      :class="item.approvalStatus === 'pending' ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'"
                    >
                      <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                        :disabled="item.approvalStatus !== 'pending'"
                        :checked="isActivitySelected(item.activityId)"
                        @change="toggleActivity(item.activityId)"
                      />
                    </label>

                    <div class="min-w-0 flex-1">
                      <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                          <div class="font-medium text-slate-900">{{ item.activityTitle }}</div>
                          <div class="mt-0.5 text-xs text-slate-500">
                            #{{ item.activityId }} • {{ item.activityKindName }} • {{ item.memberRoleName }}
                          </div>
                        </div>

                        <div class="text-right text-xs">
                          <div class="text-slate-500">Giờ dùng để duyệt</div>
                          <div class="text-sm font-semibold text-slate-900">
                            {{ formatHours(item.effectiveHoursDisplay ?? item.hoursConverted) }} giờ
                          </div>
                          <div class="mt-1">
                            <span
                              class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium ring-1"
                              :class="itemStatusPillClass(item.approvalStatus)"
                            >
                              {{ itemStatusLabel(item.approvalStatus) }}
                            </span>
                          </div>
                        </div>
                      </div>

                      <div v-if="item.ruleSummary" class="mt-1 text-xs text-slate-600">
                        {{ item.ruleSummary }}
                      </div>
                      <div
                        v-if="item.formulaExplanation"
                        class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-[11px] text-slate-700"
                      >
                        <div class="font-medium text-slate-800">
                          {{ item.formulaExplanation.ruleName }}
                        </div>
                        <div class="mt-1">
                          Tổng giờ công trình:
                          <span class="font-semibold text-slate-900">
                            {{ formatHours(item.totalHoursActivity ?? item.formulaExplanation.totalHoursActivity) }} giờ
                          </span>
                        </div>
                        <div>
                          Giờ của giảng viên:
                          <span class="font-semibold text-slate-900">
                            {{ formatHours(item.memberHours ?? item.formulaExplanation.memberHours ?? item.effectiveHoursDisplay) }} giờ
                          </span>
                          <span
                            v-if="item.formulaExplanation.memberSharePercent != null"
                            class="ml-1 text-slate-500"
                          >
                            ({{ item.formulaExplanation.memberSharePercent }}%)
                          </span>
                        </div>
                      </div>
                      <div v-if="item.rejectionReason" class="mt-1 text-xs text-rose-700">
                        Lý do từ chối: {{ item.rejectionReason }}
                      </div>
                    </div>
                  </div>

                  <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                    <div class="mb-1 text-xs font-medium text-slate-700">Minh chứng</div>
                    <div v-if="item.evidenceFiles.length === 0" class="text-xs text-slate-500">
                      Chưa có minh chứng.
                    </div>
                    <ul v-else class="space-y-1.5">
                      <li
                        v-for="file in item.evidenceFiles"
                        :key="file.id"
                        class="flex items-center justify-between gap-2 rounded-lg bg-white px-2 py-1.5"
                      >
                        <div class="min-w-0">
                          <div class="truncate text-xs text-slate-800">{{ file.originalName }}</div>
                          <div class="text-[11px] text-slate-500">
                            {{ file.fileTypeName ?? `Loại #${file.fileTypeId}` }} •
                            {{ formatBytes(file.sizeBytes) }}
                          </div>
                        </div>
                        <a
                          v-if="file.downloadUrl"
                          :href="file.downloadUrl"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] text-slate-700 hover:bg-slate-50"
                        >
                          <Download class="h-3 w-3" />
                          Xem
                        </a>
                      </li>
                    </ul>
                  </div>
                </article>
              </div>

              <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="flex items-start gap-2">
                  <Info class="mt-0.5 h-4 w-4 text-slate-500" />
                  <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">
                      Tổng giờ hợp lệ: {{ formatHours(detail.totalHours) }} giờ
                    </div>
                    <div v-if="detail.noteFromFaculty" class="mt-1 text-xs text-rose-700">
                      Ghi chú từ khoa: {{ detail.noteFromFaculty }}
                    </div>
                    <div v-if="detail.noteFromLecturer" class="mt-1 text-xs text-slate-600">
                      Ghi chú từ giảng viên: {{ detail.noteFromLecturer }}
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="border-t border-slate-200 bg-white px-4 py-3 lg:px-5">
            <div class="flex items-center justify-between gap-2">
              <div v-if="detail" class="text-xs text-slate-600">
                Trạng thái:
                <span class="font-semibold" :class="statusTextClass(detail.status)">
                  {{ statusLabel(detail.status) }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                  :disabled="!canAct || selectedPendingCount === 0 || loadingApprove || loadingReject"
                  @click="onApproveSelected"
                >
                  <Check class="h-4 w-4" />
                  Duyệt các mục đã chọn
                </button>

                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
                  :disabled="!canAct || selectedPendingCount === 0 || loadingApprove || loadingReject"
                  @click="rejectModalOpen = true"
                >
                  <X class="h-4 w-4" />
                  Từ chối các mục đã chọn
                </button>
              </div>
            </div>

            <div v-if="errorApprove" class="mt-2 text-xs text-rose-700">{{ errorApprove }}</div>
            <div v-if="errorReject" class="mt-2 text-xs text-rose-700">{{ errorReject }}</div>
          </div>
        </div>

        <RejectReasonModal
          :open="rejectModalOpen"
          :loading="loadingReject"
          @close="rejectModalOpen = false"
          @submit="onRejectSubmit"
        />
      </aside>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
  BookOpen,
  Check,
  Clock,
  Download,
  Info,
  User,
  X,
} from "lucide-vue-next";
import type {
  HourApprovalRequestDetail,
  HourApprovalRequestItem,
  HourApprovalRequestStatus,
  RejectPayload,
} from "../contracts/hourApproval.contract";
import {
  formatBytes,
  formatDateTimeVietnamese,
} from "../contracts/hourApproval.contract";
import RejectReasonModal from "./RejectReasonModal.vue";

interface Props {
  open: boolean;
  detail: HourApprovalRequestDetail | null;
  loadingDetail: boolean;
  errorDetail: string | null;
  loadingApprove: boolean;
  loadingReject: boolean;
  errorApprove: string | null;
  errorReject: string | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: "close"): void;
  (e: "approve", requestId: number, activityIds: number[]): void;
  (e: "reject", requestId: number, payload: RejectPayload): void;
}>();

const rejectModalOpen = ref(false);
const selectedActivityIds = ref<number[]>([]);

watch(
  () => [props.open, props.detail?.requestId],
  ([isOpen]) => {
    if (!isOpen) rejectModalOpen.value = false;
    selectedActivityIds.value = [];
  }
);

const pendingItems = computed(() => {
  if (!props.detail) return [] as HourApprovalRequestItem[];
  return props.detail.items.filter((item) => item.approvalStatus === "pending");
});

const canAct = computed(() => {
  return Boolean(props.detail && props.detail.status === "pending" && pendingItems.value.length > 0);
});

const selectedPendingCount = computed(() => selectedActivityIds.value.length);

const selectedPendingTotalHours = computed(() => {
  if (!props.detail) return 0;
  const selected = new Set(selectedActivityIds.value);
  return props.detail.items
    .filter((item) => selected.has(item.activityId))
    .reduce((sum, item) => sum + Number(item.effectiveHoursDisplay ?? item.hoursConverted ?? 0), 0);
});

const isAllPendingSelected = computed(() => {
  if (pendingItems.value.length === 0) return false;
  const selected = new Set(selectedActivityIds.value);
  return pendingItems.value.every((item) => selected.has(item.activityId));
});

function formatDateTime(iso: string) {
  return formatDateTimeVietnamese(iso);
}

function formatHours(v: number | null): string {
  return Number.isFinite(v ?? NaN) ? Number(v).toFixed(0) : "—";
}

function statusLabel(status: HourApprovalRequestStatus) {
  if (status === "pending") return "Chờ khoa duyệt giờ";
  if (status === "approved") return "Đã duyệt giờ";
  return "Khoa từ chối giờ";
}

function statusTextClass(status: HourApprovalRequestStatus) {
  if (status === "pending") return "text-amber-700";
  if (status === "approved") return "text-emerald-700";
  return "text-rose-700";
}

function itemStatusLabel(status: HourApprovalRequestItem["approvalStatus"]) {
  if (status === "approved") return "Đã duyệt";
  if (status === "rejected") return "Bị từ chối";
  return "Chờ duyệt";
}

function itemStatusPillClass(status: HourApprovalRequestItem["approvalStatus"]) {
  if (status === "approved") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (status === "rejected") return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-amber-50 text-amber-700 ring-amber-200";
}

function isActivitySelected(activityId: number): boolean {
  return selectedActivityIds.value.includes(activityId);
}

function toggleActivity(activityId: number) {
  if (!pendingItems.value.some((item) => item.activityId === activityId)) return;

  if (isActivitySelected(activityId)) {
    selectedActivityIds.value = selectedActivityIds.value.filter((id) => id !== activityId);
    return;
  }

  selectedActivityIds.value = [...selectedActivityIds.value, activityId];
}

function toggleSelectAll(event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  if (!checked) {
    selectedActivityIds.value = [];
    return;
  }

  selectedActivityIds.value = pendingItems.value.map((item) => item.activityId);
}

function onApproveSelected() {
  if (!props.detail || selectedActivityIds.value.length === 0) return;
  emit("approve", props.detail.requestId, [...selectedActivityIds.value]);
}

function onRejectSubmit(payload: {
  reasonCode: RejectPayload["reasonCode"];
  reasonNote: RejectPayload["reasonNote"];
}) {
  if (!props.detail || selectedActivityIds.value.length === 0) return;
  rejectModalOpen.value = false;
  emit("reject", props.detail.requestId, {
    ...payload,
    activityIds: [...selectedActivityIds.value],
  });
}
</script>
