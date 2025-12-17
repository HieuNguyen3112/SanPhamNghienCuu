# Sanctum cleanup & scheduler

- `SANCTUM_EXPIRATION` (phút): thời gian hết hạn của Personal Access Token do Sanctum tạo. Giá trị mẫu 120 phút trong `.env.example`.
- Cần chạy cron ở môi trường sản xuất (vd: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`) để thực thi lịch Laravel.
- Hiện tại `app/Console/Kernel.php` đã lên lịch `sanctum:prune-expired --hours=48` chạy hằng ngày để dọn PAT hết hạn. Điều chỉnh `--hours` nếu muốn giữ log lâu hơn.
