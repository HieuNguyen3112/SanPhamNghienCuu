# Ánh xạ vai trò (canonical ↔ backend)

- Canonical (frontend/API) dùng: `LECTURER`, `DEPARTMENT_BOARD`, `SCIENCE_OFFICE`.
- Backend (Spatie roles) dùng: `GV`, `DL`, `QL`, `ADMIN`.

| Canonical | Backend chấp nhận | Ghi chú |
| --- | --- | --- |
| LECTURER | GV | Giảng viên |
| DEPARTMENT_BOARD | DL | Ban chủ nhiệm khoa |
| SCIENCE_OFFICE | QL, ADMIN | Phòng KHCN / Quản lý (bao gồm ADMIN) |

- Backend trả cả hai trường trong response: `roles` (canonical) và `backend_roles` (Spatie gốc) để tương thích lùi.
- Login payload nhận `role` ở dạng canonical; backend sẽ map sang backend roles tương ứng và kiểm tra quyền sở hữu.
