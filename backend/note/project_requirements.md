# DỰ ÁN: QUẢN LÝ CÔNG TRÌNH KHOA HỌC CỦA GIẢNG VIÊN

## 1. CÁC CHỨC NĂNG CHÍNH (SYSTEM FEATURES)

### 1.1. Quản lý thông tin giảng viên

-   **Lưu trữ hồ sơ**: Thông tin cá nhân, học hàm, học vị, bộ môn, đơn vị công tác.
-   **Phân quyền (RBAC)**: Chỉ giảng viên (xem/sửa thông tin mình) và bộ phận liên quan (quản lý) mới có quyền truy cập.

### 1.2. Kê khai giờ nghiên cứu khoa học (NCKH)

-   **Tự kê khai**: Giảng viên nhập liệu các hoạt động (Bài báo, Đề tài, Sách, Hội thảo...).
-   **Tính toán tự động**: Hệ thống tự động cộng dồn giờ và phân loại theo từng loại hình.

### 1.3. Quản lý, Phê duyệt và Xác nhận

-   **Quy trình phê duyệt**: Bộ phận quản lý khoa/phòng KHCN kiểm tra → Xác nhận hoặc Phản hồi (Từ chối).
-   **Lịch sử (Audit Log)**: Ghi nhận chi tiết các lần chỉnh sửa, người duyệt, thời gian duyệt, lý do từ chối.

### 1.4. Báo cáo, Thống kê

-   **Xuất báo cáo**: Theo Cá nhân, Bộ môn, Khoa, Học kỳ, Năm học.
-   **Thống kê so sánh**: Giờ thực hiện vs Định mức quy định (600h).
-   **Chi tiết**: Liệt kê từng hoạt động để minh bạch hóa việc đánh giá.

### 1.5. Tích hợp quy định, định mức

-   **Cấu hình định mức**: Cập nhật định mức theo quy định Trường/Bộ.
-   **Cảnh báo**: Tự động nhắc nhở khi giảng viên chưa đạt hoặc đã vượt định mức.

### 1.6. Quản lý minh chứng (Evidence)

-   **Đính kèm file**: Bắt buộc tải lên minh chứng (Quyết định, bài báo, giấy mời...).
-   **Xác thực**: Bộ phận quản lý xem trực tiếp file trên hệ thống để duyệt.

### 1.7. Các chức năng mở rộng (Option)

-   **Tích hợp hệ thống khác**: Kết nối Đào tạo/Nhân sự để đồng bộ thông tin giảng viên.
-   **Tra cứu**: Tìm kiếm theo Tên, Mã GV, Loại hoạt động, Thời gian.
-   **Phi chức năng**: Bảo mật thông tin cá nhân, Sao lưu dữ liệu định kỳ.

## 2. QUY TRÌNH NGHIỆP VỤ (WORKFLOW)

### Bước 1 - Kê khai

-   Giảng viên đăng nhập, nhập thông tin công trình (Bài báo, Đề tài, Sách...).
-   Upload minh chứng (PDF).
-   Trạng thái ban đầu: **"Mới khai báo"**.

### Bước 2 - Phê duyệt

-   Người được phân công (Trợ lý khoa học/Ban chủ nhiệm Khoa) xem hồ sơ.
-   Kiểm tra thông tin so với minh chứng đính kèm.
-   Hành động:
    -   **Duyệt** → Trạng thái **"Đã duyệt"**.
-   Hệ thống tự động tính điểm/giờ cho tác giả và các thành viên tham gia ngay khi duyệt.

### Bước 3 - Thống kê & Báo cáo

-   Ban chủ nhiệm Khoa xem thống kê tổng hợp.
-   Export dữ liệu gửi Phòng KHCN.

## 3. QUY ĐỊNH CHUNG & ĐỊNH MỨC (BUSINESS RULES)

-   **Năm tính giờ**: Từ tháng 9 năm nay đến tháng 8 năm sau.
-   **Định mức chuẩn**: 600 giờ/giảng viên/năm.

### Loại công trình chính

-   Bài báo khoa học.
-   Đề tài nghiên cứu khoa học.
-   Giáo trình / Tài liệu tham khảo.

### Yêu cầu Minh chứng (File PDF)

-   **Bài báo**: Nội dung bài báo, trang bìa, mục lục.
-   **Đề tài**: Quyết định nghiệm thu hoặc Chứng nhận hoàn thành, Thuyết minh.
-   **Sách/Giáo trình**: Bản scan Quyết định phê duyệt, Bản scan trang bìa (có ISBN).

## 4. CÔNG THỨC TÍNH GIỜ CHI TIẾT (CALCULATION FORMULAS)

### 4.1. Đề tài KH&CN các cấp

-   **Thông tin lưu trữ**: Loại đề tài (Bộ, Cơ sở...), Chủ nhiệm, Danh sách thành viên, Minh chứng.
-   **Công thức**: Dựa trên bảng quy đổi (cấu hình động theo loại đề tài và vai trò).

### 4.2. Bài báo khoa học

-   **Thông tin lưu trữ**: Tên bài, Số, Trang, Tạp chí, DOI, Link, Minh chứng, Loại bài báo.

#### Định mức giờ (Input Rule)

-   Danh mục HDGSNN (1 → 2 điểm): **900 giờ**.
-   Danh mục HDGSNN (đến 1 điểm): **600 giờ**.
-   Bài báo khác có chỉ số ISSN/ISBN: **300 giờ**.

#### Quy tắc chia giờ

-   Tổng giờ chia đều cho số lượng thành viên tham gia.
-   **Công thức**:  
    `Giờ cá nhân = Tổng giờ bài báo / Tổng số thành viên`

### 4.3. Biên soạn Giáo trình / Tài liệu tham khảo

#### Định mức giờ

-   Giáo trình: **900 giờ**.
-   Tài liệu tham khảo: **600 giờ**.

#### Quy tắc chia giờ (Phức tạp)

-   **Chủ biên**: Hưởng 1/5 tổng giờ.
-   **Nhóm tác giả** (bao gồm cả Chủ biên): Hưởng 4/5 tổng giờ còn lại, chia đều cho tất cả mọi người.

**Ví dụ**: Giáo trình 900h, nhóm 3 người (1 chủ biên, 2 thành viên).

-   Chủ biên nhận:  
    `Giờ chủ biên = (900 * 1/5) + (900 * 4/5 / 3)`
-   Thành viên nhận:  
    `Giờ thành viên = (900 * 4/5 / 3)`

### 4.4. Hội nghị, Hội thảo khoa học

-   **Báo cáo (Tham luận)**: `Số lần x 40 giờ`.
-   **Tham dự**: `4 giờ/lần` (Tối đa 40 lần/năm, tức tối đa 160 giờ).
