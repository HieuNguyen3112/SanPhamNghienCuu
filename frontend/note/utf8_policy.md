# UTF-8 Policy (Frontend)

## Mục tiêu
- Toàn bộ mã nguồn frontend phải ở `UTF-8` (không BOM).
- Không để lọt chuỗi tiếng Việt bị lỗi dấu (mojibake).

## Quy định bắt buộc
- Trước khi commit, chạy:
  - `npm run check:utf8`
- Nếu script báo lỗi, phải sửa encoding/nội dung trước khi push.

## VS Code
- Workspace đã cấu hình:
  - `files.encoding = utf8`
  - `files.autoGuessEncoding = false`
  - `files.eol = \n`

## Cách sửa file bị lỗi
1. Mở file trong VS Code.
2. Chọn `Reopen with Encoding` để kiểm tra encoding hiện tại.
3. Chọn `Save with Encoding` -> `UTF-8`.
4. Rà lại các chuỗi tiếng Việt bị hỏng và sửa về đúng chính tả.
