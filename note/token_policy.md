# Token policy đề xuất (Sanctum)

## Hiện trạng
- SessionAuthController (/login) chỉ tạo session (cookie + CSRF); không còn auto-issue PAT cho SPA. Response trả `token: null`. PAT chỉ lấy qua endpoints token riêng.
- TokenAuthController hỗ trợ issue/revoke/revoke-all/rotate, duyệt token, và trả TTL (`/api/auth/token/ttl`).
- Middleware AutoRotateSanctumToken xoay PAT tự động khi minutes_left <= `sanctum.refresh_threshold` (env SANCTUM_REFRESH_THRESHOLD, mặc định 5 phút); có thể revoke token cũ khi `sanctum.revoke_old_on_rotate` / env SANCTUM_REVOKE_OLD_ON_ROTATE = true.
- Config `sanctum.expiration` (env SANCTUM_EXPIRATION) = 120 phút là nguồn duy nhất về TTL; refresh threshold và revoke_old_on_rotate cũng nằm trong `config/sanctum.php`.
- Nguy cơ: PAT đang được tạo mỗi lần login dù SPA chỉ dùng session/cookie -> tích lũy PAT, rò rỉ Bearer nếu lưu vào local storage.

## Lựa chọn A (đang áp dụng, khuyến nghị): SPA chỉ dùng session, PAT chỉ phát sinh khi on-demand
- Nguyên tắc: login chỉ tạo session cookie + CSRF; không tạo PAT ngầm. PAT chỉ được issue qua `/auth/token/issue` khi user có nhu cầu tích hợp API hoặc máy chủ.
- Tính năng giữ lại:
  - Giữ lại TokenAuthController cho use-case API (Bearer).
  - Giữ AutoRotateSanctumToken cho các route `auth:sanctum` (API) nếu cần tự động làm mới Bearer.
- Điều chỉnh code tương lai:
  - Bỏ tạo PAT trong SessionAuthController@login; session_token_id không cần lưu.
  - Đảm bảo /logout chỉ hủy session; PAT API do user issue sẽ tự quản lý (revoke/rotate).
  - Frontend không lưu/plainTextToken vào storage; chỉ dựa vào cookie stateful + CSRF.
- Cấu hình:
  - Giữ `sanctum.expiration` = 120 (hoặc như yêu cầu API).
  - Để `sanctum.refresh_threshold` = 5 (nếu muốn auto-rotate cho API), có thể bật `sanctum.revoke_old_on_rotate` nếu muốn xóa token cũ sau khi xoay.
  - Lịch prune: chạy `sanctum:prune-expired` (Scheduler) để dọn token hết hạn.

## Lựa chọn B (nếu cần): Vẫn tạo PAT khi login, nhưng đặt quy tắc rõ TTL/rotation/cleanup
- Mục đích: giữ lại khả năng dùng Bearer token cho SPA (nếu cần) và tự động làm mới.
- Nguyên tắc:
  - PAT login chỉ song song với session, không thay thế session; không lưu PAT vào local storage công cộng.
  - Đặt single_device=true khi login để không tích lũy PAT cùng tên.
  - Tên token login rõ ràng (vd `session-token`), abilities tối thiểu (`['*']` nếu chưa phân quyền chi tiết).
- Cấu hình:
  - `sanctum.expiration` = 120 phút (hoặc giá trị được chọn, nhất quán giữa envs).
  - `SANCTUM_REFRESH_THRESHOLD` = 5 (phút) để AutoRotateSanctumToken trả token mới trước khi hết hạn.
  - `SANCTUM_REVOKE_OLD_ON_ROTATE` = true để tự động xóa token cũ sau khi xoay.
  - Scheduler: chạy `sanctum:prune-expired` hàng ngày để dọn PAT hết hạn.
- Luồng logout:
  - Giữ logic hiện tại: revoke session_token_id và Bearer nếu đang dùng.
  - Frontend chỉ lưu Bearer trong memory (nếu thực sự cần), sau logout phải gọi /logout và xóa Bearer memory.

## Đề xuất tổng thể
- Ưu tiên Lựa chọn A cho SPA nội bộ: giảm rủi ro PAT, đơn giản hóa luồng auth (cookie + CSRF).
- Chỉ áp dụng Lựa chọn B nếu SPA thực sự cần Bearer (native app, script ngoài trình duyệt) và có quy trình lưu trữ an toàn + auto-rotate + cleanup.

## Endpoint su dung cho "me"
- SPA (session/cookie + CSRF): goi `/me` (routes/web.php, middleware auth + verified + web). Dung cho frontend HCMUE.
- Client dung Bearer PAT: goi `/api/auth/me` (routes/api.php, middleware auth:sanctum + auto.rotate.sanctum). Dung cho tich hop API hoac native.
