import type {
  ApprovePayloadDTO,
  HourApprovalFilter,
  HourApprovalListResponseDTO,
  HourApprovalRequestDetailDTO,
  HourApprovalRequestStatus,
  HourApprovalRequestSummaryDTO,
  RejectPayloadDTO,
} from "../contracts/hourApproval.contract";

type HourApprovalServiceDatabase = {
  summaries: HourApprovalRequestSummaryDTO[];
  detailsById: Record<number, HourApprovalRequestDetailDTO>;
};

export interface HourApprovalService {
  getRequests(
    filter: HourApprovalFilter,
    page: number,
    perPage: number,
  ): Promise<HourApprovalListResponseDTO>;
  getRequestDetail(
    requestId: number,
    options?: { academicYearId?: number | null },
  ): Promise<HourApprovalRequestDetailDTO>;
  approve(
    requestId: number,
    payload?: ApprovePayloadDTO,
  ): Promise<HourApprovalRequestDetailDTO>;
  reject(
    requestId: number,
    payload: RejectPayloadDTO,
  ): Promise<HourApprovalRequestDetailDTO>;
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
  to: string | null,
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

function resolveAggregateStatus(
  items: HourApprovalRequestDetailDTO["items"],
): HourApprovalRequestStatus {
  const statuses = items.map((item) => item.approval_status ?? "pending");
  if (statuses.length === 0) {
    return "pending";
  }

  if (statuses.includes("pending")) {
    return "pending";
  }

  const uniqueStatuses = Array.from(new Set(statuses));

  if (uniqueStatuses.length === 1) {
    return uniqueStatuses[0] as HourApprovalRequestStatus;
  }

  return "partially_approved";
}

export function createHourApprovalService(
  db: HourApprovalServiceDatabase,
): HourApprovalService {
  const state: HourApprovalServiceDatabase = {
    summaries: db.summaries.map((s) => ({ ...s })),
    detailsById: Object.fromEntries(
      Object.entries(db.detailsById).map(([k, v]) => [
        k,
        { ...v, items: v.items.map((i) => ({ ...i })) },
      ]),
    ),
  };

  async function getRequests(
    filter: HourApprovalFilter,
    page: number,
    perPage: number,
  ): Promise<HourApprovalListResponseDTO> {
    await delay(randomLatencyMs());

    let rows = [...state.summaries];

    if (filter.facultyId) {
      rows = rows.filter((r) => r.faculty_id === filter.facultyId);
    }

    if (filter.status !== "all") {
      rows = rows.filter((r) => r.status === filter.status);
    }

    rows = rows.filter((r) =>
      inDateRange(r.submitted_at, filter.submittedFrom, filter.submittedTo),
    );

    const q = normalizeText(filter.searchText);
    if (q) {
      rows = rows.filter((r) => {
        const code = normalizeText(r.lecturer_code);
        const name = normalizeText(r.lecturer_full_name);
        return code.includes(q) || name.includes(q);
      });
    }

    rows.sort(
      (a, b) =>
        new Date(b.submitted_at).getTime() - new Date(a.submitted_at).getTime(),
    );

    const total = rows.length;
    const start = (page - 1) * perPage;
    const items = rows.slice(start, start + perPage);

    return {
      success: true,
      message: "ok",
      data: {
        items,
        pagination: {
          page,
          per_page: perPage,
          total,
          last_page: Math.max(1, Math.ceil(total / perPage)),
        },
      },
    };
  }

  async function getRequestDetail(
    requestId: number,
    _options?: { academicYearId?: number | null },
  ): Promise<HourApprovalRequestDetailDTO> {
    await delay(randomLatencyMs());
    const detail = state.detailsById[requestId];
    if (!detail) throw new Error("Không tìm thấy yêu cầu.");
    return { ...detail, items: detail.items.map((i) => ({ ...i })) };
  }

  async function approve(
    requestId: number,
    payload?: ApprovePayloadDTO,
  ): Promise<HourApprovalRequestDetailDTO> {
    await delay(randomLatencyMs());

    const summary = state.summaries.find((s) => s.request_id === requestId);
    const detail = state.detailsById[requestId];

    if (!summary || !detail) throw new Error("Không tìm thấy yêu cầu.");

    const selectedActivityIds = new Set(payload?.activity_ids ?? []);
    const shouldApplyPartial = selectedActivityIds.size > 0;
    const pendingItems = detail.items.filter(
      (item) => item.approval_status === "pending",
    );
    if (pendingItems.length === 0) {
      return detail;
    }

    detail.items = detail.items.map((item) => {
      if (item.approval_status !== "pending") return item;
      if (shouldApplyPartial && !selectedActivityIds.has(item.activity_id))
        return item;
      return {
        ...item,
        approval_status: "approved",
        rejection_reason: null,
        rejection_reason_code: null,
        rejection_reason_detail: null,
      };
    });

    const nextStatus = resolveAggregateStatus(detail.items);
    summary.status = nextStatus;
    detail.status = nextStatus;

    return detail;
  }

  async function reject(
    requestId: number,
    payload: RejectPayloadDTO,
  ): Promise<HourApprovalRequestDetailDTO> {
    await delay(randomLatencyMs());

    const summary = state.summaries.find((s) => s.request_id === requestId);
    const detail = state.detailsById[requestId];

    if (!summary || !detail) throw new Error("Không tìm thấy yêu cầu.");

    const selectedActivityIds = new Set(payload.activity_ids ?? []);
    const shouldApplyPartial = selectedActivityIds.size > 0;
    const pendingItems = detail.items.filter(
      (item) => item.approval_status === "pending",
    );
    if (pendingItems.length === 0) {
      return detail;
    }

    const rejectedStatus =
      payload.decision_mode === "revision" ? "need_revision" : "rejected";

    detail.items = detail.items.map((item) => {
      if (item.approval_status !== "pending") return item;
      if (shouldApplyPartial && !selectedActivityIds.has(item.activity_id))
        return item;
      return {
        ...item,
        approval_status: rejectedStatus,
        rejection_reason: payload.reason_detail ?? null,
        rejection_reason_code: payload.reason_code,
        rejection_reason_detail: payload.reason_detail ?? null,
      };
    });

    const nextStatus = resolveAggregateStatus(detail.items);
    summary.status = nextStatus;
    detail.status = nextStatus;

    return detail;
  }

  return { getRequests, getRequestDetail, approve, reject };
}
