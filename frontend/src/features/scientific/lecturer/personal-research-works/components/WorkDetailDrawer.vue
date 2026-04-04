<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-slate-900/30"
        aria-hidden="true"
        @click="emit('close')"
      />
    </Transition>

    <Transition
      enter-active-class="transition-transform duration-250 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[540px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900">
                  {{ work ? work.title : "Chi tiết công trình" }}
                </div>

                <div class="mt-1 flex items-center gap-2">
                  <span
                    v-if="work"
                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                    :class="badgeClass(work.statusCode)"
                  >
                    {{ work.statusName }}
                  </span>

                  <!-- <span v-if="work" class="text-xs text-slate-500">
                    {{ work.activityCode }}
                  </span> -->
                </div>
              </div>

              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                ×
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-4">
            <div
              v-if="loading"
              class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
            >
              Đang tải chi tiết...
            </div>

            <div
              v-else-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
            >
              {{ error }}
            </div>

            <div v-else-if="!work" class="text-sm text-slate-600">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-5">
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Thông tin chung
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <InfoRow label="Loại công trình" :value="work.kindName" />
                  <InfoRow label="Phân loại" :value="work.typeName ?? '—'" />
                  <InfoRow label="Vai trò" :value="work.roleName ?? '—'" />
                  <InfoRow
                    label="Năm"
                    :value="work.workYear ? String(work.workYear) : '—'"
                  />
                  <InfoRow
                    wrapper-class="md:col-span-2"
                    label="Nơi công bố/đơn vị"
                    :value="work.venueName ?? '—'"
                  />

                  <InfoRow
                    label="Ngày gửi"
                    :value="formatDateTime(work.submittedAt)"
                  />
                  <InfoRow
                    label="Ngày duyệt"
                    :value="formatDateTime(work.approvedAt)"
                  />

                  <InfoRow
                    v-if="work.statusCode === 'approved'"
                    label="Giờ NCKH của bạn"
                    :value="work.lecturerHours ?? '—'"
                  />
                </div>

                <div
                  v-if="
                    (work.statusCode === 'rejected' ||
                      work.statusCode === 'need_revision') &&
                    lecturerRejectionReasonDisplay
                  "
                  class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
                >
                  <div class="text-xs font-semibold">
                    {{
                      work.statusCode === "need_revision"
                        ? "Lý do yêu cầu chỉnh sửa"
                        : "Lý do từ chối"
                    }}
                  </div>
                  <div class="mt-1 font-medium">
                    {{ lecturerRejectionReasonDisplay.label }}
                  </div>
                  <div
                    v-if="lecturerRejectionReasonDisplay.detail"
                    class="mt-1 whitespace-pre-line"
                  >
                    {{ lecturerRejectionReasonDisplay.detail }}
                  </div>
                </div>

                <div
                  v-if="work.statusCode === 'member_rejected'"
                  class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                >
                  <div class="text-xs font-semibold text-amber-900">
                    Tác giả đã từ chối tham gia
                  </div>
                  <p class="mt-1">
                    Bạn cần xóa/thay thế tác giả bị từ chối hoặc gửi lại yêu cầu
                    xác nhận trước khi gửi lên khoa.
                  </p>
                </div>

                <div
                  v-if="work.statusCode === 'need_revision'"
                  class="mt-4 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800"
                >
                  <div class="text-xs font-semibold text-sky-900">
                    Khoa yêu cầu chỉnh sửa
                  </div>
                  <p class="mt-1">
                    Vui lòng cập nhật hồ sơ theo góp ý rồi gửi duyệt lại.
                  </p>
                </div>
              </section>

              <section
                v-for="workDetailSection in workDetailSections"
                :key="workDetailSection.code"
                class="rounded-xl border border-slate-200 bg-white p-4"
              >
                <div class="text-xs font-semibold text-slate-700">
                  {{ workDetailSection.title }}
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div
                    v-for="detailField in workDetailSection.fields"
                    :key="`${workDetailSection.code}-${detailField.key}`"
                    :class="{
                      'md:col-span-2': shouldSpanTwoColumns(
                        detailField.key,
                        detailField.value,
                      ),
                    }"
                  >
                    <div class="text-xs font-medium text-slate-500">
                      {{ detailField.label }}
                    </div>
                    <a
                      v-if="isLinkFieldValue(detailField.value)"
                      :href="String(detailField.value)"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="mt-0.5 inline-flex break-all text-sm text-sky-700 hover:underline"
                    >
                      {{ String(detailField.value) }}
                    </a>
                    <div
                      v-else
                      class="mt-0.5 whitespace-pre-wrap text-sm text-slate-900"
                    >
                      {{ formatDetailFieldValue(detailField.value) }}
                    </div>
                  </div>
                </div>
              </section>

              <section
                v-if="work.statusCode === 'member_rejected'"
                class="rounded-xl border border-rose-200 bg-rose-50 p-4"
              >
                <div class="text-xs font-semibold text-rose-700">
                  Danh sách tác giả từ chối
                </div>

                <div
                  v-if="work.rejectedMembers.length === 0"
                  class="mt-3 text-sm text-rose-700"
                >
                  Chưa có chi tiết tác giả từ chối.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="member in work.rejectedMembers"
                    :key="member.memberId"
                    class="rounded-lg border border-rose-200 bg-white p-3"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <div class="font-semibold text-slate-900">
                          {{ member.lecturerFullName }}
                          <span class="text-slate-500"
                            >({{ member.lecturerCode ?? "N/A" }})</span
                          >
                        </div>
                        <div class="mt-0.5 text-xs text-slate-600">
                          Vai trò: {{ member.memberRoleName ?? "—" }}
                        </div>
                        <div class="mt-1 text-xs text-rose-700">
                          Lý do:
                          {{ member.confirmationNote || "Không có ghi chú" }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                          Thời điểm phản hồi:
                          {{ formatDateTime(member.respondedAt) }}
                        </div>
                      </div>

                      <button
                        type="button"
                        class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                        @click="emit('reinvite-member', member.memberId)"
                      >
                        Gửi lại yêu cầu
                      </button>
                    </div>
                  </div>
                </div>
              </section>

              <section
                v-if="work.statusCode === 'pending_member_confirm'"
                class="rounded-xl border border-amber-200 bg-amber-50 p-4"
              >
                <div class="text-xs font-semibold text-amber-900">
                  Danh sách tác giả đang chờ xác nhận
                </div>

                <div
                  v-if="pendingMemberConfirmations.length === 0"
                  class="mt-3 text-sm text-amber-800"
                >
                  Không còn tác giả nào đang chờ xác nhận.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="member in pendingMemberConfirmations"
                    :key="member.memberId"
                    class="rounded-lg border border-amber-200 bg-white p-3"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div class="min-w-0">
                        <div class="font-semibold text-slate-900">
                          {{ member.lecturerFullName }}
                          <span class="text-slate-500"
                            >({{ member.lecturerCode ?? "N/A" }})</span
                          >
                        </div>
                        <div class="mt-0.5 text-xs text-slate-600">
                          Vai trò: {{ member.memberRoleName ?? "—" }}
                        </div>
                      </div>

                      <div class="flex shrink-0 items-center gap-2">
                        <button
                          v-if="work.actions.canResendPendingInvitation"
                          type="button"
                          class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                          @click="
                            emit('resend-pending-member', member.memberId)
                          "
                        >
                          Gửi lại lời mời
                        </button>
                        <button
                          v-if="work.actions.canRemovePendingMember"
                          type="button"
                          class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100"
                          @click="
                            emit('remove-pending-member', member.memberId)
                          "
                        >
                          Xóa thành viên
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Danh sách tác giả
                </div>

                <div
                  v-if="work.authors.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Chưa có tác giả.
                </div>

                <div
                  v-else
                  class="mt-3 overflow-hidden rounded-lg border border-slate-200"
                >
                  <table class="w-full text-left text-sm">
                    <thead
                      class="bg-slate-50 text-xs font-semibold text-slate-600"
                    >
                      <tr>
                        <th class="px-3 py-2">Tác giả</th>
                        <th class="px-3 py-2">Vai trò</th>
                        <th class="px-3 py-2">Đơn vị</th>
                      </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                      <tr v-for="author in work.authors" :key="author.memberId">
                        <td class="px-3 py-2 font-medium text-slate-900">
                          {{ author.lecturerFullName }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ author.memberRoleName }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ author.departmentName ?? "—" }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Minh chứng
                </div>

                <div
                  v-if="work.evidenceItems.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Chưa có minh chứng.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="item in work.evidenceItems"
                    :key="item.evidenceFileId"
                    class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:bg-slate-50"
                  >
                    <div class="min-w-0">
                      <div
                        class="truncate text-sm font-semibold text-slate-900"
                      >
                        {{ item.originalName }}
                      </div>
                      <div class="mt-1 text-xs text-slate-500">
                        {{ item.fileTypeName }}
                        <span class="px-1 text-slate-300">•</span>
                        {{ item.mimeType }}
                        <span class="px-1 text-slate-300">•</span>
                        {{ formatBytes(item.sizeBytes) }}
                      </div>
                      <div class="mt-1 text-xs text-slate-400">
                        Uploaded: {{ formatDateTime(item.uploadedAt) }}
                      </div>
                    </div>

                    <button
                      type="button"
                      class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="isEvidenceLoading(item.evidenceFileId)"
                      @click="openEvidenceFile(item)"
                    >
                      {{
                        isEvidenceLoading(item.evidenceFileId)
                          ? "Đang mở..."
                          : "Mở"
                      }}
                    </button>
                  </div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Trạng thái và lịch sử
                </div>

                <div class="mt-3 space-y-3">
                  <TimelineItem
                    v-for="item in sortedTimelineItems"
                    :key="item.key"
                    :label="item.label"
                    :value="item.value"
                    :actor-name="item.actorName"
                    :note="item.note"
                    :status="item.status"
                  />
                </div>
              </section>
            </div>
          </div>

          <div class="border-t border-slate-200 px-4 py-3">
            <div class="flex items-center justify-between gap-2">
              <button
                v-if="
                  work &&
                  (work.actions.canEdit || work.statusCode === 'rejected')
                "
                type="button"
                class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                @click="onClickPrimaryAction(work)"
              >
                {{ editActionLabel(work) }}
              </button>
            </div>
          </div>
        </div>
      </aside>
    </Transition>

    <PdfPreviewModal
      :open="previewOpen"
      :title="previewTitle"
      :file-name="previewFileName"
      :preview-url="previewUrl"
      :loading="previewLoading"
      :error-message="previewError"
      :can-download="canDownload"
      @close="closePdfPreview"
      @retry="retryOpenPdfPreview"
      @download="downloadPreviewedPdf"
    />
  </Teleport>
