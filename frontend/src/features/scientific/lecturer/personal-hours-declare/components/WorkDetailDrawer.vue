<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-slate-900/30"
        aria-hidden="true"
        @click="emit('close')"
      />
    </Transition>

    <Transition
      enter-active-class="transition-transform duration-250 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200 sm:w-[520px] lg:w-[640px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900">
                  Chi tiết công trình
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  Theo dõi công thức tính giờ tự động, quản lý minh chứng và
                  trạng thái duyệt giờ NCKH.
                </div>
              </div>
            </div>
          </div>

          <div class="flex-1 overflow-auto px-4 py-4">
            <div v-if="loading" class="text-sm text-slate-700">
              Đang tải chi tiết...
            </div>

            <div
              v-else-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4"
            >
              <div class="text-sm font-medium text-rose-700">
                Không tải được chi tiết
              </div>
              <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
                {{ error }}
              </div>
            </div>

            <div v-else-if="!detail" class="text-sm text-slate-700">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-4">
              <div
                class="rounded-2xl border bg-white p-4"
                :class="
                  highlightActivitySection
                    ? 'border-amber-300 ring-1 ring-amber-100'
                    : 'border-slate-200'
                "
              >
                <div class="text-sm font-semibold text-slate-900">
                  {{ detail.title }}
                </div>
                <div class="mt-2 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div class="flex items-center gap-2 text-slate-700">
                    <BookOpen class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Loại:</span>
                    <span class="font-medium">{{ detail.kindName }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-slate-700">
                    <Calendar class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Năm học:</span>
                    <span class="font-medium">{{
                      detail.academicYearCode
                    }}</span>
                  </div>

                  <div
                    class="flex items-center gap-2 text-slate-700 md:col-span-2"
                  >
                    <Building2 class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500"
                      >Đơn vị / nơi công bố:</span
                    >
                    <span class="font-medium">{{
                      detail.publicationOrUnit
                    }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-slate-700">
                    <User class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Vai trò:</span>
                    <span class="font-medium">{{ detail.memberRoleName }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-slate-700">
                    <Percent class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Tỷ lệ:</span>
                    <span class="font-medium">
                      {{
                        detail.contributionShare == null
                          ? "—"
                          : `${Math.round(detail.contributionShare * 100)}%`
                      }}
                    </span>
                  </div>
                </div>
              </div>

              <div
                class="rounded-2xl border bg-white p-4"
                :class="
                  highlightHoursSection
                    ? 'border-amber-300 ring-1 ring-amber-100'
                    : 'border-slate-200'
                "
              >
                <div class="text-sm font-semibold text-slate-900">
                  Giờ NCKH tính tự động
                </div>

                <div class="mt-3 flex items-center gap-2">
                  <span
                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                    :class="hoursPillClass(detail)"
                  >
                    <Check
                      v-if="detail.hoursRequestState === 'hours_approved'"
                      class="mr-1 h-4 w-4"
                    />
                    {{ hoursStatusLabel(detail) }}
                  </span>
                </div>

                <div
                  v-if="
                    detail.hoursRequestState === 'hours_rejected' ||
                    detail.hoursRequestState === 'hours_need_revision'
                  "
                  class="mt-3 rounded-xl border p-3 text-xs"
                  :class="
                    detail.hoursRequestState === 'hours_need_revision'
                      ? 'border-blue-200 bg-blue-50 text-blue-800'
                      : 'border-rose-200 bg-rose-50 text-rose-700'
                  "
                >
                  <div class="font-semibold">
                    {{
                      detail.hoursRequestState === "hours_need_revision"
                        ? "Yêu cầu chỉnh sửa từ khoa"
                        : "Lý do từ chối"
                    }}
                  </div>
                  <div v-if="detail.hoursRejectionReasonCode" class="mt-1">
                    {{ rejectionReasonLabel }}
                  </div>
                  <div v-if="rejectionReasonText" class="mt-1">
                    {{ rejectionReasonText }}
                  </div>
                  <div
                    v-if="detail.hoursRequestState === 'hours_need_revision'"
                    class="mt-2 rounded-lg border border-blue-200 bg-white/80 px-2.5 py-2"
                  >
                    {{ revisionActionHint }}
                  </div>
                </div>

                <div
                  class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3"
                >
                  <div class="text-xs text-slate-500">
                    Giờ quy đổi (tự động)
                  </div>
                  <div class="mt-1 text-xl font-semibold text-slate-900">
                    {{ formatHours(detail.calculatedHours) }} giờ
                  </div>
                </div>

                <div
                  v-if="detail.calculatedHours === null"
                  class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900"
                >
                  <div class="flex items-start gap-2">
                    <AlertTriangle class="mt-0.5 h-4 w-4" />
                    <span v-if="detail.conversionRulePresent">
                      Hệ thống chưa tính được giờ quy đổi tự động cho công trình
                      này. Bạn chưa thể gửi duyệt giờ.
                    </span>
                    <span v-else>
                      Chưa có quy tắc quy đổi cho công trình này. Vui lòng liên
                      hệ Phòng quản lý khoa học để cấu hình trước khi gửi duyệt
                      giờ.
                    </span>
                  </div>
                </div>

                <div
                  class="mt-4 rounded-xl border border-slate-200 bg-white p-3"
                >
                  <div class="text-xs text-slate-500">Giờ dùng để duyệt</div>
                  <div class="mt-1 text-xl font-semibold text-slate-900">
                    {{ formatHours(detail.effectiveHoursDisplay) }} giờ
                  </div>
                </div>

                <div
                  class="mt-4 rounded-xl border border-slate-200 bg-white p-3"
                >
                  <div class="text-xs font-semibold text-slate-700">
                    Công thức tính giờ
                  </div>
                  <div class="mt-2 text-xs text-slate-600">
                    {{
                      detail.formulaExplanation?.ruleName ??
                      "Chưa có quy tắc quy đổi"
                    }}
                  </div>
                  <div
                    class="mt-2 grid grid-cols-1 gap-2 text-xs text-slate-700 sm:grid-cols-2"
                  >
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                      <span class="text-slate-500">Giờ gốc:</span>
                      <span class="ml-1 font-medium text-slate-900">
                        {{
                          formatHours(
                            detail.formulaExplanation?.baseHours ?? null,
                          )
                        }}
                        giờ
                      </span>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                      <span class="text-slate-500">Tổng giờ công trình:</span>
                      <span class="ml-1 font-medium text-slate-900">
                        {{
                          formatHours(
                            detail.totalHoursActivity ??
                              detail.formulaExplanation?.totalHoursActivity ??
                              null,
                          )
                        }}
                        giờ
                      </span>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2 sm:col-span-2">
                      <span class="text-slate-500">Giờ của bạn:</span>
                      <span class="ml-1 font-medium text-slate-900">
                        {{
                          formatHours(
                            detail.memberHours ??
                              detail.formulaExplanation?.memberHours ??
                              detail.effectiveHoursDisplay,
                          )
                        }}
                        giờ
                      </span>
                      <span
                        v-if="
                          detail.formulaExplanation?.memberSharePercent != null
                        "
                        class="ml-2 text-slate-500"
                      >
                        ({{ detail.formulaExplanation.memberSharePercent }}%)
                      </span>
                    </div>
                  </div>
                  <ul
                    v-if="
                      (detail.formulaExplanation?.modifiers?.length ?? 0) > 0
                    "
                    class="mt-2 space-y-1 text-xs text-slate-600"
                  >
                    <li
                      v-for="modifier in detail.formulaExplanation?.modifiers ??
                      []"
                      :key="`${modifier.name}-${modifier.value}`"
                    >
                      {{ modifier.name }}: {{ modifier.value }}
                    </li>
                  </ul>
                </div>
              </div>

              <div
                class="rounded-2xl border bg-white p-4"
                :class="
                  highlightEvidenceSection
                    ? 'border-amber-300 ring-1 ring-amber-100'
                    : 'border-slate-200'
                "
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="text-sm font-semibold text-slate-900">
                    Minh chứng duyệt giờ
                  </div>
                  <span class="text-xs text-slate-500"
                    >{{ evidenceFiles.length }} tệp</span
                  >
                </div>

                <div
                  v-if="detail.hoursRequestState === 'hours_approved'"
                  class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600"
                >
                  Hồ sơ đã được duyệt giờ, không thể chỉnh sửa minh chứng.
                </div>

                <div
                  v-else
                  class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
                >
                  <div
                    class="grid grid-cols-1 gap-3 lg:grid-cols-[220px_minmax(0,1fr)_auto] lg:items-end"
                  >
                    <div class="min-w-0 space-y-1">
                      <label class="text-xs font-medium text-slate-600"
                        >Loại minh chứng</label
                      >
                      <select
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm focus:border-slate-400 focus:ring-0"
                        :value="selectedEvidenceTypeId ?? ''"
                        :disabled="loadingEvidenceTypes || drawerActionsLocked"
                        @change="onChangeEvidenceType"
                      >
                        <option value="">Chọn loại minh chứng</option>
                        <option
                          v-for="type in evidenceFileTypes"
                          :key="type.id"
                          :value="type.id"
                        >
                          {{ type.name }}
                        </option>
                      </select>
                      <div
                        v-if="evidenceTypeError"
                        class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
                      >
                        {{ evidenceTypeError }}
                      </div>
                    </div>

                    <div class="min-w-0 space-y-1">
                      <label class="text-xs font-medium text-slate-600"
                        >Tệp minh chứng (PDF)</label
                      >
                      <input
                        :key="evidenceFileInputResetKey"
                        ref="evidenceFileInputRef"
                        type="file"
                        accept="application/pdf,.pdf"
                        class="sr-only"
                        :disabled="drawerActionsLocked"
                        @change="onPickEvidenceFile"
                      />
                      <div class="flex min-w-0 items-center gap-2">
                        <button
                          type="button"
                          class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-slate-100 px-4 text-sm font-semibold text-slate-800 hover:bg-slate-200 disabled:opacity-50"
                          :disabled="drawerActionsLocked"
                          @click="triggerEvidenceFilePicker"
                        >
                          Chọn tệp
                        </button>
                        <span class="min-w-0 truncate text-xs text-slate-600">
                          {{ selectedEvidenceFile?.name ?? "Chưa chọn tệp" }}
                        </span>
                      </div>
                      <div
                        v-if="evidenceFileError"
                        class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
                      >
                        {{ evidenceFileError }}
                      </div>
                      <div
                        v-else-if="duplicateEvidenceWarning"
                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
                      >
                        {{ duplicateEvidenceWarning }}
                      </div>
                    </div>

                    <button
                      type="button"
                      class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white disabled:opacity-50"
                      :disabled="!canUploadEvidence"
                      @click="emit('upload-evidence')"
                    >
                      <Upload class="h-4 w-4" />
                      {{ uploadingEvidence ? "Đang tải..." : "Tải minh chứng" }}
                    </button>
                  </div>

                  <div
                    v-if="evidenceError"
                    class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
                  >
                    {{ evidenceError }}
                  </div>
                  <div
                    v-if="uploadRequestError"
                    class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
                  >
                    {{ uploadRequestError }}
                  </div>
                </div>

                <div v-if="loadingEvidence" class="mt-3 text-sm text-slate-600">
                  Đang tải minh chứng...
                </div>

                <div
                  v-else-if="evidenceFiles.length === 0"
                  class="mt-3 rounded-xl border border-dashed border-slate-300 p-3 text-sm text-slate-500"
                >
                  Chưa có minh chứng nào.
                </div>

                <ul v-else class="mt-3 space-y-2">
                  <li
                    v-for="file in evidenceFiles"
                    :key="file.id"
                    class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3"
                  >
                    <div class="min-w-0">
                      <div class="truncate text-sm font-medium text-slate-900">
                        {{ file.originalName }}
                      </div>
                      <div class="mt-0.5 text-xs text-slate-500">
                        {{ file.fileTypeName ?? `Loại #${file.fileTypeId}` }} •
                        {{ formatBytes(file.sizeBytes) }} •
                        {{ formatDate(file.uploadedAt) }}
                      </div>
                    </div>

                    <div class="flex items-center gap-2">
                      <a
                        v-if="file.downloadUrl"
                        :href="file.downloadUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs text-slate-700 hover:bg-slate-50"
                      >
                        <Download class="h-3.5 w-3.5" />
                        Tải xuống
                      </a>

                      <button
                        v-if="detail.hoursRequestState !== 'hours_approved'"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-xs text-rose-700 hover:bg-rose-100 disabled:opacity-50"
                        :disabled="drawerActionsLocked"
                        @click="emit('request-delete-evidence', file.id)"
                      >
                        <Trash2 class="h-3.5 w-3.5" />
                        {{
                          deletingEvidenceId === file.id ? "Đang xóa..." : "Xóa"
                        }}
                      </button>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="border-t border-slate-200 px-4 py-3">
            <button
              type="button"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
              @click="emit('close')"
            >
              Đóng
            </button>
          </div>
        </div>
      </aside>
    </Transition>

    <ConfirmActionModal
      :open="deleteEvidenceConfirmOpen"
      title="Xác nhận xóa minh chứng"
      :message="deleteEvidenceConfirmMessage"
      confirm-text="Xóa minh chứng"
      cancel-text="Hủy"
      loading-text="Đang xóa..."
      variant="danger"
      :loading="deletingEvidenceId !== null"
      @confirm="emit('confirm-delete-evidence')"
      @cancel="emit('cancel-delete-evidence')"
      @update:open="
        (value) => {
          if (!value) emit('cancel-delete-evidence');
        }
      "
    />
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, toRefs } from "vue";
import {
  AlertTriangle,
  BookOpen,
  Building2,
  Calendar,
  Check,
  Download,
  Percent,
  Trash2,
  Upload,
  User,
} from "lucide-vue-next";
import ConfirmActionModal from "@/shared/components/modals/ConfirmActionModal.vue";
import type {
  EvidenceFile,
  EvidenceFileType,
  WorkDetail,
} from "../contracts/selectHoursRequest.contract";
import {
  formatBytes,
  formatHours,
  hoursRejectReasonActionHint,
  hoursRejectReasonLabel,
  normalizeHoursRejectReasonCode,
} from "../contracts/selectHoursRequest.contract";

const props = defineProps<{
  open: boolean;
  detail: WorkDetail | null;
  loading: boolean;
  error: string | null;
  evidenceFiles: EvidenceFile[];
  evidenceFileTypes: EvidenceFileType[];
  selectedEvidenceTypeId: number | null;
  selectedEvidenceFile: File | null;
  evidenceFileInputResetKey: number;
  loadingEvidence: boolean;
  loadingEvidenceTypes: boolean;
  evidenceError: string | null;
  evidenceTypeError: string | null;
  evidenceFileError: string | null;
  uploadRequestError: string | null;
  duplicateEvidenceWarning: string | null;
  uploadingEvidence: boolean;
  deletingEvidenceId: number | null;
  drawerActionsLocked: boolean;
  canUploadEvidence: boolean;
  deleteEvidenceConfirmOpen: boolean;
  deleteEvidenceConfirmMessage: string;
}>();

const {
  open,
  detail,
  loading,
  error,
  evidenceFiles,
  evidenceFileTypes,
  selectedEvidenceTypeId,
  selectedEvidenceFile,
  evidenceFileInputResetKey,
  loadingEvidence,
  loadingEvidenceTypes,
  evidenceError,
  evidenceTypeError,
  evidenceFileError,
  uploadRequestError,
  duplicateEvidenceWarning,
  uploadingEvidence,
  deletingEvidenceId,
  drawerActionsLocked,
  canUploadEvidence,
  deleteEvidenceConfirmOpen,
  deleteEvidenceConfirmMessage,
} = toRefs(props);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "update:evidenceTypeId", value: number | null): void;
  (e: "update:evidenceFile", files: FileList | null): void;
  (e: "upload-evidence"): void;
  (e: "request-delete-evidence", evidenceId: number): void;
  (e: "confirm-delete-evidence"): void;
  (e: "cancel-delete-evidence"): void;
}>();

