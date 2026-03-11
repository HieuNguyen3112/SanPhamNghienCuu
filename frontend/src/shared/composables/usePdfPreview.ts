import { computed, ref } from "vue";
import http from "@/lib/http";
import {
  resolveFilenameFromHeader,
  triggerBlobDownload,
} from "@/shared/utils/fileResponse";

type PdfPreviewFetchResult = {
  blob: Blob;
  filename?: string;
};

type OpenPdfPreviewOptions = {
  cacheKey: string;
  title?: string;
  fallbackFileName: string;
  previewUrl?: string | null;
  downloadUrl?: string | null;
  fetcher?: () => Promise<PdfPreviewFetchResult>;
  errorMessage?: string;
};

type PrefetchPdfPreviewOptions = {
  cacheKey: string;
  previewUrl: string;
  fallbackFileName?: string;
  downloadUrl?: string | null;
};

type UrlCacheEntry = {
  kind: "url";
  previewUrl: string;
  downloadUrl: string | null;
  filename: string;
  lastAccessAt: number;
};

type BlobCacheEntry = {
  kind: "blob";
  previewUrl: string;
  downloadUrl: string | null;
  filename: string;
  blob: Blob;
  objectUrl: string;
  lastAccessAt: number;
};

type PdfPreviewCacheEntry = UrlCacheEntry | BlobCacheEntry;

const PDF_PREVIEW_CACHE_MAX_ITEMS = 16;
const PDF_PREVIEW_CACHE_TTL_MS = 20 * 60 * 1000;
const PREFETCH_RANGE_BYTES = 1024 * 64;
const pdfPreviewCache = new Map<string, PdfPreviewCacheEntry>();
const prefetchPromises = new Map<string, Promise<void>>();

function resolveAbsoluteUrl(rawUrl: string): string {
  const trimmed = rawUrl.trim();
  if (!trimmed) {
    throw new Error("URL không hợp lệ.");
  }

  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed;
  }

  const baseUrl = http.defaults.baseURL ?? window.location.origin;
  return new URL(trimmed, baseUrl).toString();
}

function revokeEntryObjectUrl(entry: PdfPreviewCacheEntry): void {
  if (entry.kind !== "blob") return;
  window.URL.revokeObjectURL(entry.objectUrl);
}

function prunePdfPreviewCache(exceptKey: string | null = null): void {
  const now = Date.now();
  for (const [key, entry] of pdfPreviewCache.entries()) {
    if (key === exceptKey) continue;
    if (now - entry.lastAccessAt <= PDF_PREVIEW_CACHE_TTL_MS) continue;
    revokeEntryObjectUrl(entry);
    pdfPreviewCache.delete(key);
  }

  while (pdfPreviewCache.size > PDF_PREVIEW_CACHE_MAX_ITEMS) {
    let lruKey: string | null = null;
    let lruTime = Number.POSITIVE_INFINITY;
    for (const [key, entry] of pdfPreviewCache.entries()) {
      if (key === exceptKey) continue;
      if (entry.lastAccessAt < lruTime) {
        lruKey = key;
        lruTime = entry.lastAccessAt;
      }
    }

    if (!lruKey) break;
    const lruEntry = pdfPreviewCache.get(lruKey);
    if (lruEntry) revokeEntryObjectUrl(lruEntry);
    pdfPreviewCache.delete(lruKey);
  }
}

function getCachedPreview(cacheKey: string): PdfPreviewCacheEntry | null {
  const cached = pdfPreviewCache.get(cacheKey);
  if (!cached) return null;

  if (Date.now() - cached.lastAccessAt > PDF_PREVIEW_CACHE_TTL_MS) {
    revokeEntryObjectUrl(cached);
    pdfPreviewCache.delete(cacheKey);
    return null;
  }

  cached.lastAccessAt = Date.now();
  return cached;
}

function setCachedPreview(cacheKey: string, entry: PdfPreviewCacheEntry): void {
  pdfPreviewCache.set(cacheKey, entry);
  prunePdfPreviewCache(cacheKey);
}

function looksLikePdf(blob: Blob): boolean {
  const type = blob.type.trim().toLowerCase();
  if (!type) return true;
  return type.includes("pdf");
}

