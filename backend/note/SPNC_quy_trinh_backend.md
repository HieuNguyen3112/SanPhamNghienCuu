# SPNC Backend — Quy trình triển khai theo thứ tự (làm đến đâu FE dùng đến đó)

> Tài liệu này là **bản đúc kết quy trình** (process roadmap) để bạn làm lần lượt từng cụm.  
> Thứ tự ưu tiên: **chốt auth → lookups → profile → kê khai công trình → members → minh chứng → workflow duyệt → tính giờ → báo cáo/export**.

---

## 0) Chốt “hợp đồng Auth cho SPA” (để API nào cũng dùng được ổn định)

### Mục tiêu
- FE luôn gọi ổn định các endpoint liên quan user/session (đặc biệt `/me`, `/api/auth/me`, `/api/profile/*`, và các API nghiệp vụ sau này).
- Không còn tình trạng “đăng nhập rồi nhưng gọi API vẫn 401/419”.

### Checklist chính
- Chốt **1 mode chính** cho FE:
  - **Cookie-session + CSRF (Sanctum SPA)**: `GET /sanctum/csrf-cookie` → login → gọi API kèm cookie + `X-XSRF-TOKEN`.
  - **Bearer PAT** chỉ dành cho integration/CLI/admin (không phải mode chính cho FE).
- Thống nhất **middleware/guard** cho các route `/api/*`:
  - Nếu FE dùng cookie-session: `/api/*` nên chạy với `auth:sanctum` (stateful).
- Chuẩn hoá **response contract** cho `GET /api/v1/auth/me`:
  - `user{...}`, `roles[]`, `permissions[]` (hoặc `abilities[]`).

### DoD
- FE login xong gọi được:
  - `GET /api/v1/auth/me` (hoặc endpoint tương đương dự án)
  - `GET /api/v1/profile/me`
- Các lỗi trả về nhất quán:
  - `401` (unauth), `403` (forbidden), `422` (validation), `419` (CSRF) được xử lý rõ.

---

## 1) API + Seed danh mục xương sống (Lookups)

### Mục tiêu
- FE có dữ liệu dropdown/options thật để bắt đầu làm **kê khai công trình** và **lọc danh sách**.

### DB (nhóm bảng)
- Năm học/học kỳ: `academic_years` (hoặc tương đương)
- Đơn vị: `faculties`, `departments`
- Học hàm/học vị: `academic_ranks`, `degrees`
- Danh mục nghiệp vụ: `activity_kinds`, `activity_types`, `member_roles`, `evidence_file_types`, `activity_statuses`, `approval_stages`, `hour_rules`, `workload_quotas` (tuỳ thiết kế)

### Endpoints đề xuất (read-only trước)
- `GET /api/lookups/academic-years`
- `GET /api/lookups/faculties`
- `GET /api/lookups/departments?faculty_id=...`
- `GET /api/lookups/degrees`
- `GET /api/lookups/academic-ranks`
- `GET /api/lookups/activity-kinds`
- `GET /api/lookups/activity-types?kind=paper|book|project|conference`
- `GET /api/lookups/member-roles`
- `GET /api/lookups/evidence-file-types`
- `GET /api/lookups/activity-statuses`
- `GET /api/lookups/approval-stages`

### DoD
- FE form kê khai có dropdown đúng dữ liệu thật (không mock).
- FE filter/list có option theo năm học, loại công trình, trạng thái.

---

## 2) Hoàn thiện các tab Hồ sơ giảng viên (Profile)

### Mục tiêu
- FE trang “Hồ sơ giảng viên” load/save đầy đủ theo tab (không chỉ contact).

### DB (nhóm bảng)
- Hồ sơ: `lecturers`, `lecturer_profiles` (1-1)
- Lịch sử/chi tiết: `lecturer_training_histories`, `lecturer_work_histories`, `lecturer_language_proficiencies` (tuỳ DB thật)

### Endpoints đề xuất
- `GET /api/profile/me`
- `PATCH /api/profile/me` (update chung)
- `PUT /api/profile/contact`
- `PUT /api/profile/educations`
- `PUT /api/profile/work-histories`
- `PUT /api/profile/languages`
- (Tuỳ chọn) `PUT /api/profile/scientific` (ORCID/Scholar/Scopus…)

### DoD
- Mỗi tab có API riêng, save không ghi đè sai tab khác.
- Quyền rõ:
  - Giảng viên chỉ sửa hồ sơ mình
  - Admin/QL có thể xem/sửa hồ sơ người khác (nếu requirement).

---

## 3) CRUD kê khai công trình NCKH (draft-first)

### Mục tiêu
- Thay mock ở FE phần kê khai bằng API thật: tạo/sửa/xoá **draft**.

### DB (thiết kế chuẩn “base + detail”)
- Base: `research_activities`
- Detail 1-1 theo loại: `paper_details`, `book_details`, `project_details`, `conference_details` (hoặc tên tương đương)

### Luồng tối thiểu
1. Tạo activity `draft` trong bảng base.
2. Upsert bảng detail theo loại công trình.
3. List “Công trình của tôi” theo filter + phân trang.

### Endpoints đề xuất
- `GET /api/activities/my?year=&kind=&status=&q=&page=`
- `POST /api/activities` (tạo draft)
- `GET /api/activities/{id}`
- `PATCH /api/activities/{id}` (chỉ khi draft/rejected)
- `PUT /api/activities/{id}/details` (tự route theo kind hoặc tách ra từng loại)

