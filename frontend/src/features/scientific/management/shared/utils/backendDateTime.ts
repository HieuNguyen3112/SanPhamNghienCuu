export function parseBackendDateTime(
  rawDateTime: string | null | undefined,
): Date | null {
  if (!rawDateTime) return null;

  const value = String(rawDateTime).trim();
  if (!value) return null;

  // MySQL DATETIME format without timezone: treat as UTC.
  if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d+)?$/.test(value)) {
    const parsed = new Date(value.replace(" ", "T") + "Z");
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  // ISO-like datetime without timezone: treat as UTC.
  if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?$/.test(value)) {
    const parsed = new Date(value + "Z");
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  // Date-only string: normalize to UTC midnight for stable display.
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    const parsed = new Date(`${value}T00:00:00Z`);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  // Already includes timezone info (Z/+07:00/...) or browser-supported format.
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

export function toBackendDateTimeTimestamp(
  rawDateTime: string | null | undefined,
): number {
  return parseBackendDateTime(rawDateTime)?.getTime() ?? 0;
}
