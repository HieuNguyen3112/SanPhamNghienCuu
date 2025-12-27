// File: src/features/declaration/participation-notifications/services/participationNotifications.service.ts
import { participationNotificationMockStore } from "../mock-data/participationNotificationsMock";
import type {
  ParticipationNotificationDto,
  NotificationStatusDto,
  ParticipationMemberDto,
} from "../contracts/participationNotificationsContract";

function delay(ms: number): Promise<void> {
  return new Promise((resolve) => window.setTimeout(resolve, ms));
}

function nowIso(): string {
  return new Date().toISOString();
}

function cloneNotificationDto(
  x: ParticipationNotificationDto
): ParticipationNotificationDto {
  return {
    ...x,
    confirmation_log: x.confirmation_log
      ? { ...x.confirmation_log }
      : undefined,
    members: x.members.map((m) => ({ ...m })),
    evidences: x.evidences.map((e) => ({ ...e })),
  };
}

function getByIdOrThrow(id: number): ParticipationNotificationDto {
  const found = participationNotificationMockStore.find((x) => x.id === id);
  if (!found) throw new Error("Không tìm thấy yêu cầu.");
  return found;
}

function updateCurrentUserMemberStatus(
  members: ParticipationMemberDto[],
  current_user_id: number,
  nextStatus: NotificationStatusDto
): ParticipationMemberDto[] {
  return members.map((m) =>
    m.id === current_user_id || m.is_current_user
      ? { ...m, status: nextStatus }
      : m
  );
}

export async function list_participation_notifications(): Promise<
  ParticipationNotificationDto[]
> {
  await delay(250);
  return participationNotificationMockStore.map(cloneNotificationDto);
}

export async function accept_participation_notification(
  id: number,
  current_user_id: number
): Promise<ParticipationNotificationDto> {
  await delay(250);

  const item = getByIdOrThrow(id);

  if (item.status !== "PENDING") {
    return cloneNotificationDto(item);
  }

  const nextStatus: NotificationStatusDto = "ACCEPTED";

  item.status = nextStatus;
  item.confirmation_log = { status: "ACCEPTED", confirmed_at: nowIso() };
  item.members = updateCurrentUserMemberStatus(
    item.members,
    current_user_id,
    nextStatus
  );

  return cloneNotificationDto(item);
}

export async function reject_participation_notification(
  id: number,
  current_user_id: number,
  reason: string
): Promise<ParticipationNotificationDto> {
  await delay(250);

  const trimmed = reason.trim();
  if (!trimmed) throw new Error("Vui lòng nhập lý do từ chối.");

  const item = getByIdOrThrow(id);

  if (item.status !== "PENDING") {
    return cloneNotificationDto(item);
  }

  const nextStatus: NotificationStatusDto = "REJECTED";

  item.status = nextStatus;
  item.confirmation_log = {
    status: "REJECTED",
    confirmed_at: nowIso(),
    reason: trimmed,
  };
  item.members = updateCurrentUserMemberStatus(
    item.members,
    current_user_id,
    nextStatus
  );

  return cloneNotificationDto(item);
}