### DoD
- FE tạo/sửa/lưu nháp được cho ít nhất 1–2 loại công trình trước.
- List & detail hoạt động ổn, không N+1 nghiêm trọng.

---

## 4) Quản lý thành viên/tác giả + vai trò + % đóng góp

### Mục tiêu
- Chuẩn hoá logic phân bổ thành viên để phục vụ tính giờ đúng.

### DB
- `research_activity_members` (unique: `activity_id + lecturer_id`)
- Join lookup role: `member_roles`
- Các field chính: `role_id`, `contribution_share` (nếu dùng), `hours_assigned` (sau tính giờ)

### Endpoints đề xuất
- `GET /api/activities/{id}/members`
- `PUT /api/activities/{id}/members` (replace list members)

### Rule/Validation gợi ý
- Có ít nhất 1 “principal/chủ nhiệm/chủ biên” (tuỳ loại).
- Nếu dùng `contribution_share`: tổng share = 1.0 hoặc 100%.

### DoD
- FE quản lý members thật, validate hợp lý, không cho sửa members khi đã submitted (tuỳ rule).

---

## 5) Upload minh chứng / tệp đính kèm

### Mục tiêu
- Upload file minh chứng, gắn vào công trình, và người duyệt có thể tải/xem.

### DB
- `evidence_files` (gắn `activity_id`)
- Lookup loại file: `evidence_file_types`
- Metadata: `original_name`, `mime`, `size`, `path`, `sha256` (unique)

### Endpoints đề xuất
- `POST /api/activities/{id}/evidences` (multipart upload)
- `GET /api/activities/{id}/evidences`
- `DELETE /api/evidences/{evidenceId}`
- `GET /api/evidences/{evidenceId}/download` (hoặc signed url)

### DoD
- FE upload xong thấy ngay file trong danh sách.
- Phân quyền tải file đúng (owner + reviewer + admin).

---

## 6) Workflow duyệt 2 vòng + comment/feedback + lịch sử trạng thái

### Mục tiêu
- Dòng đời công trình: `draft → submitted → (faculty review) → (pqlkh review) → approved/rejected`.
- Có comment/feedback, lưu lịch sử chuyển trạng thái.

### DB
- Trạng thái: `activity_statuses` (lookup)
- Lịch sử: `activity_status_histories`
- Duyệt theo stage: `activity_approvals` (stage, status, note, approver_id)
- (Tuỳ) bảng `approval_stages`

### Endpoints đề xuất
- `POST /api/activities/{id}/submit`
- `GET /api/approvals/inbox` (danh sách chờ duyệt theo role)
- `POST /api/activities/{id}/approve` (kèm stage)
- `POST /api/activities/{id}/reject` (kèm note)
- `GET /api/activities/{id}/timeline` (history + approvals + comments)

### DoD
- FE có màn “Gửi duyệt”, “Hộp thư duyệt”, “Duyệt/Từ chối”, “Timeline”.
- Permission đúng:
  - GV không duyệt được
  - Người duyệt chỉ thấy item thuộc phạm vi (khoa/đơn vị) theo rule.

---

## 7) Tính giờ NCKH tự động + tổng hợp theo năm học

### Mục tiêu
- Khi công trình **approved**, hệ thống tự tính giờ cho từng thành viên và tổng hợp theo năm học/học kỳ.

### DB
- Rules: `hour_rules`, (tuỳ) `workload_quotas`
- Kết quả: `research_activity_members.hours_assigned`, `research_activities.total_hours_calc`
- Tổng hợp: `lecturer_yearly_hours`
- Theo dõi: `calculation_logs`

### Endpoints đề xuất
- `GET /api/workloads/me?academic_year=...`
- `GET /api/workloads/lecturers/{id}?academic_year=...` (role cao hơn)
- `POST /api/admin/hour-rules/recalculate?academic_year=...` (batch/queue)
- `POST /api/activities/{id}/recalculate-hours` (nếu cần)

### DoD
- FE xem được tổng giờ theo năm học cho cá nhân.
- Chạy batch khi rule đổi, có log.

---

## 8) Tra cứu + Báo cáo + Export

### Mục tiêu
- BCN Khoa / PQLKH tra cứu công trình và export báo cáo theo năm, khoa, đơn vị, cá nhân.

### Endpoints đề xuất
- `GET /api/activities/search?year=&kind=&status=&q=&faculty_id=&department_id=&page=`
- `GET /api/reports/my-summary?academic_year=...`
- `GET /api/reports/department-summary?academic_year=...&department_id=...`
- `POST /api/reports/exports` (tạo job export)
- `GET /api/reports/exports/{id}` (status)
- `GET /api/reports/exports/{id}/download`

### DoD
- FE trang tổng hợp/overview chạy end-to-end.
- Export chạy qua queue (khuyến nghị), user tải file được.

---

## Thứ tự implement thực tế (để ít bị “kẹt dây chuyền”)
1. (0) Chốt Auth contract + `/api/auth/me`
2. (1) Lookups (read-only + seed)
3. (2) Profile tabs
4. (3) CRUD Activity (draft) cho 1–2 loại trước
5. (4) Members
6. (5) Evidence upload
7. (6) Workflow duyệt
8. (7) Tính giờ
9. (8) Search/Report/Export
