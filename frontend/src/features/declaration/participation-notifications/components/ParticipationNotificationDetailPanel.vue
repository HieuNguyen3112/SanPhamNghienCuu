<template>
  <Teleport to="body">
    <!-- Overlay -->
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
        class="fixed inset-0 z-40 bg-slate-900/20"
        aria-hidden="true"
        @click="emit('close')"
      />
    </Transition>

    <!-- Panel -->
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
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[640px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <!-- Header -->
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900">
                  Chi tiết yêu cầu xác nhận
                </div>
                <div
                  v-if="notification"
                  class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500"
                >
                  <span
                    class="rounded-md bg-slate-100 px-2 py-1 text-slate-700"
                  >
                    {{ typeLabel(notification.workType) }}
                  </span>
                  <span class="text-slate-300">•</span>
                  <span>{{ statusLabel(notification.status) }}</span>
                </div>
              </div>

              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto p-4">
            <div
              v-if="!notification"
              class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-600"
            >
              Chọn một thông báo để xem chi tiết.
            </div>

            <template v-else>
              <!-- Card: Work info -->
              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">
                      Thông tin công trình
                    </div>
                    <div class="mt-2 text-base font-semibold text-slate-900">
                      {{ notification.workTitle }}
                    </div>
                    <div class="mt-1 text-sm text-slate-600">
                      {{ notification.workShortInfo }}
                    </div>
                  </div>

                  <div class="shrink-0">
                    <span
                      class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-semibold"
                      :class="statusBadgeClass(notification.status)"
                    >
                      {{ statusLabel(notification.status) }}
                    </span>
                  </div>
                </div>

                <div
                  class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2"
                >
                  <div>
                    <span class="text-slate-500">Loại:</span>
                    <span class="ml-1 font-medium text-slate-900">{{
                      typeLabel(notification.workType)
                    }}</span>
                  </div>
                  <div>
                    <span class="text-slate-500">Trạng thái công trình:</span>
                    <span class="ml-1 font-medium text-slate-900">{{
                      notification.workSystemStatus
                    }}</span>
                  </div>
                  <div>
                    <span class="text-slate-500">Người kê khai:</span>
                    <span class="ml-1 font-medium text-slate-900">{{
                      notification.ownerName
                    }}</span>
                  </div>
                  <div>
                    <span class="text-slate-500">Ngày yêu cầu:</span>
                    <span class="ml-1 font-medium text-slate-900">{{
                      formatDateTime(notification.requestedAt)
                    }}</span>
                  </div>
                </div>
              </div>

              <!-- Card: Members -->
              <div
                class="mt-4 rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Danh sách thành viên
                </div>

                <div
                  class="mt-3 overflow-hidden rounded-xl border border-slate-200"
                >
                  <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-600">
                      <tr>
                        <th class="px-3 py-2 font-semibold">Họ tên</th>
                        <th class="px-3 py-2 font-semibold">Đơn vị</th>
                        <th class="px-3 py-2 font-semibold">Vai trò</th>
                        <th class="px-3 py-2 font-semibold">Trạng thái</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                      <tr
                        v-for="m in notification.members"
                        :key="m.id"
                        :class="
                          m.isCurrentUser
                            ? 'bg-amber-50/40'
                            : 'hover:bg-slate-50'
                        "
                      >
                        <td class="px-3 py-2">
                          <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-900">{{
                              m.fullName
                            }}</span>
                            <span
                              v-if="m.isCurrentUser"
                              class="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800"
                            >
                              Bạn
                            </span>
                          </div>
                        </td>
                        <td class="px-3 py-2 text-slate-700">{{ m.unit }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ m.role }}</td>
                        <td class="px-3 py-2">
                          <span
                            class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-semibold"
                            :class="statusBadgeClass(m.status)"
                          >
                            {{ statusLabel(m.status) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Card: Evidence -->
              <div
                class="mt-4 rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Minh chứng
                </div>

                <div
                  v-if="notification.evidences.length"
                  class="mt-3 space-y-2"
                >
                  <div
                    v-for="ev in notification.evidences"
                    :key="ev.id"
                    class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                  >
                    <div class="flex min-w-0 items-center gap-2">
                      <FileText
                        v-if="ev.type === 'FILE'"
                        class="h-4 w-4 text-slate-500"
                      />
                      <Link2 v-else class="h-4 w-4 text-slate-500" />
                      <div class="min-w-0">
                        <div
                          class="truncate text-sm font-medium text-slate-900"
                        >
                          {{ ev.label }}
                        </div>
                        <div class="truncate text-xs text-slate-500">
                          {{ ev.url }}
                        </div>
                      </div>
                    </div>

                    <template v-if="ev.type === 'LINK'">
                      <a
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                        :href="ev.url"
                        target="_blank"
                        rel="noreferrer"
                      >
                        <ExternalLink class="h-4 w-4" />
                        Mở link
                      </a>
                    </template>
                    <template v-else>
                      <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isEvidenceLoading(ev.id) || props.processing"
                        @click="openEvidenceFile(ev)"
                      >
                        <ExternalLink class="h-4 w-4" />
                        {{ isEvidenceLoading(ev.id) ? "Đang mở..." : "Xem" }}
                      </button>
                    </template>
                  </div>
                </div>

                <div v-else class="mt-2 text-sm text-slate-500">
                  Chưa có minh chứng.
                </div>
              </div>

              <!-- Card: Note -->
              <div
                class="mt-4 rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Ghi chú từ người kê khai
                </div>
                <p
                  v-if="notification.noteFromOwner?.trim()"
                  class="mt-2 text-sm text-slate-700"
                >
                  {{ notification.noteFromOwner }}
                </p>
                <p v-else class="mt-2 text-sm text-slate-500">
                  Không có ghi chú.
                </p>
              </div>

              <!-- Reject reason -->
              <div
                v-if="isRejecting && notification.status === 'PENDING'"
                class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4"
              >
                <div class="text-sm font-semibold text-rose-900">
                  Lý do từ chối <span class="text-rose-600">*</span>
                </div>
                <textarea
                  v-model.trim="rejectReason"
                  rows="3"
                  class="mt-2 w-full resize-none rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-slate-900 focus:border-rose-300 focus:outline-none"
                  placeholder="Nhập lý do từ chối..."
                />
                <div
                  v-if="rejectError"
                  class="mt-1 text-xs font-medium text-rose-700"
                >
                  {{ rejectError }}
                </div>

                <div class="mt-3 flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    :disabled="props.processing"
                    @click="cancelReject"
                  >
                    Hủy
                  </button>
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-rose-700"
                    :disabled="props.processing"
                    @click="confirmReject"
                  >
                    <XCircle class="h-4 w-4" />
                    {{
                      props.processing ? "Đang xử lý..." : "Xác nhận từ chối"
                    }}
                  </button>
                </div>
              </div>
            </template>
          </div>

          <!-- Footer actions (sticky) -->
          <div class="border-t border-slate-200 bg-white p-4">
            <div
              v-if="notification && notification.status === 'PENDING'"
              class="flex items-center justify-between gap-2"
            >
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-700 shadow-sm hover:bg-rose-50"
                :disabled="isRejecting || props.processing"
                @click="startReject"
              >
                <X class="h-4 w-4" />
                {{ props.processing ? "Đang xử lý..." : "Từ chối tham gia" }}
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700"
                :disabled="isRejecting || props.processing"
                @click="emit('accept')"
              >
                <Check class="h-4 w-4" />
                {{ props.processing ? "Đang xử lý..." : "Xác nhận tham gia" }}
              </button>
            </div>

            <div
              v-else-if="notification && notification.status !== 'PENDING'"
              class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
            >
              <template v-if="notification.confirmationLog">
                <div
                  v-if="notification.confirmationLog.status === 'ACCEPTED'"
                  class="flex items-start gap-2"
                >
                  <CheckCircle2 class="mt-0.5 h-4 w-4 text-emerald-700" />
                  <div>
                    <div class="font-medium text-slate-900">
                      Bạn đã xác nhận tham gia.
                    </div>
                    <div class="text-xs text-slate-500">
                      {{
                        formatDateTime(notification.confirmationLog.confirmedAt)
                      }}
                    </div>
                  </div>
                </div>

                <div v-else class="flex items-start gap-2">
                  <XCircle class="mt-0.5 h-4 w-4 text-rose-700" />
                  <div class="min-w-0">
                    <div class="font-medium text-slate-900">
                      Bạn đã từ chối tham gia.
                    </div>
                    <div class="text-xs text-slate-500">
                      {{
                        formatDateTime(notification.confirmationLog.confirmedAt)
                      }}
                    </div>
                    <div
                      v-if="notification.confirmationLog.reason?.trim()"
                      class="mt-1 text-sm text-slate-700"
                    >
                      <span class="text-slate-500">Lý do:</span>
                      <span class="ml-1">{{
                        notification.confirmationLog.reason
                      }}</span>
                    </div>
                  </div>
                </div>
              </template>

              <template v-else>
                <div class="text-slate-600">
                  Yêu cầu đã được xử lý trước đó.
                </div>
              </template>
            </div>

            <div v-else class="text-sm text-slate-500">
              Chọn một thông báo để xem chi tiết.
            </div>
          </div>
        </div>
      </aside>
    </Transition>

    <PdfPreviewModal
      :open="previewOpen"
      :title="previewTitle"
      :file-name="previewFileName"
      :preview-url="previewUrl"
      :loading="previewLoading"
      :error-message="previewError"
      :can-download="canDownload"
      @close="closePdfPreview"
      @retry="retryOpenPdfPreview"
      @download="downloadPreviewedPdf"
    />
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
  Check,
  CheckCircle2,
  ExternalLink,
  FileText,
  Link2,
  X,
  XCircle,
} from "lucide-vue-next";
import PdfPreviewModal from "@/shared/components/modals/PdfPreviewModal.vue";
import { usePdfPreview } from "@/shared/composables/usePdfPreview";
import { formatBackendDateTimeVi } from "@/shared/utils/backendDateTime";

type WorkType = "ARTICLE" | "PROJECT" | "BOOK" | "CONFERENCE";
type NotificationStatus = "PENDING" | "ACCEPTED" | "REJECTED";
type EvidenceType = "FILE" | "LINK";

interface ParticipationEvidence {
  id: number;
  type: EvidenceType;
  label: string;
  url: string;
  previewUrl?: string | null;
  downloadUrl?: string | null;
}

interface ParticipationMember {
  id: number;
  fullName: string;
  unit: string;
  role: string;
  status: NotificationStatus;
  isCurrentUser: boolean;
}

interface ParticipationNotification {
  id: number;
  workTitle: string;
  workType: WorkType;
  yourRole: string;
  ownerName: string;
  requestedAt: string;
  status: NotificationStatus;
  workShortInfo: string;
  noteFromOwner?: string;

  confirmationLog?: {
    status: "ACCEPTED" | "REJECTED";
    confirmedAt: string;
    reason?: string;
  };

  workSystemStatus: string;
  members: ParticipationMember[];
  evidences: ParticipationEvidence[];
}

const props = defineProps<{
  open: boolean;
  notification: ParticipationNotification | null;
  processing?: boolean;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "accept"): void;
  (e: "reject", reason: string): void;
}>();

