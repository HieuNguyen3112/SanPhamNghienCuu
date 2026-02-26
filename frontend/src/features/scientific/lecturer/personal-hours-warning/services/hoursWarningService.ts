import axios from "axios";
import http from "@/lib/http";
import {
  deleteNotificationByRole,
  listNotificationsByRole,
  markNotificationAsReadByRole,
  type AppNotificationItem,
} from "@/shared/services/lecturerNotifications.service";
import type {
  HoursWarningSeenResponseDTO,
  HoursWarningsResponseDTO,
  HoursWarningSeverityDTO,
  HoursWarningStatusDTO,
  HoursWarningsTabDTO,
} from "../contracts/hoursWarning.contract";

type ApiResponse<T> = {
  data: T;
  message?: string;
  success?: boolean;
};

type OverviewDTO = {
  academic_year_id: number | null;
  academic_year_code: string;
  required_hours: number;
  approved_hours: number;
  pending_hours: number;
  rejected_hours: number;
};

type AcademicYearLookupDTO = {
  id: number;
  code: string;
  end_date: string | null;
};

const WARNING_EVENT_KEYS = [
  "hours_warning",
  "missing_hours",
  "deadline_near",
  "deadline_passed",
  "approved_not_submitted",
  "hours_pending",
  "hours_rejected",
];

const DANGER_TYPE_KEYS = new Set(["missing_hours", "deadline_passed"]);
const WARNING_TYPE_KEYS = new Set([
  "deadline_near",
  "approved_not_submitted",
  "hours_pending",
  "hours_rejected",
  "missing_evidence",
]);

const extractErrorMessage = (err: unknown, fallback: string) => {
  if (axios.isAxiosError(err)) {
    const status = err.response?.status ?? 0;
    if (status >= 500) return fallback;
    const message = err.response?.data?.message;
    if (typeof message === "string" && message.trim()) return message;
  }

  return err instanceof Error ? err.message : fallback;
};

function asRecord(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    return {};
  }
  return value as Record<string, unknown>;
}

function toLower(value: unknown): string {
  if (typeof value !== "string") return "";
  return value.trim().toLowerCase();
}

function resolveTypeKey(item: AppNotificationItem): string {
  const data = asRecord(item.data);
  return (
    toLower(data.type_key) ||
    toLower(data.reason_code) ||
    toLower(item.event_key) ||
    "hours_warning"
  );
}

function resolveSeverity(item: AppNotificationItem): HoursWarningSeverityDTO {
  const typeKey = resolveTypeKey(item);
  if (DANGER_TYPE_KEYS.has(typeKey) || typeKey === "deadline_passed") {
    return "danger";
  }
  if (WARNING_TYPE_KEYS.has(typeKey) || typeKey === "deadline_near") {
    return "warning";
  }
  return "info";
}

function resolveStatus(item: AppNotificationItem): HoursWarningStatusDTO {
  return item.read_at ? "resolved" : "unseen";
}

function resolveActionLabel(item: AppNotificationItem): string | null {
  const data = asRecord(item.data);
  const explicitLabel = typeof data.action_label === "string" ? data.action_label.trim() : "";
  if (explicitLabel) return explicitLabel;

  if (item.target_url.includes("/hours/calculate")) return "Tính giờ NCKH";
  if (item.target_url.includes("/works/personal")) return "Xem công trình";
  if (item.target_url.includes("/hours/personal")) return "Xem giờ NCKH";
  return "Xem chi tiết";
}

async function fetchWarningSummaryFromOverview() {
  const [overviewResponse, academicYearsResponse] = await Promise.all([
    http.get<ApiResponse<OverviewDTO>>("/api/lecturer/hours/personal/overview"),
    http.get<{ data: AcademicYearLookupDTO[] }>("/api/lookups/academic-years"),
  ]);

  const overview = overviewResponse.data.data;
  const academicYears = academicYearsResponse.data.data;
  const year = academicYears.find((item) => item.id === overview.academic_year_id);

  let daysRemaining: number | null = null;
  if (year?.end_date) {
    const endDate = new Date(year.end_date);
    const today = new Date();
    endDate.setHours(0, 0, 0, 0);
    today.setHours(0, 0, 0, 0);
    daysRemaining = Math.round((endDate.getTime() - today.getTime()) / 86400000);
  }

  return {
    academic_year_id: overview.academic_year_id ?? 0,
    academic_year_code: overview.academic_year_code || "N/A",
    required_hours: overview.required_hours,
    approved_hours: overview.approved_hours,
    pending_hours: overview.pending_hours,
    rejected_hours: overview.rejected_hours,
    total_hours_current: overview.approved_hours + overview.pending_hours,
    shortage_hours: Math.max(overview.required_hours - overview.approved_hours, 0),
    deadline_date: year?.end_date ?? null,
    days_remaining: daysRemaining,
  };
}

