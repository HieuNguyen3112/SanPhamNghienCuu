# Ghi chú frontend: đăng nhập mock & hồ sơ

## UserStore & luồng đăng nhập mock
- `UserRole` enum: `"LECTURER" | "DEPARTMENT_BOARD" | "SCIENCE_OFFICE"`.
- User shape: `{ id, name, code, department, roles: UserRole[], avatar? }`.
- State store: `currentUser: User | null`, `currentRole: UserRole | null`; getters `isAuthenticated`, `role`.
- Login mock: `useUserStore.login({ username, password })` tìm trong danh sách tài khoản mẫu (gv/123 → role LECTURER; bcn/123 → DEPARTMENT_BOARD; qlkh/123 → SCIENCE_OFFICE; multi/123 → LECTURER + SCIENCE_OFFICE). Nếu sai, ném `SAI_TAI_KHOAN`; nếu đúng đặt `currentUser` và `currentRole` = role đầu tiên.
- Chọn role: `setRole(role)` kiểm tra user hiện tại có role đó; nếu không ném `ROLE_KHONG_HOP_LE`.
- Logout: đặt `currentUser` và `currentRole` về null.
- LoginPage.vue: form nhận email/username, password, role chọn từ dropdown (mặc định LECTURER); gọi `login`, sau đó `setRole`, chuyển router `/`; có chế độ “quên mật khẩu” chỉ đổi UI (chưa gọi API).

## Menu & lọc theo role
- `buildMenuForRole(role)` lọc menu theo `item.roles`; nếu không đặt `roles` thì hiển thị cho mọi role. Menu gồm nhóm hồ sơ cá nhân, công trình KH, giờ KH, người dùng, tra cứu… (profileMenuGroup/items, researchWorks, declarations, hours, users, search).

## Trang hồ sơ & dữ liệu mong đợi
- `ProfileScientificView.vue`: trang tổng hợp khoa học, hiện placeholders cho thông tin liên hệ/công tác/đào tạo/đảng; chưa có binding dữ liệu.
- `ProfileContactView.vue`: dùng object `contact` (mock) với các field `fullName, gender, birthDate, birthPlace, ethnicity, hometown, position, department, address, degree, academicTitle, teachingSpecialty, researchAreas, languages, email, phone`. Cho phép chỉnh sửa/lưu cục bộ (chưa gọi API).
- `ProfileAcademicRankView.vue`: quản lý danh sách học vị & chức danh; entry gồm `highestDegree`, `degreeMajor`, `degreeInstitution`, `degreeCountry?`, `degreeYear?`, `academicTitle`, `academicTitleYear?`, `academicTitleInstitution?`, `note?`, `id`. Có modal thêm/sửa, chưa gọi API.
- `ProfileEducationView.vue`: danh sách quá trình đào tạo; entry `{ id, degreeLevel (UNDERGRADUATE/MASTER/PHD/POSTDOC/OTHER), major, institution, country, startYear, endYear, trainingType (FULL_TIME/PART_TIME/IN_SERVICE/DISTANCE/OTHER) }`; mock data, CRUD cục bộ.
- `ProfileLanguageSkillsView.vue`: danh sách ngoại ngữ; record `{ id, language, level (BASIC/INTERMEDIATE/ADVANCED/A1..C2), certificateName?, certificateIssuer?, certificateScore?, issueDate?, expireDate?, note?, attachmentName? }`; modal thêm/sửa, upload mock (lưu tên file).
- `ProfileResearchAreaView.vue`: danh sách lĩnh vực nghiên cứu; entry `{ id, name, type (PRIMARY/SECONDARY), startYear?, keywords?, description? }`; CRUD cục bộ.
- `ProfileWorkHistoryView.vue`: quá trình công tác; entry `{ id, fromDate, toDate?, isCurrent, organization, department?, position, workplace?, workType (BIEN_CHE/HOP_DONG/KIEM_NHIEM/THINH_GIANG/KHAC), note? }`; có format ngày DD/MM/YYYY.

## Gợi ý map role frontend ↔ backend
- Front `LECTURER` ↔ backend role `GV` (giảng viên).
- Front `DEPARTMENT_BOARD` (ban chủ nhiệm/khoa) ↔ backend `DL` hoặc `QL` tùy phân quyền phê duyệt/phòng; ưu tiên `DL` nếu dùng cho duyệt/phòng ban, `QL` nếu là quản lý khoa/phòng.
- Front `SCIENCE_OFFICE` ↔ backend `QL` (quản lý, phòng QLKH) hoặc `ADMIN` nếu cần toàn quyền. Đề xuất mặc định: `SCIENCE_OFFICE` → `QL`, còn tài khoản quản trị hệ thống dùng `ADMIN` riêng.
