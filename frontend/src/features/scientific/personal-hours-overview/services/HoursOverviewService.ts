// File: src/features/lecturer-hours-overview/services/lecturerHoursOverviewService.ts
import type {
  HoursApprovalBatchDetail,
  HoursApprovalBatchSummary,
  HoursDistributionItem,
  HoursOverview,
} from "../contracts/HoursOverviewContracts";

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

export const lecturerHoursOverviewService = {
  async getOverview(): Promise<HoursOverview> {
    await sleep(180);

    // TODO(BE):
    // - targetHours: workload_quotas.required_hours (by academic_year_id)
    // - approvedHours/pendingHours/rejectedHours: derived from research_activity_members.hours_assigned + activity_statuses.code
    return {
      academicYearId: 1,
      academicYearCode: "2024–2025",

      targetHours: 150,
      approvedHours: 96,
      pendingHours: 24,
      rejectedHours: 10,
    };
  },

  async getDistribution(): Promise<HoursDistributionItem[]> {
    await sleep(180);

    // TODO(BE): group by activity_kinds (kind_id + name) and sum approved hours for current lecturer.
    const rows = [
      { kindId: 1, label: "Bài báo khoa học", hours: 60 },
      { kindId: 2, label: "Đề tài nghiên cứu", hours: 30 },
      { kindId: 3, label: "Hội thảo", hours: 6 },
      { kindId: 4, label: "Hướng dẫn sinh viên", hours: 0 },
      { kindId: 5, label: "Khác", hours: 0 },
    ];

    const total = rows.reduce((sum, r) => sum + r.hours, 0) || 1;

    return rows
      .filter((r) => r.hours > 0)
      .map((r) => ({
        kindId: r.kindId,
        label: r.label,
        hours: r.hours,
        percentage: Math.round((r.hours / total) * 100),
      }));
  },

  async getBatches(): Promise<HoursApprovalBatchSummary[]> {
    await sleep(220);

    // TODO(BE) P0:
    // schema chưa có "batch/đợt", cần endpoint DTO derived hoặc thêm bảng hour_approval_batches.
    return [
      {
        batchId: 101,
        batchName: "Đợt 1",
        academicYearId: 1,
        academicYearCode: "2024–2025",
        status: "approved",
        submittedAt: "2025-03-10T09:15:00Z",
        decidedAt: "2025-03-20T10:30:00Z",
        totalHours: 72,
      },
      {
        batchId: 102,
        batchName: "Đợt 2",
        academicYearId: 1,
        academicYearCode: "2024–2025",
        status: "pending",
        submittedAt: "2025-05-05T08:00:00Z",
        decidedAt: null,
        totalHours: 24,
      },
      {
        batchId: 103,
        batchName: "Đợt 3",
        academicYearId: 1,
        academicYearCode: "2024–2025",
        status: "rejected",
        submittedAt: "2025-06-12T08:00:00Z",
        decidedAt: "2025-06-18T09:10:00Z",
        totalHours: 10,
      },
    ];
  },

  async getBatchDetail(batchId: number): Promise<HoursApprovalBatchDetail> {
    await sleep(220);

    // TODO(BE) P0:
    // cần endpoint trả batch detail + list activity trong batch (title/kind/hours/status)
    if (batchId === 101) {
      return {
        batchId: 101,
        batchName: "Đợt 1",
        academicYearId: 1,
        academicYearCode: "2024–2025",
        status: "approved",
        submittedAt: "2025-03-10T09:15:00Z",
        decidedAt: "2025-03-20T10:30:00Z",
        totalHours: 72,
        items: [
          {
            activityId: 9001,
            title: "Mô hình AI X",
            kindName: "Bài báo",
            lecturerHours: 20,
            status: "approved",
          },
          {
            activityId: 9002,
            title: "Hệ thống Y",
            kindName: "Đề tài",
            lecturerHours: 52,
            status: "approved",
          },
        ],
      };
    }

    if (batchId === 102) {
      return {
        batchId: 102,
        batchName: "Đợt 2",
        academicYearId: 1,
        academicYearCode: "2024–2025",
        status: "pending",
        submittedAt: "2025-05-05T08:00:00Z",
        decidedAt: null,
        totalHours: 24,
        items: [
          {
            activityId: 9003,
            title: "Bài báo Z",
            kindName: "Bài báo",
            lecturerHours: 24,
            status: "pending",
          },
        ],
      };
    }

    return {
      batchId,
      batchName: `Đợt ${batchId}`,
      academicYearId: 1,
      academicYearCode: "2024–2025",
      status: "rejected",
      submittedAt: "2025-06-12T08:00:00Z",
      decidedAt: "2025-06-18T09:10:00Z",
      totalHours: 10,
      items: [
        {
          activityId: 9004,
          title: "Hội thảo Q",
          kindName: "Hội thảo",
          lecturerHours: 10,
          status: "rejected",
        },
      ],
    };
  },
};
