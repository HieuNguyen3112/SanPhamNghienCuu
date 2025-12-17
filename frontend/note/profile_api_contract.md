# Hợp đồng API hồ sơ giảng viên (dựa trên màn hình Vue + bảng lecturers)

## Nguyên tắc chung
- Tất cả endpoint yêu cầu `auth:sanctum` (Bearer hoặc session cookie) và dùng CSRF cho cookie session.
- Response JSON dạng `{ data: ..., message?: string }`; lỗi validation trả 422 với `{message, errors:{...}}`; 401 khi chưa đăng nhập; 404 khi không tìm thấy bản ghi; 500 cho lỗi khác.
- Vai trò frontend lấy từ store sau khi map backend role (GV, DL, QL, ADMIN).

## GET /api/profile/me
- Mục đích: lấy thông tin tổng hợp User + Lecturer + profile mở rộng để prefill các màn hình.
- Response 200 `application/json`:
  ```json
  {
    "data": {
      "user": {
        "id": 1,
        "name": "...",
        "email": "...",
        "roles": ["GV", "QL"],
        "current_role": "GV"
      },
      "lecturer": {
        "id": 10,
        "code": "48.01.104.001",
        "full_name": "...",
        "email": "...",
        "phone": "...",
        "department_id": 3,
        "department_name": "Khoa CNTT",
        "degree_id": 2,
        "degree_name": "Tiến sĩ",
        "academic_rank_id": 1,
        "academic_rank_name": "Giảng viên chính",
        "active": true
      },
      "profile": {
        "gender": "Nam",
        "date_of_birth": "1985-05-20",
        "place_of_birth": "TP.HCM",
        "ethnicity": "Kinh",
        "hometown": "Quảng Nam",
        "address": "...",
        "personal_email": "...",
        "alternate_phone": "...",
        "emergency_contact_name": null,
        "emergency_contact_phone": null,
        "emergency_contact_relation": null,
        "current_position": "Giảng viên",
        "current_unit": "Khoa CNTT",
        "research_area": "...",
        "teaching_specialization": "...",
        "orcid_id": null,
        "google_scholar_profile": null,
        "research_gate_profile": null,
        "scopus_id": null,
        "publons_id": null,
        "personal_website": null,
        "academic_portfolio_url": null
      },
      "academic_titles": [
        {
          "id": 1,
          "highest_degree": "PHD",
          "degree_major": "CNTT",
          "degree_institution": "ĐHSP TPHCM",
          "degree_country": "VN",
          "degree_year": 2015,
          "academic_title": "ASSOCIATE_PROFESSOR",
          "academic_title_year": 2022,
          "academic_title_institution": "Bộ GDĐT",
          "note": null
        }
      ],
      "educations": [
        {
          "id": 1,
          "degree_level": "MASTER",
          "major": "CNPM",
          "institution": "ĐH Bách Khoa",
          "country": "VN",
          "start_year": 2012,
          "end_year": 2014,
          "training_type": "FULL_TIME"
        }
      ],
      "languages": [
        {
          "id": "abc123",
          "language": "English",
          "level": "B2",
          "certificate_name": "IELTS",
          "certificate_issuer": "IDP",
          "certificate_score": "6.5",
          "issue_date": "2020-01-01",
          "expire_date": null,
          "attachment_name": null
        }
      ],
      "research_areas": [
        {
          "id": 1,
          "name": "AI",
          "type": "PRIMARY",
          "start_year": 2018,
          "keywords": "machine learning, NLP",
          "description": "..."
        }
      ],
      "work_histories": [
        {
          "id": 1,
          "from_date": "2015-09-01",
          "to_date": null,
          "is_current": true,
          "organization": "ĐHSP TPHCM",
          "department": "Khoa CNTT",
          "position": "Giảng viên",
          "workplace": "Cơ sở chính",
          "work_type": "BIEN_CHE",
          "note": null
        }
      ]
    }
  }
  ```

