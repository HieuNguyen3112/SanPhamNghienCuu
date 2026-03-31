import http from "@/lib/http";

interface ApiEnvelope<T> {
  success?: boolean;
  message?: string;
  data: T;
}

export type BackupOperation =
  | "backup"
  | "backup_postprocess"
  | "prune"
  | "forget"
  | "restore"
  | "snapshot_refresh"
  | string;

export interface BackupListQuery {
  page?: number;
  per_page?: number;
  status?: "queued" | "running" | "success" | "failed" | "unknown";
  from?: string;
  to?: string;
  refresh?: boolean;
}

export interface BackupListItem {
  snapshot_id: string;
  snapshot_id_full?: string | null;
  short_id: string;
  backup_name: string;
  created_at: string;
  backup_type: "full" | "db_only" | "files_only" | "unknown" | string;
  run_id: string | null;
  run_state: string | null;
  status: string | null;
  trigger: string | null;
  size_bytes: number | null;
  contains_db_dump: boolean;
  contains_files: boolean;
  hostname: string;
  paths: string[];
  tags: string[];
  export_available?: boolean;
  export_path?: string | null;
  export_drive_path?: string | null;
  export_generated_at?: string | null;
  export_bundle_filename?: string | null;
  export_artifacts?: string[];
  export_state?: string | null;
  export_message?: string | null;
  export_run?: BackupRunState | null;
}

export interface BackupRunLog {
  at: string;
  level: "debug" | "info" | "warning" | "error" | string;
  message: string;
}

export interface BackupRunState {
  run_id: string;
  operation?: BackupOperation;
  status: "queued" | "running" | "success" | "failed" | string;
  trigger?: string;
  step?: string | null;
  message?: string | null;
  user_message?: string | null;
  error_code?: string | null;
  requested_by_user_id?: number | null;
  requested_at?: string | null;
  started_at?: string | null;
  finished_at?: string | null;
  error_message?: string | null;
  technical_message?: string | null;
  logs?: BackupRunLog[];
  result?: Record<string, unknown> | null;
  snapshot_id?: string | null;
  snapshot_ids?: string[] | null;
  scope?: "db_only" | "files_only" | "full" | string | null;
  target?: "staging" | "current" | string | null;
}

export interface BackupScheduleMeta {
  mode?: "fixed" | "weekly" | "biweekly" | "test" | string;
  interval_minutes?: number | null;
  interval_weeks?: number | null;
  weekday?: number;
  days: number[];
  time: string;
  timezone: string;
  next_run_at: string | null;
  description_vi?: string | null;
}

export interface BackupScheduleRuntimeMeta {
  last_run_id?: string | null;
  last_run_status?: string | null;
  last_run_at?: string | null;
}

export interface BackupRetentionMeta {
  keep_last: number;
  keep_weekly: number;
  keep_monthly: number;
}

