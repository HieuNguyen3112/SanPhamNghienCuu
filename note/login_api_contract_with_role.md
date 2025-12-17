# Hợp đồng API login (kèm role canonical)

- Endpoint: `POST /login` (middleware web + CSRF, throttle:login)
- Payload (JSON):
  - `email` (string, bắt buộc, email)
  - `password` (string, bắt buộc)
  - `role` (string, bắt buộc, canonical: `LECTURER` | `DEPARTMENT_BOARD` | `SCIENCE_OFFICE`)
  - `remember` (bool, tùy chọn)
- Xử lý:
  - Backend map `role` canonical sang backend roles (GV/DL/QL/ADMIN) và kiểm tra user có ít nhất một role tương ứng.
  - Không tự cấp PAT; chỉ thiết lập session/cookie.
- Response 200 (thành công):
```json
{
  "success": true,
  "message": "Dang nhap thanh cong",
  "token": null,
  "user": {
    "id": 1,
    "name": "User",
    "email": "u@example.com",
    "roles": ["SCIENCE_OFFICE"],        // canonical
    "backend_roles": ["QL","ADMIN"]     // Spatie gốc
  }
}
```
- Response 401: `{"message":"Invalid credentials"}`
- Response 403: `{"message":"Role not allowed for this user"}` (không sở hữu role yêu cầu) hoặc `{"message":"Email not verified"}`.
