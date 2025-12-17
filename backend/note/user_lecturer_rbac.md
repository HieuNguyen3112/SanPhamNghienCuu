# Người dùng, giảng viên & RBAC

## Bảng/models chính
- `users`: trường quan trọng `name`, `email` (unique), `password`, `email_verified_at`, `remember_token`, 2FA (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at` nếu bật). Model `App\Models\User` dùng `HasApiTokens`, `HasRoles`, `MustVerifyEmail`; cast `email_verified_at`; fillable name/email/password; append `role_names`.
- `lecturers`: khóa chính `id`; khóa ngoại `user_id` nullable (FK users, nullOnDelete, unique để đảm bảo 1-1); mã cán bộ `code` (unique), `full_name`, `email` (nullable), `phone`; FK `degree_id`, `academic_rank_id`, `department_id` (restrictOnDelete); `active` bool; timestamps. Cast `active` bool. Model `App\Models\Lecturer` fillable cùng các cột trên.
- Quan hệ: `User::lecturer()` hasOne (user_id), `Lecturer::user()` belongsTo; unique `lecturers.user_id` (migration 2025_11_11_170748) bảo đảm mỗi user chỉ gắn 1 giảng viên.

## Bảng RBAC (Spatie)
- Từ migration `create_permission_tables`: bảng `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`; dùng cấu hình `config/permission.php` (guard `web`).
- User gắn role/permission qua trait `HasRoles` (morph key `model_id`).

## Seeder RBAC & users mẫu
- `RolesPermissionsSeeder`: tạo roles `ADMIN, QL, DL, GV`; permissions `user.viewSelf`, `user.updateSelf`, `user.manage`, `report.viewPersonal`, `report.viewDepartment`, `report.export`, `rules.manage`, `quota.manage`, `rbac.manage`; gán sẵn: GV (self/report cá nhân), DL (report phòng, export), QL (report phòng, export, rules/quota), ADMIN (tất cả + rbac/user.manage).
- `UsersDemoSeeder`: tạo 4 user demo (admin@local.test, ql@local.test, dl@local.test, gv@local.test) mật khẩu `Password!123`, set email_verified_at=now, gán role tương ứng (guard web).

## Trường giảng viên liên quan màn hình hồ sơ
- Thông tin cơ bản: `code`, `full_name`, `email` (nội bộ), `phone`, `active`, `department_id`, `degree_id`, `academic_rank_id`.
- Hồ sơ chi tiết (bảng lecturer_profiles): giới tính `gender`, `date_of_birth`, `place_of_birth`, `ethnicity`, `hometown`, liên hệ `personal_email`, `alternate_phone`, `address`, liên hệ khẩn `emergency_contact_*`; nghề nghiệp hiện tại `current_position`, `current_unit`, `research_area`, `teaching_specialization`; liên kết học thuật `orcid_id`, `google_scholar_profile`, `research_gate_profile`, `scopus_id`, `publons_id`, `personal_website`, `academic_portfolio_url`.
- Đảng/đoàn (lecturer_party_memberships): `is_member`, `membership_no`, `joined_at`, `official_at`, `joining_place`, `current_branch`, `position`, `status`, `notes`.
- Đào tạo (lecturer_training_histories): `degree_id`/`degree_title`, `major`, `institution`, `country`, `city`, `start_date`, `end_date`, `is_current`, `training_form`, `funding_source`, `certificate_no`, `notes`.
- Kinh nghiệm làm việc (lecturer_work_histories): `organization`, `position`, `department`, `workplace`, `start_date`, `end_date`, `is_current`, `employment_type`, `reason_for_leaving`, `notes`.
- Ngoại ngữ (lecturer_language_proficiencies): `language`, `proficiency_level`, `is_native`, chứng chỉ `certificate_*`, `issued_by`, `issued_at`, `expires_at`, `notes`.

## Tóm tắt quan hệ & guard
- User guard chính `web` (Spatie cũng guard web). Sanctum PAT dùng cho API nhưng RBAC vẫn trên guard web. Mỗi User tối đa 1 Lecturer (lecturers.user_id unique, nullOnDelete). Lecturer có nhiều bảng mở rộng (profile, party membership, training, work, language) gắn FK lecturer_id.