</template>

<script setup lang="ts">
import { computed, watch } from "vue";
import type {
  PersonalWorkDetailField,
  PersonalWorkEvidence,
  PersonalWorkDetail,
  PersonalWorkStatusHistory,
  PersonalWorkStatusCode,
} from "../contracts/personalResearchWorksContracts";
import InfoRow from "@/features/scientific/lecturer/personal-research-works/components/InfoRow.vue";
import TimelineItem from "@/features/scientific/lecturer/personal-research-works/components/TimelineItem.vue";
import PdfPreviewModal from "@/shared/components/modals/PdfPreviewModal.vue";
import { usePdfPreview } from "@/shared/composables/usePdfPreview";
import { formatBackendDateTimeVi } from "@/shared/utils/backendDateTime";
import { formatResearchWorkRejectionReason } from "@/features/scientific/shared/utils/researchWorkRejectionReason";

type TimelineStatus = "pending" | "approved" | "rejected" | null;

type MemberWorkflowAction =
  | "member_invitation_sent"
  | "pending_member_invitation_resent"
  | "member_reinvited"
  | "member_accepted_invitation"
  | "member_rejected_by_invitee"
  | "member_rejected"
  | "member_rejected_block_submit"
  | "pending_member_removed"
  | "member_removed_from_list";

