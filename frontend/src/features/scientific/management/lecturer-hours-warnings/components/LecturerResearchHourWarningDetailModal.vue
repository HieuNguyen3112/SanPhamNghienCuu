<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="$emit('close')" />

    <div
      class="absolute right-0 top-0 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[520px]"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex h-full flex-col">
        <!-- Header -->
        <div class="border-b border-slate-200 px-4 py-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-sm font-semibold text-slate-900">
                Chi tiết cảnh báo
              </div>
              <div v-if="entry" class="mt-1 text-xs text-slate-500">
                {{ entry.lecturerFullName }} - {{ entry.lecturerCode }}
              </div>
            </div>

            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
              @click="$emit('close')"
              title="Đóng"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-auto p-4">
          <div v-if="!entry" class="text-sm text-slate-700">
            Không có dữ liệu.
          </div>

          <div v-else class="space-y-4">
            <!-- Info -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="text-xs font-medium text-slate-600">Thông tin</div>
              <div class="mt-2 grid gap-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-600">Khoa</span>
                  <span class="font-semibold text-slate-900">{{
                    entry.facultyShortName
                  }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">Năm học</span>
                  <span class="font-semibold text-slate-900">{{
                    entry.academicYearIdentifier
                  }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">Giờ hiện có</span>
                  <span class="font-semibold text-slate-900">{{
                    formatHours(entry.currentHours)
                  }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">Định mức</span>
                  <span class="font-semibold text-slate-900">{{
                    formatHours(entry.requiredHours)
                  }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">Cần thiếu</span>
                  <span class="font-semibold text-slate-900">{{
                    formatHours(entry.remainingHours)
                  }}</span>
                </div>
              </div>
            </div>

            <!-- Last warning -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="text-xs font-medium text-slate-600">
                Lần cảnh báo gần nhất
              </div>
              <div class="mt-2 text-sm text-slate-700">
                <div>
                  <span class="text-slate-600">Trạng thái: </span>
                  <span class="font-semibold text-slate-900">
                    {{
                      entry.hasRequestedWarning
                        ? "Đã cảnh báo"
                        : "Chưa cảnh báo"
                    }}
                  </span>
                </div>
                <div class="mt-1">
                  <span class="text-slate-600">Thời gian: </span>
                  <span class="font-semibold text-slate-900">{{
                    formatDateTimeVi(entry.lastRequestedAt)
                  }}</span>
                </div>
                <div v-if="entry.lastRequestedReasonCode" class="mt-1">
                  <span class="text-slate-600">Lý do: </span>
                  <span class="font-semibold text-slate-900">{{
                    reasonLabel(entry.lastRequestedReasonCode)
                  }}</span>
                </div>
                <div
                  v-if="entry.lastRequestedReasonNote"
                  class="mt-1 whitespace-pre-wrap text-xs text-slate-600"
                >
                  Ghi chú: {{ entry.lastRequestedReasonNote }}
                </div>
              </div>
            </div>

            <!-- Reason picker -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="flex items-center gap-2">
                <AlertTriangle class="h-4 w-4 text-slate-700" />
                <div class="text-sm font-semibold text-slate-900">
                  Chọn lý do cảnh báo
                </div>
              </div>

              <div class="mt-3 space-y-2">
                <label
                  v-for="opt in RESEARCH_HOUR_WARNING_REASON_OPTIONS"
                  :key="opt.code"
                  class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 hover:bg-slate-50"
                >
                  <input
                    type="radio"
                    class="mt-1 h-4 w-4"
                    name="warning-reason"
                    :value="opt.code"
                    v-model="selectedReasonCode"
                  />
                  <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-900">
                      {{ opt.label }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-600">
                      {{ opt.description }}
                    </div>
                  </div>
                </label>
              </div>

              <div class="mt-3">
                <label class="text-xs font-medium text-slate-600"
                  >Ghi chú (nếu cần)</label
                >
                <textarea
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
                  rows="3"
                  v-model="reasonNote"
                  :placeholder="
                    selectedReasonCode === 'OTHER'
                      ? 'Vui lòng nhập lý do cụ thể...'
                      : 'Nhập ghi chú...'
                  "
                />
                <div v-if="localError" class="mt-2 text-xs text-rose-700">
                  {{ localError }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-slate-200 p-4">
          <button
            type="button"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm disabled:opacity-50"
            :disabled="!entry || submitting"
            @click="onSubmit"
          >
            <Send class="h-4 w-4" />
            Cảnh báo giảng viên
          </button>

          <div
            v-if="submitError"
            class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
          >
            {{ submitError }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { AlertTriangle, Send, X } from "lucide-vue-next";
import type {
  AcademicYearIdentifier,
  LecturerResearchHourShortfallWarningEntry,
  ResearchHourWarningReasonCode,
} from "../contracts/lecturerResearchHourWarning.contract";
import {
  formatDateTimeVi,
  formatHours,
  RESEARCH_HOUR_WARNING_REASON_OPTIONS,
} from "../contracts/lecturerResearchHourWarning.contract";

const props = defineProps<{
  open: boolean;
  entry: LecturerResearchHourShortfallWarningEntry | null;
  submitting: boolean;
  submitError: string | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (
    e: "request-warning",
    payload: {
      lecturerIdentifier: string;
      academicYearIdentifier: AcademicYearIdentifier;
      reasonCode: ResearchHourWarningReasonCode;
      reasonNote: string | null;
    }
  ): void;
}>();

const selectedReasonCode = ref<ResearchHourWarningReasonCode | "">("");
const reasonNote = ref<string>("");

const localError = ref<string | null>(null);

watch(
  () => props.open,
  (v) => {
    if (v) {
      selectedReasonCode.value = "";
      reasonNote.value = "";
      localError.value = null;
    }
  }
);

function reasonLabel(code: ResearchHourWarningReasonCode) {
  const opt = RESEARCH_HOUR_WARNING_REASON_OPTIONS.find((x) => x.code === code);
  return opt?.label ?? code;
}

function onSubmit() {
  localError.value = null;

  if (!props.entry) return;

  if (!selectedReasonCode.value) {
    localError.value = "Vui lòng chọn lý do cảnh báo.";
    return;
  }

  if (
    selectedReasonCode.value === "OTHER" &&
    reasonNote.value.trim().length === 0
  ) {
    localError.value = "Vui lòng nhập ghi chú cho 'Lý do khác'.";
    return;
  }

  emit("request-warning", {
    lecturerIdentifier: props.entry.lecturerIdentifier,
    academicYearIdentifier: props.entry.academicYearIdentifier,
    reasonCode: selectedReasonCode.value,
    reasonNote: reasonNote.value.trim() ? reasonNote.value.trim() : null,
  });
}
</script>
