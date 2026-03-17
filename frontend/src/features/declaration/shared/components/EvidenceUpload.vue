<template>
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-4 md:p-6">
      <div class="text-sm font-semibold text-slate-900">Minh chứng</div>
      <div class="mt-0.5 text-xs text-slate-500">
        Tải tệp PDF và thêm link minh chứng. Dữ liệu sẽ được lưu khi bấm
        <b>Lưu nháp</b>.
      </div>

      <div class="mt-3 flex flex-col gap-2 md:flex-row md:items-center">
        <input
          type="file"
          multiple
          accept="application/pdf"
          class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800 disabled:opacity-60"
          :disabled="readOnly"
          @change="onPickFiles"
        />

        <div class="flex items-center gap-2">
          <input
            v-model.trim="linkUrl"
            type="url"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm placeholder:text-slate-400 focus:border-slate-300 focus:outline-none md:w-[320px]"
            placeholder="Thêm link minh chứng (URL)"
            :disabled="readOnly"
          />
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 hover:bg-slate-50 disabled:opacity-50"
            :disabled="readOnly || !linkUrl"
            @click="addLink"
          >
            <Plus class="h-4 w-4" />
            Thêm link
          </button>
        </div>
      </div>
    </div>

    <div class="p-4 md:p-6">
      <div class="text-xs font-semibold text-slate-600">File đã lưu</div>
      <div v-if="existingFiles.length === 0" class="mt-2 text-sm text-slate-500">
        Chưa có file đã lưu.
      </div>

        <div v-else class="mt-2 space-y-2">
          <div
            v-for="file in existingFiles"
            :key="file.id"
            class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 md:flex-row md:items-center md:justify-between"
        >
          <div class="min-w-0">
            <div class="truncate text-sm font-medium text-slate-900">
              {{ file.original_name }}
            </div>
            <div class="mt-0.5 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
                {{ file.file_type_name ?? `file_type_id=${file.file_type_id}` }}
              </span>
              <span class="text-slate-300">•</span>
              <span>{{ file.mime_type }}</span>
              <span class="text-slate-300">•</span>
              <span>{{ fmtBytes(file.size_bytes) }}</span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              :disabled="isPreviewLoading(file.id)"
              @click="previewExistingFile(file)"
            >
              {{ isPreviewLoading(file.id) ? "Đang mở..." : "Xem" }}
            </button>
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              :disabled="isDownloading(file.id)"
              @click="downloadExistingFile(file)"
            >
              {{ isDownloading(file.id) ? "Đang tải..." : "Tải xuống" }}
            </button>
            <button
              v-if="!readOnly"
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              :disabled="isDeleting(file.id)"
              @click="requestDeleteExistingFile(file)"
            >
              {{ isDeleting(file.id) ? "Đang xoá..." : "Xóa" }}
            </button>
          </div>
        </div>
      </div>

      <div class="mt-5 text-xs font-semibold text-slate-600">File vừa chọn</div>
      <div v-if="pendingFiles.length === 0" class="mt-2 text-sm text-slate-500">
        Chưa chọn file.
      </div>

      <div v-else class="mt-2 space-y-2">
        <div
          v-for="(pendingFile, idx) in pendingFiles"
          :key="idx"
          class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 md:flex-row md:items-center md:justify-between"
        >
          <div class="min-w-0">
            <div class="truncate text-sm font-medium text-slate-900">
              {{ pendingFile.file.name }}
            </div>
            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
              <span>{{ pendingFile.file.type || "application/pdf" }}</span>
              <span class="text-slate-300">•</span>
              <span>{{ fmtBytes(pendingFile.file.size) }}</span>
            </div>
          </div>

          <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <select
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="readOnly"
              :value="pendingFile.file_type_id ?? ''"
              @change="setPendingType(idx, $event)"
            >
              <option value="">— Chọn loại minh chứng —</option>
              <option v-for="fileType in fileTypeOptions" :key="fileType.id" :value="fileType.id">
                {{ fileType.name }}
              </option>
            </select>

            <button
              v-if="!readOnly"
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              @click="removePending(idx)"
            >
              Xóa
            </button>
          </div>
        </div>
      </div>

      <div class="mt-5 text-xs font-semibold text-slate-600">
        Link minh chứng đã lưu
      </div>
      <div v-if="existingLinks.length === 0" class="mt-2 text-sm text-slate-500">
        Chưa có link đã lưu.
      </div>
      <div v-else class="mt-2 space-y-2">
        <div
          v-for="link in existingLinks"
          :key="link.id"
          class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 md:flex-row md:items-center md:justify-between"
        >
          <div class="min-w-0">
            <div class="mb-1 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
                {{ link.file_type_name ?? `file_type_id=${link.file_type_id}` }}
              </span>
            </div>
            <a
              class="truncate text-sm text-slate-700 hover:underline"
              :href="link.url"
              target="_blank"
              rel="noreferrer"
            >
              {{ link.url }}
            </a>
          </div>
          <button
            v-if="!readOnly"
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
            @click="$emit('remove-existing-link', link.id)"
          >
            Xóa
          </button>
        </div>
      </div>

      <div class="mt-5 text-xs font-semibold text-slate-600">Link vừa thêm</div>
      <div v-if="pendingLinks.length === 0" class="mt-2 text-sm text-slate-500">
        Chưa có link mới.
      </div>
      <div v-else class="mt-2 space-y-2">
        <div
          v-for="(pendingLink, idx) in pendingLinks"
          :key="idx"
          class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 md:flex-row md:items-center md:justify-between"
        >
          <div class="min-w-0">
            <div class="mb-1 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
                {{ resolveTypeName(pendingLink.file_type_id) }}
              </span>
            </div>
            <a
              class="truncate text-sm text-slate-700 hover:underline"
              :href="pendingLink.url"
              target="_blank"
              rel="noreferrer"
            >
              {{ pendingLink.url }}
            </a>
          </div>
          <div class="flex items-center gap-2">
            <select
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="readOnly"
              :value="pendingLink.file_type_id ?? ''"
              @change="setPendingLinkType(idx, $event)"
            >
              <option value="">— Chọn loại link minh chứng —</option>
              <option
                v-for="fileType in linkTypeOptions"
                :key="`pending-link-type-${fileType.id}`"
                :value="fileType.id"
              >
                {{ fileType.name }}
              </option>
            </select>

            <button
              v-if="!readOnly"
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              @click="removeLink(idx)"
            >
              Xóa
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ConfirmActionModal
    v-model:open="deleteConfirmOpen"
    title="Xác nhận xoá"
    message="Bạn có chắc muốn xoá file minh chứng này không?"
    confirm-text="Xoá"
    cancel-text="Huỷ"
    loading-text="Đang xoá..."
    variant="warning"
    :loading="deleteConfirmLoading"
    @confirm="confirmDeleteExistingFile"
    @cancel="cancelDeleteExistingFile"
  />

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
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Plus } from "lucide-vue-next";
import ConfirmActionModal from "@/shared/components/modals/ConfirmActionModal.vue";
import PdfPreviewModal from "@/shared/components/modals/PdfPreviewModal.vue";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import { usePdfPreview } from "@/shared/composables/usePdfPreview";
import type {
  EvidenceFileDto,
  EvidenceFileTypeDto,
  EvidenceLinkDto,
} from "../contracts/declarationSharedContract";
import { download_evidence_file } from "../services/declarations.service";

