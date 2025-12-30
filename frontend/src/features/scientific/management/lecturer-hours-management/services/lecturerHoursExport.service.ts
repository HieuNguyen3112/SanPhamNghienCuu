import http from "@/lib/http";

export type ExportHoursParams = {
  faculty_id?: number | null;
  academic_year_id?: number | null;
  kpi_status?: string;
  q?: string;
};

export type ExportResult = {
  blob: Blob;
  filename: string;
};

function normalizeParams(params: ExportHoursParams) {
  const cleaned: Record<string, string | number> = {};
  Object.entries(params).forEach(([key, value]) => {
    if (value === null || value === undefined) return;
    if (typeof value === "string" && value.trim() === "") return;
    cleaned[key] = typeof value === "string" ? value.trim() : value;
  });
  return cleaned;
}

function parseFilename(disposition?: string | null): string | null {
  if (!disposition) return null;

  const match =
    /filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i.exec(disposition);
  const raw = match?.[1] ?? match?.[2];
  if (!raw) return null;

  try {
    return decodeURIComponent(raw);
  } catch {
    return raw;
  }
}

async function exportSummary(
  url: string,
  params: ExportHoursParams,
  fallbackFilename: string
): Promise<ExportResult> {
  const response = await http.get(url, {
    params: normalizeParams(params),
    responseType: "blob",
  });

  const disposition = response.headers?.["content-disposition"] as
    | string
    | undefined;
  const filename = parseFilename(disposition) ?? fallbackFilename;

  return { blob: response.data as Blob, filename };
}

export async function exportHoursExcel(
  params: ExportHoursParams
): Promise<ExportResult> {
  return exportSummary(
    "/api/admin/hours/lecturers/summary/export/excel",
    params,
    "hours_summary_lecturers.xlsx"
  );
}

export async function exportHoursPdf(
  params: ExportHoursParams
): Promise<ExportResult> {
  return exportSummary(
    "/api/admin/hours/lecturers/summary/export/pdf",
    params,
    "hours_summary_lecturers.pdf"
  );
}
