# Ki?m th? h?i quy Issue #5 (Sanctum session vs token)

- [ ] Login SPA (cookie/session): GET `/sanctum/csrf-cookie`, POST `/login` v?i email/password/role; ki?m tra response `token: null`, cookie `XSRF-TOKEN` + `laravel_session` nh?n du?c.
- [ ] Không tang PAT: tru?c/sau login, d?m b?ng `personal_access_tokens` (ho?c GET `/api/auth/tokens` n?u dang có PAT) d? xác nh?n không có token m?i sinh ra khi login SPA.
- [ ] Token on-demand: v?i user có quy?n `ADMIN` ho?c `QL`, g?i POST `/api/auth/token/issue` (Bearer PAT hi?n có ho?c session + auth:sanctum) và nh?n `token` m?i; xác nh?n route v?n ho?t d?ng.
- [ ] Logout/session: POST `/logout` (kèm cookie) và ki?m tra GET `/me` tr? 401/redirect; d?ng th?i, n?u dang g?i PAT hi?n t?i, route revoke c?a SessionAuthController không xóa nh?m PAT khác (ch? session_token_id ho?c bearer hi?n t?i).
