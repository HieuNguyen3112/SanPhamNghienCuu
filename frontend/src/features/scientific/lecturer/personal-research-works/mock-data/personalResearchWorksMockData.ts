import type {
  PersonalStatsDTO,
  PersonalWorkDetailDTO,
  PersonalWorkRowDTO,
  PersonalWorkStatusCodeDTO,
} from "../contracts/personalResearchWorksContracts";

const nowIso = () => new Date().toISOString();

function daysAgoIso(daysAgo: number): string {
  const date = new Date();
  date.setDate(date.getDate() - daysAgo);
  return date.toISOString();
}

const STATUS_NAME: Record<PersonalWorkStatusCodeDTO, string> = {
  draft: "Bản nháp",
  pending_member_confirm: "Chờ thành viên xác nhận",
  member_rejected: "Thành viên từ chối",
  pending_faculty_review: "Chờ khoa duyệt",
  submitted: "Chờ duyệt",
  approved: "Đã duyệt",
  rejected: "Bị từ chối",
};

export const personalWorksMock = (() => {
  const current_lecturer_id = 101;

  const detailsById: Record<number, PersonalWorkDetailDTO> = {
    1: {
      activity_id: 1,
      activity_code: "RA-2025-0001",
      title: "Bài báo về tối ưu hóa truy vấn trong hệ thống SPNC",
      abstract: "Nghiên cứu cải thiện hiệu năng truy vấn cho hệ thống quản lý nghiên cứu khoa học.",
      kind_id: 1,
      kind_code: "paper",
      kind_name: "Bài báo",
      type_id: 11,
      type_name: "Tạp chí",
      academic_year_id: 3,
      academic_year_code: "2024-2025",
      status_id: 3,
      status_code: "approved",
      status_name: STATUS_NAME.approved,
      work_year: 2025,
      venue_name: "Journal of Software Engineering",
      member_role_id: 1,
      member_role_name: "Tác giả chính",
      lecturer_hours: "45.00",
      submitted_at: daysAgoIso(60),
      approved_at: daysAgoIso(20),
      total_hours_calc: "45.00",
      rejection_note: null,
      authors: [
        {
          lecturer_id: 101,
          lecturer_full_name: "Nguyễn Văn A",
          member_role_id: 1,
          member_role_name: "Tác giả chính",
          department_name: "Công nghệ phần mềm",
          contribution_share: "0.7000",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(58),
        },
        {
          lecturer_id: 102,
          lecturer_full_name: "Trần Thị B",
          member_role_id: 2,
          member_role_name: "Đồng tác giả",
          department_name: "Khoa học dữ liệu",
          contribution_share: "0.3000",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(57),
        },
      ],
      member_confirmations: [
        {
          member_id: 11,
          lecturer_id: 101,
          lecturer_code: "GV101",
          lecturer_full_name: "Nguyễn Văn A",
          member_role_code: "principal",
          member_role_name: "Tác giả chính",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(58),
        },
        {
          member_id: 12,
          lecturer_id: 102,
          lecturer_code: "GV102",
          lecturer_full_name: "Trần Thị B",
          member_role_code: "coauthor",
          member_role_name: "Đồng tác giả",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(57),
        },
      ],
      rejected_members: [],
      evidence_items: [
        {
          evidence_file_id: 101,
          file_type_id: 1,
          file_type_name: "Toàn văn",
          disk: "public",
          path: "evidence/paper-1.pdf",
          original_name: "paper-1.pdf",
          mime_type: "application/pdf",
          size_bytes: 1258291,
          uploaded_at: daysAgoIso(59),
        },
      ],
      approvals: [
        {
          stage_code: "assistant",
          stage_name: "Khoa",
          status: "approved",
          decided_by_user_id: 9001,
          decided_by_user_name: "Trưởng khoa",
          decided_at: daysAgoIso(21),
          note: "Hồ sơ đầy đủ.",
        },
      ],
      status_histories: [
        {
          acted_at: daysAgoIso(60),
          acted_by_user_id: 5001,
          acted_by_user_name: "Nguyễn Văn A",
          from_status_code: "draft",
          to_status_code: "pending_member_confirm",
          note: "requested_approval_waiting_members",
        },
        {
          acted_at: daysAgoIso(56),
          acted_by_user_id: 5002,
          acted_by_user_name: "Hệ thống",
          from_status_code: "pending_member_confirm",
          to_status_code: "pending_faculty_review",
          note: "auto_sent_to_faculty_all_members_accepted",
        },
        {
          acted_at: daysAgoIso(21),
          acted_by_user_id: 9001,
          acted_by_user_name: "Trưởng khoa",
          from_status_code: "pending_faculty_review",
          to_status_code: "approved",
          note: "faculty approved",
        },
      ],
    },

    2: {
      activity_id: 2,
      activity_code: "RA-2025-0002",
      title: "Tham dự hội thảo quốc tế về AI ứng dụng",
      abstract: null,
      kind_id: 4,
      kind_code: "conference",
      kind_name: "Hội thảo",
      type_id: 44,
      type_name: "Tham dự",
      academic_year_id: 3,
      academic_year_code: "2024-2025",
      status_id: 4,
      status_code: "member_rejected",
      status_name: STATUS_NAME.member_rejected,
      work_year: 2025,
      venue_name: "International Conference on Applied AI",
      member_role_id: 1,
      member_role_name: "Chủ nhiệm",
      lecturer_hours: null,
      submitted_at: daysAgoIso(8),
      approved_at: null,
      total_hours_calc: "30.00",
      rejection_note: "Không thể tham gia do trùng lịch.",
      authors: [
        {
          lecturer_id: 101,
          lecturer_full_name: "Nguyễn Văn A",
          member_role_id: 1,
          member_role_name: "Chủ nhiệm",
          department_name: "CNTT",
          contribution_share: "0.6000",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(7),
        },
        {
          lecturer_id: 103,
          lecturer_full_name: "Lê Văn C",
          member_role_id: 2,
          member_role_name: "Thành viên",
          department_name: "Khoa học dữ liệu",
          contribution_share: "0.4000",
          confirmation_status: "rejected",
          confirmation_note: "Không thể tham gia do trùng lịch.",
          responded_at: daysAgoIso(6),
        },
      ],
      member_confirmations: [
        {
          member_id: 21,
          lecturer_id: 101,
          lecturer_code: "GV101",
          lecturer_full_name: "Nguyễn Văn A",
          member_role_code: "principal",
          member_role_name: "Chủ nhiệm",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(7),
        },
        {
          member_id: 22,
          lecturer_id: 103,
          lecturer_code: "GV103",
          lecturer_full_name: "Lê Văn C",
          member_role_code: "member",
          member_role_name: "Thành viên",
          confirmation_status: "rejected",
          confirmation_note: "Không thể tham gia do trùng lịch.",
          responded_at: daysAgoIso(6),
        },
      ],
      rejected_members: [
        {
          member_id: 22,
          lecturer_id: 103,
          lecturer_code: "GV103",
          lecturer_full_name: "Lê Văn C",
          member_role_code: "member",
          member_role_name: "Thành viên",
          confirmation_status: "rejected",
          confirmation_note: "Không thể tham gia do trùng lịch.",
          responded_at: daysAgoIso(6),
        },
      ],
      evidence_items: [],
      approvals: [],
      status_histories: [
        {
          acted_at: daysAgoIso(8),
          acted_by_user_id: 5001,
          acted_by_user_name: "Nguyễn Văn A",
          from_status_code: "draft",
          to_status_code: "pending_member_confirm",
          note: "requested_approval_waiting_members",
        },
        {
          acted_at: daysAgoIso(6),
          acted_by_user_id: 5103,
          acted_by_user_name: "Lê Văn C",
          from_status_code: "pending_member_confirm",
          to_status_code: "member_rejected",
          note: "member_rejected_by_invitee",
        },
      ],
    },

    3: {
      activity_id: 3,
      activity_code: "RA-2025-0003",
      title: "Đề tài cấp cơ sở: Tối ưu hóa luồng duyệt NCKH",
      abstract: "Xây dựng quy trình duyệt số cho đề tài nghiên cứu khoa học.",
      kind_id: 3,
      kind_code: "project",
      kind_name: "Đề tài",
      type_id: 33,
      type_name: "Cơ sở",
      academic_year_id: 3,
      academic_year_code: "2024-2025",
      status_id: 1,
      status_code: "draft",
      status_name: STATUS_NAME.draft,
      work_year: 2025,
      venue_name: "SPNC Lab",
      member_role_id: 1,
      member_role_name: "Chủ nhiệm",
      lecturer_hours: null,
      submitted_at: null,
      approved_at: null,
      total_hours_calc: null,
      rejection_note: null,
      authors: [
        {
          lecturer_id: 101,
          lecturer_full_name: "Nguyễn Văn A",
          member_role_id: 1,
          member_role_name: "Chủ nhiệm",
          department_name: "CNTT",
          contribution_share: "1.0000",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(2),
        },
      ],
      member_confirmations: [
        {
          member_id: 31,
          lecturer_id: 101,
          lecturer_code: "GV101",
          lecturer_full_name: "Nguyễn Văn A",
          member_role_code: "principal",
          member_role_name: "Chủ nhiệm",
          confirmation_status: "accepted",
          confirmation_note: null,
          responded_at: daysAgoIso(2),
        },
      ],
      rejected_members: [],
      evidence_items: [],
      approvals: [],
      status_histories: [],
    },
  };

  const rows: PersonalWorkRowDTO[] = Object.values(detailsById).map((detail) => ({
    activity_id: detail.activity_id,
    activity_code: detail.activity_code,
    title: detail.title,
    kind_id: detail.kind_id,
    kind_code: detail.kind_code,
    kind_name: detail.kind_name,
    type_id: detail.type_id,
    type_name: detail.type_name,
    academic_year_id: detail.academic_year_id,
    academic_year_code: detail.academic_year_code,
    status_id: detail.status_id,
    status_code: detail.status_code,
    status_name: detail.status_name,
    work_year: detail.work_year,
    venue_name: detail.venue_name,
    member_role_id: detail.member_role_id,
    member_role_name: detail.member_role_name,
    lecturer_hours: detail.lecturer_hours,
    submitted_at: detail.submitted_at,
    approved_at: detail.approved_at,
    updated_at: detail.approved_at ?? detail.submitted_at ?? daysAgoIso(1),
  }));

  const recomputeStats = (inputRows: PersonalWorkRowDTO[]): PersonalStatsDTO => {
    const total = inputRows.length;
    const approved = inputRows.filter((r) => r.status_code === "approved").length;
    const pending = inputRows.filter((r) =>
      ["pending_member_confirm", "pending_faculty_review", "submitted"].includes(r.status_code)
    ).length;
    const rejected = inputRows.filter((r) => ["member_rejected", "rejected"].includes(r.status_code)).length;
    const draft = inputRows.filter((r) => r.status_code === "draft").length;

    return {
      total_count: total,
      approved_count: approved,
      pending_count: pending,
      rejected_count: rejected,
      draft_count: draft,
    };
  };

  return {
    current_lecturer_id,
    rows,
    detailsById,
    recomputeStats,
    nowIso,
  };
})();