function filterItemsByTab(items: HoursWarningsResponseDTO["items"], tab: HoursWarningsTabDTO) {
  if (tab === "danger") {
    return items.filter((item) => item.severity_key === "danger" && item.status_key !== "resolved");
  }
  if (tab === "warning") {
    return items.filter((item) => item.severity_key === "warning" && item.status_key !== "resolved");
  }
  if (tab === "done") {
    return items.filter((item) => item.status_key === "resolved");
  }
  return items;
}

function buildTabCounts(items: HoursWarningsResponseDTO["items"]) {
  return {
    all: items.length,
    danger: items.filter(
      (item) => item.severity_key === "danger" && item.status_key !== "resolved"
    ).length,
    warning: items.filter(
      (item) => item.severity_key === "warning" && item.status_key !== "resolved"
    ).length,
    done: items.filter((item) => item.status_key === "resolved").length,
  };
}

function buildSuggestions(items: HoursWarningsResponseDTO["items"]) {
  const seenTargets = new Set<string>();
  const suggestions: HoursWarningsResponseDTO["suggestions"] = [];

  for (const item of items) {
    const routePath = item.action?.route_path ?? null;
    if (!routePath || seenTargets.has(routePath)) {
      continue;
    }

    seenTargets.add(routePath);
    suggestions.push({
      id: item.id,
      title: item.title,
      description: item.message,
      cta_label: item.action?.label ?? "Xem chi tiết",
      cta_to: routePath,
    });

    if (suggestions.length >= 4) {
      break;
    }
  }

  return suggestions;
}

export async function fetchHoursWarnings(params?: {
  tab?: HoursWarningsTabDTO;
  page?: number;
  per_page?: number;
}): Promise<HoursWarningsResponseDTO> {
  const page = Math.max(1, params?.page ?? 1);
  const perPage = Math.max(1, params?.per_page ?? 12);
  const tab = params?.tab ?? "all";

  try {
    const [notificationsResponse, summary] = await Promise.all([
      listNotificationsByRole("LECTURER", {
        page: 1,
        per_page: 200,
        read_state: "all",
        event_keys: WARNING_EVENT_KEYS.join(","),
      }),
      fetchWarningSummaryFromOverview(),
    ]);

    const items = notificationsResponse.items
      .map((item) => {
        const data = asRecord(item.data);

        return {
          id: item.id,
          type_key: resolveTypeKey(item),
          severity_key: resolveSeverity(item),
          title: item.title,
          message: item.message,
          status_key: resolveStatus(item),
          updated_at: item.created_at,
          deadline_at:
            typeof data.deadline_at === "string"
              ? data.deadline_at
              : typeof data.deadline_date === "string"
                ? data.deadline_date
                : null,
          action: {
            label: resolveActionLabel(item),
            route_path: item.target_url || "/hours/personal_warnings",
            external_url: null,
          },
        };
      })
      .sort((a, b) => {
        const aTime = a.updated_at ? new Date(a.updated_at).getTime() : 0;
        const bTime = b.updated_at ? new Date(b.updated_at).getTime() : 0;
        return bTime - aTime;
      });

    const tabCounts = buildTabCounts(items);
    const filtered = filterItemsByTab(items, tab);

    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const start = (page - 1) * perPage;
    const paginatedItems = filtered.slice(start, start + perPage);

    return {
      summary,
      tab_counts: tabCounts,
      items: paginatedItems,
      pagination: {
        page,
        per_page: perPage,
        total,
        last_page: lastPage,
      },
      suggestions: buildSuggestions(filtered),
    };
  } catch (err) {
    throw new Error(
      extractErrorMessage(
        err,
        "Không tải được cảnh báo giờ NCKH. Vui lòng thử lại."
      )
    );
  }
}

export async function markHoursWarningSeen(
  warningId: string
): Promise<HoursWarningSeenResponseDTO> {
  try {
    const response = await markNotificationAsReadByRole("LECTURER", warningId);
    return {
      id: warningId,
      status_key: "resolved",
      seen_at: response.data.read_at,
    };
  } catch (err) {
    throw new Error(
      extractErrorMessage(err, "Không thể cập nhật trạng thái cảnh báo.")
    );
  }
}

export async function deleteHoursWarning(warningId: string): Promise<void> {
  try {
    await deleteNotificationByRole("LECTURER", warningId);
  } catch (err) {
    throw new Error(
      extractErrorMessage(err, "Không thể xóa cảnh báo. Vui lòng thử lại.")
    );
  }
}