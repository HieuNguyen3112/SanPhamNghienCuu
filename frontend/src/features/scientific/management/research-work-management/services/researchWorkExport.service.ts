import http from "@/lib/http";

export type ExportSummaryParams = {
  faculty_id?: number | null;
  department_id?: number | null;
  academic_year_id?: number | null;
  q?: string;
  status_mode?: string;
  count_status?: string;
};

export type ExportResult = {
  blob: Blob;
  filename: string;
};

function normalizeParams(params: ExportSummaryParams) {
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
    /filename\*=UTF-8''([^;]+)|filename=\"?([^\";]+)\"?/i.exec(disposition);
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
  params: ExportSummaryParams,
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

export async function exportLecturerSummaryExcel(
  params: ExportSummaryParams
): Promise<ExportResult> {
  return exportSummary(
    "/api/admin/works/lecturers/summary/export/excel",
    params,
    "works_summary_lecturers.xlsx"
  );
}

export async function exportLecturerSummaryPdf(
  params: ExportSummaryParams
): Promise<ExportResult> {
  return exportSummary(
    "/api/admin/works/lecturers/summary/export/pdf",
    params,
    "works_summary_lecturers.pdf"
  );
}

export async function exportFacultySummaryExcel(
  params: ExportSummaryParams
): Promise<ExportResult> {
  return exportSummary(
    "/api/faculty/works/export/excel",
    params,
    "faculty_works_summary.xlsx"
  );
}

export async function exportFacultySummaryPdf(
  params: ExportSummaryParams
): Promise<ExportResult> {
  return exportSummary(
    "/api/faculty/works/export/pdf",
    params,
    "faculty_works_summary.pdf"
  );
}
