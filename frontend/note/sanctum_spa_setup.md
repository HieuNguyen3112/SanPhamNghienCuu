# Cấu hình Sanctum cho SPA (Laravel 9 + Vue)

## Backend
- `config/sanctum.php`: thêm domain stateful mặc định gồm `localhost:5173`, `127.0.0.1:5173`, `spa.example.test` (cùng các host localhost khác). Guard vẫn `web`, expiration giữ nguyên.
- `config/cors.php`: dùng `CORS_ALLOWED_ORIGINS` (CSV) với mặc định `http://localhost:5173,http://127.0.0.1:5173`; bật `supports_credentials = true`.
- `.env`: đặt placeholders `SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173,localhost,127.0.0.1,spa.example.test` và `SESSION_DOMAIN=localhost`. Khi triển khai, thay bằng domain thực của frontend/backend (ví dụ `spa.example.com` và `.example.com`).

## Frontend (axios)
- `src/lib/http.ts`: cấu hình `baseURL` từ `VITE_API_URL` (fallback `http://localhost:8000`), `withCredentials=true`, `xsrfCookieName=XSRF-TOKEN`, `xsrfHeaderName=X-XSRF-TOKEN`.
- Export helper `ensureCsrfCookie()` để gọi `GET /sanctum/csrf-cookie` trước các request state-changing.

## Luồng SPA đăng nhập (đề xuất)
1) Trước POST login, gọi `ensureCsrfCookie()` để nhận cookie `XSRF-TOKEN` + `laravel_session` từ backend.
2) Gửi POST `/login` kèm header `X-XSRF-TOKEN` tự động bởi axios; cookie session sẽ được lưu do `withCredentials=true`.
3) Các request tiếp theo (web routes) dùng session cookie; với `/api/auth/*` có thể dùng Bearer PAT nếu cần.
