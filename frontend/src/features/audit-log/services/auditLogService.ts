import type {
  ActorOptionDTO,
  AuditLogEntryDTO,
  AuditLogQueryDTO,
  FacultyOptionDTO,
} from "../contracts/audit-log.contract";
import {
  auditLogMockActors,
  auditLogMockEntries,
  auditLogMockFaculties,
} from "../mock-data/audit-log.mock";

function sleep(ms: number) {
  return new Promise<void>((resolve) => setTimeout(resolve, ms));
}

export async function fetchAuditLogEntries(
  _query: AuditLogQueryDTO
): Promise<AuditLogEntryDTO[]> {
  // TODO: Replace by real API:
  // GET /api/audit-logs (scope enforced in backend)
  await sleep(250);
  return auditLogMockEntries
    .slice()
    .sort((a, b) => (a.occurred_at < b.occurred_at ? 1 : -1));
}

export async function fetchAuditActors(): Promise<ActorOptionDTO[]> {
  // TODO: GET /api/audit-logs/actors
  await sleep(150);
  return auditLogMockActors;
}

export async function fetchFaculties(): Promise<FacultyOptionDTO[]> {
  // TODO: GET /api/faculties
  await sleep(150);
  return auditLogMockFaculties;
}

export async function resolveMyFacultyId(): Promise<number> {
  // TODO: Use:
  // GET /api/profile/me -> lecturers.department_id -> departments.faculty_id
  await sleep(120);
  return 1; // mock: BCN thuộc khoa CNTT
}
