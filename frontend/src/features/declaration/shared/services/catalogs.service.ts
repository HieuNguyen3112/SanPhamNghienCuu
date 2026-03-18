import type {
  AcademicYearDto,
  ActivityKindDto,
  ActivityStatusDto,
  ActivityTypeDto,
  EvidenceFileTypeDto,
  LecturerOptionDto,
  MemberRoleDto,
  PublisherOptionDto,
} from "../contracts/declarationSharedContract";
import http from "@/lib/http";

const ROLE_LABELS_VI: Record<string, string> = {
  principal: "Chủ nhiệm",
  corresponding_author: "Tác giả chính",
  coauthor: "Đồng tác giả",
  member: "Thành viên",
  secretary: "Thư ký",
  chief_editor: "Chủ biên",
};

const ACTIVITY_TYPE_LABELS_VI: Record<string, string> = {
  hdgsnn_900: "HDGSNN 1-2 điểm (900 giờ)",
  hdgsnn_600: "HDGSNN <= 1 điểm (600 giờ)",
  hdgsnn_300: "Có ISSN/ISBN (300 giờ)",
  textbook: "Giáo trình",
  reference: "Tài liệu tham khảo",
  report: "Báo cáo hội thảo",
  attend: "Tham dự hội thảo",
  bo: "Đề tài cấp Bộ (2 năm)",
  ministry: "Đề tài cấp Bộ (2 năm)",
  coso: "Đề tài cấp Trường (1 năm)",
  university: "Đề tài cấp Trường (1 năm)",
};

const EXPECTED_ACADEMIC_YEAR_CODES = ["2024-2025", "2025-2026"];

type CacheState<T> = {
  value: T[] | null;
  promise: Promise<T[]> | null;
};

const academicYearsCache: CacheState<AcademicYearDto> = {
  value: null,
  promise: null,
};
const activityKindsCache: CacheState<ActivityKindDto> = {
  value: null,
  promise: null,
};
const memberRolesCache: CacheState<MemberRoleDto> = {
  value: null,
  promise: null,
};
const evidenceFileTypesByKindCache = new Map<string, EvidenceFileTypeDto[]>();
const evidenceFileTypesByKindPromise = new Map<
  string,
  Promise<EvidenceFileTypeDto[]>
>();
const activityStatusesCache: CacheState<ActivityStatusDto> = {
  value: null,
  promise: null,
};
const activityTypesByKindCache = new Map<number, ActivityTypeDto[]>();
const activityTypesByKindPromise = new Map<
  number,
  Promise<ActivityTypeDto[]>
>();
const lecturersBySearchCache = new Map<string, LecturerOptionDto[]>();
const lecturersBySearchPromise = new Map<
  string,
  Promise<LecturerOptionDto[]>
>();

export type JournalOptionDto = {
  id: number;
  name: string;
  issn: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;
  source_name: string | null;
  point_min: string | number | null;
  point_max: string | number | null;
  classification: string;
  research_hours: number;
  is_active: boolean;
};

export type ConferenceOptionDto = {
  id: number;
  name: string;
  level: "NATIONAL" | "INTERNATIONAL";
  research_field: string | null;
  organization: string | null;
  year: number | null;
  has_proceedings: boolean;
  has_isbn: boolean;
  isbn: string | null;
  point: string | number | null;
  notes: string | null;
  is_active: boolean;
};

function cloneRows<T>(rows: T[]): T[] {
  return rows.map((row) => {
    if (typeof row === "object" && row !== null) {
      return { ...(row as Record<string, unknown>) } as T;
    }
    return row;
  });
}

function normalizeSearch(search: string): string {
  return search.trim().toLowerCase();
}

async function readCachedList<T>(
  cache: CacheState<T>,
  loader: () => Promise<T[]>,
): Promise<T[]> {
  if (cache.value) return cloneRows(cache.value);
  if (cache.promise) {
    const rows = await cache.promise;
    return cloneRows(rows);
  }

  cache.promise = loader()
    .then((rows) => {
      cache.value = rows;
      return rows;
    })
    .finally(() => {
      cache.promise = null;
    });

  const rows = await cache.promise;
  return cloneRows(rows);
}

export async function fetch_academic_years(): Promise<AcademicYearDto[]> {
  const rows = await readCachedList(academicYearsCache, async () => {
    const { data } = await http.get<{ data: AcademicYearDto[] }>(
      "/api/lookups/academic-years",
    );
    return data.data;
  });

  if (import.meta.env.DEV) {
    const codes = new Set(rows.map((y) => y.code));
    const missing = EXPECTED_ACADEMIC_YEAR_CODES.filter((c) => !codes.has(c));
    if (missing.length > 0) {
      console.warn(
        "[catalogs] Missing academic years in API:",
        missing.join(", "),
      );
    }
  }

  return rows;
}