type MemberWorkflowTimelineItem = {
  key: string;
  label: string;
  value: string;
  actedAt: string | null;
  actorName: string | null;
  note: string | null;
  status: TimelineStatus;
};

type TimelineRenderItem = {
  key: string;
  label: string;
  value: string;
  actedAt: string | null;
  actorName: string | null;
  note: string | null;
  status: TimelineStatus;
  order: number;
};

const props = defineProps<{
  open: boolean;
  loading: boolean;
  error: string | null;
  work: PersonalWorkDetail | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "edit-draft", workId: number): void;
  (e: "copy-rejected", workId: number): void;
  (e: "reinvite-member", memberId: number): void;
  (e: "resend-pending-member", memberId: number): void;
  (e: "remove-pending-member", memberId: number): void;
}>();
const {
  previewOpen,
  previewLoading,
  previewError,
  previewTitle,
  previewFileName,
  previewUrl,
  canDownload,
  openPdfPreview,
  retryOpenPdfPreview,
  closePdfPreview,
  isPreviewLoading,
  downloadPreviewedPdf,
  prefetchPdfPreview,
} = usePdfPreview();

const workDetailSections = computed(() => {
  return props.work?.workDetail?.sections ?? [];
});

function formatDetailFieldValue(
  value: PersonalWorkDetailField["value"],
): string {
  if (value === null || value === undefined) return "—";
  if (typeof value === "boolean") return value ? "Có" : "Không";
  const text = String(value).trim();
  return text === "" ? "—" : text;
}

function isLinkFieldValue(value: PersonalWorkDetailField["value"]): boolean {
  if (typeof value !== "string") return false;
  const normalized = value.trim().toLowerCase();
  return normalized.startsWith("http://") || normalized.startsWith("https://");
}

function shouldSpanTwoColumns(
  key: string,
  value: PersonalWorkDetailField["value"],
): boolean {
  const wideFieldKeys = new Set([
    "objectives",
    "content_summary",
    "main_results",
    "keywords",
    "application_address",
    "article_url",
    "journal_website",
  ]);
  if (wideFieldKeys.has(key)) return true;
  if (typeof value !== "string") return false;
  return value.trim().length > 70;
}

function findLatestHistoryByToStatus(statusCode: PersonalWorkStatusCode) {
  const currentWork = props.work;
  if (!currentWork) return null;

  for (
    let index = currentWork.statusHistories.length - 1;
    index >= 0;
    index -= 1
  ) {
    const history = currentWork.statusHistories[index];
    if (history?.toStatusCode === statusCode) return history;
  }

  return null;
}

