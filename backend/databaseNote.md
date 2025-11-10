# Thiết kế CSDL MySQL (3NF) – Hệ thống “Quản lý Công trình Khoa học của Giảng viên”

_Ngày xuất bản:_ 2025-11-10 17:50:54

---

## 0) Nguyên tắc chung

- **Chuẩn hoá 3NF**; tách danh mục (lookup) riêng; tránh lặp dữ liệu.
- **Kiểu dữ liệu**: `BIGINT UNSIGNED` cho PK/FK; `utf8mb4_unicode_ci`.
- **Quan hệ**: ràng buộc FK đầy đủ; chọn `RESTRICT`/`CASCADE` hợp lý (log/minh chứng thường CASCADE).
- **Laravel**: dùng migrations, seeders; có thể dùng **spatie/laravel-permission** cho RBAC.

---

## 1) Thứ tự tạo bảng (migration order)

1. `academic_years`
2. `workload_quotas`
3. `faculties`
4. `departments`
5. `degrees`
6. `academic_ranks`
7. `users` _(mặc định Laravel)_
8. `lecturers`
9. `activity_kinds`
10. `activity_types`
11. `member_roles`
12. `evidence_file_types`
13. `activity_statuses`
14. `approval_stages`
15. `research_activities`
16. `paper_details`
17. `book_details`
18. `project_details`
19. `conference_details`
20. `research_activity_members`
21. `activity_status_histories`
22. `activity_approvals`
23. `evidence_files`
24. `hour_rules`
25. `calculation_logs`
26. `lecturer_yearly_hours`

> Gợi ý: Publish migrations của **spatie/laravel-permission** ngay sau khi tạo DB (không phụ thuộc bảng khác).

---

## 2) Cấu trúc chi tiết các bảng

### (A) Danh mục & Niên học

#### 1. `academic_years`

- `id` BIGINT PK
- `code` VARCHAR(9) **UNIQUE** (vd `2024-2025`)
- `start_date` DATE
- `end_date` DATE
- `is_active` TINYINT(1)
- `created_at`, `updated_at`

#### 2. `workload_quotas`

- `id` BIGINT PK
- `academic_year_id` BIGINT FK → `academic_years.id` (**RESTRICT**)
- `required_hours` DECIMAL(6,2) DEFAULT 600.00
- `notes` VARCHAR(255) NULL
- UNIQUE(`academic_year_id`)
- timestamps

#### 3. `faculties`

- `id` BIGINT PK
- `code` VARCHAR(50) **UNIQUE**
- `name` VARCHAR(255)
- timestamps

#### 4. `departments`

- `id` BIGINT PK
- `faculty_id` BIGINT FK → `faculties.id` (**RESTRICT**)
- `code` VARCHAR(50)
- `name` VARCHAR(255)
- UNIQUE(`faculty_id`,`code`)
- timestamps

#### 5. `degrees`

- `id` BIGINT PK
- `code` VARCHAR(50) **UNIQUE**
- `name` VARCHAR(255)
- timestamps

#### 6. `academic_ranks`

- `id` BIGINT PK
- `code` VARCHAR(50) **UNIQUE**
- `name` VARCHAR(255)
- timestamps

### (B) Người dùng & Giảng viên

#### 7. `users` _(mặc định Laravel)_

- Các trường chuẩn: `id`, `name`, `email`, `password`, ...

#### 8. `lecturers`

- `id` BIGINT PK
- `user_id` BIGINT FK → `users.id` (**SET NULL**)
- `code` VARCHAR(50) **UNIQUE**
- `full_name` VARCHAR(255)
- `email` VARCHAR(255) **UNIQUE**
- `phone` VARCHAR(30) NULL
- `degree_id` BIGINT FK → `degrees.id` (**SET NULL**)
- `academic_rank_id` BIGINT FK → `academic_ranks.id` (**SET NULL**)
- `department_id` BIGINT FK → `departments.id` (**RESTRICT**)
- `active` TINYINT(1) DEFAULT 1
- timestamps

### (C) RBAC (tuỳ chọn – Spatie)

