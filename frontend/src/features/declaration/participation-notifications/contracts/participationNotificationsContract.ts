export type WorkTypeDto = "ARTICLE" | "PROJECT" | "BOOK" | "CONFERENCE";
export type NotificationStatusDto = "PENDING" | "ACCEPTED" | "REJECTED";
export type EvidenceTypeDto = "FILE" | "LINK";

export interface ParticipationWorkDetailFieldDto {
  key: string;
  label: string;
  value: string | number | boolean | null;
}

export interface ParticipationWorkDetailSectionDto {
  code: string;
  title: string;
  fields: ParticipationWorkDetailFieldDto[];
}

export interface ParticipationWorkDetailDto {
  kind_code?: string | null;
  sections?: ParticipationWorkDetailSectionDto[];
}

export interface ParticipationEvidenceDto {
  id: number;
  type: EvidenceTypeDto;
  label: string;
  url: string;
  preview_url?: string | null;
  download_url?: string | null;
}

export interface ParticipationMemberDto {
  id: number;
  full_name: string;
  unit: string;
  role: string;
  status: NotificationStatusDto;
  is_current_user: boolean;
}

export interface ParticipationConfirmationLogDto {
  status: "ACCEPTED" | "REJECTED";
  confirmed_at: string; // ISO
  reason?: string;
}

export interface ParticipationNotificationDto {
  id: number;
  work_title: string;
  work_type: WorkTypeDto;
  your_role: string;
  owner_name: string;
  requested_at: string; // ISO
  status: NotificationStatusDto;
  work_short_info: string;

  note_from_owner?: string;
  confirmation_log?: ParticipationConfirmationLogDto;

  work_system_status: string;
  work_detail?: ParticipationWorkDetailDto | null;
  members?: ParticipationMemberDto[];
  evidences?: ParticipationEvidenceDto[];
}

/* =========================
   UI Models (camelCase)
========================= */

export type WorkType = WorkTypeDto;
export type NotificationStatus = NotificationStatusDto;
export type EvidenceType = EvidenceTypeDto;

export interface ParticipationWorkDetailField {
  key: string;
  label: string;
  value: string | number | boolean | null;
}

export interface ParticipationWorkDetailSection {
  code: string;
  title: string;
  fields: ParticipationWorkDetailField[];
}

export interface ParticipationWorkDetail {
  kindCode: string | null;
  sections: ParticipationWorkDetailSection[];
}

export interface ParticipationEvidence {
  id: number;
  type: EvidenceType;
  label: string;
  url: string;
  previewUrl?: string | null;
  downloadUrl?: string | null;
}

export interface ParticipationMember {
  id: number;
  fullName: string;
  unit: string;
  role: string;
  status: NotificationStatus;
  isCurrentUser: boolean;
}

export interface ParticipationConfirmationLog {
  status: "ACCEPTED" | "REJECTED";
  confirmedAt: string; // ISO
  reason?: string;
}

export interface ParticipationNotification {
  id: number;
  workTitle: string;
  workType: WorkType;
  yourRole: string;
  ownerName: string;
  requestedAt: string; // ISO
  status: NotificationStatus;
  workShortInfo: string;

  noteFromOwner?: string;
  confirmationLog?: ParticipationConfirmationLog;

  workSystemStatus: string;
  workDetail?: ParticipationWorkDetail;
  members: ParticipationMember[];
  evidences: ParticipationEvidence[];
}

export function mapParticipationNotificationDtoToModel(
  dto: ParticipationNotificationDto,
): ParticipationNotification {
  const members = dto.members ?? [];
  const evidences = dto.evidences ?? [];
  const workDetailSections = dto.work_detail?.sections ?? [];

  return {
    id: dto.id,
    workTitle: dto.work_title,
    workType: dto.work_type,
    yourRole: dto.your_role,
    ownerName: dto.owner_name,
    requestedAt: dto.requested_at,
    status: dto.status,
    workShortInfo: dto.work_short_info,
    noteFromOwner: dto.note_from_owner,
    confirmationLog: dto.confirmation_log
      ? {
          status: dto.confirmation_log.status,
          confirmedAt: dto.confirmation_log.confirmed_at,
          reason: dto.confirmation_log.reason,
        }
      : undefined,
    workSystemStatus: dto.work_system_status,
    workDetail: dto.work_detail
      ? {
          kindCode: dto.work_detail.kind_code ?? null,
          sections: workDetailSections.map((section) => ({
            code: section.code,
            title: section.title,
            fields: (section.fields ?? []).map((field) => ({
              key: field.key,
              label: field.label,
              value: field.value ?? null,
            })),
          })),
        }
      : undefined,
    members: members.map((m) => ({
      id: m.id,
      fullName: m.full_name,
      unit: m.unit,
      role: m.role,
      status: m.status,
      isCurrentUser: m.is_current_user,
    })),
    evidences: evidences.map((e) => ({
      id: e.id,
      type: e.type,
      label: e.label,
      url: e.url,
      previewUrl: e.preview_url ?? null,
      downloadUrl: e.download_url ?? null,
    })),
  };
}

export function mapParticipationNotificationModelToDto(
  model: ParticipationNotification,
): ParticipationNotificationDto {
  return {
    id: model.id,
    work_title: model.workTitle,
    work_type: model.workType,
    your_role: model.yourRole,
    owner_name: model.ownerName,
    requested_at: model.requestedAt,
    status: model.status,
    work_short_info: model.workShortInfo,
    note_from_owner: model.noteFromOwner,
    confirmation_log: model.confirmationLog
      ? {
          status: model.confirmationLog.status,
          confirmed_at: model.confirmationLog.confirmedAt,
          reason: model.confirmationLog.reason,
        }
      : undefined,
    work_system_status: model.workSystemStatus,
    work_detail: model.workDetail
      ? {
          kind_code: model.workDetail.kindCode,
          sections: model.workDetail.sections.map((section) => ({
            code: section.code,
            title: section.title,
            fields: section.fields.map((field) => ({
              key: field.key,
              label: field.label,
              value: field.value,
            })),
          })),
        }
      : undefined,
    members: model.members.map((m) => ({
      id: m.id,
      full_name: m.fullName,
      unit: m.unit,
      role: m.role,
      status: m.status,
      is_current_user: m.isCurrentUser,
    })),
    evidences: model.evidences.map((e) => ({
      id: e.id,
      type: e.type,
      label: e.label,
      url: e.url,
      preview_url: e.previewUrl ?? null,
      download_url: e.downloadUrl ?? null,
    })),
  };
}
