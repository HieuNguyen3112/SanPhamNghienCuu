# Kiểm thử hồi quy Issue #5 (Sanctum session vs token)

- [ ] Login SPA (cookie/session): GET `/sanctum/csrf-cookie`, POST `/login` với email/password/role; kiểm tra response `token: null`, cookie `XSRF-TOKEN` + `laravel_session` nhận được.
- [ ] Không tăng PAT: trước/sau login, đếm bảng `personal_access_tokens` (hoặc GET `/api/auth/tokens` nếu đang có PAT) để xác nhận không có token mới sinh ra khi login SPA.
- [ ] Token on-demand: với user có quyền `ADMIN` hoặc `QL`, gọi POST `/api/auth/token/issue` (Bearer PAT hiện có hoặc session + auth:sanctum) và nhận `token` mới; xác nhận route vẫn hoạt động.
- [ ] Logout/session: POST `/logout` (kèm cookie) và kiểm tra GET `/me` trả 401/redirect; đồng thời, nếu đang gọi PAT hiện tại, route revoke của SessionAuthController không xóa nhầm PAT khác (chỉ session_token_id hoặc bearer hiện tại).