const rejectedActedAt = computed<string | null>(() => {
  return findLatestHistoryByToStatus("rejected")?.actedAt ?? null;
});

const needRevisionActedAt = computed<string | null>(() => {
  return findLatestHistoryByToStatus("need_revision")?.actedAt ?? null;
});

const shouldShowNeedRevisionTimeline = computed<boolean>(() => {
  const needRevisionAt = needRevisionActedAt.value;
  if (!needRevisionAt) return false;

  if (props.work?.statusCode === "need_revision") return true;

  const rejectedAt = rejectedActedAt.value;
  if (!rejectedAt) return true;

  // Hide old "need_revision" events when the final outcome is a later rejection.
  return needRevisionAt > rejectedAt;
});

const requestedMemberConfirmationHistory = computed(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  return (
    currentWork.statusHistories.find(
      (history) => history.toStatusCode === "pending_member_confirm",
    ) ?? null
  );
});

const pendingMemberConfirmations = computed(() => {
  const currentWork = props.work;
  if (!currentWork) return [];

  return currentWork.memberConfirmations.filter(
    (member) => member.confirmationStatus === "pending",
  );
});

const sentToFacultyHistory = computed(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  return (
    currentWork.statusHistories.find(
      (history) => history.toStatusCode === "pending_faculty_review",
    ) ?? null
  );
});

const submitterName = computed<string | null>(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  const submittedHistory = currentWork.statusHistories.find(
    (history) => history.toStatusCode === "pending_faculty_review",
  );
  return submittedHistory?.actedByUserName ?? null;
});

const memberWorkflowTimelineItems = computed<MemberWorkflowTimelineItem[]>(
  () => {
    const currentWork = props.work;
    if (!currentWork) return [];

    return currentWork.statusHistories.flatMap((history, index) => {
      const parsed = parseMemberTimelineHistory(history);
      if (!parsed) return [];

      const item: MemberWorkflowTimelineItem = {
        key: `member-flow-${index}-${parsed.action}`,
        label: parsed.label,
        value: formatDateTime(history.actedAt),
        actedAt: history.actedAt,
        actorName: history.actedByUserName || null,
        note: parsed.note,
        status: parsed.status,
      };

      return [item];
    });
  },
);

const hasMultipleMemberConfirmations = computed<boolean>(() => {
  return (props.work?.memberConfirmations.length ?? 0) > 1;
});

const requiresMemberConfirmationFlow = computed<boolean>(() => {
  const currentWork = props.work;
  if (!currentWork) return false;

  if (memberWorkflowTimelineItems.value.length > 0) {
    return true;
  }

  if (!hasMultipleMemberConfirmations.value) {
    return false;
  }

  if (requestedMemberConfirmationHistory.value) return true;
  if (
    currentWork.statusCode === "pending_member_confirm" ||
    currentWork.statusCode === "member_rejected"
  ) {
    return true;
  }

  return currentWork.statusHistories.some(
    (history) => history.fromStatusCode === "pending_member_confirm",
  );
});

const memberConfirmationRequestedTimeline = computed<{
  value: string;
  actedAt: string | null;
  actorName: string | null;
  note: string | null;
  status: TimelineStatus;
} | null>(() => {
  const currentWork = props.work;
  if (!currentWork || !requiresMemberConfirmationFlow.value) return null;

  const requestedHistory = requestedMemberConfirmationHistory.value;
  const requestedAt = requestedHistory?.actedAt ?? null;

  let status: TimelineStatus = "pending";
  if (currentWork.statusCode === "member_rejected") {
    status = "rejected";
  } else if (sentToFacultyHistory.value || requestedAt) {
    status = "approved";
  }

  let note: string | null = null;
  if (requestedHistory?.note === "requested_approval_waiting_members") {
    note = "Đã gửi yêu cầu xác nhận cho các giảng viên tham gia.";
  }

  return {
    value: formatDateTime(requestedAt),
    actedAt: requestedAt,
    actorName: requestedHistory?.actedByUserName ?? null,
    note,
    status,
  };
});

