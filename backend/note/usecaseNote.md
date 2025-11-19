# Nhật ký kiểm nghiệm sơ đồ Use Case

_Hệ thống: “Quản lý công trình khoa học của giảng viên”_  
_Người kiểm nghiệm: Hiếu_  
_Ngày: 19/11/2025_

Tài liệu này ghi lại các **phát hiện (findings)** và **kiến nghị chỉnh sửa** đối với sơ đồ Use Case hiện tại, tập trung vào 3 nhóm nội dung:

1. Cảnh báo định mức (Note 1)
2. Cụm Use Case kê khai công trình NCKH (Note 2)
3. Quản lý danh mục & quy tắc tính giờ NCKH (Note 5)

---

## 1. Note 1 – Cảnh báo định mức

### 1.1. Hiện trạng trên sơ đồ

-   Actor: **Giảng viên**.
-   Use Case:
    -   **“Xem tổng hợp giờ NCKH”**.
    -   **“Nhận cảnh báo định mức”**.
-   Quan hệ hiện tại trên sơ đồ:
    -   “Nhận cảnh báo định mức” được nối với “Xem tổng hợp giờ NCKH” bằng quan hệ `<<extend>>`.

### 1.2. Yêu cầu nghiệp vụ liên quan

-   Hệ thống phải:
    -   Tính tổng giờ NCKH của từng giảng viên theo năm học.
    -   So sánh với định mức (ví dụ: 600 giờ/năm).
    -   **Cảnh báo** khi:
        -   Giảng viên chưa đạt định mức (thiếu giờ).
        -   Hoặc vượt mức cho phép (nếu có quy định).
-   Cảnh báo có thể xuất hiện:
    -   Ngay trên màn hình **“Xem tổng hợp giờ NCKH”** (popup, banner, trạng thái màu).
    -   Và/hoặc qua các kênh khác (email, thông báo hệ thống, nhắc định kỳ).

### 1.3. Nhận xét kỹ thuật UML

-   Trong thực tế sử dụng:
    -   Mỗi lần giảng viên mở **“Xem tổng hợp giờ NCKH”**, hệ thống **luôn phải** kiểm tra giờ và hiển thị trạng thái đạt / chưa đạt.
    -   Nghĩa là “kiểm tra & hiển thị cảnh báo” là **bước bắt buộc** của use case “Xem tổng hợp giờ NCKH”.
-   Với bản chất này:
    -   **Không phù hợp** để dùng `<<extend>>` (vì `extend` chỉ dành cho hành vi **mở rộng, tùy chọn**).
    -   `extend` làm người đọc hiểu rằng: đôi khi xem tổng hợp giờ chỉ hiển thị số giờ, **không có cảnh báo**, chỉ trong một số kịch bản đặc biệt mới “extend” sang cảnh báo – điều này không đúng với yêu cầu gốc.

### 1.4. Kiến nghị chỉnh sửa

1. **Thay đổi quan hệ UML**

    - Đổi quan hệ giữa:
        - “Xem tổng hợp giờ NCKH”
        - “Nhận cảnh báo định mức”
    - Từ `<<extend>>` sang **`<<include>>`** (hoặc gộp luôn logic cảnh báo vào flow mô tả của “Xem tổng hợp giờ NCKH”).
    - Cách hiểu mới:
        > Bất cứ khi nào giảng viên thực hiện “Xem tổng hợp giờ NCKH” thì hệ thống bắt buộc **include** bước “Kiểm tra định mức & hiển thị cảnh báo”.

2. **(Tuỳ chọn) Thêm use case cho cảnh báo tự động**
    - Nếu hệ thống có kế hoạch triển khai **nhắc nhở tự động** (cron job, gửi email…), nên bổ sung:
        - Use Case mới: **“Hệ thống gửi cảnh báo định mức định kỳ”**.
        - Actor: **Hệ thống / Scheduler** (không phải người dùng).
    - Use case này độc lập với việc giảng viên có bấm “Xem tổng hợp giờ” hay không, mô tả hành vi nền.

---

## 2. Note 2 – Cụm Use Case kê khai công trình NCKH

### 2.1. Hiện trạng trên sơ đồ

-   Actor: **Giảng viên**.
-   Các Use Case liên quan:

    1. **“Kê khai và quản lý giờ hoạt động NCKH”** (Use case tổng).
    2. Bốn use case chi tiết:
        - “Kê khai tham gia / báo cáo hội nghị”
        - “Kê khai bài báo”
        - “Kê khai đề tài NCKH”
        - “Kê khai giáo trình / tài liệu tham khảo”
    3. Các use case hỗ trợ:
        - “Tải minh chứng có cấu trúc”
        - “Phân bổ phần trăm đóng góp”
        - “Đề xuất đồng tác giả”