export interface PaginationMeta {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface BackupCacheMeta {
  refreshed_at: string | null;
  refreshing: boolean;
  refresh_run_id: string | null;
  last_refresh_run_id: string | null;
  last_error: string | null;
  last_error_code?: string | null;
  last_error_step?: string | null;
  last_error_technical_message?: string | null;
  last_error_operation?: BackupOperation | null;
  health?: {
    config_valid?: boolean | null;
    drive_reachable?: boolean | null;
    repository_openable?: boolean | null;
    snapshots_readable?: boolean | null;
    snapshot_cache_fresh?: boolean | null;
    last_successful_refresh_at?: string | null;
    last_failure_at?: string | null;
    last_failure_code?: string | null;
    last_failure_step?: string | null;
    updated_at?: string | null;
  } | null;
  metrics?: {
    last_drive_probe_at?: string | null;
    last_drive_probe_duration_seconds?: number | null;
    last_repository_open_at?: string | null;
    last_repository_open_duration_seconds?: number | null;
    last_snapshot_refresh_at?: string | null;
    last_snapshot_refresh_duration_seconds?: number | null;
    last_snapshot_listing_at?: string | null;
    last_snapshot_listing_duration_seconds?: number | null;
  } | null;
  stale: boolean;
  stale_after_seconds?: number;
  refresh_queued?: boolean;
  refresh_operation?: BackupOperation | null;
  refresh_status?: string | null;
  refresh_trigger?: string | null;
}

export interface BackupExportOverview {
  enabled: boolean;
  repository: string;
  repository_type: "local" | "rclone" | string;
  export_root: string;
  export_folder_name: string;
  note: string;
}

export interface BackupFriendlyMessages {
  safe: string;
  drive: string;
  restore: string;
}

export interface BackupListResponse {
  engine: string;
  items: BackupListItem[];
  snapshots?: BackupListItem[];
  pagination: PaginationMeta;
  is_syncing?: boolean;
  last_sync_at?: string | null;
  active_run?: BackupRunState | null;
  system_active_run?: BackupRunState | null;
  cache: BackupCacheMeta;
  runs?: BackupRunState[];
  schedule: BackupScheduleMeta;
  schedule_runtime?: BackupScheduleRuntimeMeta | null;
  retention: BackupRetentionMeta;
  export_overview?: BackupExportOverview | null;
  friendly_messages?: BackupFriendlyMessages | null;
  last_successful_backup_at: string | null;
  repository_configured?: boolean;
  repository_error?: string | null;
  performance?: {
    served_in_ms?: number;
  } | null;
}

export interface BackupExportMetadata {
  snapshot_id: string;
  available: boolean;
  folder_name?: string | null;
  export_path?: string | null;
  drive_path?: string | null;
  remote_bundle_path?: string | null;
  bundle_filename?: string | null;
  local_root_relative_path?: string | null;
  local_bundle_relative_path?: string | null;
  generated_at?: string | null;
  artifacts?: string[];
  visible_artifacts?: string[];
  technical_artifacts?: string[];
  stats?: Record<string, unknown> | null;
  snapshot_created_at?: string | null;
  snapshot_backup_type?: string | null;
}

export interface BackupDetailResponse {
  snapshot: BackupListItem;
  includes: {
    database_dump: boolean;
    evidence_files: boolean;
    summary: boolean;
    metadata: boolean;
  };
  export: {
    available: boolean;
    state?: string | null;
    message?: string | null;
    run?: BackupRunState | null;
    drive_path?: string | null;
    export_path?: string | null;
    bundle_filename?: string | null;
    generated_at?: string | null;
    folder_name?: string | null;
    artifacts?: string[];
    technical_artifacts?: string[];
    stats?: Record<string, unknown> | null;
  };
  messages: BackupFriendlyMessages;
}

export interface ExportsInfoResponse {
  exports_root_path: string | null;
  exports_drive_path?: string | null;
  exports_folder_name?: string | null;
  repository_type?: string | null;
  open_url?: string | null;
  note?: string | null;
  runtime_readiness?: Record<string, unknown> | null;
}

export interface BackupActionRunResponse {
  run_id: string;
  operation?: BackupOperation;
  status: string;
  user_message?: string | null;
  error_code?: string | null;
  status_url: string | null;
}

export interface BackupUnlockResponse {
  unlocked: boolean;
  user_message?: string | null;
  technical?: {
    stdout?: string | null;
    stderr?: string | null;
  } | null;
}

export interface RestoreBackupPayload {
  scope: "db_only" | "files_only" | "full";
  target: "staging" | "current";
  confirm: boolean;
  confirm_phrase: string;
}

export interface FileDownloadPayload {
  blob: Blob;
  filename: string;
}

export interface ForgetSnapshotsPayload {
  snapshot_ids: string[];
}

export interface ForgetSnapshotsResponse {
  accepted?: boolean;
  run_id?: string;
  operation?: BackupOperation;
  status?: string;
  user_message?: string | null;
  error_code?: string | null;
  status_url?: string | null;
  snapshot_ids: string[];
  deleted_count?: number;
  stdout?: string | null;
  stderr?: string | null;
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

export async function listBackups(
  params: BackupListQuery = {}
): Promise<BackupListResponse> {
  const { data } = await http.get<ApiEnvelope<BackupListResponse>>(
    "/api/admin/backups",
    { params }
  );
  return data.data;
}

export async function getBackupDetail(
  snapshotId: string
): Promise<BackupDetailResponse> {
  const { data } = await http.get<ApiEnvelope<BackupDetailResponse>>(
    `/api/admin/backups/${snapshotId}`
  );
  return data.data;
}

export async function getExportsInfo(): Promise<ExportsInfoResponse> {
  const { data } = await http.get<ApiEnvelope<ExportsInfoResponse>>(
    "/api/admin/backups/exports-info"
  );
  return data.data;
}

export async function refreshBackups(): Promise<BackupActionRunResponse> {
  const { data } = await http.post<ApiEnvelope<BackupActionRunResponse>>(
    "/api/admin/backups/refresh"
  );
  return data.data;
}

export async function runBackup(): Promise<BackupActionRunResponse> {
  const { data } = await http.post<ApiEnvelope<BackupActionRunResponse>>(
    "/api/admin/backups/run"
  );
  return data.data;
}

export async function unlockStaleLock(): Promise<BackupUnlockResponse> {
  const { data } = await http.post<ApiEnvelope<BackupUnlockResponse>>(
    "/api/admin/backups/unlock-stale"
  );
  return data.data;
}

export async function getRunStatus(runId: string): Promise<BackupRunState> {
  const { data } = await http.get<ApiEnvelope<BackupRunState>>(
    `/api/admin/backups/runs/${runId}`
  );
  return data.data;
}

export async function restoreSnapshot(
  snapshotId: string,
  payload: RestoreBackupPayload
): Promise<BackupActionRunResponse> {
  const { data } = await http.post<ApiEnvelope<BackupActionRunResponse>>(
    `/api/admin/backups/${snapshotId}/restore`,
    payload
  );
  return data.data;
}

export async function downloadManifest(
  snapshotId: string
): Promise<FileDownloadPayload> {
  return downloadFile(
    `/api/admin/backups/${snapshotId}/manifest`,
    `backup_manifest_${snapshotId}.json`
  );
}

export async function downloadDbDump(
  snapshotId: string
): Promise<FileDownloadPayload> {
  return downloadFile(
    `/api/admin/backups/${snapshotId}/database-dump`,
    `backup_database_${snapshotId}.sql`
  );
}

export async function getExportMetadata(
  snapshotId: string
): Promise<BackupExportMetadata> {
  const { data } = await http.get<ApiEnvelope<BackupExportMetadata>>(
    `/api/admin/backups/${snapshotId}/export/metadata`
  );
  return data.data;
}

export async function downloadExport(
  snapshotId: string
): Promise<FileDownloadPayload> {
  return downloadFile(
    `/api/admin/backups/${snapshotId}/export/download`,
    "goi-sao-luu.zip"
  );
}

export async function forgetSnapshots(
  payload: ForgetSnapshotsPayload
): Promise<ForgetSnapshotsResponse> {
  const { data } = await http.post<ApiEnvelope<ForgetSnapshotsResponse>>(
    "/api/admin/backups/forget",
    payload
  );
  return data.data;
}