const lecturerConfirmedTimeline = computed<{
  value: string;
  actedAt: string | null;
  actorName: string | null;
  note: string | null;
  status: TimelineStatus;
} | null>(() => {
  const currentWork = props.work;
  if (!currentWork || !requiresMemberConfirmationFlow.value) return null;

  const confirmations = currentWork.memberConfirmations;
  const acceptedMembers = confirmations.filter(
    (member) => member.confirmationStatus === "accepted",
  );
  const pendingMembers = confirmations.filter(
    (member) => member.confirmationStatus === "pending",
  );
  const rejectedMembers = confirmations.filter(
    (member) => member.confirmationStatus === "rejected",
  );

  const allMembersAccepted =
    confirmations.length > 0 &&
    pendingMembers.length === 0 &&
    rejectedMembers.length === 0;

  const transitionAfterConfirmed = currentWork.statusHistories.find(
    (history) =>
      history.fromStatusCode === "pending_member_confirm" &&
      history.toStatusCode === "pending_faculty_review",
  );

  const rejectedHistory = [...currentWork.statusHistories]
    .reverse()
    .find((history) => history.toStatusCode === "member_rejected");

  const latestAcceptedResponseAt = pickLatestDateTime(
    acceptedMembers.map((member) => member.respondedAt),
  );

  const latestRejectedResponseAt = pickLatestDateTime(
    rejectedMembers.map((member) => member.respondedAt),
  );

  if (allMembersAccepted || transitionAfterConfirmed) {
    const actedAt =
      transitionAfterConfirmed?.actedAt ?? latestAcceptedResponseAt;
    return {
      value: formatDateTime(actedAt),
      actedAt,
      actorName: transitionAfterConfirmed?.actedByUserName ?? null,
      note:
        confirmations.length > 0
          ? `${acceptedMembers.length}/${confirmations.length} giảng viên đã xác nhận.`
          : "Đã hoàn tất xác nhận thành viên.",
      status: "approved",
    };
  }

  if (
    rejectedMembers.length > 0 ||
    currentWork.statusCode === "member_rejected"
  ) {
    const actedAt = rejectedHistory?.actedAt ?? latestRejectedResponseAt;
    return {
      value: formatDateTime(actedAt),
      actedAt,
      actorName: rejectedHistory?.actedByUserName ?? null,
      note:
        confirmations.length > 0
          ? `${acceptedMembers.length}/${confirmations.length} đã xác nhận, ${rejectedMembers.length} từ chối.`
          : "Có giảng viên từ chối xác nhận.",
      status: "rejected",
    };
  }

  return {
    value: "—",
    actedAt: null,
    actorName: null,
    note:
      confirmations.length > 0
        ? `${acceptedMembers.length}/${confirmations.length} giảng viên đã xác nhận.`
        : "Đang chờ giảng viên xác nhận.",
    status: "pending",
  };
});

const submitToFacultyTimeline = computed<{
  value: string;
  actedAt: string | null;
  actorName: string | null;
  note: string | null;
  status: TimelineStatus;
}>(() => {
  const currentWork = props.work;
  if (!currentWork) {
    return {
      value: "—",
      actedAt: null,
      actorName: null,
      note: null,
      status: null,
    };
  }

  const history = sentToFacultyHistory.value;
  const sentAt = history?.actedAt ?? currentWork.submittedAt;
  const hasSentToFaculty = Boolean(sentAt);

  let note: string | null = null;
  if (history?.note === "auto_sent_to_faculty_all_members_accepted") {
    note = "Hệ thống tự động gửi duyệt sau khi các giảng viên đã xác nhận.";
  } else if (history?.note === "auto_sent_to_faculty_no_pending") {
    note = "Không có thành viên chờ xác nhận nên hệ thống gửi duyệt ngay.";
  } else if (history?.note === "pending_member_removed_auto_sent_to_faculty") {
    note =
      "Đã xóa thành viên chờ xác nhận cuối cùng, hệ thống tự động chuyển hồ sơ lên khoa.";
  } else if (history?.note === "minor_revision_sent_to_faculty") {
    note = "Chỉnh sửa nhỏ được gửi thẳng lên khoa duyệt lại.";
  }

  const status: TimelineStatus = hasSentToFaculty
    ? "approved"
    : currentWork.statusCode === "draft" ||
        currentWork.statusCode === "pending_member_confirm" ||
        currentWork.statusCode === "member_rejected"
      ? "pending"
      : null;

  return {
    value: formatDateTime(sentAt),
    actedAt: sentAt,
    actorName: history?.actedByUserName ?? submitterName.value,
    note,
    status,
  };
});