const isRejecting = ref(false);
const rejectReason = ref("");
const rejectError = ref<string | null>(null);
const {
  previewOpen,
  previewLoading,
  previewError,
  previewTitle,
  previewFileName,
  previewUrl,
  canDownload,
  openPdfPreview,
  retryOpenPdfPreview,
  closePdfPreview,
  isPreviewLoading,
  downloadPreviewedPdf,
  prefetchPdfPreview,
} = usePdfPreview();

watch(
  () => props.open,
  (v) => {
    if (!v) {
      isRejecting.value = false;
      rejectReason.value = "";
      rejectError.value = null;
    }
  },
);

watch(
  () => props.notification?.id ?? null,
  () => {
    isRejecting.value = false;
    rejectReason.value = "";
    rejectError.value = null;
  },
);

const notification = computed(() => props.notification);

function isEvidenceLoading(evidenceId: number): boolean {
  return isPreviewLoading(`participation-evidence:${evidenceId}`);
}

async function openEvidenceFile(ev: ParticipationEvidence): Promise<void> {
  if (ev.type !== "FILE") {
    return;
  }

  const previewRawUrl = (ev.previewUrl ?? ev.url).trim();
  if (!previewRawUrl) return;
  const downloadRawUrl = (ev.downloadUrl ?? ev.url).trim();
  const fallbackFileName = ev.label.trim() || `minh-chung-${ev.id}.pdf`;

  await openPdfPreview({
    cacheKey: `participation-evidence:${ev.id}`,
    title: "Xem minh chứng",
    fallbackFileName,
    previewUrl: previewRawUrl,
    downloadUrl: downloadRawUrl,
    errorMessage: "Không thể mở file minh chứng. Vui lòng thử lại.",
  });
}

