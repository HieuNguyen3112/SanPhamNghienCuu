import type {
  HoursAlertActionSuggestionDTO,
  HoursAlertItemDTO,
  HoursAlertsSummaryDTO,
} from "../contracts/hoursWarning.contract";

/**
 * Mock assumptions:
 * - deadline_date uses academic_years.end_date (proxy)
 * - is_seen is client-side mock
 */
export const hoursAlertsSummaryMockDTO: HoursAlertsSummaryDTO = {
  academic_year_code: "2025-2026",
  hours_total: 220,
  required_hours: 300,
  deadline_date: "2026-06-30",
  days_remaining: 15,
};

export const hoursAlertListMockDTO: HoursAlertItemDTO[] = [
  {
    id: 1,
    level: "danger",
    title: "[!] Thiếu giờ NCKH",
    description:
      "Bạn còn thiếu 80 giờ để đủ mức giờ NCKH của năm học 2025–2026.",
    updated_at: "2025-12-19T09:10:00+07:00",
    deadline_date: "2026-06-30",
    cta_label: "Xem công trình đã kê khai",
    cta_to: "/works/personal",
    is_seen: false,
  },
  {
    id: 2,
    level: "warning",
    title: "[⏳] Sắp hết hạn kê khai",
    description: "Thời hạn kê khai giờ NCKH sẽ kết thúc sau 15 ngày.",
    updated_at: "2025-12-20T15:30:00+07:00",
    deadline_date: "2026-06-30",
    cta_label: "Đi kê khai công trình",
    cta_to: "/lecturer/research-activities?tab=submitted",
    is_seen: false,
  },
  {
    id: 3,
    level: "info",
    title: "[i] Nhắc bạn kiểm tra lịch sử xét duyệt giờ",
    description:
      "Bạn có các đợt xét duyệt giờ trước đó. Hãy kiểm tra để tránh bỏ lỡ phản hồi.",
    updated_at: "2025-12-18T10:00:00+07:00",
    deadline_date: null,
    cta_label: "Xem lịch sử xét duyệt",
    cta_to: "/hours/personal",
    is_seen: true,
  },
  {
    id: 4,
    level: "warning",
    title: "[!] Có công trình đã duyệt nhưng chưa gửi duyệt giờ",
    description:
      "Bạn có một số công trình đã được duyệt nội dung nhưng chưa gửi yêu cầu xét duyệt giờ.",
    updated_at: "2025-12-17T08:10:00+07:00",
    deadline_date: null,
    cta_label: "Tính giờ NCKH",
    cta_to: "/hours/calculate",
    is_seen: false,
  },
];

export const hoursActionSuggestionsMockDTO: HoursAlertActionSuggestionDTO[] = [
  {
    id: 101,
    title: "💡 Gợi ý cho bạn",
    description:
      "Bạn có 3 công trình đã duyệt nhưng chưa tính giờ. Hãy chọn công trình để gửi duyệt giờ.",
    cta_label: "Tính giờ NCKH",
    cta_to: "/lecturer/hours/select-approved-works",
  },
  {
    id: 102,
    title: "Hoàn thiện bản nháp (nếu có)",
    description:
      "Nếu bạn còn công trình ở trạng thái bản nháp, hãy hoàn thiện để kịp thời hạn.",
    cta_label: "Đi đến công trình cá nhân",
    cta_to: "/lecturer/research-activities?tab=draft",
  },
];