## PUT /api/profile/contact
- Mục đích: cập nhật thông tin liên hệ/cơ bản (User + Lecturer + profile phụ trợ).
- Body JSON (các trường optional, validate string/email/date):
  - `full_name`, `phone`, `department_id`, `degree_id`, `academic_rank_id` (Lecturer)
  - `gender`, `date_of_birth`, `place_of_birth`, `ethnicity`, `hometown`, `address`, `personal_email`, `alternate_phone`, `current_position`, `current_unit`, `teaching_specialization`, `research_area` (profile phụ)
- Response 200: `{ "message": "profile contact updated", "data": { ...các trường như GET /api/profile/me -> lecturer + profile + user{name,email} } }`
- Lỗi: 422 validation; 401 chưa đăng nhập; 404 nếu không có lecturer cho user.

## PUT /api/profile/academic-titles
- Cập nhật danh sách học vị/chức danh (thay thế toàn bộ danh sách của giảng viên).
- Body JSON: `{ "items": [ {"id": null|number, "highest_degree": "BACHELOR|MASTER|PHD|OTHER", "degree_major": "...", "degree_institution": "...", "degree_country": "...", "degree_year": 2020, "academic_title": "NONE|ASSOCIATE_PROFESSOR|PROFESSOR", "academic_title_year": 2022, "academic_title_institution": "...", "note": "..."} ] }`
- Response 200: `{ "message": "academic titles saved", "data": {"items": [...] } }`
- Lỗi: 422 nếu thiếu trường bắt buộc; 401/404 như trên.

## PUT /api/profile/educations
- Thay thế danh sách quá trình đào tạo.
- Body: `{ "items": [ {"id": null|number, "degree_level": "UNDERGRADUATE|MASTER|PHD|POSTDOC|OTHER", "major": "...", "institution": "...", "country": "...", "start_year": 2010, "end_year": 2014, "training_type": "FULL_TIME|PART_TIME|IN_SERVICE|DISTANCE|OTHER"} ] }`
- Response 200: `{ "message": "educations saved", "data": {"items": [...] } }`

## PUT /api/profile/languages
- Thay thế danh sách ngoại ngữ.
- Body: `{ "items": [ {"id": null|string, "language": "English", "level": "BASIC|INTERMEDIATE|ADVANCED|A1|A2|B1|B2|C1|C2", "certificate_name": "IELTS", "certificate_issuer": "IDP", "certificate_score": "6.5", "issue_date": "2020-01-01", "expire_date": null, "note": "...", "attachment_name": "file.pdf"} ] }`
- Response 200: `{ "message": "languages saved", "data": {"items": [...] } }`

## PUT /api/profile/research-areas
- Thay thế danh sách lĩnh vực nghiên cứu.
- Body: `{ "items": [ {"id": null|number, "name": "AI", "type": "PRIMARY|SECONDARY", "start_year": 2018, "keywords": "...", "description": "..."} ] }`
- Response 200: `{ "message": "research areas saved", "data": {"items": [...] } }`

## PUT /api/profile/work-histories
- Thay thế danh sách quá trình công tác.
- Body: `{ "items": [ {"id": null|number, "from_date": "2015-09-01", "to_date": null, "is_current": true, "organization": "...", "department": "...", "position": "...", "workplace": "...", "work_type": "BIEN_CHE|HOP_DONG|KIEM_NHIEM|THINH_GIANG|KHAC", "note": "..."} ] }`
- Response 200: `{ "message": "work histories saved", "data": {"items": [...] } }`

## Ghi chú bổ sung
- Nếu cần đồng bộ bảng `lecturer_profiles`/`lecturer_party_memberships`/`lecturer_training_histories`... có thể dùng cùng endpoint PUT theo dạng danh sách như trên, map với form Vue tương ứng.
- Các endpoint PUT nên áp dụng middleware `auth:sanctum`, `verified`, và server sẽ kiểm tra quyền theo role (GV có thể sửa hồ sơ cá nhân; DL/QL/ADMIN có thể sửa thay cho giảng viên khác nếu được phép - ngoài phạm vi tài liệu này).