export function usePdfPreview() {
  const previewOpen = ref(false);
  const previewLoading = ref(false);
  const previewError = ref<string | null>(null);
  const previewTitle = ref("Xem minh chứng");
  const previewFileName = ref("minh-chung.pdf");
  const previewUrl = ref<string | null>(null);
  const activeCacheKey = ref<string | null>(null);
  const activeDownloadUrl = ref<string | null>(null);
  const lastOpenOptions = ref<OpenPdfPreviewOptions | null>(null);

  const canDownload = computed(() => {
    if (activeDownloadUrl.value) return true;
    if (!activeCacheKey.value) return false;
    const cached = getCachedPreview(activeCacheKey.value);
    return Boolean(cached?.previewUrl);
  });

  async function openPdfPreview(options: OpenPdfPreviewOptions): Promise<void> {
    lastOpenOptions.value = options;
    previewOpen.value = true;
    previewLoading.value = true;
    previewError.value = null;
    previewTitle.value = options.title?.trim() || "Xem minh chứng";
    previewFileName.value = options.fallbackFileName.trim() || "minh-chung.pdf";
    activeCacheKey.value = options.cacheKey;
    activeDownloadUrl.value = null;

    prunePdfPreviewCache(options.cacheKey);

    const cached = getCachedPreview(options.cacheKey);
    if (cached) {
      previewUrl.value = cached.previewUrl;
      previewFileName.value = cached.filename;
      activeDownloadUrl.value = cached.downloadUrl;
      previewLoading.value = false;
      return;
    }

    try {
    if (options.previewUrl?.trim()) {
      const resolvedPreviewUrl = resolveAbsoluteUrl(options.previewUrl);
      const resolvedDownloadUrl = options.downloadUrl?.trim()
        ? resolveAbsoluteUrl(options.downloadUrl)
        : null;
        const filename = options.fallbackFileName.trim() || "minh-chung.pdf";

      setCachedPreview(options.cacheKey, {
        kind: "url",
        previewUrl: resolvedPreviewUrl,
        downloadUrl: resolvedDownloadUrl,
        filename,
        lastAccessAt: Date.now(),
      });

      previewUrl.value = resolvedPreviewUrl;
      previewFileName.value = filename;
      activeDownloadUrl.value = resolvedDownloadUrl;
      previewLoading.value = false;
      previewError.value = null;
      return;
    }

      if (!options.fetcher) {
        throw new Error("Thiếu thông tin preview.");
      }

      const fetched = await options.fetcher();
      if (!looksLikePdf(fetched.blob)) {
        throw new Error("Tệp không phải định dạng PDF hợp lệ.");
      }

      const resolvedFileName =
        fetched.filename?.trim() ||
        options.fallbackFileName.trim() ||
        "minh-chung.pdf";
      const objectUrl = window.URL.createObjectURL(fetched.blob);

      setCachedPreview(options.cacheKey, {
        kind: "blob",
        previewUrl: objectUrl,
        downloadUrl: null,
        filename: resolvedFileName,
        blob: fetched.blob,
        objectUrl,
        lastAccessAt: Date.now(),
      });

      previewUrl.value = objectUrl;
      previewFileName.value = resolvedFileName;
      activeDownloadUrl.value = null;
      previewError.value = null;
    } catch {
      previewUrl.value = null;
      previewError.value =
        options.errorMessage?.trim() ||
        "Không thể mở file minh chứng. Vui lòng thử lại.";
    } finally {
      previewLoading.value = false;
    }
  }

  async function retryOpenPdfPreview(): Promise<void> {
    if (!lastOpenOptions.value) return;
    await openPdfPreview(lastOpenOptions.value);
  }

  function closePdfPreview(): void {
    previewOpen.value = false;
  }

  function isPreviewLoading(cacheKey: string | number): boolean {
    return previewLoading.value && activeCacheKey.value === String(cacheKey);
  }

  async function downloadPreviewedPdf(): Promise<void> {
    if (!activeCacheKey.value) return;
    const cached = getCachedPreview(activeCacheKey.value);
    if (!cached) return;

    if (cached.kind === "blob") {
      triggerBlobDownload(cached.blob, cached.filename);
      return;
    }

    const targetUrl = activeDownloadUrl.value || cached.previewUrl;
    const response = await http.get<Blob>(targetUrl, {
      responseType: "blob",
    });
    const resolvedName = resolveFilenameFromHeader(
      String(response.headers?.["content-disposition"] ?? ""),
      previewFileName.value || cached.filename,
    );
    triggerBlobDownload(response.data, resolvedName);
  }

  async function prefetchPdfPreview(options: PrefetchPdfPreviewOptions): Promise<void> {
    const cached = getCachedPreview(options.cacheKey);
    if (cached) return;
    if (prefetchPromises.has(options.cacheKey)) {
      await prefetchPromises.get(options.cacheKey);
      return;
    }

    const prefetchTask = (async () => {
      try {
        const resolvedPreviewUrl = resolveAbsoluteUrl(options.previewUrl);
        const resolvedDownloadUrl = options.downloadUrl?.trim()
          ? resolveAbsoluteUrl(options.downloadUrl)
          : null;
        const filename = options.fallbackFileName?.trim() || "minh-chung.pdf";

        await http.get<ArrayBuffer>(resolvedPreviewUrl, {
          responseType: "arraybuffer",
          headers: {
            Range: `bytes=0-${PREFETCH_RANGE_BYTES - 1}`,
          },
        });

        setCachedPreview(options.cacheKey, {
          kind: "url",
          previewUrl: resolvedPreviewUrl,
          downloadUrl: resolvedDownloadUrl,
          filename,
          lastAccessAt: Date.now(),
        });
      } catch {
        // Prefetch best-effort: bỏ qua lỗi để không ảnh hưởng luồng chính.
      }
    })();

    prefetchPromises.set(options.cacheKey, prefetchTask);
    await prefetchTask;
    prefetchPromises.delete(options.cacheKey);
  }

  return {
    previewOpen,
    previewLoading,
    previewError,
    previewTitle,
    previewFileName,
    previewUrl,
    activeCacheKey,
    canDownload,
    openPdfPreview,
    retryOpenPdfPreview,
    closePdfPreview,
    isPreviewLoading,
    downloadPreviewedPdf,
    prefetchPdfPreview,
  };
}
