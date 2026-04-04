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
        v-if="isOpen"
        class="fixed inset-0 z-40 bg-slate-900/20"
        aria-hidden="true"
        @click="emitClose"
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
        v-if="isOpen"
        class="fixed right-0 top-0 z-50 h-full w-full max-w-[860px] overflow-hidden bg-white shadow-2xl ring-1 ring-slate-200"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 p-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span
                    class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700"
                  >
                    Đã duyệt
                  </span>
                  <div class="text-xs text-slate-500">
                    {{ detail ? detail.academicYearCode : "" }}
                  </div>
                </div>
                <div
                  class="mt-2 truncate text-base font-semibold text-slate-900"
                >
                  {{ detail?.title ?? "Chi tiết công trình" }}
                </div>
                <div class="mt-1 text-sm text-slate-600">
                  {{ detail?.typeName ?? detail?.kindName ?? "-" }}
                  <span class="text-slate-300">•</span>
                  <span
                    >Đã duyệt:
                    {{ detail ? formatDate(detail.approvedAt) : "-" }}</span
                  >
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="rounded-lg bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                  @click="emitBack"
                >
                  <ArrowLeftToLine />
                </button>
              </div>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-5">
            <div v-if="isLoading" class="text-sm text-slate-600">
              Đang tải chi tiết...
            </div>

            <div
              v-else-if="errorMessage"
              class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
              {{ errorMessage }}
            </div>

            <div v-else-if="!detail" class="text-sm text-slate-600">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-6">
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Thông tin chung
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Loại công trình
                    </div>
                    <div class="mt-1 text-slate-900">{{ detail.kindName }}</div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Phân loại
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ detail.typeName ?? "—" }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Vai trò
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ detail.memberRoleName ?? "—" }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">Năm</div>
                    <div class="mt-1 text-slate-900">
                      {{ detail.workYear ?? "—" }}
                    </div>
                  </div>

                  <div class="md:col-span-2">
                    <div class="text-xs font-semibold text-slate-500">
                      Nơi công bố/đơn vị
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ detail.venueName ?? "—" }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Ngày gửi
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ formatDate(detail.submittedAt) }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Ngày duyệt
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ formatDate(detail.approvedAt) }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Giờ NCKH của giảng viên
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ detail.lecturerHours ?? "—" }}
                    </div>
                  </div>
                </div>
              </section>

              <section
                v-for="workDetailSection in workDetailSections"
                :key="workDetailSection.code"
                class="rounded-xl border border-slate-200 bg-white p-4"
              >
                <div class="text-sm font-semibold text-slate-900">
                  {{ workDetailSection.title }}
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div
                    v-for="detailField in workDetailSection.fields"
                    :key="`${workDetailSection.code}-${detailField.key}`"
                    :class="{
                      'md:col-span-2': shouldSpanTwoColumns(
                        detailField.key,
                        detailField.value,
                      ),
                    }"
                  >
                    <div class="text-xs font-semibold text-slate-500">
                      {{ detailField.label }}
                    </div>

                    <a
                      v-if="isLinkFieldValue(detailField.value)"
                      :href="String(detailField.value)"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="mt-1 inline-flex break-all text-sky-700 hover:underline"
                    >
                      {{ String(detailField.value) }}
                    </a>
                    <div v-else class="mt-1 whitespace-pre-wrap text-slate-900">
                      {{ formatDetailFieldValue(detailField.value) }}
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">Tóm tắt</div>
                <div class="mt-2 text-sm text-slate-700">
                  <div v-if="detail.abstract" class="whitespace-pre-wrap">
                    {{ detail.abstract }}
                  </div>
                  <div v-else class="text-slate-500">Không có tóm tắt.</div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">Tác giả</div>
                <div
                  v-if="detail.authors.length === 0"
                  class="mt-2 text-sm text-slate-500"
                >
                  Chưa có thông tin tác giả.
                </div>
                <ul v-else class="mt-2 space-y-2">
                  <li
                    v-for="author in detail.authors"
                    :key="author.memberId"
                    class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"
                  >
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-slate-900">
                        {{ author.lecturerFullName }}
                      </div>
                      <div class="text-xs text-slate-500">
                        {{ author.memberRoleName }}
                      </div>
                    </div>
                    <div class="text-xs text-slate-600">
                      {{
                        author.contributionShare
                          ? `Tỉ lệ: ${author.contributionShare}`
                          : "-"
                      }}
                    </div>
                  </li>
                </ul>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Minh chứng
                </div>

                <div class="mt-3">
                  <div class="text-xs font-semibold text-slate-600">
                    Liên kết
                  </div>
                  <div class="mt-2 space-y-2">
                    <div
                      v-for="item in linkEvidenceItems"
                      :key="item.evidenceFileId"
                      class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"
                    >
                      <div class="min-w-0">
                        <div
                          class="truncate text-sm font-medium text-slate-900"
                        >
                          {{ item.originalName }}
                        </div>
                        <div class="text-xs text-slate-500">
                          {{ item.fileTypeName }}
                        </div>
                      </div>
                      <a
                        class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        :href="item.path"
                        target="_blank"
                        rel="noreferrer"
                      >
                        Mở link
                      </a>
                    </div>

                    <div
                      v-if="linkEvidenceItems.length === 0"
                      class="text-sm text-slate-500"
                    >
                      Không có liên kết.
                    </div>
                  </div>
                </div>

                <div class="mt-4">
                  <div class="text-xs font-semibold text-slate-600">Tệp</div>
                  <div class="mt-2 space-y-2">
                    <div
                      v-for="item in fileEvidenceItems"
                      :key="item.evidenceFileId"
                      class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"
                    >
                      <div class="min-w-0">
                        <div
                          class="truncate text-sm font-medium text-slate-900"
                        >
                          {{ item.originalName }}
                        </div>
                        <div class="text-xs text-slate-500">
                          {{ item.fileTypeName }} •
                          {{ formatFileSize(item.sizeBytes) }}
                        </div>
                      </div>
                      <button
                        type="button"
                        class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isEvidenceLoading(item.evidenceFileId)"
                        @click="openEvidenceUrl(item)"
                      >
                        {{
                          isEvidenceLoading(item.evidenceFileId)
                            ? "Đang mở..."
                            : "Xem"
                        }}
                      </button>
                    </div>

                    <div
                      v-if="fileEvidenceItems.length === 0"
                      class="text-sm text-slate-500"
                    >
                      {{ emptyEvidenceMessage }}
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Phê duyệt cuối
                </div>

                <div
                  v-if="!detail.finalApproval"
                  class="mt-2 text-sm text-slate-500"
                >
                  Chưa có dữ liệu phê duyệt cuối.
                </div>

                <div v-else class="mt-2 space-y-1 text-sm text-slate-700">
                  <div>
                    <span class="text-slate-500">Người duyệt:</span>
                    <span class="font-medium text-slate-900">
                      {{ detail.finalApproval.decidedByUserName ?? "-" }}
                    </span>
                  </div>
                  <div>
                    <span class="text-slate-500">Thời gian:</span>
                    <span class="font-medium text-slate-900">
                      {{
                        detail.finalApproval.decidedAt
                          ? formatDate(detail.finalApproval.decidedAt)
                          : "-"
                      }}
                    </span>
                  </div>
                  <div
                    v-if="detail.finalApproval.note"
                    class="pt-2 text-slate-600"
                  >
                    Ghi chú: {{ detail.finalApproval.note }}
                  </div>
                </div>
              </section>
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
  ApprovedDetail,
  Evidence,
  WorkDetailField,
} from "../lecturerResearchWork.contracts";
import { ArrowLeftToLine } from "lucide-vue-next";
import PdfPreviewModal from "@/shared/components/modals/PdfPreviewModal.vue";
import { usePdfPreview } from "@/shared/composables/usePdfPreview";
import { parseBackendDateTime } from "../../shared/utils/backendDateTime";

