# SPNC Backup & Restore Runbook

## 1) Yêu cầu hạ tầng

- Cài `restic` trên máy chủ backend.
- Cài `rclone` và cấu hình remote cloud (khuyến nghị Google Drive).
- Cấu hình biến môi trường trong `.env`:
  - `SPNC_BACKUP_REPOSITORY` (ví dụ `rclone:gdrive:spnc-backups`)
  - `SPNC_BACKUP_PASSWORD`
  - `SPNC_BACKUP_RESTIC_BINARY` (ví dụ `C:/Tools/restic/restic.exe`)
  - `SPNC_BACKUP_RCLONE_BINARY` (ví dụ `C:/Tools/rclone/rclone.exe`)
  - `SPNC_BACKUP_RCLONE_CONFIG` (nếu cần chỉ rõ file cấu hình rclone)
  - Tuỳ chọn khi dùng proxy:
    - `SPNC_BACKUP_HTTP_PROXY`
    - `SPNC_BACKUP_HTTPS_PROXY`
    - `SPNC_BACKUP_NO_PROXY`

## 2) Phạm vi sao lưu mặc định

- Database MySQL (full dump).
- `storage/app/evidence`
- `storage/app/public`

## 3) Dữ liệu loại trừ mặc định

- `storage/framework`
- `storage/logs`
- `backend/vendor`
- `backend/node_modules`
- `frontend/node_modules`
- `.git`
- `.env` (không bao giờ đưa secrets lên cloud)

## 4) Lệnh vận hành

- Chạy backup thủ công:
  - `php artisan spnc:backup:run --trigger=manual`
- Dọn snapshot cũ theo retention:
  - `php artisan spnc:backup:prune --trigger=manual`

## 5) Lịch tự động

- `Mon` + `Thu` lúc `02:00`: chạy backup
- `Mon` + `Thu` lúc `03:00`: chạy prune

> Cần cron scheduler Laravel:
> `* * * * * php /path/to/backend/artisan schedule:run >> /dev/null 2>&1`

## 6) Khôi phục an toàn

- Mặc định khôi phục vào vùng staging (`target=staging`).
- Khôi phục trực tiếp (`target=current`) chỉ khi:
  - `SPNC_BACKUP_ALLOW_LIVE_RESTORE=true`
  - truyền đúng cụm từ xác nhận `SPNC_BACKUP_RESTORE_CONFIRM_PHRASE`.
