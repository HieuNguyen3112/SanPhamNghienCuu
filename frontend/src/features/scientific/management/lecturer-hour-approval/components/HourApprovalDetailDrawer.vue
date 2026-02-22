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
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[520px] lg:w-[640px]"
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
                  {{ formatHours(detail.totalHours) }}h
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
            <div v-if="loadingDetail" class="text-sm text-slate-700">
              Đang tải chi tiết...
            </div>

            <div
              v-else-if="errorDetail"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4"
            >
              <div class="text-sm font-medium text-rose-700">
                Không tải được chi tiết
              </div>
              <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
                {{ errorDetail }}
              </div>
            </div>

            <template v-else-if="detail">
              <div class="mb-3 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">
                  Danh sách công trình trong yêu cầu
                </div>
                <span class="text-xs text-slate-500">
                  Chỉ hiển thị công trình đã duyệt nội dung
                </span>
              </div>

              <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full text-left text-sm">
                  <thead class="bg-slate-50 text-xs text-slate-600">
                    <tr class="[&>th]:px-3 [&>th]:py-2">
                      <th>Công trình</th>
                      <th>Loại</th>
                      <th>Vai trò</th>
                      <th class="text-right">Giờ quy đổi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200">
                    <tr
                      v-for="item in detail.items"
                      :key="item.activityId"
                      class="hover:bg-slate-50"
                    >
                      <td class="px-3 py-2">
                        <div class="font-medium text-slate-900">
                          {{ item.activityTitle }}
                        </div>
                        <div class="mt-0.5 text-xs text-slate-500">
                          #{{ item.activityId }}
                        </div>
                      </td>
                      <td class="px-3 py-2 text-slate-700">
                        {{ item.activityKindName }}
                      </td>
                      <td class="px-3 py-2 text-slate-700">
                        {{ item.memberRoleName }}
                      </td>
                      <td
                        class="px-3 py-2 text-right font-semibold text-slate-900"
                      >
                        {{ formatHours(item.hoursConverted) }}h
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div
                class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
              >
                <div class="flex items-start gap-2">
                  <Info class="mt-0.5 h-4 w-4 text-slate-500" />
                  <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">
                      Tổng giờ hợp lệ: {{ formatHours(detail.totalHours) }}h
                    </div>
                    <div
                      v-if="detail.noteFromLecturer"
                      class="mt-1 text-xs text-slate-600"
                    >
                      Ghi chú từ giảng viên:
                      <span class="font-medium">{{
                        detail.noteFromLecturer
                      }}</span>
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
                <span
                  class="font-semibold"
                  :class="statusTextClass(detail.status)"
                >
                  {{ statusLabel(detail.status) }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                  :disabled="!canAct || loadingApprove || loadingReject"
                  @click="detail && emit('approve', detail.requestId)"
                >
                  <Check class="h-4 w-4" />
                  Duyệt giờ
                </button>

                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
                  :disabled="!canAct || loadingApprove || loadingReject"
                  @click="rejectModalOpen = true"
                >
                  <X class="h-4 w-4" />
                  Từ chối
                </button>
              </div>
            </div>

            <div v-if="errorApprove" class="mt-2 text-xs text-rose-700">
              {{ errorApprove }}
            </div>
            <div v-if="errorReject" class="mt-2 text-xs text-rose-700">
              {{ errorReject }}
            </div>
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
import { BookOpen, Check, Clock, Info, User, X } from "lucide-vue-next";
import type {
  HourApprovalRequestDetail,
  HourApprovalRequestStatus,
  RejectPayload,
} from "../contracts/hourApproval.contract";
import { formatDateTimeVietnamese } from "../contracts/hourApproval.contract";
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
  (e: "approve", requestId: number): void;
  (e: "reject", requestId: number, payload: RejectPayload): void;
}>();

const rejectModalOpen = ref(false);

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) rejectModalOpen.value = false;
  },
);

const canAct = computed(() => {
  return Boolean(props.detail && props.detail.status === "pending");
});

function formatDateTime(iso: string) {
  return formatDateTimeVietnamese(iso);
}

function formatHours(v: number) {
  return Number.isFinite(v) ? v.toFixed(0) : "0";
}

function statusLabel(status: HourApprovalRequestStatus) {
  if (status === "pending") return "Chờ duyệt";
  if (status === "approved") return "Đã duyệt";
  return "Từ chối";
}

function statusTextClass(status: HourApprovalRequestStatus) {
  if (status === "pending") return "text-amber-700";
  if (status === "approved") return "text-emerald-700";
  return "text-rose-700";
}

function onRejectSubmit(payload: {
  reasonCode: RejectPayload["reasonCode"];
  reasonNote: RejectPayload["reasonNote"];
}) {
  if (!props.detail) return;
  rejectModalOpen.value = false;
  emit("reject", props.detail.requestId, payload);
}
</script>
