<template>
  <teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-slate-900/30" @click="emit('close')" />

      <div
        class="absolute left-1/2 top-1/2 w-[92vw] max-w-[520px] -translate-x-1/2 -translate-y-1/2"
      >
        <div class="rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div class="border-b border-slate-200 px-4 py-3">
            <div class="flex items-center gap-2">
              <Info class="h-5 w-5 text-slate-500" />
              <div class="text-sm font-semibold text-slate-900">
                Từ chối yêu cầu
              </div>
            </div>
            <div class="mt-1 text-xs text-slate-500">
              Chọn lý do để ghi nhận và phản hồi cho giảng viên.
            </div>
          </div>

          <div class="p-4">
            <div class="space-y-2">
              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="hours_not_reasonable"
                  v-model="reasonCode"
                />
                <div>
                  <div class="text-sm font-medium text-slate-900">
                    Giờ quy đổi chưa hợp lý
                  </div>
                  <div class="text-xs text-slate-500">
                    Cần rà soát lại phân bổ/định mức quy đổi.
                  </div>
                </div>
              </label>

              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="work_not_eligible"
                  v-model="reasonCode"
                />
                <div>
                  <div class="text-sm font-medium text-slate-900">
                    Công trình chưa đủ điều kiện
                  </div>
                  <div class="text-xs text-slate-500">
                    Một số công trình chưa đáp ứng tiêu chí.
                  </div>
                </div>
              </label>

              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="missing_evidence"
                  v-model="reasonCode"
                />
                <div>
                  <div class="text-sm font-medium text-slate-900">
                    Thiếu minh chứng
                  </div>
                  <div class="text-xs text-slate-500">
                    Cần bổ sung file/biên bản liên quan.
                  </div>
                </div>
              </label>

              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="other"
                  v-model="reasonCode"
                />
                <div class="w-full">
                  <div class="text-sm font-medium text-slate-900">
                    Lý do khác
                  </div>
                  <div class="text-xs text-slate-500">
                    Nhập nội dung chi tiết.
                  </div>

                  <textarea
                    v-if="reasonCode === 'other'"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm focus:border-slate-400 focus:ring-0"
                    rows="3"
                    :value="reasonNote"
                    placeholder="Nhập lý do..."
                    @input="
                      reasonNote = ($event.target as HTMLTextAreaElement).value
                    "
                  />
                </div>
              </label>
            </div>

            <div
              v-if="validationError"
              class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-700"
            >
              {{ validationError }}
            </div>
          </div>

          <div
            class="flex items-center justify-end gap-2 border-t border-slate-200 px-4 py-3"
          >
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
              @click="emit('close')"
            >
              Hủy
            </button>

            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"
              :disabled="loading"
              @click="submit"
            >
              <X class="h-4 w-4" />
              Xác nhận từ chối
            </button>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Info, X } from "lucide-vue-next";
import type { HourApprovalRejectReasonCode } from "../contracts/hourApproval.contract";

interface Props {
  open: boolean;
  loading: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: "close"): void;
  (
    e: "submit",
    payload: {
      reasonCode: HourApprovalRejectReasonCode;
      reasonNote: string | null;
    }
  ): void;
}>();

const reasonCode = ref<HourApprovalRejectReasonCode>("hours_not_reasonable");
const reasonNote = ref<string>("");

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return;
    reasonCode.value = "hours_not_reasonable";
    reasonNote.value = "";
  }
);

const validationError = computed(() => {
  if (reasonCode.value !== "other") return null;
  if (!reasonNote.value.trim()) return "Vui lòng nhập nội dung cho lý do khác.";
  return null;
});

function submit() {
  if (validationError.value) return;
  emit("submit", {
    reasonCode: reasonCode.value,
    reasonNote: reasonCode.value === "other" ? reasonNote.value.trim() : null,
  });
}
</script>
