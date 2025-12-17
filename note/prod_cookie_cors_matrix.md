# Prod cookie/CORS matrix

Ghi chú nhanh cho môi trường production (Laravel 9, /login dùng session + CSRF qua web middleware, /api/* dùng Sanctum stateful). Luôn bắt buộc HTTPS, bật SESSION_SECURE_COOKIE, và giữ supports_credentials = true trong CORS.

## Topology A: Same-site
- Frontend và backend cùng site/host, ví dụ: https://app.example.com

## Topology B: Cross-site
- Frontend và backend khác domain/subdomain, ví dụ: frontend https://spa.example.com, backend https://api.example.com

| Biến ENV | A (same-site) | B (cross-site) | Ghi chú |
| --- | --- | --- | --- |
| APP_URL | https://app.example.com | https://api.example.com | Địa chỉ backend; dùng HTTPS khớp host thật. |
| SESSION_DOMAIN | app.example.com (hoặc .example.com nếu chia sẻ cookie giữa các subdomain) | api.example.com (hoặc .example.com nếu frontend là subdomain) | Phải khớp host backend để trình duyệt lưu cookie. |
| SESSION_SECURE_COOKIE | true | true | Yêu cầu cookie chỉ gửi qua HTTPS. |
| SESSION_SAME_SITE | lax | none | Cross-site cần none (+ Secure) để trình duyệt gửi cookie kèm request. |
| SANCTUM_STATEFUL_DOMAINS | app.example.com | spa.example.com | Danh sách host frontend (không http/https), có thể kèm port nếu khác 443/80. |
| CORS_ALLOWED_ORIGINS | https://app.example.com | https://spa.example.com | CSV các origin được phép; phải khớp origin frontend. |

Lưu ý: sửa các giá trị trên theo domain thật của mỗi môi trường (prod/staging), giữ đồng bộ giữa SESSION_DOMAIN, SANCTUM_STATEFUL_DOMAINS và CORS_ALLOWED_ORIGINS để cookie + CSRF hoạt động đúng.

## Lỗi thường gặp gây 401/419
- SANCTUM_STATEFUL_DOMAINS có giao thức (http/https) hoặc sai port -> cookie không được gắn.
- SESSION_SAME_SITE=none nhưng quên đặt SESSION_SECURE_COOKIE=true trên HTTPS -> trình duyệt chặn cookie.
- CORS_ALLOWED_ORIGINS không khớp origin frontend hoặc bỏ quên supports_credentials -> CSRF cookie/XSRF token không được gửi.
- SESSION_DOMAIN sai (khác host backend hoặc không đặt .example.com khi cần chia sẻ subdomain) -> session cookie không được trình duyệt lưu.