const evidenceFileInputRef = ref<HTMLInputElement | null>(null);
const normalizedReasonCode = computed(() =>
  normalizeHoursRejectReasonCode(detail.value?.hoursRejectionReasonCode),
);

const rejectionReasonLabel = computed(() =>
  hoursRejectReasonLabel(normalizedReasonCode.value),
);

const rejectionReasonText = computed(() => {
  return (
    detail.value?.hoursRejectionReasonDetail ??
    detail.value?.hoursRejectionReason ??
    null
  );
});

const revisionActionHint = computed(() =>
  hoursRejectReasonActionHint(normalizedReasonCode.value),
);

const highlightEvidenceSection = computed(
  () => normalizedReasonCode.value === "INVALID_EVIDENCE",
);

const highlightHoursSection = computed(
  () => normalizedReasonCode.value === "INVALID_HOURS",
);

const highlightActivitySection = computed(
  () =>
    normalizedReasonCode.value === "INVALID_ACTIVITY" ||
    normalizedReasonCode.value === "NOT_ELIGIBLE",
);

function hoursStatusLabel(detail: WorkDetail) {
  if (detail.hoursRequestState === "hours_approved") return "Đã duyệt";
  if (detail.hoursRequestState === "hours_pending_faculty")
    return "Chờ khoa duyệt";
  if (detail.hoursRequestState === "hours_need_revision")
    return "Cần chỉnh sửa";
  if (detail.hoursRequestState === "hours_rejected") return "Bị từ chối";
  return "Chưa gửi duyệt";
}

