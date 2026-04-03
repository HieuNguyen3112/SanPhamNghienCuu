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
                Phản hồi yêu cầu
              </div>
            </div>
            <div class="mt-1 text-xs text-slate-500">
              Chọn hình thức phản hồi và lý do cụ thể để giảng viên xử lý đúng
              hướng.
            </div>
          </div>

          <div class="p-4">
            <div
              class="mb-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
            >
              <div class="text-xs font-semibold text-slate-700">
                Hình thức phản hồi
              </div>
              <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label
                  class="flex items-start gap-2 rounded-lg border border-slate-200 bg-white p-2.5 hover:bg-slate-50"
                >
                  <input
                    type="radio"
                    class="mt-1"
                    value="revision"
                    v-model="decisionMode"
                  />
                  <div>
                    <div class="text-sm font-medium text-slate-900">
                      Yêu cầu chỉnh sửa
                    </div>
                    <div class="text-xs text-slate-500">
                      Giảng viên được chỉnh sửa và gửi lại các mục này.
                    </div>
                  </div>
                </label>

                <label
                  class="flex items-start gap-2 rounded-lg border border-slate-200 bg-white p-2.5 hover:bg-slate-50"
                >
                  <input
                    type="radio"
                    class="mt-1"
                    value="reject"
                    v-model="decisionMode"
                  />
                  <div>
                    <div class="text-sm font-medium text-slate-900">
                      Từ chối hẳn
                    </div>
                    <div class="text-xs text-slate-500">
                      Yêu cầu kết thúc, giảng viên không thể gửi lại.
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <div class="space-y-2">
              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="INVALID_EVIDENCE"
                  v-model="reasonCode"
                />
                <div>
                  <div class="text-sm font-medium text-slate-900">
                    Minh chứng chưa hợp lệ
                  </div>
                  <div class="text-xs text-slate-500">
                    Thiếu hồ sơ hoặc minh chứng chưa đáp ứng yêu cầu kiểm tra.
                  </div>
                </div>
              </label>

              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="INVALID_HOURS"
                  v-model="reasonCode"
                />
                <div>
                  <div class="text-sm font-medium text-slate-900">
                    Giờ quy đổi chưa hợp lệ
                  </div>
                  <div class="text-xs text-slate-500">
                    Cần rà soát lại phân bổ hoặc định mức quy đổi giờ.
                  </div>
                </div>
              </label>

              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="INVALID_ACTIVITY"
                  v-model="reasonCode"
                />
                <div>
                  <div class="text-sm font-medium text-slate-900">
                    Công trình chưa hợp lệ
                  </div>
                  <div class="text-xs text-slate-500">
                    Thông tin công trình chưa chính xác hoặc chưa đầy đủ.
                  </div>
                </div>
              </label>

              <label
                class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 hover:bg-slate-50"
              >
                <input
                  type="radio"
                  class="mt-1"
                  value="NOT_ELIGIBLE"
                  v-model="reasonCode"
                />
                <div class="w-full">
                  <div class="text-sm font-medium text-slate-900">
                    Công trình chưa đủ điều kiện
                  </div>
                  <div class="text-xs text-slate-500">
                    Công trình chưa đáp ứng tiêu chí để tính giờ NCKH.
                  </div>
                </div>
              </label>

              <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <label class="text-xs font-medium text-slate-700">
                  Ghi chú chi tiết cho giảng viên (không bắt buộc)
                </label>
                <textarea
                  class="mt-2 w-full rounded-xl border border-slate-200 bg-white p-3 text-sm focus:border-slate-400 focus:ring-0"
                  rows="3"
                  :value="reasonNote"
                  placeholder="Ví dụ: thiếu biên bản nghiệm thu hoặc cần cập nhật lại tỷ lệ đóng góp..."
                  @input="
                    reasonNote = ($event.target as HTMLTextAreaElement).value
                  "
                />
              </div>
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
              {{
                decisionMode === "revision"
                  ? "Xác nhận yêu cầu chỉnh sửa"
                  : "Xác nhận từ chối hẳn"
              }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { Info, X } from "lucide-vue-next";
import type {
  HourApprovalDecisionMode,
  HourApprovalRejectReasonCode,
} from "../contracts/hourApproval.contract";

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
      decisionMode: HourApprovalDecisionMode;
    },
  ): void;
}>();

const reasonCode = ref<HourApprovalRejectReasonCode>("INVALID_EVIDENCE");
const reasonNote = ref<string>("");
const decisionMode = ref<HourApprovalDecisionMode>("revision");

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) return;
    reasonCode.value = "INVALID_EVIDENCE";
    reasonNote.value = "";
    decisionMode.value = "revision";
  },
);

function submit() {
  emit("submit", {
    reasonCode: reasonCode.value,
    reasonNote: reasonNote.value.trim() || null,
    decisionMode: decisionMode.value,
  });
}
</script>