export async function fetch_activity_kinds(): Promise<ActivityKindDto[]> {
  return readCachedList(activityKindsCache, async () => {
    const { data } = await http.get<{ data: ActivityKindDto[] }>(
      "/api/lookups/activity-kinds",
    );
    return data.data;
  });
}

export async function fetch_activity_types_by_kind(
  kind_id: number,
): Promise<ActivityTypeDto[]> {
  if (activityTypesByKindCache.has(kind_id)) {
    return cloneRows(activityTypesByKindCache.get(kind_id) ?? []);
  }

  if (activityTypesByKindPromise.has(kind_id)) {
    const pending = activityTypesByKindPromise.get(kind_id);
    const rows = pending ? await pending : [];
    return cloneRows(rows);
  }

  const request = http
    .get<{ data: ActivityTypeDto[] }>("/api/lookups/activity-types", {
      params: { kind_id },
    })
    .then(({ data }) =>
      data.data.map((t) => ({
        ...t,
        name: ACTIVITY_TYPE_LABELS_VI[t.code] ?? t.name,
      })),
    )
    .then((rows) => {
      activityTypesByKindCache.set(kind_id, rows);
      return rows;
    })
    .finally(() => {
      activityTypesByKindPromise.delete(kind_id);
    });

  activityTypesByKindPromise.set(kind_id, request);
  const rows = await request;
  return cloneRows(rows);
}

export async function fetch_member_roles(): Promise<MemberRoleDto[]> {
  return readCachedList(memberRolesCache, async () => {
    const { data } = await http.get<{ data: MemberRoleDto[] }>(
      "/api/lookups/member-roles",
    );

    return data.data.map((r) => ({
      ...r,
      name: ROLE_LABELS_VI[r.code] ?? r.name,
    }));
  });
}

export async function fetch_evidence_file_types(
  kindCode?: string,
): Promise<EvidenceFileTypeDto[]> {
  const normalizedKind = normalizeSearch(kindCode ?? "");

  if (evidenceFileTypesByKindCache.has(normalizedKind)) {
    return cloneRows(evidenceFileTypesByKindCache.get(normalizedKind) ?? []);
  }

  if (evidenceFileTypesByKindPromise.has(normalizedKind)) {
    const pending = evidenceFileTypesByKindPromise.get(normalizedKind);
    const rows = pending ? await pending : [];
    return cloneRows(rows);
  }

  const request = http
    .get<{ data: EvidenceFileTypeDto[] }>("/api/lookups/evidence-file-types", {
      params: normalizedKind ? { kind_code: normalizedKind } : undefined,
    })
    .then(({ data }) => data.data)
    .then((rows) => {
      evidenceFileTypesByKindCache.set(normalizedKind, rows);
      return rows;
    })
    .finally(() => {
      evidenceFileTypesByKindPromise.delete(normalizedKind);
    });

  evidenceFileTypesByKindPromise.set(normalizedKind, request);
  const rows = await request;
  return cloneRows(rows);
}

export async function fetch_activity_statuses(): Promise<ActivityStatusDto[]> {
  return readCachedList(activityStatusesCache, async () => {
    const { data } = await http.get<{ data: ActivityStatusDto[] }>(
      "/api/lookups/activity-statuses",
    );
    return data.data;
  });
}

export async function search_lecturer_options(
  search: string,
): Promise<LecturerOptionDto[]> {
  const cacheKey = normalizeSearch(search);
  if (lecturersBySearchCache.has(cacheKey)) {
    return cloneRows(lecturersBySearchCache.get(cacheKey) ?? []);
  }

  if (lecturersBySearchPromise.has(cacheKey)) {
    const pending = lecturersBySearchPromise.get(cacheKey);
    const rows = pending ? await pending : [];
    return cloneRows(rows);
  }

  const request = http
    .get<{ data: LecturerOptionDto[] }>("/api/lookups/lecturers", {
      params: { search },
    })
    .then(({ data }) => data.data)
    .then((rows) => {
      lecturersBySearchCache.set(cacheKey, rows);
      return rows;
    })
    .finally(() => {
      lecturersBySearchPromise.delete(cacheKey);
    });

  lecturersBySearchPromise.set(cacheKey, request);
  const rows = await request;
  return cloneRows(rows);
}

export async function search_journals(
  search: string,
): Promise<JournalOptionDto[]> {
  const { data } = await http.get<{ data: JournalOptionDto[] }>(
    "/api/lookups/journals",
    { params: { search, active: 1 } },
  );
  return data.data;
}

export async function search_conferences(
  search: string,
): Promise<ConferenceOptionDto[]> {
  const { data } = await http.get<{ data: ConferenceOptionDto[] }>(
    "/api/lookups/conferences",
    { params: { search, active: 1 } },
  );
  return data.data;
}

export async function search_publishers(
  search: string,
): Promise<PublisherOptionDto[]> {
  const { data } = await http.get<{ data: PublisherOptionDto[] }>(
    "/api/lookups/publishers",
    { params: { search, active: 1 } },
  );
  return data.data;
}
