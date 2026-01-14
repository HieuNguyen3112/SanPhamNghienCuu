import http, { ensureCsrfCookie } from "@/lib/http";
import type { ParticipationNotificationDto } from "../contracts/participationNotificationsContract";

export type ParticipationNotificationListResponse = {
  items: ParticipationNotificationDto[];
  pagination: {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};

export async function list_participation_notifications(params: {
  status?: string;
  q?: string;
  from?: string;
  to?: string;
  page?: number;
  per_page?: number;
}): Promise<ParticipationNotificationListResponse> {
  const { data } = await http.get<{
    success: boolean;
    message: string;
    data: ParticipationNotificationListResponse;
  }>("/api/lecturer/participation-requests", {
    params,
  });

  return data.data;
}

export async function get_participation_notification_detail(
  id: number
): Promise<ParticipationNotificationDto> {
  const { data } = await http.get<{
    success: boolean;
    message: string;
    data: ParticipationNotificationDto;
  }>(`/api/lecturer/participation-requests/${id}`);

  return data.data;
}

export async function accept_participation_notification(
  id: number
): Promise<ParticipationNotificationDto> {
  await ensureCsrfCookie();

  const { data } = await http.post<{
    success: boolean;
    message: string;
    data: ParticipationNotificationDto;
  }>(`/api/lecturer/participation-requests/${id}/confirm`);

  return data.data;
}

export async function reject_participation_notification(
  id: number,
  reason: string
): Promise<ParticipationNotificationDto> {
  await ensureCsrfCookie();

  const { data } = await http.post<{
    success: boolean;
    message: string;
    data: ParticipationNotificationDto;
  }>(`/api/lecturer/participation-requests/${id}/reject`, {
    reason,
  });

  return data.data;
}
