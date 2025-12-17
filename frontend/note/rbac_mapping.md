# Mapping role backend ↔ frontend

- Backend roles: `ADMIN`, `QL` (quản lý/QLKH), `DL` (điều phối/khoa/phòng), `GV` (giảng viên).
- Frontend `UserRole`: `LECTURER`, `DEPARTMENT_BOARD`, `SCIENCE_OFFICE`.

## Quy tắc ánh xạ
- `GV` → `LECTURER`.
- `DL` → `DEPARTMENT_BOARD` (ban chủ nhiệm/điều phối khoa/phòng).
- `QL` → `SCIENCE_OFFICE` (phòng/ban quản lý khoa học; quản lý cấp cao nếu chưa tách ADMIN riêng).
- `ADMIN` → `SCIENCE_OFFICE` (có thể kèm flag admin nếu UI cần phân biệt quản trị hệ thống).

## Ví dụ nhiều vai trò
- Backend `["GV", "QL"]` → frontend `["LECTURER", "SCIENCE_OFFICE"]`; người dùng chọn role active qua dropdown.
- Backend `["GV", "DL"]` → frontend `["LECTURER", "DEPARTMENT_BOARD"]`.
- Backend `["ADMIN", "QL"]` → frontend `["SCIENCE_OFFICE"]` (có thể kèm flag admin để hiển thị nhãn/quyền rộng hơn).

## Ghi chú cho store frontend
- `currentUser.roles` lưu mảng đã ánh xạ; loại bỏ role không nhận diện.
- Nếu kết quả mapping rỗng, nên chặn đăng nhập với lỗi `ROLE_KHONG_HOP_LE`.
- `setRole` chỉ chấp nhận giá trị nằm trong `currentUser.roles`; UI dropdown nên lấy từ mảng này.

## Chạy seeders RBAC (backend)
- Tạo roles/permissions: `php artisan db:seed --class=RolesPermissionsSeeder`.
- Tạo người dùng demo (ADMIN/QL/DL/GV) và gán role: `php artisan db:seed --class=UsersDemoSeeder`.
- Có thể chạy gộp: `php artisan db:seed --class="RolesPermissionsSeeder,UsersDemoSeeder"` hoặc chạy toàn bộ `php artisan db:seed` nếu cấu hình `DatabaseSeeder` đã gọi hai seeder trên.
