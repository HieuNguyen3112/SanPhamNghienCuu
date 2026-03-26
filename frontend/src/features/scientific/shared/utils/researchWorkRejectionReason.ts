export type ResearchWorkRejectionReasonCode =
  | "MISSING_EVIDENCE"
  | "INACCURATE_INFORMATION"
  | "OUTSIDE_FACULTY_SCOPE"
  | "WRONG_HOUR_CONVERSION"
  | "EVIDENCE_NOT_QUALIFIED"
  | "NOT_COMPLIANT_WITH_RESEARCH_POLICY"
  | "OTHER";

export type ResearchWorkRejectionReasonScope =
  | "FACULTY_SCOPE"
  | "UNIVERSITY_SCOPE";

export interface ResearchWorkRejectionReasonDisplay {
  code: ResearchWorkRejectionReasonCode | null;
  label: string;
  detail: string | null;
  fullText: string;
}

const RESEARCH_WORK_REJECTION_REASON_LABELS: Record<
  ResearchWorkRejectionReasonCode,
  string
> = {
  MISSING_EVIDENCE: "Thiếu minh chứng",
  INACCURATE_INFORMATION: "Thông tin kê khai chưa chính xác",
  OUTSIDE_FACULTY_SCOPE: "Công trình không thuộc phạm vi xử lý của khoa",
  WRONG_HOUR_CONVERSION: "Giờ quy đổi chưa phù hợp",
  EVIDENCE_NOT_QUALIFIED: "Minh chứng chưa đạt yêu cầu",
  NOT_COMPLIANT_WITH_RESEARCH_POLICY:
    "Công trình chưa phù hợp với quy định nghiên cứu khoa học",
  OTHER: "Lý do khác",
};

const FACULTY_REASON_OPTIONS: ResearchWorkRejectionReasonCode[] = [
  "MISSING_EVIDENCE",
  "INACCURATE_INFORMATION",
  "OUTSIDE_FACULTY_SCOPE",
  "OTHER",
];

const UNIVERSITY_REASON_OPTIONS: ResearchWorkRejectionReasonCode[] = [
  "WRONG_HOUR_CONVERSION",
  "EVIDENCE_NOT_QUALIFIED",
  "NOT_COMPLIANT_WITH_RESEARCH_POLICY",
  "OTHER",
];

export function getResearchWorkRejectionReasonLabel(
  code: string | null | undefined,
): string {
  return RESEARCH_WORK_REJECTION_REASON_LABELS[normalizeKnownCode(code) ?? "OTHER"];
}

export function getResearchWorkRejectionReasonOptions(
  scope: ResearchWorkRejectionReasonScope,
): Array<{ value: ResearchWorkRejectionReasonCode; label: string }> {
  const codes =
    scope === "FACULTY_SCOPE"
      ? FACULTY_REASON_OPTIONS
      : UNIVERSITY_REASON_OPTIONS;

  return codes.map((code) => ({
    value: code,
    label: RESEARCH_WORK_REJECTION_REASON_LABELS[code],
  }));
}

export function formatResearchWorkRejectionReason(parameters: {
  reasonCode?: string | null;
  reasonDetail?: string | null;
  rawNote?: string | null;
}): ResearchWorkRejectionReasonDisplay {
  const explicitCode = normalizeKnownCode(parameters.reasonCode);
  const explicitDetail = normalizeDetail(parameters.reasonDetail);
  const rawNote = normalizeDetail(parameters.rawNote);
  const parsedRaw = parseRawNote(rawNote);

  const code = explicitCode ?? parsedRaw.code ?? (rawNote ? "OTHER" : null);
  const label = getResearchWorkRejectionReasonLabel(code);
  const detail =
    explicitDetail ??
    parsedRaw.detail ??
    (explicitCode || parsedRaw.code ? null : rawNote);

  return {
    code,
    label,
    detail,
    fullText: detail ? `${label}\n${detail}` : label,
  };
}

function parseRawNote(rawNote: string | null): {
  code: ResearchWorkRejectionReasonCode | null;
  detail: string | null;
} {
  if (!rawNote) {
    return { code: null, detail: null };
  }

  const exactCode = normalizeKnownCode(rawNote);
  if (exactCode) {
    return { code: exactCode, detail: null };
  }

  const matched = rawNote.match(/^\s*([A-Z_]+)\s*[:\-–]\s*(.+)\s*$/);
  if (!matched) {
    return { code: null, detail: null };
  }

  return {
    code: normalizeKnownCode(matched[1]),
    detail: normalizeDetail(matched[2]),
  };
}

function normalizeKnownCode(
  value: string | null | undefined,
): ResearchWorkRejectionReasonCode | null {
  const normalized = normalizeToken(value);
  if (!normalized) {
    return null;
  }

  if (normalized in RESEARCH_WORK_REJECTION_REASON_LABELS) {
    return normalized as ResearchWorkRejectionReasonCode;
  }

  return null;
}

function normalizeToken(value: string | null | undefined): string | null {
  const normalized = value?.trim().toUpperCase() ?? "";
  return normalized !== "" ? normalized : null;
}

function normalizeDetail(value: string | null | undefined): string | null {
  const normalized = value?.trim() ?? "";
  return normalized !== "" ? normalized : null;
}