function hoursPillClass(detail: WorkDetail) {
  if (detail.hoursRequestState === "hours_approved")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (detail.hoursRequestState === "hours_pending_faculty")
    return "bg-amber-50 text-amber-700 ring-amber-200";
  if (detail.hoursRequestState === "hours_need_revision")
    return "bg-blue-50 text-blue-700 ring-blue-200";
  if (detail.hoursRequestState === "hours_rejected")
    return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-slate-50 text-slate-700 ring-slate-200";
}

function onChangeEvidenceType(event: Event) {
  const raw = (event.target as HTMLSelectElement).value;
  if (!raw) {
    emit("update:evidenceTypeId", null);
    return;
  }

  const parsed = Number(raw);
  emit("update:evidenceTypeId", Number.isFinite(parsed) ? parsed : null);
}

function onPickEvidenceFile(event: Event) {
  const input = event.target as HTMLInputElement;
  emit("update:evidenceFile", input.files ?? null);
}

function triggerEvidenceFilePicker() {
  if (!evidenceFileInputRef.value) return;
  evidenceFileInputRef.value.value = "";
  evidenceFileInputRef.value.click();
}

function formatDate(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  const dd = String(date.getDate()).padStart(2, "0");
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const yyyy = date.getFullYear();
  const hh = String(date.getHours()).padStart(2, "0");
  const min = String(date.getMinutes()).padStart(2, "0");

  return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
}
</script>