watch(
  () => [props.open, props.notification?.id, props.notification?.evidences],
  ([isOpen]) => {
    if (!isOpen) return;
    const firstFile = (props.notification?.evidences ?? []).find(
      (item) => item.type === "FILE",
    );
    if (!firstFile) return;
    const previewRawUrl = (firstFile.previewUrl ?? firstFile.url).trim();
    if (!previewRawUrl) return;
    const downloadRawUrl = (firstFile.downloadUrl ?? firstFile.url).trim();

    void prefetchPdfPreview({
      cacheKey: `participation-evidence:${firstFile.id}`,
      previewUrl: previewRawUrl,
      downloadUrl: downloadRawUrl,
      fallbackFileName:
        firstFile.label.trim() || `minh-chung-${firstFile.id}.pdf`,
    });
  },
  { immediate: true, deep: true },
);

function typeLabel(t: WorkType) {
  if (t === "ARTICLE") return "Bài báo";
  if (t === "PROJECT") return "Đề tài";
  if (t === "BOOK") return "Sách";
  return "Hội thảo";
}

function statusLabel(s: NotificationStatus) {
  if (s === "PENDING") return "Chờ xác nhận";
  if (s === "ACCEPTED") return "Đã xác nhận";
  return "Đã từ chối";
}

function statusBadgeClass(s: NotificationStatus) {
  if (s === "PENDING") return "bg-amber-50 text-amber-700";
  if (s === "ACCEPTED") return "bg-emerald-50 text-emerald-700";
  return "bg-rose-50 text-rose-700";
}

function formatDateTime(iso: string) {
  return formatBackendDateTimeVi(iso);
}

function startReject() {
  if (props.processing) return;
  rejectError.value = null;
  isRejecting.value = true;
  rejectReason.value = "";
}

function cancelReject() {
  isRejecting.value = false;
  rejectReason.value = "";
  rejectError.value = null;
}

function confirmReject() {
  if (props.processing) return;
  const reason = rejectReason.value.trim();
  if (!reason) {
    rejectError.value = "Vui lòng nhập lý do từ chối.";
    return;
  }
  rejectError.value = null;
  emit("reject", reason);
  isRejecting.value = false;
  rejectReason.value = "";
}
</script>
