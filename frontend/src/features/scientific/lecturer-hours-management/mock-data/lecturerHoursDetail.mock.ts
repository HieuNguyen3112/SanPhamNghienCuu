import type { LecturerHoursDetailRowDTO } from "../lecturerHours.contract";

type Key = `${number}_${number}`; // lecturerId_yearId

const detailRowsByKey: Record<Key, LecturerHoursDetailRowDTO[]> = {
  "101_1": [
    {
      activity_id: 9001,
      activity_title: "Nghiên cứu tối ưu hóa truy vấn trên hệ thống phân tán",
      activity_kind_name: "Bài báo",
      academic_year_code: "2024-2025",
      hours_converted: 400,
    },
    {
      activity_id: 9002,
      activity_title:
        "Xây dựng mô hình phát hiện bất thường trong log ứng dụng",
      activity_kind_name: "Đề tài",
      academic_year_code: "2024-2025",
      hours_converted: 320,
    },
  ],

  "102_1": [
    {
      activity_id: 9010,
      activity_title:
        "Khảo sát mức độ sẵn sàng chuyển đổi số trong doanh nghiệp",
      activity_kind_name: "Hội thảo",
      academic_year_code: "2024-2025",
      hours_converted: 450,
    },
  ],

  "103_1": [],

  "104_1": [
    {
      activity_id: 9020,
      activity_title: "Thiết kế kiến trúc microservices cho hệ thống đào tạo",
      activity_kind_name: "Dự án",
      academic_year_code: "2024-2025",
      hours_converted: 250,
    },
    {
      activity_id: 9021,
      activity_title:
        "Phân tích chất lượng mã nguồn dựa trên chỉ số maintainability",
      activity_kind_name: "Bài báo",
      academic_year_code: "2024-2025",
      hours_converted: 340,
    },
  ],

  "105_1": [
    {
      activity_id: 9030,
      activity_title: "Giáo trình nhập môn trí tuệ nhân tạo",
      activity_kind_name: "Sách",
      academic_year_code: "2024-2025",
      hours_converted: 300,
    },
    {
      activity_id: 9031,
      activity_title: "Đề tài nghiên cứu ứng dụng học sâu trong xử lý ảnh y tế",
      activity_kind_name: "Đề tài",
      academic_year_code: "2024-2025",
      hours_converted: 300,
    },
    {
      activity_id: 9032,
      activity_title: "Báo cáo chuyên đề về an toàn thông tin",
      activity_kind_name: "Hội thảo",
      academic_year_code: "2024-2025",
      hours_converted: 300,
    },
  ],

  "106_1": [
    {
      activity_id: 9040,
      activity_title:
        "Nghiên cứu cải tiến quy trình CI/CD trong dự án phần mềm",
      activity_kind_name: "Bài báo",
      academic_year_code: "2024-2025",
      hours_converted: 610,
    },
  ],

  "201_1": [
    {
      activity_id: 9101,
      activity_title: "Phân tích tác động lạm phát đến tiêu dùng hộ gia đình",
      activity_kind_name: "Bài báo",
      academic_year_code: "2024-2025",
      hours_converted: 640,
    },
  ],

  "202_1": [
    {
      activity_id: 9102,
      activity_title:
        "Đánh giá hiệu quả danh mục đầu tư trong điều kiện biến động",
      activity_kind_name: "Đề tài",
      academic_year_code: "2024-2025",
      hours_converted: 520,
    },
  ],

  "203_1": [
    {
      activity_id: 9103,
      activity_title:
        "Khung phân tích rủi ro tài chính cho doanh nghiệp vừa và nhỏ",
      activity_kind_name: "Hội thảo",
      academic_year_code: "2024-2025",
      hours_converted: 300,
    },
  ],

  "204_1": [
    {
      activity_id: 9104,
      activity_title: "Nghiên cứu hành vi khách hàng trong thương mại điện tử",
      activity_kind_name: "Bài báo",
      academic_year_code: "2024-2025",
      hours_converted: 605,
    },
  ],

  "205_1": [],

  "206_1": [
    {
      activity_id: 9106,
      activity_title: "Xây dựng bộ chỉ tiêu đánh giá năng lực cạnh tranh ngành",
      activity_kind_name: "Đề tài",
      academic_year_code: "2024-2025",
      hours_converted: 480,
    },
    {
      activity_id: 9107,
      activity_title: "Chuyên khảo về quản trị chiến lược trong bối cảnh số",
      activity_kind_name: "Sách",
      academic_year_code: "2024-2025",
      hours_converted: 300,
    },
  ],

  "101_2": [
    {
      activity_id: 9201,
      activity_title: "Tối ưu hóa thuật toán phân cụm cho dữ liệu lớn",
      activity_kind_name: "Bài báo",
      academic_year_code: "2023-2024",
      hours_converted: 610,
    },
  ],
};

export function readMockLecturerHoursDetailRows(
  lecturer_id: number,
  academic_year_id: number
): LecturerHoursDetailRowDTO[] {
  const key: Key = `${lecturer_id}_${academic_year_id}`;
  return detailRowsByKey[key] ? [...detailRowsByKey[key]] : [];
}