-   Quan hệ:
    -   Giảng viên → “Kê khai và quản lý giờ hoạt động NCKH”.
    -   Từ use case tổng này tỏa ra 4 use case kê khai cụ thể.
    -   Các use case kê khai cụ thể đều `<<include>>`:
        -   “Tải minh chứng có cấu trúc”
        -   “Phân bổ phần trăm đóng góp”
        -   “Đề xuất đồng tác giả”

### 2.2. Yêu cầu nghiệp vụ liên quan

-   Giảng viên phải có khả năng kê khai tất cả các loại công trình:
    -   Bài báo, đề tài, giáo trình/tài liệu tham khảo, hội nghị.
-   Các loại công trình **dùng chung cùng một bộ bước nghiệp vụ**:
    1. Nhập thông tin cơ bản.
    2. Khai báo danh sách thành viên.
    3. **Phân bổ tỉ lệ đóng góp** (chủ biên, đồng tác giả…).
    4. **Upload minh chứng có cấu trúc** (file PDF, quyết định…).
    5. Gửi phê duyệt.

### 2.3. Nhận xét kỹ thuật UML

-   Về nghiệp vụ: sơ đồ hiện tại **phản ánh đúng các chức năng** yêu cầu.
-   Về UML & tính tối ưu:
    -   Cùng một nhóm bước chung (“Tải minh chứng”, “Phân bổ phần trăm…”) đang được vẽ `<<include>>` **lặp lại 4 lần** từ 4 use case kê khai chi tiết → sơ đồ rối, nhiều đường chéo.
    -   Bản chất 4 use case “Kê khai bài báo / đề tài / giáo trình / hội nghị” là **4 biến thể (specializations)** của một hành vi chung “Kê khai công trình NCKH”, chứ không phải 4 use case độc lập rồi mới dùng include để gắn vào use case tổng.

### 2.4. Kiến nghị chỉnh sửa

1. **Đặt tên lại use case tổng**

    - Đổi **“Kê khai và quản lý giờ hoạt động NCKH”** thành **“Kê khai công trình NCKH”** (ngắn gọn, đúng bản chất hơn).

2. **Dùng quan hệ generalization (kế thừa) cho 4 loại kê khai**

    - “Kê khai bài báo”, “Kê khai đề tài NCKH”, “Kê khai giáo trình / tài liệu tham khảo”, “Kê khai tham gia / báo cáo hội nghị” **kế thừa** từ use case cha “Kê khai công trình NCKH”.
    - Ý nghĩa:
        > 4 use case này là 4 “phiên bản chuyên biệt” của cùng một hành vi kê khai công trình.

3. **Dùng `<<include>>` một lần từ use case cha**

    - Use case cha “Kê khai công trình NCKH” `<<include>>`:
        - “Tải minh chứng có cấu trúc”
        - “Quản lý thành viên & tỉ lệ đóng góp” (có thể đổi tên từ “Phân bổ phần trăm đóng góp” cho rõ).
    - Không cần vẽ 4 đường include từ từng use case con nữa → sơ đồ gọn và dễ đọc hơn.

4. **Giữ nguyên logic nghiệp vụ**
    - Mặc dù cách vẽ thay đổi, nhưng:
        - Giảng viên vẫn truy cập được từng loại kê khai riêng.
        - Tất cả các bước bắt buộc (minh chứng, tỉ lệ đóng góp) vẫn được đảm bảo thông qua use case cha.

---

## 3. Note 5 – Quản lý danh mục & quy tắc tính giờ NCKH

### 3.1. Hiện trạng trên sơ đồ

-   Actor: **Phòng quản lý khoa học**.
-   Use Case liên quan:
    -   **“Quản lý định mức và hệ số quy đổi”** (duy nhất, tên chung chung).
-   Không có use case nào mô tả rõ:
    -   Quản lý danh mục loại công trình NCKH.
    -   Quản lý danh mục loại minh chứng.
    -   Quản lý các trạng thái hoạt động, giai đoạn duyệt.

Trong khi đó, thiết kế cơ sở dữ liệu đã có rất nhiều bảng danh mục và bảng quy tắc:

-   Các bảng danh mục: `activity_kinds`, `activity_types`, `evidence_file_types`, `activity_statuses`, `approval_stages`, `faculties`, `departments`, v.v.
-   Bảng quy tắc tính giờ: `hour_rules` (bao gồm loại hoạt động, chiến lược phân bổ, giới hạn số lần, thời gian hiệu lực…).

### 3.2. Yêu cầu nghiệp vụ liên quan

-   Hệ thống phải linh hoạt để:
    -   Thêm/sửa các loại công trình NCKH mới.
    -   Cập nhật các loại minh chứng kèm theo.
    -   Điều chỉnh quy tắc tính giờ theo từng giai đoạn, từng quy định mới.
