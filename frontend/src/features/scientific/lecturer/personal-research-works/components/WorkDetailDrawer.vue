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
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[540px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900">
                  {{ work ? work.title : "Chi tiết công trình" }}
                </div>

                <div class="mt-1 flex items-center gap-2">
                  <span
                    v-if="work"
                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                    :class="badgeClass(work.statusCode)"
                  >
                    {{ work.statusName }}
                  </span>

                  <!-- <span v-if="work" class="text-xs text-slate-500">
                    {{ work.activityCode }}
                  </span> -->
                </div>
              </div>

              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                ×
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-4">
            <div
              v-if="loading"
              class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
            >
              Đang tải chi tiết...
            </div>

            <div
              v-else-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
            >
              {{ error }}
            </div>

            <div v-else-if="!work" class="text-sm text-slate-600">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-5">
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Thông tin chung
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <InfoRow label="Loại công trình" :value="work.kindName" />
                  <InfoRow label="Phân loại" :value="work.typeName ?? '—'" />
                  <InfoRow label="Vai trò" :value="work.roleName ?? '—'" />
                  <InfoRow
                    label="Năm"
                    :value="work.workYear ? String(work.workYear) : '—'"
                  />
                  <InfoRow
                    wrapper-class="md:col-span-2"
                    label="Nơi công bố/đơn vị"
                    :value="work.venueName ?? '—'"
                  />

                  <InfoRow
                    label="Ngày gửi"
                    :value="formatDateTime(work.submittedAt)"
                  />
                  <InfoRow
                    label="Ngày duyệt"
                    :value="formatDateTime(work.approvedAt)"
                  />

                  <InfoRow
                    v-if="work.statusCode === 'approved'"
                    label="Giờ NCKH của bạn"
                    :value="work.lecturerHours ?? '—'"
                  />
                </div>

                <div
                  v-if="work.statusCode === 'rejected' && work.rejectionNote"
                  class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
                >
                  <div class="text-xs font-semibold">Lý do từ chối</div>
                  <div class="mt-1">{{ work.rejectionNote }}</div>
                </div>

                <div
                  v-if="work.statusCode === 'member_rejected'"
                  class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                >
                  <div class="text-xs font-semibold text-amber-900">
                    Thành viên đã từ chối tham gia
                  </div>
                  <p class="mt-1">
                    Bạn cần xóa/thay thế thành viên bị từ chối hoặc gửi lại yêu
                    cầu xác nhận trước khi gửi lên khoa.
                  </p>
                </div>
              </section>

              <section
                v-if="work.statusCode === 'member_rejected'"
                class="rounded-xl border border-rose-200 bg-rose-50 p-4"
              >
                <div class="text-xs font-semibold text-rose-700">
                  Danh sách thành viên từ chối
                </div>

                <div
                  v-if="work.rejectedMembers.length === 0"
                  class="mt-3 text-sm text-rose-700"
                >
                  Chưa có chi tiết thành viên từ chối.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="member in work.rejectedMembers"
                    :key="member.memberId"
                    class="rounded-lg border border-rose-200 bg-white p-3"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <div class="font-semibold text-slate-900">
                          {{ member.lecturerFullName }}
                          <span class="text-slate-500"
                            >({{ member.lecturerCode ?? "N/A" }})</span
                          >
                        </div>
                        <div class="mt-0.5 text-xs text-slate-600">
                          Vai trò: {{ member.memberRoleName ?? "—" }}
                        </div>
                        <div class="mt-1 text-xs text-rose-700">
                          Lý do:
                          {{ member.confirmationNote || "Không có ghi chú" }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                          Thời điểm phản hồi:
                          {{ formatDateTime(member.respondedAt) }}
                        </div>
                      </div>

                      <button
                        type="button"
                        class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        @click="emit('reinvite-member', member.memberId)"
                      >
                        Gửi lại yêu cầu
                      </button>
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Danh sách tác giả
                </div>

                <div
                  v-if="work.authors.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Chưa có tác giả.
                </div>

                <div
                  v-else
                  class="mt-3 overflow-hidden rounded-lg border border-slate-200"
                >
                  <table class="w-full text-left text-sm">
                    <thead
                      class="bg-slate-50 text-xs font-semibold text-slate-600"
                    >
                      <tr>
                        <th class="px-3 py-2">Tác giả</th>
                        <th class="px-3 py-2">Vai trò</th>
                        <th class="px-3 py-2">Đơn vị</th>
                      </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                      <tr
                        v-for="author in work.authors"
                        :key="author.lecturerId"
                      >
                        <td class="px-3 py-2 font-medium text-slate-900">
                          {{ author.lecturerFullName }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ author.memberRoleName }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ author.departmentName ?? "—" }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Minh chứng
                </div>

                <div
                  v-if="work.evidenceItems.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Chưa có minh chứng.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="item in work.evidenceItems"
                    :key="item.evidenceFileId"
                    class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:bg-slate-50"
                  >
                    <div class="min-w-0">
                      <div
                        class="truncate text-sm font-semibold text-slate-900"
                      >
                        {{ item.originalName }}
                      </div>
                      <div class="mt-1 text-xs text-slate-500">
                        {{ item.fileTypeName }}
                        <span class="px-1 text-slate-300">•</span>
                        {{ item.mimeType }}
                        <span class="px-1 text-slate-300">•</span>
                        {{ formatBytes(item.sizeBytes) }}
                      </div>
                      <div class="mt-1 text-xs text-slate-400">
                        Uploaded: {{ formatDateTime(item.uploadedAt) }}
                      </div>
                    </div>

                    <button
                      type="button"
                      class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="isEvidenceLoading(item.evidenceFileId)"
                      @click="openEvidenceFile(item)"
                    >
                      {{
                        isEvidenceLoading(item.evidenceFileId)
                          ? "Đang mở..."
                          : "Mở"
                      }}
                    </button>
                  </div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Trạng thái và lịch sử
                </div>

                <div class="mt-3 space-y-3">
                  <TimelineItem
                    label="Gửi duyệt"
                    :value="formatDateTime(work.submittedAt)"
                    :actor-name="submitterName"
                    :note="null"
                    :status="null"
                  />

                  <TimelineItem
                    v-for="approval in work.approvals"
                    :key="approval.stageCode"
                    :label="`Xét duyệt ${approval.stageName}`"
                    :value="
                      approval.decidedAt
                        ? formatDateTime(approval.decidedAt)
                        : '—'
                    "
                    :actor-name="approval.decidedByUserName"
                    :note="approval.note"
                    :status="approval.status"
                  />

                  <TimelineItem
                    v-if="work.statusCode === 'approved'"
                    label="Phê duyệt"
                    :value="formatDateTime(work.approvedAt)"
                    :actor-name="approvalActorName"
                    :note="null"
                    status="approved"
                  />

                  <TimelineItem
                    v-if="work.statusCode === 'rejected'"
                    label="Từ chối"
                    :value="formatDateTime(rejectedActedAt)"
                    :actor-name="rejectedActorName"
                    :note="work.rejectionNote"
                    status="rejected"
                  />
                </div>
              </section>
            </div>
          </div>

          <div class="border-t border-slate-200 px-4 py-3">
            <div class="flex items-center justify-between gap-2">
              <button
                v-if="work && work.actions.canEdit"
                type="button"
                class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                @click="emit('edit-draft', work.activityId)"
              >
                {{ editActionLabel(work) }}
              </button>
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
import { computed, watch } from "vue";
import type {
  PersonalWorkEvidence,
  PersonalWorkDetail,
  PersonalWorkStatusCode,
} from "../contracts/personalResearchWorksContracts";
import InfoRow from "@/features/scientific/lecturer/personal-research-works/components/InfoRow.vue";
import TimelineItem from "@/features/scientific/lecturer/personal-research-works/components/TimelineItem.vue";
import PdfPreviewModal from "@/shared/components/modals/PdfPreviewModal.vue";
import { usePdfPreview } from "@/shared/composables/usePdfPreview";

const props = defineProps<{
  open: boolean;
  loading: boolean;
  error: string | null;
  work: PersonalWorkDetail | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "edit-draft", workId: number): void;
  (e: "reinvite-member", memberId: number): void;
}>();
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

