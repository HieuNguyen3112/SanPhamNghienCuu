import type {
  ApprovedWorkRowDTO,
  WorkDetailDTO,
} from "../contracts/selectHoursRequest.contract";
import {
  approvedWorkRowDtoListMock,
  getWorkDetailDtoMock,
} from "../mock-data/approvedWorks.mock";

function delay(ms: number) {
  return new Promise<void>((resolve) => window.setTimeout(resolve, ms));
}

function randomLatencyMs() {
  return 200 + Math.floor(Math.random() * 200);
}

export async function loadApprovedWorksDTO(): Promise<ApprovedWorkRowDTO[]> {
  await delay(randomLatencyMs());
  return approvedWorkRowDtoListMock;
}

export async function loadWorkDetailDTO(
  activityId: number
): Promise<WorkDetailDTO> {
  await delay(randomLatencyMs());
  const dto = getWorkDetailDtoMock(activityId);
  if (!dto) throw new Error("Không tìm thấy chi tiết công trình (mock).");
  return dto;
}

export async function submitHoursApprovalRequestDTO(payload: {
  activity_ids: number[];
}): Promise<void> {
  await delay(randomLatencyMs());
  if (!payload.activity_ids || payload.activity_ids.length === 0) {
    throw new Error("Payload không hợp lệ: activity_ids rỗng.");
  }
  // mock: success
}
