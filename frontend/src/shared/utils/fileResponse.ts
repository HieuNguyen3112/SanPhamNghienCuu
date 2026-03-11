export function decodeContentDispositionFilename(headerValue: string): string | null {
  const value = headerValue.trim();
  if (!value) return null;

  const utf8Match = value.match(/filename\*=UTF-8''([^;]+)/i);
  if (utf8Match?.[1]) {
    const encodedName = utf8Match[1].trim().replace(/^"(.*)"$/, "$1");
    try {
      const decoded = decodeURIComponent(encodedName);
      if (decoded.trim()) return decoded;
    } catch {
      // Fallback to plain filename parsing below.
    }
  }

  const plainMatch = value.match(/filename="?([^";]+)"?/i);
  if (plainMatch?.[1] && plainMatch[1].trim()) {
    return plainMatch[1].trim();
  }

  return null;
}

export function resolveFilenameFromHeader(headerValue: string, fallback: string): string {
  const decoded = decodeContentDispositionFilename(headerValue);
  const safeFallback = fallback.trim();
  if (decoded?.trim()) return decoded.trim();
  if (safeFallback) return safeFallback;
  return "minh-chung.pdf";
}

export function triggerBlobDownload(blob: Blob, filename: string): void {
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