const sortedTimelineItems = computed<TimelineRenderItem[]>(() => {
  const currentWork = props.work;
  if (!currentWork) return [];

  const items: TimelineRenderItem[] = [];
  let order = 0;

  const pushItem = (item: Omit<TimelineRenderItem, "order">) => {
    items.push({ ...item, order });
    order += 1;
  };

  if (
    memberConfirmationRequestedTimeline.value &&
    memberWorkflowTimelineItems.value.length === 0
  ) {
    pushItem({
      key: "member-confirmation-requested",
      label: "Gửi giảng viên xác nhận",
      value: memberConfirmationRequestedTimeline.value.value,
      actedAt: memberConfirmationRequestedTimeline.value.actedAt,
      actorName: memberConfirmationRequestedTimeline.value.actorName,
      note: memberConfirmationRequestedTimeline.value.note,
      status: memberConfirmationRequestedTimeline.value.status,
    });
  }

  if (
    lecturerConfirmedTimeline.value &&
    memberWorkflowTimelineItems.value.length === 0
  ) {
    pushItem({
      key: "member-confirmation-completed",
      label: "Giảng viên đã xác nhận",
      value: lecturerConfirmedTimeline.value.value,
      actedAt: lecturerConfirmedTimeline.value.actedAt,
      actorName: lecturerConfirmedTimeline.value.actorName,
      note: lecturerConfirmedTimeline.value.note,
      status: lecturerConfirmedTimeline.value.status,
    });
  }

  memberWorkflowTimelineItems.value.forEach((item) => {
    pushItem({
      key: item.key,
      label: item.label,
      value: item.value,
      actedAt: item.actedAt,
      actorName: item.actorName,
      note: item.note,
      status: item.status,
    });
  });

  pushItem({
    key: "submit-to-faculty",
    label: "Gửi duyệt",
    value: submitToFacultyTimeline.value.value,
    actedAt: submitToFacultyTimeline.value.actedAt,
    actorName: submitToFacultyTimeline.value.actorName,
    note: submitToFacultyTimeline.value.note,
    status: submitToFacultyTimeline.value.status,
  });

  currentWork.approvals.forEach((approval, index) => {
    pushItem({
      key: `approval-${approval.stageCode}-${index}`,
      label: `Xét duyệt ${approval.stageName}`,
      value: approval.decidedAt ? formatDateTime(approval.decidedAt) : "—",
      actedAt: approval.decidedAt ?? null,
      actorName: approval.decidedByUserName,
      note: approval.note,
      status: approval.status,
    });
  });

  if (currentWork.statusCode === "approved") {
    pushItem({
      key: "status-approved",
      label: "Phê duyệt",
      value: formatDateTime(currentWork.approvedAt),
      actedAt: currentWork.approvedAt,
      actorName: approvalActorName.value,
      note: null,
      status: "approved",
    });
  }

  if (currentWork.statusCode === "rejected") {
    pushItem({
      key: "status-rejected",
      label: "Từ chối",
      value: formatDateTime(rejectedActedAt.value),
      actedAt: rejectedActedAt.value,
      actorName: rejectedActorName.value,
      note: lecturerRejectionReasonDisplay.value?.fullText ?? null,
      status: "rejected",
    });
  }

  if (shouldShowNeedRevisionTimeline.value) {
    pushItem({
      key: "status-need-revision",
      label: "Yêu cầu chỉnh sửa",
      value: formatDateTime(needRevisionActedAt.value),
      actedAt: needRevisionActedAt.value,
      actorName: needRevisionActorName.value,
      note: lecturerRejectionReasonDisplay.value?.fullText ?? null,
      status: "rejected",
    });
  }

  return [...items].sort((a, b) => {
    const aTs = toEpochMs(a.actedAt);
    const bTs = toEpochMs(b.actedAt);

    if (aTs !== null && bTs !== null) {
      if (aTs === bTs) {
        return a.order - b.order;
      }
      return aTs - bTs;
    }

    if (aTs !== null) return -1;
    if (bTs !== null) return 1;
    return a.order - b.order;
  });
});

const approvalActorName = computed<string | null>(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  for (
    let index = currentWork.statusHistories.length - 1;
    index >= 0;
    index -= 1
  ) {
    const history = currentWork.statusHistories[index];
    if (history?.toStatusCode === "approved") return history.actedByUserName;
  }

  for (let index = currentWork.approvals.length - 1; index >= 0; index -= 1) {
    const approval = currentWork.approvals[index];
    if (approval?.status === "approved" && approval.decidedByUserName) {
      return approval.decidedByUserName;
    }
  }
  return null;
});

const rejectedActorName = computed<string | null>(() => {
  const rejectedHistory = findLatestHistoryByToStatus("rejected");
  if (rejectedHistory) return rejectedHistory.actedByUserName;

  const currentWork = props.work;
  if (!currentWork) return null;

  for (let index = currentWork.approvals.length - 1; index >= 0; index -= 1) {
    const approval = currentWork.approvals[index];
    if (approval?.status === "rejected" && approval.decidedByUserName) {
      return approval.decidedByUserName;
    }
  }
  return null;
});

const needRevisionActorName = computed<string | null>(() => {
  return findLatestHistoryByToStatus("need_revision")?.actedByUserName ?? null;
});

const lecturerRejectionReasonDisplay = computed(() => {
  const rejectionNote = props.work?.rejectionNote ?? null;
  if (!rejectionNote) return null;

  return formatResearchWorkRejectionReason({
    rawNote: rejectionNote,
  });
});

function badgeClass(statusCode: PersonalWorkStatusCode): string {
  switch (statusCode) {
    case "approved":
      return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
    case "pending_member_confirm":
    case "pending_faculty_review":
      return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    case "need_revision":
      return "bg-sky-50 text-sky-700 ring-1 ring-sky-200";
    case "member_rejected":
    case "rejected":
      return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
    case "draft":
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
    default:
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
  }
}