export type PendingEvidenceFile = {
  file: File;
  file_type_id: number | null;
};

export type PendingEvidenceLink = {
  url: string;
  file_type_id: number | null;
};

const props = defineProps<{
  existingFiles: EvidenceFileDto[];
  existingLinks: EvidenceLinkDto[];
  pendingFiles: PendingEvidenceFile[];
  pendingLinks: PendingEvidenceLink[];
  fileTypes: EvidenceFileTypeDto[];
  readOnly: boolean;
  deletingFileId?: number | null;
}>();

const emit = defineEmits<{
  (e: "update:pendingFiles", v: PendingEvidenceFile[]): void;
  (e: "update:pendingLinks", v: PendingEvidenceLink[]): void;
  (e: "remove-existing", id: number): void;
  (e: "remove-existing-link", id: number): void;
}>();

const linkUrl = ref("");
const deleteConfirmOpen = ref(false);
const targetDeletingFile = ref<EvidenceFileDto | null>(null);
const downloadingFileId = ref<number | null>(null);
const { runWithFeedback } = useActionFeedback();
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
const activeDeletingFileId = computed(() => props.deletingFileId ?? null);
const fileTypeOptions = computed(() =>
  props.fileTypes.filter((type) => !type.code.includes("_link_")),
);
const linkTypeOptions = computed(() =>
  props.fileTypes.filter((type) => type.code.includes("_link_")),
);
const deleteConfirmLoading = computed(() => {
  const targetId = targetDeletingFile.value?.id ?? null;
  return targetId !== null && targetId === activeDeletingFileId.value;
});

function onPickFiles(e: Event) {
  const input = e.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  if (files.length === 0) return;

  const validPdfFiles = files.filter((file) => {
    if (file.type === "application/pdf") return true;
    return file.name.toLowerCase().endsWith(".pdf");
  });
  if (validPdfFiles.length === 0) {
    input.value = "";
    return;
  }

  const next = [
    ...props.pendingFiles,
    ...validPdfFiles.map((file) => ({ file, file_type_id: null })),
  ];
  emit("update:pendingFiles", next);
  input.value = "";
}

function setPendingType(idx: number, e: Event) {
  const value = (e.target as HTMLSelectElement).value;
  const typeId = value ? Number(value) : null;
  const next = props.pendingFiles.map((pendingFile, index) =>
    index === idx
      ? {
          ...pendingFile,
          file_type_id: Number.isFinite(typeId as number) ? (typeId as number) : null,
        }
      : pendingFile,
  );
  emit("update:pendingFiles", next);
}

function removePending(idx: number) {
  const next = props.pendingFiles.filter((_, index) => index !== idx);
  emit("update:pendingFiles", next);
}