const rejectedActedAt = computed<string | null>(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  const histories = currentWork.statusHistories;
  for (let index = histories.length - 1; index >= 0; index -= 1) {
    const history = histories[index];
    if (!history) continue;
    if (history.toStatusCode === "rejected") return history.actedAt;
  }
  return null;
});

const submitterName = computed<string | null>(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  const submittedHistory = currentWork.statusHistories.find(
    (history) => history.toStatusCode === "submitted" || history.toStatusCode === "pending_faculty_review"
  );
  return submittedHistory?.actedByUserName ?? null;
});

const approvalActorName = computed<string | null>(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  for (let index = currentWork.statusHistories.length - 1; index >= 0; index -= 1) {
    const history = currentWork.statusHistories[index];
    if (history?.toStatusCode === "approved") return history.actedByUserName;
  }

  for (let index = currentWork.approvals.length - 1; index >= 0; index -= 1) {
    const approval = currentWork.approvals[index];
    if (approval?.status === "approved" && approval.decidedByUserName) {
      return approval.decidedByUserName;
    }
  }
  return null;
});

const rejectedActorName = computed<string | null>(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  for (let index = currentWork.statusHistories.length - 1; index >= 0; index -= 1) {
    const history = currentWork.statusHistories[index];
    if (history?.toStatusCode === "rejected") return history.actedByUserName;
  }

  for (let index = currentWork.approvals.length - 1; index >= 0; index -= 1) {
    const approval = currentWork.approvals[index];
    if (approval?.status === "rejected" && approval.decidedByUserName) {
      return approval.decidedByUserName;
    }
  }
  return null;
});