function editActionLabel(work: PersonalWorkDetail): string {
  if (work.statusCode === "member_rejected") return "Chỉnh sửa thành viên";
  if (work.statusCode === "need_revision") return "Chỉnh sửa theo yêu cầu khoa";
  if (work.statusCode === "rejected") return "Sao chép để kê khai lại";
  return "Chỉnh sửa công trình";
}

function onClickPrimaryAction(work: PersonalWorkDetail): void {
  if (work.statusCode === "rejected") {
    emit("copy-rejected", work.activityId);
    return;
  }

  emit("edit-draft", work.activityId);
}

function formatDateTime(value: string | null): string {
  if (!value) return "—";
  const formatted = formatBackendDateTimeVi(value);
  return formatted || value;
}

function toEpochMs(value: string | null): number | null {
  if (!value) return null;
  const normalized = value.includes("T") ? value : value.replace(" ", "T");
  const parsed = Date.parse(normalized);
  return Number.isNaN(parsed) ? null : parsed;
}

function parseMemberTimelineHistory(history: PersonalWorkStatusHistory): {
  action: MemberWorkflowAction;
  label: string;
  note: string;
  status: TimelineStatus;
} | null {
  const parsed = parseMemberTimelineNote(history.note);
  if (!parsed) return null;

  const memberLabel = resolveMemberDisplayName(
    parsed.memberId,
    parsed.memberName,
  );

  switch (parsed.action) {
    case "member_invitation_sent":
      return {
        action: parsed.action,
        label: "Gửi lời mời xác nhận",
        note: `${memberLabel} đã được mời xác nhận tham gia.`,
        status: "pending",
      };
    case "pending_member_invitation_resent":
      return {
        action: parsed.action,
        label: "Gửi lại lời mời",
        note: `Đã gửi lại lời mời xác nhận cho ${memberLabel}.`,
        status: "pending",
      };
    case "member_reinvited":
      return {
        action: parsed.action,
        label: "Mời lại giảng viên",
        note: `Đã mời lại ${memberLabel} tham gia công trình.`,
        status: "pending",
      };
    case "member_accepted_invitation":
      return {
        action: parsed.action,
        label: "Giảng viên xác nhận tham gia",
        note: `${memberLabel} đã đồng ý tham gia công trình.`,
        status: "approved",
      };
    case "member_rejected_by_invitee":
      return {
        action: parsed.action,
        label: "Giảng viên từ chối tham gia",
        note: `${memberLabel} đã từ chối tham gia công trình.`,
        status: "rejected",
      };
    case "member_rejected":
      return {
        action: parsed.action,
        label: "Có giảng viên từ chối tham gia",
        note: "Công trình chuyển về trạng thái thành viên từ chối do có phản hồi từ chối.",
        status: "rejected",
      };
    case "member_rejected_block_submit":
      return {
        action: parsed.action,
        label: "Bị chặn gửi duyệt",
        note: "Không thể gửi duyệt vì còn giảng viên từ chối tham gia. Vui lòng xử lý danh sách tác giả trước khi gửi lại.",
        status: "rejected",
      };
    case "pending_member_removed":
      return {
        action: parsed.action,
        label: "Xóa giảng viên khỏi công trình",
        note: `Đã xóa ${memberLabel} khỏi danh sách chờ xác nhận.`,
        status: null,
      };
    case "member_removed_from_list":
      return {
        action: parsed.action,
        label: "Cập nhật danh sách tác giả",
        note: `Đã loại ${memberLabel} khỏi danh sách tác giả.`,
        status: null,
      };
    default:
      return null;
  }
}

function resolveMemberDisplayName(
  memberId: number | null,
  explicitName: string | null,
): string {
  const normalizedExplicitName = explicitName?.trim();
  if (normalizedExplicitName) {
    return normalizedExplicitName;
  }

  const currentWork = props.work;
  if (!currentWork || !memberId) {
    return "giảng viên";
  }

  const confirmationMatch = currentWork.memberConfirmations.find(
    (member) => member.memberId === memberId,
  );
  const confirmationName = confirmationMatch?.lecturerFullName?.trim();
  if (confirmationName) {
    return confirmationName;
  }

  const authorMatch = currentWork.authors.find(
    (member) => member.memberId === memberId,
  );
  const authorName = authorMatch?.lecturerFullName?.trim();
  if (authorName) {
    return authorName;
  }

  return "giảng viên";
}