function addLink() {
  const url = linkUrl.value.trim();
  if (!url) return;
  const next = [...props.pendingLinks, { url, file_type_id: null }];
  emit("update:pendingLinks", next);
  linkUrl.value = "";
}

function removeLink(idx: number) {
  const next = props.pendingLinks.filter((_, index) => index !== idx);
  emit("update:pendingLinks", next);
}

function setPendingLinkType(idx: number, e: Event) {
  const value = (e.target as HTMLSelectElement).value;
  const typeId = value ? Number(value) : null;
  const next = props.pendingLinks.map((pendingLink, index) =>
    index === idx
      ? {
          ...pendingLink,
          file_type_id: Number.isFinite(typeId as number) ? (typeId as number) : null,
        }
      : pendingLink,
  );
  emit("update:pendingLinks", next);
}

function resolveTypeName(fileTypeId: number | null | undefined): string {
  if (!fileTypeId) return "Chưa chọn loại link";
  const matched = props.fileTypes.find((type) => type.id === fileTypeId);
  return matched?.name ?? `file_type_id=${fileTypeId}`;
}

function requestDeleteExistingFile(file: EvidenceFileDto) {
  targetDeletingFile.value = file;
  deleteConfirmOpen.value = true;
}

function cancelDeleteExistingFile() {
  if (deleteConfirmLoading.value) return;
  deleteConfirmOpen.value = false;
  targetDeletingFile.value = null;
}

function confirmDeleteExistingFile() {
  if (!targetDeletingFile.value) return;
  emit("remove-existing", targetDeletingFile.value.id);
  deleteConfirmOpen.value = false;
  targetDeletingFile.value = null;
}

function isDeleting(fileId: number): boolean {
  return activeDeletingFileId.value === fileId;
}

function isDownloading(fileId: number): boolean {
  return downloadingFileId.value === fileId;
}

async function previewExistingFile(file: EvidenceFileDto) {
  const previewUrl =
    String(file.preview_url ?? file.url ?? "").trim() ||
    `/api/research-activities/${file.activity_id}/evidence-files/${file.id}/preview`;
  const downloadUrl =
    String(file.download_url ?? "").trim() ||
    `/api/research-activities/${file.activity_id}/evidence-files/${file.id}/download`;

  await openPdfPreview({
    cacheKey: `declaration-evidence:${file.activity_id}:${file.id}`,
    title: "Xem minh chứng",
    fallbackFileName: file.original_name || `minh-chung-${file.id}.pdf`,
    previewUrl,
    downloadUrl,
    errorMessage: "Không thể mở file minh chứng. Vui lòng thử lại.",
  });
}

function triggerBrowserDownload(blob: Blob, filename: string) {
  const objectUrl = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = objectUrl;
  link.download = filename;
  link.rel = "noopener";
  link.style.display = "none";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(objectUrl);
}

async function downloadExistingFile(file: EvidenceFileDto) {
  if (isDownloading(file.id)) return;

  downloadingFileId.value = file.id;
  try {
    const result = await runWithFeedback(
      () => download_evidence_file(file.activity_id, file.id, file.original_name),
      {
        loading: {
          title: "Đang tải file",
          message: "Vui lòng đợi trong giây lát...",
          delayMs: 450,
          minShowMs: 250,
        },
        success: {
          enabled: true,
          title: "Thành công",
          message: "Tải xuống thành công.",
        },
        error: {
          enabled: true,
          title: "Không thể tải file",
          message: "Không thể tải file minh chứng. Vui lòng thử lại.",
        },
        rethrow: true,
      },
    );

    triggerBrowserDownload(result.blob, result.filename);
  } catch {
    // Error modal is already shown by runWithFeedback.
  } finally {
    downloadingFileId.value = null;
  }
}

function fmtBytes(n: number) {
  if (n < 1024) return `${n} B`;
  const kb = n / 1024;
  if (kb < 1024) return `${kb.toFixed(1)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}

watch(
  () => props.existingFiles,
  (files) => {
    const firstPdf = files.find((file) => {
      const mime = String(file.mime_type ?? "").toLowerCase();
      return mime.includes("pdf") || String(file.original_name ?? "").toLowerCase().endsWith(".pdf");
    });
    if (!firstPdf) return;

    const previewUrl =
      String(firstPdf.preview_url ?? firstPdf.url ?? "").trim() ||
      `/api/research-activities/${firstPdf.activity_id}/evidence-files/${firstPdf.id}/preview`;
    const downloadUrl =
      String(firstPdf.download_url ?? "").trim() ||
      `/api/research-activities/${firstPdf.activity_id}/evidence-files/${firstPdf.id}/download`;

    void prefetchPdfPreview({
      cacheKey: `declaration-evidence:${firstPdf.activity_id}:${firstPdf.id}`,
      previewUrl,
      downloadUrl,
      fallbackFileName: firstPdf.original_name || `minh-chung-${firstPdf.id}.pdf`,
    });
  },
  { immediate: true, deep: true },
);
</script>
