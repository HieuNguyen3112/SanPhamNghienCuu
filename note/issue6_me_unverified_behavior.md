# /me behavior (Issue #6)

## Chinh sach hien tai
- `/me` (web): middleware `force.json`, `auth`, `verified`, `role:GV|DL|QL|ADMIN`; luon tra JSON (khong redirect/HTML).
- `/api/auth/me`: middleware `auth:sanctum`, `auto.rotate.sanctum`; khong `verified`/`role` filter.

## Ma trang thai ky vong
- Chua dang nhap: `/me` web tra 401 JSON `{ "message": "Unauthenticated" }` (khong 302 redirect).
- Chua xac minh email: middleware `verified` abort 403 => Handler tra JSON `{ code: "UNVERIFIED_EMAIL", message: "Email chua duoc xac minh" }`.
- Thieu role hop le: Spatie UnauthorizedException => Handler tra 403 JSON `{ "message": "Forbidden: missing role or permission" }`.
- Hop le: 200 JSON user `{ id, name, email, roles, backend_roles }`.

## Ly do tranh loop frontend
- `force.json` bat Accept=application/json giup auth/verified/role tra ve JSON thay vi 302 HTML; axios se nhan 401/403 ro rang, khong follow redirect -> tranh vong lap.
- `/api/auth/me` van 401 JSON neu chua auth; neu auth nhung chua verify/role van 200 (khong doi tuong chinh).
