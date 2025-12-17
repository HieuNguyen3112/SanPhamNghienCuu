# Hợp đồng API xác thực (session + Sanctum)

Nguồn: `routes/web.php`, `routes/api.php`, `SessionAuthController`, `ProfileController`. Các endpoint dưới đây đều trả JSON. Với route `web` cần cookie session + header `X-CSRF-TOKEN`; với route `auth:sanctum` cần Bearer PAT hợp lệ.

## POST /login (web, throttle:login)
- Payload JSON:
  - `email` (string, bắt buộc, email)
  - `password` (string, bắt buộc)
  - `remember` (bool, tùy chọn)
  - `token_name` (string ≤100, tùy chọn, mặc định `session-token`)
  - `token_abilities` (array<string>, tùy chọn, mặc định `['*']`)
  - `single_device` (bool, tùy chọn; true sẽ xóa PAT cùng tên trước khi cấp mới)
- Response 200:
  ```json
  {
    "success": true,
    "message": "Dang nhap thanh cong",
    "token": "<plainTextToken>",
    "user": { "id": 1, "name": "...", "email": "...", "roles": ["GV", "..."] }
  }
  ```
- Lỗi:
  - 422 validation (thiếu/sai định dạng trường) dạng mặc định Laravel `{message, errors: {...}}`.
  - 422 `{"message": "Invalid credentials"}` khi sai email/password.
  - 403 `{"message": "Email not verified"}` nếu tài khoản chưa xác minh.
  - 500 `{"message": "User not found"}` nếu đăng nhập xong nhưng không tìm thấy user (hiếm).

## POST /logout (web, auth)
- Yêu cầu: session hợp lệ; controller cũng thử đọc Bearer PAT để thu hồi.
- Payload: không bắt buộc (chỉ cần cookie CSRF/session; có thể kèm Bearer token hiện tại để bị revoke).
- Response 200: `{"message": "ok"}`
- Lỗi: 401/419 khi chưa đăng nhập hoặc CSRF sai (mặc định Laravel); 422 nếu validation CSRF fail.

## GET /me
- `/me` (web, auth + verified) hoặc `/api/auth/me` (auth:sanctum + auto.rotate.sanctum).
- Payload: none.
- Response 200:
  ```json
  { "id": 1, "name": "...", "email": "...", "roles": ["GV", "..."] }
  ```
- Lỗi: 401/419 nếu chưa đăng nhập; 403 nếu web route nhưng email chưa verify (do middleware `verified`).

## PUT /profile/password (web, auth + verified)
- Payload JSON:
  - `current_password` (string, bắt buộc, phải đúng mật khẩu hiện tại)
  - `password` (string, bắt buộc, tối thiểu 8 ký tự, có hoa/thường/số/ký tự đặc biệt)
  - `password_confirmation` (string, bắt buộc, trùng `password`)
- Response 200: `{"message": "password updated"}` (kèm regenerate session phía server)
- Lỗi:
  - 422 validation `{message, errors: {...}}` (sai định dạng, thiếu confirmation, rule current_password sai cũng trả 422).
  - 401/419 nếu chưa đăng nhập/CSRF sai; 403 nếu chưa verify email (do middleware `verified`).
