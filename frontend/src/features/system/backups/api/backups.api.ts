import http from "@/lib/http";

interface ApiEnvelope<T> {
  success?: boolean;
  message?: string;
  data: T;
}

export interface BackupSnapshot {
  snapshot_id: string;
  short_id: string;
  created_at: string;
  run_id: string | null;
  trigger: string | null;
  run_state: string | null;
  size_bytes: number | null;
  hostname: string;
  paths: string[];
  tags: string[];
}

export interface BackupRunState {
  run_id: string;
  status: "queued" | "running" | "success" | "failed" | string;
  trigger?: string;
  step?: string | null;
  message?: string | null;
  requested_by_user_id?: number | null;
  requested_at?: string | null;
  started_at?: string | null;
  finished_at?: string | null;
  error_message?: string | null;
  result?: Record<string, unknown> | null;
}

export interface BackupScheduleMeta {
  days: number[];
  time: string;
  timezone: string;
  next_run_at: string | null;
}

export interface BackupRetentionMeta {
  keep_last: number;
  keep_weekly: number;
  keep_monthly: number;
}

export interface BackupListResponse {
  engine: string;
  repository_configured?: boolean;
  repository_error?: string | null;
  snapshots: BackupSnapshot[];
  runs: BackupRunState[];
  schedule: BackupScheduleMeta;
  retention: BackupRetentionMeta;
  last_successful_backup_at: string | null;
}

export interface BackupRunResponse {
  run_id: string;
  launch_mode: "detached" | "sync" | string;
  status: string;
  status_url: string;
}

export interface RestoreBackupPayload {
  scope: "db_only" | "files_only" | "full";
  target: "staging" | "current";
  confirm: boolean;
  confirm_phrase: string;
}

export interface RestoreBackupResponse {
  snapshot_id: string;
  scope: string;
  target: string;
  restore_root: string;
  db_imported: boolean;
  mirrored_paths: string[];
  stdout?: string;
  stderr?: string;
}

export interface FileDownloadPayload {
  blob: Blob;
  filename: string;
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

async function downloadFile(
  url: string,
  fallbackName: string
): Promise<FileDownloadPayload> {
  const response = await http.get(url, { responseType: "blob" });
  const disposition = response.headers?.["content-disposition"] as
    | string
    | undefined;
  const filename = parseFilename(disposition) ?? fallbackName;
  return { blob: response.data as Blob, filename };
}

export async function listBackups(limit = 30): Promise<BackupListResponse> {
  const { data } = await http.get<ApiEnvelope<BackupListResponse>>(
    "/api/admin/backups",
    { params: { limit } }
  );
  return data.data;
}

export async function runBackupNow(): Promise<BackupRunResponse> {
  const { data } = await http.post<ApiEnvelope<BackupRunResponse>>(
    "/api/admin/backups/run"
  );
  return data.data;
}

export async function getBackupRunStatus(
  runId: string
): Promise<BackupRunState> {
  const { data } = await http.get<ApiEnvelope<BackupRunState>>(
    `/api/admin/backups/runs/${runId}`
  );
  return data.data;
}

export async function pruneBackups(): Promise<{
  successful: boolean;
  retention: BackupRetentionMeta;
}> {
  const { data } = await http.post<
    ApiEnvelope<{ successful: boolean; retention: BackupRetentionMeta }>
  >("/api/admin/backups/prune");
  return data.data;
}

export async function restoreBackup(
  snapshotId: string,
  payload: RestoreBackupPayload
): Promise<RestoreBackupResponse> {
  const { data } = await http.post<ApiEnvelope<RestoreBackupResponse>>(
    `/api/admin/backups/${snapshotId}/restore`,
    payload
  );
  return data.data;
}

export async function downloadBackupManifest(
  snapshotId: string
): Promise<FileDownloadPayload> {
  return downloadFile(
    `/api/admin/backups/${snapshotId}/manifest`,
    `backup_manifest_${snapshotId}.json`
  );
}

export async function downloadBackupDatabaseDump(
  snapshotId: string
): Promise<FileDownloadPayload> {
  return downloadFile(
    `/api/admin/backups/${snapshotId}/database-dump`,
    `backup_database_${snapshotId}.sql`
  );
}