function badgeClass(statusCode: PersonalWorkStatusCode): string {
  switch (statusCode) {
    case "approved":
      return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
    case "pending_member_confirm":
    case "pending_faculty_review":
    case "submitted":
      return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    case "member_rejected":
    case "rejected":
      return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
    case "draft":
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
    default:
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
  }
}

function editActionLabel(work: PersonalWorkDetail): string {
  if (work.statusCode === "member_rejected") return "Chỉnh sửa thành viên";
  if (work.statusCode === "rejected") return "Mở lại để chỉnh sửa";
  return "Chỉnh sửa công trình";
}

function formatDateTime(value: string | null): string {
  if (!value) return "—";
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString();
}

function formatBytes(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes <= 0) return "0 B";
  const units = ["B", "KB", "MB", "GB", "TB"] as const;
  let size = bytes;
  let unitIndex = 0;
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }
  return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function isEvidenceLoading(evidenceId: number): boolean {
  return isPreviewLoading(`personal-work-evidence:${evidenceId}`);
}

async function openEvidenceFile(item: PersonalWorkEvidence): Promise<void> {
  const fallbackUrl = buildEvidenceUrl(item.disk, item.path);
  const previewRawUrl = (
    item.previewUrl ??
    item.downloadUrl ??
    fallbackUrl
  ).trim();
  if (!previewRawUrl) return;

  if (item.disk === "url" && /^https?:\/\//i.test(previewRawUrl)) {
    window.open(previewRawUrl, "_blank", "noopener,noreferrer");
    return;
  }

  const downloadRawUrl = (item.downloadUrl ?? fallbackUrl).trim();
  const fallbackFileName =
    item.originalName?.trim() || `minh-chung-${item.evidenceFileId}.pdf`;

  await openPdfPreview({
    cacheKey: `personal-work-evidence:${item.evidenceFileId}`,
    title: "Xem minh chứng",
    fallbackFileName,
    previewUrl: previewRawUrl,
    downloadUrl: downloadRawUrl,
    errorMessage: "Không thể mở file minh chứng. Vui lòng thử lại.",
  });
}

function buildEvidenceUrl(disk: string, path: string): string {
  void disk;
  return path.startsWith("http") ? path : `/${path.replace(/^\/+/, "")}`;
}

watch(
  () => [props.open, props.work?.activityId, props.work?.evidenceItems],
  ([isOpen]) => {
    if (!isOpen) return;
    const firstFile = (props.work?.evidenceItems ?? []).find(
      (item) => item.disk !== "url",
    );
    if (!firstFile) return;

    const fallbackUrl = buildEvidenceUrl(firstFile.disk, firstFile.path);
    const previewRawUrl = (
      firstFile.previewUrl ??
      firstFile.downloadUrl ??
      fallbackUrl
    ).trim();
    if (!previewRawUrl) return;
    const downloadRawUrl = (firstFile.downloadUrl ?? fallbackUrl).trim();

    void prefetchPdfPreview({
      cacheKey: `personal-work-evidence:${firstFile.evidenceFileId}`,
      previewUrl: previewRawUrl,
      downloadUrl: downloadRawUrl,
      fallbackFileName:
        firstFile.originalName?.trim() ||
        `minh-chung-${firstFile.evidenceFileId}.pdf`,
    });
  },
  { immediate: true, deep: true },
);
</script>
