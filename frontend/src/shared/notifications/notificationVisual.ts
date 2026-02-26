import type { Component } from "vue";
import {
  BadgeCheck,
  Bell,
  Clock3,
  FilePlus2,
  Inbox,
  TriangleAlert,
  Undo2,
  UserCheck,
  UserPlus,
  UserX,
  XCircle,
} from "lucide-vue-next";

type NotificationLike = {
  event_key?: unknown;
  type?: unknown;
  data?: unknown;
};

export interface NotificationVisual {
  eventKey: string;
  icon: Component;
  wrapClass: string;
  iconClass: string;
}

const HOURS_WARNING_KEYS = new Set([
  "hours_warning",
  "missing_hours",
  "deadline_near",
  "deadline_passed",
  "approved_not_submitted",
  "hours_pending",
]);

const WORK_REVISION_KEYS = new Set([
  "work_returned",
  "work_revision_requested",
  "work_needs_revision",
  "returned_for_revision",
]);

function normalizeKey(value: unknown): string {
  if (typeof value !== "string") return "";
  return value.trim().toLowerCase();
}

function inferEventKeyFromType(typeValue: unknown): string {
  const normalizedType = normalizeKey(typeValue);
  if (!normalizedType) return "";

  if (normalizedType.includes("participationinvitationacceptednotification")) {
    return "participation_accepted";
  }
  if (normalizedType.includes("participationinvitationrejectednotification")) {
    return "participation_rejected";
  }
  if (normalizedType.includes("participationinvitationnotification")) {
    return "participation_invitation";
  }

  return "";
}

function asRecord(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    return {};
  }
  return value as Record<string, unknown>;
}

function canonicalEventKey(rawKey: string): string {
  if (!rawKey) return "";

  if (HOURS_WARNING_KEYS.has(rawKey)) {
    return "hours_warning";
  }
  if (WORK_REVISION_KEYS.has(rawKey)) {
    return "work_revision_requested";
  }

  if (rawKey === "participation_confirmed") return "participation_accepted";
  if (rawKey === "participation_declined") return "participation_rejected";
  if (rawKey === "hours_declined") return "hours_rejected";

  return rawKey;
}

function resolveEventKey(notification: NotificationLike): string {
  const payload = asRecord(notification.data);

  const directKey = canonicalEventKey(normalizeKey(notification.event_key));
  if (directKey) {
    return directKey;
  }

  const dataCandidates = [
    payload.event_key,
    payload.type_key,
    payload.action,
    payload.state,
    payload.status,
  ];

  for (const candidate of dataCandidates) {
    const normalized = canonicalEventKey(normalizeKey(candidate));
    if (normalized) {
      return normalized;
    }
  }

  const fromType = canonicalEventKey(inferEventKeyFromType(notification.type));
  if (fromType) {
    return fromType;
  }

  return "system_notification";
}

export function getNotificationVisual(notification: NotificationLike): NotificationVisual {
  const eventKey = resolveEventKey(notification);

  switch (eventKey) {
    case "participation_accepted":
      return {
        eventKey,
        icon: UserCheck,
        wrapClass: "bg-emerald-50 ring-emerald-200",
        iconClass: "text-emerald-600",
      };
    case "participation_rejected":
      return {
        eventKey,
        icon: UserX,
        wrapClass: "bg-rose-50 ring-rose-200",
        iconClass: "text-rose-600",
      };
    case "participation_invitation":
      return {
        eventKey,
        icon: UserPlus,
        wrapClass: "bg-violet-50 ring-violet-200",
        iconClass: "text-violet-600",
      };
    case "work_revision_requested":
      return {
        eventKey,
        icon: Undo2,
        wrapClass: "bg-amber-50 ring-amber-200",
        iconClass: "text-amber-600",
      };
    case "work_approved":
      return {
        eventKey,
        icon: BadgeCheck,
        wrapClass: "bg-emerald-50 ring-emerald-200",
        iconClass: "text-emerald-600",
      };
    case "work_rejected":
      return {
        eventKey,
        icon: XCircle,
        wrapClass: "bg-rose-50 ring-rose-200",
        iconClass: "text-rose-600",
      };
    case "hours_approved":
      return {
        eventKey,
        icon: Clock3,
        wrapClass: "bg-cyan-50 ring-cyan-200",
        iconClass: "text-cyan-600",
      };
    case "hours_rejected":
      return {
        eventKey,
        icon: XCircle,
        wrapClass: "bg-orange-50 ring-orange-200",
        iconClass: "text-orange-600",
      };
    case "hours_warning":
      return {
        eventKey,
        icon: TriangleAlert,
        wrapClass: "bg-amber-50 ring-amber-200",
        iconClass: "text-amber-600",
      };
    case "hours_submitted_to_faculty":
      return {
        eventKey,
        icon: FilePlus2,
        wrapClass: "bg-sky-50 ring-sky-200",
        iconClass: "text-sky-600",
      };
    case "work_submitted_to_faculty":
      return {
        eventKey,
        icon: Inbox,
        wrapClass: "bg-indigo-50 ring-indigo-200",
        iconClass: "text-indigo-600",
      };
    default:
      return {
        eventKey,
        icon: Bell,
        wrapClass: "bg-slate-100 ring-slate-200",
        iconClass: "text-slate-500",
      };
  }
}
