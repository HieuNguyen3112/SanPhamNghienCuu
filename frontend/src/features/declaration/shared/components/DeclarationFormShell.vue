<template>
  <div class="space-y-4">
    <div
      class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <component :is="icon" v-if="icon" class="h-5 w-5 text-slate-700" />
            <h1 class="truncate text-lg font-semibold text-slate-900">
              {{ title }}
            </h1>
          </div>
          <p class="mt-0.5 text-sm text-slate-500">{{ description }}</p>
        </div>

        <div class="flex items-center gap-2">
          <DeclarationStatusBadge :status="status" />
        </div>
      </div>
    </div>

    <div
      v-if="$slots.intro"
      class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
    >
      <slot name="intro" :read-only="readOnly" />
    </div>

    <div class="space-y-4">
      <slot :read-only="readOnly" />
    </div>

    <div
      v-if="errorMessage"
      class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="status === 'MEMBER_REJECTED'"
      class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
    >
      Có thành viên đã từ chối. Bạn cần xóa/thay thế hoặc gửi lại yêu cầu xác
      nhận trước khi gửi lên khoa.
    </div>

    <div
      class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
    >
      <div
        class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
      >
        <div class="text-sm text-slate-600">
          <span class="font-medium text-slate-900">Trạng thái:</span>
          <span class="ml-1">{{ statusText }}</span>
          <span v-if="submittedAt" class="ml-2 text-slate-400">
            • Gửi: {{ formatDateTime(submittedAt) }}
          </span>
          <span v-if="approvedAt" class="ml-2 text-slate-400">
            • Duyệt: {{ formatDateTime(approvedAt) }}
          </span>
        </div>

        <div class="flex items-center justify-end gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow-sm hover:bg-slate-50 disabled:opacity-50"
            :disabled="pending || readOnly"
            @click="$emit('save-draft')"
          >
            <Save class="h-4 w-4" />
            Lưu bản nháp
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800 disabled:opacity-50"
            :disabled="pending || !canSubmitNow"
            @click="openSubmitClassificationModal"
          >
            <Send class="h-4 w-4" />
            Gửi duyệt
          </button>
        </div>
      </div>

      <div
        v-if="readOnly"
        class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600"
      >
        Bản kê khai đang ở trạng thái
        <span class="font-semibold">{{ statusText }}</span>
        nên đang ở chế độ chỉ xem.
      </div>
    </div>

    <div
      v-if="submitClassificationModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4"
      @click.self="closeSubmitClassificationModal"
    >
      <div
        class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-4 shadow-xl md:p-5"
      >
        <div class="text-base font-semibold text-slate-900">
          Phân loại lần gửi duyệt
        </div>
        <p class="mt-1 text-sm text-slate-600">
          Chọn loại thay đổi để hệ thống quyết định có cần gửi lại xác nhận cho
          tác giả hay không.
        </p>

        <div class="mt-4 space-y-2">
          <label
            class="flex cursor-pointer gap-3 rounded-xl border px-3 py-3"
            :class="
              submitMode === 'major'
                ? 'border-slate-300 bg-slate-50'
                : 'border-slate-200 bg-white'
            "
          >
            <input
              v-model="submitMode"
              value="major"
              type="radio"
              class="mt-0.5"
            />
            <div>
              <div class="text-sm font-semibold text-slate-900">
                Thay đổi lớn
              </div>
              <div class="mt-1 text-xs text-slate-600">
                Có chỉnh sửa đáng kể (đặc biệt liên quan tác giả/thành viên). Hệ
                thống có thể yêu cầu xác nhận lại.
              </div>
            </div>
          </label>

          <label
            class="flex cursor-pointer gap-3 rounded-xl border px-3 py-3"
            :class="
              submitMode === 'minor'
                ? 'border-slate-300 bg-slate-50'
                : 'border-slate-200 bg-white'
            "
          >
            <input
              v-model="submitMode"
              value="minor"
              type="radio"
              class="mt-0.5"
            />
            <div>
              <div class="text-sm font-semibold text-slate-900">
                Thay đổi nhỏ
              </div>
              <div class="mt-1 text-xs text-slate-600">
                Chỉ chỉnh sửa nội dung nhỏ, không thay đổi ý nghĩa đóng góp của
                tác giả.
              </div>
            </div>
          </label>
        </div>

        <div class="mt-4 flex items-center justify-end gap-2">
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="closeSubmitClassificationModal"
          >
            Hủy
          </button>
          <button
            type="button"
            class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
            @click="confirmSubmitWithClassification"
          >
            Xác nhận gửi duyệt
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Save, Send } from "lucide-vue-next";
import DeclarationStatusBadge from "./DeclarationStatusBadge.vue";
import type { DeclarationStatusUi } from "../contracts/declarationSharedContract";
import { formatBackendDateTimeVi } from "@/shared/utils/backendDateTime";

const props = defineProps<{
  title: string;
  description: string;
  icon?: any;
  status: DeclarationStatusUi;
  canSubmit: boolean;
  participantCount?: number;
  pending: boolean;
  errorMessage?: string | null;
  submittedAt?: string | null;
  approvedAt?: string | null;
  successVisible?: boolean;
  successMessage?: string | null;
}>();

function isEditableStatus(status: DeclarationStatusUi): boolean {
  return (
    status === "DRAFT" ||
    status === "MEMBER_REJECTED" ||
    status === "NEED_REVISION"
  );
}

const readOnly = computed(() => !isEditableStatus(props.status));
const canSubmitNow = computed(() => !readOnly.value && props.canSubmit);
const shouldShowSubmitClassification = computed(
  () => props.status === "NEED_REVISION" && (props.participantCount ?? 0) > 1,
);
const submitClassificationModalOpen = ref(false);
const submitMode = ref<"major" | "minor">(
  props.status === "NEED_REVISION" ? "minor" : "major",
);

const emit = defineEmits<{
  (e: "save-draft"): void;
  (e: "submit", payload: { minorChange: boolean }): void;
  (e: "close-success"): void;
}>();

watch(
  () => props.status,
  (status) => {
    submitMode.value = status === "NEED_REVISION" ? "minor" : "major";
    if (status !== "NEED_REVISION") {
      submitClassificationModalOpen.value = false;
    }
  },
);

function openSubmitClassificationModal() {
  if (!canSubmitNow.value || props.pending) return;

  if (!shouldShowSubmitClassification.value) {
    emit("submit", { minorChange: false });
    return;
  }

  submitClassificationModalOpen.value = true;
}

function closeSubmitClassificationModal() {
  submitClassificationModalOpen.value = false;
}

function confirmSubmitWithClassification() {
  emit("submit", { minorChange: submitMode.value === "minor" });
  submitClassificationModalOpen.value = false;
}

const statusText = computed(() => {
  switch (props.status) {
    case "DRAFT":
      return "Bản nháp";
    case "PENDING_MEMBER_CONFIRM":
      return "Chờ thành viên xác nhận";
    case "MEMBER_REJECTED":
      return "Thành viên từ chối";
    case "PENDING_FACULTY_REVIEW":
      return "Chờ khoa duyệt";
    case "NEED_REVISION":
      return "Cần chỉnh sửa theo yêu cầu khoa";
    case "APPROVED":
      return "Được duyệt";
    case "REJECTED":
      return "Bị từ chối";
  }
});

function formatDateTime(iso: string) {
  return formatBackendDateTimeVi(iso);
}
</script>