function parseMemberTimelineNote(note: string | null): {
  action: MemberWorkflowAction;
  memberId: number | null;
  memberName: string | null;
} | null {
  if (!note) return null;

  const trimmed = note.trim();
  if (trimmed === "") return null;

  if (
    trimmed === "member_rejected" ||
    trimmed === "member_rejected_block_submit"
  ) {
    return {
      action: trimmed,
      memberId: null,
      memberName: null,
    };
  }

  const legacyReinviteMatch = trimmed.match(/^member_reinvited:(\d+)$/i);
  if (legacyReinviteMatch) {
    return {
      action: "member_reinvited",
      memberId: Number(legacyReinviteMatch[1]),
      memberName: null,
    };
  }

  const [rawAction, rawMemberId, ...rest] = trimmed.split("|");
  const action = (rawAction ?? "").trim() as MemberWorkflowAction;
  const parsedMemberIdRaw = (rawMemberId ?? "").trim();
  const parsedMemberId =
    parsedMemberIdRaw === "" ? null : Number(parsedMemberIdRaw);
  if (!rawAction) {
    return null;
  }

  if (!isKnownMemberWorkflowAction(action)) {
    return null;
  }

  const requiresMemberId =
    action !== "member_rejected" && action !== "member_rejected_block_submit";
  if (
    requiresMemberId &&
    (parsedMemberId === null ||
      !Number.isFinite(parsedMemberId) ||
      parsedMemberId <= 0)
  ) {
    return null;
  }

  const memberNameRaw = rest.join("|").trim();

  return {
    action,
    memberId:
      parsedMemberId !== null &&
      Number.isFinite(parsedMemberId) &&
      parsedMemberId > 0
        ? parsedMemberId
        : null,
    memberName: memberNameRaw !== "" ? memberNameRaw : null,
  };
}

function isKnownMemberWorkflowAction(
  action: string,
): action is MemberWorkflowAction {
  return (
    action === "member_invitation_sent" ||
    action === "pending_member_invitation_resent" ||
    action === "member_reinvited" ||
    action === "member_accepted_invitation" ||
    action === "member_rejected_by_invitee" ||
    action === "member_rejected" ||
    action === "member_rejected_block_submit" ||
    action === "pending_member_removed" ||
    action === "member_removed_from_list"
  );
}

function pickLatestDateTime(
  values: Array<string | null | undefined>,
): string | null {
  let latest: string | null = null;

  values.forEach((value) => {
    if (!value) return;
    if (!latest || value > latest) {
      latest = value;
    }
  });

  return latest;
}

function formatBytes(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes <= 0) return "0 B";
  const units = ["B", "KB", "MB", "GB", "TB"] as const;
  let size = bytes;
  let unitIndex = 0;
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }
  return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function isEvidenceLoading(evidenceId: number): boolean {
  return isPreviewLoading(`personal-work-evidence:${evidenceId}`);
}

async function openEvidenceFile(item: PersonalWorkEvidence): Promise<void> {
  const fallbackUrl = buildEvidenceUrl(item.disk, item.path);
  const previewRawUrl = (
    item.previewUrl ??
    item.downloadUrl ??
    fallbackUrl
  ).trim();
  if (!previewRawUrl) return;

  if (item.disk === "url" && /^https?:\/\//i.test(previewRawUrl)) {
    window.open(previewRawUrl, "_blank", "noopener,noreferrer");
    return;
  }

  const downloadRawUrl = (item.downloadUrl ?? fallbackUrl).trim();
  const fallbackFileName =
    item.originalName?.trim() || `minh-chung-${item.evidenceFileId}.pdf`;

  await openPdfPreview({
    cacheKey: `personal-work-evidence:${item.evidenceFileId}`,
    title: "Xem minh chứng",
    fallbackFileName,
    previewUrl: previewRawUrl,
    downloadUrl: downloadRawUrl,
    errorMessage: "Không thể mở file minh chứng. Vui lòng thử lại.",
  });
}

function buildEvidenceUrl(disk: string, path: string): string {
  void disk;
  return path.startsWith("http") ? path : `/${path.replace(/^\/+/, "")}`;
}

watch(
  () => [props.open, props.work?.activityId, props.work?.evidenceItems],
  ([isOpen]) => {
    if (!isOpen) return;
    const firstFile = (props.work?.evidenceItems ?? []).find(
      (item) => item.disk !== "url",
    );
    if (!firstFile) return;

    const fallbackUrl = buildEvidenceUrl(firstFile.disk, firstFile.path);
    const previewRawUrl = (
      firstFile.previewUrl ??
      firstFile.downloadUrl ??
      fallbackUrl
    ).trim();
    if (!previewRawUrl) return;
    const downloadRawUrl = (firstFile.downloadUrl ?? fallbackUrl).trim();

    void prefetchPdfPreview({
      cacheKey: `personal-work-evidence:${firstFile.evidenceFileId}`,
      previewUrl: previewRawUrl,
      downloadUrl: downloadRawUrl,
      fallbackFileName:
        firstFile.originalName?.trim() ||
        `minh-chung-${firstFile.evidenceFileId}.pdf`,
    });
  },
  { immediate: true, deep: true },
);
</script>
