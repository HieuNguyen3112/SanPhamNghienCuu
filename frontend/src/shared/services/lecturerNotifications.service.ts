import http from "@/lib/http";
import type { UserRole } from "@/app/stores/userStore";

export type NotificationRole = Extract<UserRole, "LECTURER" | "DEPARTMENT_BOARD">;

export interface AppNotificationItem {
  id: string;
  type: string;
  event_key: string;
  title: string;
  message: string;
  data: Record<string, unknown>;
  read_at: string | null;
  created_at: string | null;
  is_unread: boolean;
  target_url: string;
}

export interface AppNotificationPagination {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
}

export interface AppNotificationListResponse {
  items: AppNotificationItem[];
  pagination: AppNotificationPagination;
  unread_count: number;
}

const endpointByRole: Record<NotificationRole, string> = {
  LECTURER: "/api/lecturer/notifications",
  DEPARTMENT_BOARD: "/api/faculty/notifications",
};

function resolveEndpoint(role: NotificationRole): string {
  return endpointByRole[role];
}

export async function listNotificationsByRole(
  role: NotificationRole,
  params?: {
    page?: number;
    per_page?: number;
    unread_only?: boolean;
    read_state?: "all" | "read" | "unread";
    event_key?: string;
    event_keys?: string;
    academic_year?: string;
  }
) {
  const response = await http.get<{ data: AppNotificationListResponse }>(
    resolveEndpoint(role),
    {
      params,
    }
  );

  return response.data.data;
}

export async function markNotificationAsReadByRole(
  role: NotificationRole,
  notificationId: string
) {
  const response = await http.patch<{
    message: string;
    data: AppNotificationItem;
  }>(`${resolveEndpoint(role)}/${notificationId}/read`);

  return response.data;
}

export async function markAllNotificationsAsReadByRole(role: NotificationRole) {
  const response = await http.patch<{
    message: string;
    data: { marked_count: number };
  }>(`${resolveEndpoint(role)}/read-all`);

  return response.data;
}

export async function deleteNotificationByRole(
  role: NotificationRole,
  notificationId: string
) {
  const response = await http.delete<{
    message: string;
    data: { deleted_count: number };
  }>(`${resolveEndpoint(role)}/${notificationId}`);

  return response.data;
}

export async function deleteReadNotificationsByRole(
  role: NotificationRole,
  params?: {
    event_key?: string;
    event_keys?: string;
  }
) {
  const response = await http.delete<{
    message: string;
    data: { deleted_count: number };
  }>(`${resolveEndpoint(role)}/delete-read`, {
    params,
  });

  return response.data;
}

// Backward-compatible wrappers for existing imports.
export type LecturerNotificationItem = AppNotificationItem;
export type LecturerNotificationPagination = AppNotificationPagination;
export type LecturerNotificationListResponse = AppNotificationListResponse;

export async function listLecturerNotifications(params?: {
  page?: number;
  per_page?: number;
  unread_only?: boolean;
}) {
  return listNotificationsByRole("LECTURER", params);
}

export async function markLecturerNotificationAsRead(notificationId: string) {
  return markNotificationAsReadByRole("LECTURER", notificationId);
}

export async function markAllLecturerNotificationsAsRead() {
  return markAllNotificationsAsReadByRole("LECTURER");
}

export async function deleteLecturerNotification(notificationId: string) {
  return deleteNotificationByRole("LECTURER", notificationId);
}

export async function deleteReadLecturerNotifications(params?: {
  event_key?: string;
  event_keys?: string;
}) {
  return deleteReadNotificationsByRole("LECTURER", params);
}