- `roles`, `permissions`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`

### (D) Danh mục nghiệp vụ NCKH

#### 9. `activity_kinds`

- `id` BIGINT PK
- `code` VARCHAR(50) **UNIQUE** (paper, book, project, conference)
- `name` VARCHAR(255)
- timestamps

#### 10. `activity_types`

- `id` BIGINT PK
- `kind_id` BIGINT FK → `activity_kinds.id` (**RESTRICT**)
- `code` VARCHAR(50) **UNIQUE**
  - paper: `hdgsnn_900`,`hdgsnn_600`,`hdgsnn_300`
  - book: `textbook` (Giáo trình), `reference` (TLTK)
  - project: `bo`, `coso`
  - conference: `report`,`attend`
- `name` VARCHAR(255)
- timestamps

#### 11. `member_roles`

- `id` BIGINT PK
- `code` VARCHAR(50) **UNIQUE** (`principal`,`member`,...)
- `name` VARCHAR(255)
- timestamps

#### 12. `evidence_file_types`

- `id` BIGINT PK
- `code` VARCHAR(50) **UNIQUE** (`content`,`cover`,`toc`,`acceptance_decision`,`publication_decision`)
- `name` VARCHAR(255)
- timestamps

#### 13. `activity_statuses`

- `id` BIGINT PK
- `code` VARCHAR(30) **UNIQUE** (`draft`,`submitted`,`approved`,`rejected`)
- `name` VARCHAR(100)
- timestamps

#### 14. `approval_stages`

- `id` BIGINT PK
- `code` VARCHAR(50) **UNIQUE** (`assistant`,`manager`)
- `name` VARCHAR(100)
- `order_no` TINYINT
- timestamps

### (E) Nghiệp vụ chính: Kê khai – Duyệt – Minh chứng

#### 15. `research_activities`

- `id` BIGINT PK
- `activity_code` VARCHAR(50) **UNIQUE**
- `owner_lecturer_id` BIGINT FK → `lecturers.id` (**RESTRICT**)
- `kind_id` BIGINT FK → `activity_kinds.id` (**RESTRICT**)
- `type_id` BIGINT FK → `activity_types.id` (**SET NULL**)
- `academic_year_id` BIGINT FK → `academic_years.id` (**RESTRICT**)
- `status_id` BIGINT FK → `activity_statuses.id` (**RESTRICT**)
- `title` VARCHAR(500)
- `abstract` TEXT NULL
- `start_date` DATE NULL
- `end_date` DATE NULL
- `quantity` INT DEFAULT 1
- `submitted_at` DATETIME NULL
- `approved_at` DATETIME NULL
- `total_hours_calc` DECIMAL(8,2) NULL
- `notes` VARCHAR(500) NULL
- timestamps

#### 16. `paper_details` _(1–1)_

- `activity_id` BIGINT **PK/FK** → `research_activities.id` (**CASCADE**)
- `journal_name` VARCHAR(255) NULL
- `issn` VARCHAR(50) NULL
- `doi` VARCHAR(100) NULL
- `volume` VARCHAR(50) NULL
- `issue` VARCHAR(50) NULL
- `year` INT NULL
- timestamps

#### 17. `book_details` _(1–1)_

- `activity_id` BIGINT **PK/FK** → `research_activities.id` (**CASCADE**)
- `publisher` VARCHAR(255)
- `isbn` VARCHAR(50) NULL
- `pages` INT NULL
- `year` INT NULL
- timestamps

#### 18. `project_details` _(1–1)_

- `activity_id` BIGINT **PK/FK** → `research_activities.id` (**CASCADE**)
- `project_code` VARCHAR(100) NULL
- `funding` DECIMAL(12,2) NULL
- `start_month` DATE NULL
- `end_month` DATE NULL
- timestamps

#### 19. `conference_details` _(1–1)_

- `activity_id` BIGINT **PK/FK** → `research_activities.id` (**CASCADE**)
- `conference_name` VARCHAR(255)
- `location` VARCHAR(255) NULL
- `held_on` DATE NULL
- timestamps

#### 20. `research_activity_members`

- `id` BIGINT PK
- `activity_id` BIGINT FK → `research_activities.id` (**CASCADE**)
- `lecturer_id` BIGINT FK → `lecturers.id` (**RESTRICT**)
- `member_role_id` BIGINT FK → `member_roles.id` (**RESTRICT**)
- `contribution_share` DECIMAL(6,4) NULL
- `hours_assigned` DECIMAL(8,2) NULL
- UNIQUE(`activity_id`,`lecturer_id`)
- timestamps

#### 21. `activity_status_histories`

- `id` BIGINT PK
- `activity_id` BIGINT FK → `research_activities.id` (**CASCADE**)
- `from_status_id` BIGINT FK → `activity_statuses.id` (**SET NULL**)
- `to_status_id` BIGINT FK → `activity_statuses.id` (**RESTRICT**)
- `acted_by_user_id` BIGINT FK → `users.id` (**RESTRICT**)
- `acted_at` DATETIME
- `note` VARCHAR(500) NULL
- timestamps

#### 22. `activity_approvals`

- `id` BIGINT PK
- `activity_id` BIGINT FK → `research_activities.id` (**CASCADE**)
- `stage_id` BIGINT FK → `approval_stages.id` (**RESTRICT**)
- `status` ENUM('pending','approved','rejected') DEFAULT 'pending'
- `decided_by_user_id` BIGINT FK → `users.id` (**SET NULL**)
- `decided_at` DATETIME NULL
- `note` VARCHAR(500) NULL
- UNIQUE(`activity_id`,`stage_id`)
- timestamps

#### 23. `evidence_files`

- `id` BIGINT PK
- `activity_id` BIGINT FK → `research_activities.id` (**CASCADE**)
- `file_type_id` BIGINT FK → `evidence_file_types.id` (**RESTRICT**)
- `disk` VARCHAR(50) DEFAULT 's3'
- `path` VARCHAR(500)
- `original_name` VARCHAR(255)
- `mime_type` VARCHAR(100)
- `size_bytes` BIGINT
- `sha256` CHAR(64) **UNIQUE**
- `uploaded_by_user_id` BIGINT FK → `users.id` (**RESTRICT**)
- `uploaded_at` DATETIME
- timestamps

### (F) Quy tắc giờ & Tổng hợp

#### 24. `hour_rules`

- `id` BIGINT PK
- `kind_id` BIGINT FK → `activity_kinds.id` (**RESTRICT**)
- `type_id` BIGINT FK → `activity_types.id` (**SET NULL**)
- `distribution_strategy` ENUM('equal_all_members','principal_fraction_others_equal','per_lecturer_fixed')
- `hours_total_per_activity` DECIMAL(8,2) NULL
- `hours_per_occurrence` DECIMAL(8,2) NULL
- `principal_fraction` DECIMAL(6,4) NULL
- `others_fraction_total` DECIMAL(6,4) NULL
- `max_occurrences_per_year` INT NULL
- `effective_from` DATE
- `effective_to` DATE NULL
- `is_active` TINYINT(1) DEFAULT 1
- `version` INT DEFAULT 1
- UNIQUE(`kind_id`,`type_id`,`version`,`effective_from`)
- timestamps

#### 25. `calculation_logs`

- `id` BIGINT PK
- `activity_id` BIGINT FK → `research_activities.id` (**CASCADE**)
- `executed_at` DATETIME
- `rule_id` BIGINT FK → `hour_rules.id` (**RESTRICT**)
- `input_snapshot` JSON
- `result_snapshot` JSON
- `total_hours` DECIMAL(8,2)
- timestamps

#### 26. `lecturer_yearly_hours`

- `id` BIGINT PK
- `lecturer_id` BIGINT FK → `lecturers.id` (**RESTRICT**)
- `academic_year_id` BIGINT FK → `academic_years.id` (**RESTRICT**)
- `hours_total` DECIMAL(8,2) DEFAULT 0
- UNIQUE(`lecturer_id`,`academic_year_id`)
- timestamps

---

## 3) Ràng buộc & chiến lược xoá

- **CASCADE**: `*_details`, `activity_status_histories`, `activity_approvals`, `evidence_files`, `calculation_logs` theo `activity_id` _(phù hợp môi trường dev/test; production có thể RESTRICT để lưu vết)_.
- **RESTRICT**: đa số danh mục (kinds, types, statuses, roles…) và quan hệ cốt lõi (`lecturers`, `research_activities`).
- **SET NULL**: liên kết mềm như `lecturers.user_id`, `lecturers.degree_id`, `lecturers.academic_rank_id`, `research_activities.type_id`, `activity_status_histories.from_status_id`, `activity_approvals.decided_by_user_id`.

---

## 4) Ma trận phân quyền (Roles & Permissions)

> Gợi ý dùng **spatie/laravel-permission**.

| Permission                | GV  | DL  | QL  | ADMIN | Ghi chú                                                                      |
| ------------------------- | :-: | :-: | :-: | :---: | ---------------------------------------------------------------------------- |
| `user.view.self`          |  ✔  |  ✔  |  ✔  |   ✔   | Xem hồ sơ cá nhân                                                            |
| `lecturer.manage`         |     |     |     |   ✔   | CRUD hồ sơ giảng viên                                                        |
| `dictionary.manage`       |     |     |     |   ✔   | Danh mục (faculties, departments, degrees, ranks, kinds, types, file_types…) |
| `activity.create`         |  ✔  |     |     |       | Tạo kê khai (owner)                                                          |
| `activity.update.own`     |  ✔  |     |     |       | Khi `draft`/`rejected`                                                       |
| `activity.submit`         |  ✔  |     |     |       | `draft → submitted`                                                          |
| `activity.view.all`       |     |  ✔  |  ✔  |   ✔   | Xem tất cả                                                                   |
| `activity.approve.stage1` |     |  ✔  |     |       | Duyệt cấp trợ lý (`assistant`)                                               |
| `activity.approve.stage2` |     |     |  ✔  |       | Duyệt cấp quản lý (`manager`)                                                |
| `activity.reject`         |     |  ✔  |  ✔  |       | Trả về                                                                       |
| `activity.override`       |     |     |     |   ✔   | Can thiệp đặc biệt                                                           |
| `evidence.upload.own`     |  ✔  |     |     |       | Tải minh chứng cho activity của mình                                         |
| `evidence.manage`         |     |  ✔  |  ✔  |   ✔   | Quản lý minh chứng khi duyệt                                                 |
| `rules.manage`            |     |     |  ✔  |   ✔   | CRUD `hour_rules`                                                            |
| `quota.manage`            |     |     |  ✔  |   ✔   | CRUD `workload_quotas`, `academic_years`                                     |
| `report.view.personal`    |  ✔  |  ✔  |  ✔  |   ✔   | Báo cáo cá nhân                                                              |
| `report.view.department`  |     |  ✔  |  ✔  |   ✔   | Báo cáo theo khoa/đơn vị                                                     |
| `report.export`           |     |  ✔  |  ✔  |   ✔   | Xuất Excel/PDF                                                               |
| `rbac.manage`             |     |     |     |   ✔   | Quản trị vai trò & quyền                                                     |

**Luồng trạng thái:**

- GV: `draft → submitted`
- DL (`assistant`): `submitted → approved/rejected` (stage 1)
- QL (`manager`): phê duyệt cuối (stage 2) hoặc `rejected`
- `approved` thì khóa chỉnh sửa; chỉ `ADMIN` có thể `override`.

---

## 5) Mapping quy tắc tính giờ (trong `hour_rules`)

- **Bài báo (paper)**: `distribution_strategy = equal_all_members`; `hours_total_per_activity = 900 | 600 | 300` (HDGSNN 1/2/còn lại). Chia đều cho thành viên.
- **Giáo trình/TLTK (book)**: `principal_fraction_others_equal`; `hours_total_per_activity = 900 | 600`; `principal_fraction = 0.2`; `others_fraction_total = 0.8`.
- **Đề tài (project)**: theo cấp `bo`/`coso` → cấu hình `hours_total_per_activity` tương ứng; thường chia đều.
- **Hội thảo (conference)**:
  - `report`: `per_lecturer_fixed` + `hours_per_occurrence = 40`
  - `attend`: `per_lecturer_fixed` + `hours_per_occurrence = 4` + `max_occurrences_per_year = 40`.

---

## 6) Gợi ý seed dữ liệu lookup

- `activity_kinds`: paper, book, project, conference
- `activity_types`: hdgsnn_900, hdgsnn_600, hdgsnn_300, textbook, reference, bo, coso, report, attend
- `member_roles`: principal, member
- `activity_statuses`: draft, submitted, approved, rejected
- `approval_stages`: assistant(1), manager(2)
- `workload_quotas`: năm hiện hành 600 giờ
- `hour_rules`: theo mục 5

---

## 7) Ghi chú Laravel Migrations

- Dùng `foreignId()->constrained()->restrictOnDelete()` hoặc `cascadeOnDelete()` như phần trên.
- Các bảng `*_details`, `activity_status_histories`, `activity_approvals`, `evidence_files`, `calculation_logs` nên **CASCADE** theo `activity_id` trong môi trường dev/test.
- Thêm index cho các cột lọc báo cáo: `owner_lecturer_id`, `academic_year_id`, `kind_id`, `type_id`, `status_id`.

---
