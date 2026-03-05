# Backup Lifecycle (Admin /system/backups)

## 1) Chạy sao lưu (Run)

1. `POST /api/admin/backups/run` tạo `run_id`, ghi trạng thái `queued` vào `BackupRunStateStore`.
2. Controller gọi `BackupRunLauncher::launchBackup(...)` để chạy nền command `spnc:backup:run`.
3. `SpncBackupRun` thực thi:
   - dump MySQL (`ResticBackupManager::dumpDatabase`)
   - tạo `manifest.json` (metadata + verification)
   - chạy `restic backup` cho DB dump + thư mục dữ liệu quan trọng
   - (tuỳ chọn) prune theo retention
   - cập nhật cache snapshot (`BackupSnapshotStore`)
4. UI poll `GET /api/admin/backups/runs/{run_id}` để hiển thị tiến trình.

## 2) Danh sách snapshot (List)

1. `GET /api/admin/backups` chỉ đọc từ `BackupSnapshotStore` (file cache), có phân trang/filter.
2. Không gọi `restic snapshots` trực tiếp trong request list.
3. Làm mới cache bằng:
   - `POST /api/admin/backups/refresh` (manual)
   - hoặc auto refresh nền khi cache stale.
4. Command nền `spnc:backup:snapshots:refresh` cập nhật cache từ repository restic.

## 3) Khôi phục (Restore)

1. `POST /api/admin/backups/{snapshotId}/restore` validate:
   - role admin/system
   - `snapshotId` allow-list
   - `scope`, `target`, `confirm_phrase`
2. Endpoint chỉ trigger job nền (`spnc:backup:restore`) và trả `run_id`.
3. `SpncBackupRestore`:
   - bật maintenance mode nếu restore vào `current`
   - restore snapshot theo scope
   - import DB dump + mirror lại thư mục dữ liệu
   - ghi audit log success/failure
4. UI tiếp tục poll run status để thấy kết quả.

## 4) Điểm kiểm chứng “backup thật”

- DB dump bắt buộc tồn tại và có kích thước > 0.
- Snapshot metadata có `contains_db_dump`, `contains_files`, `backup_type`.
- Backup mặc định gồm DB + `storage/app/evidence` + `storage/app/public` (theo config).

## 5) Vị trí mã chính

- Route/API: `backend/routes/api.php`
- Controller: `backend/app/Http/Controllers/AdminBackupController.php`
- Core backup: `backend/app/Services/Backup/ResticBackupManager.php`
- State run: `backend/app/Services/Backup/BackupRunStateStore.php`
- Snapshot cache: `backend/app/Services/Backup/BackupSnapshotStore.php`
- Background commands:
  - `backend/app/Console/Commands/SpncBackupRun.php`
  - `backend/app/Console/Commands/SpncBackupPrune.php`
  - `backend/app/Console/Commands/SpncBackupRestore.php`
  - `backend/app/Console/Commands/SpncBackupSnapshotRefresh.php`
