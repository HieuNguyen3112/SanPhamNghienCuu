import type {
  HourApprovalFilter,
  HourApprovalRequestDetailDTO,
  HourApprovalRequestSummaryDTO,
  RejectPayloadDTO,
} from "../contracts/hourApproval.contract";

type HourApprovalServiceDatabase = {
  summaries: HourApprovalRequestSummaryDTO[];
  detailsById: Record<number, HourApprovalRequestDetailDTO>;
};

export interface HourApprovalService {
  getRequests(
    filter: HourApprovalFilter
  ): Promise<HourApprovalRequestSummaryDTO[]>;
  getRequestDetail(requestId: number): Promise<HourApprovalRequestDetailDTO>;
  approve(requestId: number): Promise<void>;
  reject(requestId: number, payload: RejectPayloadDTO): Promise<void>;
}

function delay(ms: number) {
  return new Promise<void>((resolve) => setTimeout(resolve, ms));
}

function randomLatencyMs() {
  return 200 + Math.floor(Math.random() * 200);
}

function normalizeText(input: string): string {
  return input.trim().toLowerCase();
}

function inDateRange(
  iso: string,
  from: string | null,
  to: string | null
): boolean {
  const t = new Date(iso).getTime();
  if (Number.isNaN(t)) return true;

  if (from) {
    const fromTime = new Date(`${from}T00:00:00`).getTime();
    if (!Number.isNaN(fromTime) && t < fromTime) return false;
  }
  if (to) {
    const toTime = new Date(`${to}T23:59:59`).getTime();
    if (!Number.isNaN(toTime) && t > toTime) return false;
  }
  return true;
}

export function createHourApprovalService(
  db: HourApprovalServiceDatabase
): HourApprovalService {
  // clone in-memory (mock stateful)
  const state: HourApprovalServiceDatabase = {
    summaries: db.summaries.map((s) => ({ ...s })),
    detailsById: Object.fromEntries(
      Object.entries(db.detailsById).map(([k, v]) => [
        k,
        { ...v, items: v.items.map((i) => ({ ...i })) },
      ])
    ),
  };

  async function getRequests(
    filter: HourApprovalFilter
  ): Promise<HourApprovalRequestSummaryDTO[]> {
    await delay(randomLatencyMs());

    let rows = [...state.summaries];

    if (filter.facultyId) {
      rows = rows.filter((r) => r.faculty_id === filter.facultyId);
    }

    if (filter.status !== "all") {
      rows = rows.filter((r) => r.status === filter.status);
    }

    rows = rows.filter((r) =>
      inDateRange(r.submitted_at, filter.submittedFrom, filter.submittedTo)
    );

    const q = normalizeText(filter.searchText);
    if (q) {
      rows = rows.filter((r) => {
        const code = normalizeText(r.lecturer_code);
        const name = normalizeText(r.lecturer_full_name);
        return code.includes(q) || name.includes(q);
      });
    }

    // newest first
    rows.sort(
      (a, b) =>
        new Date(b.submitted_at).getTime() - new Date(a.submitted_at).getTime()
    );
    return rows;
  }

  async function getRequestDetail(
    requestId: number
  ): Promise<HourApprovalRequestDetailDTO> {
    await delay(randomLatencyMs());
    const detail = state.detailsById[requestId];
    if (!detail) throw new Error("Không tìm thấy yêu cầu.");
    return { ...detail, items: detail.items.map((i) => ({ ...i })) };
  }

  async function approve(requestId: number): Promise<void> {
    await delay(randomLatencyMs());

    const summary = state.summaries.find((s) => s.request_id === requestId);
    const detail = state.detailsById[requestId];

    if (!summary || !detail) throw new Error("Không tìm thấy yêu cầu.");
    if (summary.status !== "pending") return;

    summary.status = "approved";
    detail.status = "approved";
  }

  async function reject(
    requestId: number,
    payload: RejectPayloadDTO
  ): Promise<void> {
    await delay(randomLatencyMs());

    const summary = state.summaries.find((s) => s.request_id === requestId);
    const detail = state.detailsById[requestId];

    if (!summary || !detail) throw new Error("Không tìm thấy yêu cầu.");
    if (summary.status !== "pending") return;

    // TODO(BE): lưu reject_reason_code/reject_reason_note vào bảng hour_approval_requests
    void payload;

    summary.status = "rejected";
    detail.status = "rejected";
  }

  return { getRequests, getRequestDetail, approve, reject };
}