interface DetailDrawerProps {
  isOpen: boolean;
  detail: ApprovedDetail | null;
  isLoading: boolean;
  errorMessage: string | null;
}

interface DetailDrawerEmits {
  (e: "back"): void;
  (e: "close"): void;
}
const detailEmptyEvidenceMessage = "Chưa có minh chứng cho công trình này.";

const props = defineProps<DetailDrawerProps>();
const emit = defineEmits<DetailDrawerEmits>();
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

const emptyEvidenceMessage = detailEmptyEvidenceMessage;

const linkEvidenceItems = computed(() => {
  return (props.detail?.evidenceItems ?? []).filter(
    (item) => item.disk === "url",
  );
});

const fileEvidenceItems = computed(() => {
  return (props.detail?.evidenceItems ?? []).filter(
    (item) => item.disk !== "url",
  );
});

const workDetailSections = computed(() => {
  return props.detail?.workDetail?.sections ?? [];
});

function emitBack() {
  emit("back");
}

function emitClose() {
  emit("close");
}

function formatDate(iso: string | null) {
  if (!iso) return "—";
  const date = parseBackendDateTime(iso);
  if (!date) return iso;
  return date.toLocaleString("vi-VN");
}

function formatFileSize(sizeBytes: number | null) {
  if (!Number.isFinite(sizeBytes) || (sizeBytes ?? 0) <= 0) return "0 B";
  const units = ["B", "KB", "MB", "GB"];
  let value = sizeBytes as number;
  let unitIndex = 0;
  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024;
    unitIndex += 1;
  }
  return `${value.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function formatDetailFieldValue(value: WorkDetailField["value"]): string {
  if (value === null || value === undefined) return "—";
  if (typeof value === "boolean") return value ? "Có" : "Không";
  const text = String(value).trim();
  return text === "" ? "—" : text;
}

function isLinkFieldValue(value: WorkDetailField["value"]): boolean {
  if (typeof value !== "string") return false;
  const normalized = value.trim().toLowerCase();
  return normalized.startsWith("http://") || normalized.startsWith("https://");
}

function shouldSpanTwoColumns(
  key: string,
  value: WorkDetailField["value"],
): boolean {
  const wideFieldKeys = new Set([
    "objectives",
    "content_summary",
    "main_results",
    "keywords",
    "application_address",
    "article_url",
    "journal_website",
  ]);
  if (wideFieldKeys.has(key)) return true;
  if (typeof value !== "string") return false;
  return value.trim().length > 70;
}

function isEvidenceLoading(evidenceId: number): boolean {
  return isPreviewLoading(`approved-work-evidence:${evidenceId}`);
}

async function openEvidenceUrl(item: Evidence) {
  if (item.disk === "url") {
    window.open(item.path, "_blank", "noreferrer");
    return;
  }

  const previewRawUrl = (
    item.previewUrl ??
    item.downloadUrl ??
    item.path
  ).trim();
  if (!previewRawUrl) return;
  const downloadRawUrl = (item.downloadUrl ?? item.path).trim();
  const fallbackFileName =
    item.originalName?.trim() || `minh-chung-${item.evidenceFileId}.pdf`;

  await openPdfPreview({
    cacheKey: `approved-work-evidence:${item.evidenceFileId}`,
    title: "Xem minh chứng",
    fallbackFileName,
    previewUrl: previewRawUrl,
    downloadUrl: downloadRawUrl,
    errorMessage: "Không thể mở file minh chứng. Vui lòng thử lại.",
  });
}

watch(
  () => [props.isOpen, props.detail?.activityId, props.detail?.evidenceItems],
  ([isOpen]) => {
    if (!isOpen) return;
    const firstFile = (props.detail?.evidenceItems ?? []).find(
      (item) => item.disk !== "url",
    );
    if (!firstFile) return;

    const previewRawUrl = (
      firstFile.previewUrl ??
      firstFile.downloadUrl ??
      firstFile.path
    ).trim();
    if (!previewRawUrl) return;
    const downloadRawUrl = (firstFile.downloadUrl ?? firstFile.path).trim();

    void prefetchPdfPreview({
      cacheKey: `approved-work-evidence:${firstFile.evidenceFileId}`,
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
