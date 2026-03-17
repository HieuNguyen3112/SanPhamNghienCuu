export type Member = {
  id: number;
  name: string;
  isLeader?: boolean; // chủ nhiệm / tác giả chính / chủ biên (tuỳ màn)
};

function splitIntegerKeepingSum(total: number, n: number): number[] {
  if (n <= 0) return [];
  const base = Math.floor(total / n);
  const rem = total - base * n; // 0..n-1
  return Array.from({ length: n }, (_, i) => base + (i < rem ? 1 : 0));
}

export type CalcResult = {
  hoursByMemberId: Record<number, number>;
  summaryLines: string[];
  total: number;
};

export function calcJournalHours(params: {
  baseHours: number; // 900 / 600 / 300
  members: Member[];
}): CalcResult {
  const n = params.members.length;
  const parts = splitIntegerKeepingSum(params.baseHours, n);

  const hoursByMemberId: Record<number, number> = {};
  params.members.forEach((m, idx) => (hoursByMemberId[m.id] = parts[idx] ?? 0));

  return {
    total: params.baseHours,
    hoursByMemberId,
    summaryLines: [
      `Tổng giờ chuẩn: ${params.baseHours} giờ.`,
      `Số thành viên: ${n} ⇒ mỗi thành viên: ~${Math.round(
        params.baseHours / Math.max(n, 1)
      )} giờ (chia đều).`,
    ],
  };
}

export function calcProjectHours(params: {
  totalHours: number; // vd: 300
  leaderPercent: number; // vd: 0.5 (50%), 0.4 (40%)...
  members: Member[];
  leaderId: number; // chủ nhiệm
}): CalcResult {
  const leaderHours = Math.max(
    0,
    Math.min(
      params.totalHours,
      Math.round(params.totalHours * params.leaderPercent)
    )
  );
  const remain = params.totalHours - leaderHours;

  const others = params.members.filter((m) => m.id !== params.leaderId);
  const otherParts = splitIntegerKeepingSum(remain, others.length);

  const hoursByMemberId: Record<number, number> = {};
  params.members.forEach((m) => (hoursByMemberId[m.id] = 0));
  hoursByMemberId[params.leaderId] = leaderHours;

  others.forEach((m, idx) => (hoursByMemberId[m.id] = otherParts[idx] ?? 0));

  return {
    total: params.totalHours,
    hoursByMemberId,
    summaryLines: [
      `Tổng giờ chuẩn cho đề tài: ${params.totalHours} giờ.`,
      `Tỷ lệ cho chủ nhiệm: ${Math.round(
        params.leaderPercent * 100
      )}% ⇒ giờ chủ nhiệm: ${leaderHours} giờ.`,
      `Số thành viên (không tính chủ nhiệm): ${
        others.length
      } ⇒ chia phần còn lại ${remain} giờ ⇒ mỗi người: ~${Math.round(
        remain / Math.max(others.length, 1)
      )} giờ.`,
    ],
  };
}

export function calcBookHours(params: {
  totalHours: number; // 900 (giáo trình) / 600 (tài liệu tham khảo)
  chiefFraction?: number; // mặc định 1/5
  members: Member[];
  chiefId: number; // chủ biên
}): CalcResult {
  const frac = params.chiefFraction ?? 1 / 5;
  const chiefExtra = Math.max(
    0,
    Math.min(params.totalHours, Math.round(params.totalHours * frac))
  );
  const remain = params.totalHours - chiefExtra;

  // phần 4/5 chia đều cho toàn nhóm (kể cả chủ biên) như ví dụ bạn đưa
  const parts = splitIntegerKeepingSum(remain, params.members.length);

  const hoursByMemberId: Record<number, number> = {};
  params.members.forEach((m, idx) => {
    const shared = parts[idx] ?? 0;
    hoursByMemberId[m.id] =
      m.id === params.chiefId ? shared + chiefExtra : shared;
  });

  return {
    total: params.totalHours,
    hoursByMemberId,
    summaryLines: [
      `Tổng giờ chuẩn: ${params.totalHours} giờ.`,
      `Chủ biên: ${Math.round(frac * 100)}% (= ${chiefExtra} giờ).`,
      `Phần còn lại: ${remain} giờ ⇒ chia đều cho ${
        params.members.length
      } người ⇒ ~${Math.round(
        remain / Math.max(params.members.length, 1)
      )} giờ/người.`,
      `Chủ biên nhận: ${
        hoursByMemberId[params.chiefId] ?? 0
      } giờ (phần chia đều + phần chủ biên).`,
    ],
  };
}

export function calcConferenceHours(params: {
  reportCount: number; // báo cáo
  attendCount: number; // tham dự
  attendCap?: number; // mặc định 40 lần
  reportHoursPerTime?: number; // mặc định 40
  attendHoursPerTime?: number; // mặc định 4
}): { total: number; summaryLines: string[] } {
  const cap = params.attendCap ?? 40;
  const reportH = params.reportHoursPerTime ?? 40;
  const attendH = params.attendHoursPerTime ?? 4;

  const attendCountEffective = Math.min(Math.max(params.attendCount, 0), cap);
  const total =
    Math.max(params.reportCount, 0) * reportH + attendCountEffective * attendH;

  return {
    total,
    summaryLines: [
      `Số lần báo cáo: ${params.reportCount} ⇒ giờ: ${
        params.reportCount * reportH
      } giờ.`,
      `Số lần tham dự: ${
        params.attendCount
      } (tính tối đa ${cap} lần ⇒ ${attendCountEffective} lần) ⇒ giờ: ${
        attendCountEffective * attendH
      } giờ.`,
      `Tổng cộng: ${total} giờ.`,
    ],
  };
}