-   Đây là phần **nghiệp vụ quản trị quan trọng**, không đơn thuần là cấu hình kỹ thuật.

### 3.3. Nhận xét

-   Sơ đồ hiện tại chỉ có một use case tổng “Quản lý định mức và hệ số quy đổi”:
    -   Tên gọi không phản ánh hết phạm vi công việc của Phòng quản lý khoa học.
    -   Không thể hiện rõ việc **quản lý danh mục** – trong khi database cho thấy đây là chức năng thực sự tồn tại.
-   Điều này làm giảm “trọng số” của phần cấu hình hệ thống trong mắt người đọc, trong khi nó là **xương sống** của logic tính giờ.

### 3.4. Kiến nghị chỉnh sửa

1. **Use Case mới: “Quản lý quy tắc tính giờ NCKH”**

    - Actor: **Phòng quản lý khoa học**.
    - Nội dung:
        - Tạo/sửa/xem các quy tắc tính giờ trong `hour_rules`.
        - Cấu hình:
            - Tổng giờ cho từng loại công trình (bài báo 900/600/300, giáo trình 900/600, đề tài...).
            - Cách phân bổ giờ (chia đều, chủ biên 1/5 và 4/5 chia đều, giới hạn 40 lần/năm...).
            - Thời gian hiệu lực (năm học áp dụng).
    - Trên sơ đồ:
        - Vẽ oval mới “Quản lý quy tắc tính giờ NCKH”.
        - Kéo mũi tên từ actor “Phòng quản lý khoa học” vào.
        - Có thể:
            - Hoặc thay thế hẳn use case cũ “Quản lý định mức và hệ số quy đổi”.
            - Hoặc giữ use case cũ như alias nhưng ghi rõ hơn trong mô tả.

2. **Use Case mới: “Quản lý danh mục NCKH”**

    - Actor: **Phòng quản lý khoa học** (hoặc thêm actor Admin hệ thống nếu muốn).
    - Nội dung:
        - Quản lý các danh mục:
            - Loại công trình (bài báo, đề tài, sách, hội nghị...).
            - Loại minh chứng (nội dung, bìa, mục lục, quyết định nghiệm thu...).
            - Trạng thái hoạt động (nháp, chờ duyệt, đã duyệt, từ chối...).
            - Giai đoạn duyệt (vòng khoa, vòng phòng KHCN...).
    - Trên sơ đồ:
        - Vẽ thêm oval “Quản lý danh mục NCKH”.
        - Kéo mũi tên từ “Phòng quản lý khoa học”.

3. **Ảnh hưởng đến các actor khác**

-   **Giảng viên**:

    -   Không cần mũi tên mới, không thay đổi use case hiện tại.
    -   Được hưởng lợi gián tiếp: danh sách loại công trình, minh chứng, trạng thái… mà họ chọn trên màn hình được cấu hình từ các use case quản trị này.

-   **BCN Khoa, Phòng QLKH (ở vai trò duyệt & báo cáo)**:
    -   Không cần chỉnh các use case “Xem danh sách chờ duyệt”, “Phê duyệt & xác nhận giờ NCKH”, “Xem BC & TK giờ NCKH”…
    -   Các use case mới chỉ bổ sung nhiệm vụ **cấu hình hệ thống** cho chính Phòng QLKH.

---

## 4. Kết luận chung cho 3 Note

-   **Note 1 – Cảnh báo định mức**:

    -   Cần đổi quan hệ `<<extend>>` thành `<<include>>` (hoặc gộp vào flow) để phản ánh đúng việc cảnh báo là **bắt buộc** mỗi khi xem tổng hợp giờ.
    -   Nếu có cảnh báo tự động, bổ sung use case riêng cho Hệ thống.

-   **Note 2 – Kê khai công trình NCKH**:

    -   Nên tổ chức lại cụm use case theo mô hình:
        -   Use case cha “Kê khai công trình NCKH”
        -   4 use case con (bài báo, đề tài, giáo trình, hội nghị) kế thừa từ cha.
        -   Các bước chung (minh chứng, tỉ lệ đóng góp) `<<include>>` từ use case cha.
    -   Giữ nguyên toàn bộ logic nghiệp vụ, chỉ tối ưu cách vẽ.

-   **Note 5 – Quản lý danh mục & quy tắc giờ**:
    -   Bổ sung 2 use case:
        -   “Quản lý quy tắc tính giờ NCKH”
        -   “Quản lý danh mục NCKH”
    -   Gắn với actor Phòng quản lý khoa học / Admin để làm rõ mảng cấu hình mà database đã hỗ trợ.
