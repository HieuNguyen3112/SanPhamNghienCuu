<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        v-if="submitNotice"
        :class="[
          'rounded-2xl border p-3 text-sm',
          submitNoticeTone === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
            : 'border-blue-200 bg-blue-50 text-blue-700',
        ]"
      >
        {{ submitNotice }}
      </div>

      <DeclarationFormShell
        title="Khai báo bài báo khoa học"
        description="Chọn loại bài báo để xác định giờ chuẩn; giờ chia đều cho số tác giả."
        :icon="FileText"
        :status="shell.status.value"
        :canSubmit="canSubmit"
        :pending="shell.pending.value"
        :errorMessage="shell.error_message.value"
        :successVisible="shell.success_visible.value"
        :successMessage="shell.success_message.value"
        @save-draft="handleSaveDraft"
        @submit="handleSubmit"
        @close-success="shell.close_success_modal"
      >
        <template #intro>
          <div
            class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
          >
            <div class="font-semibold text-slate-900">Bảng giờ chuẩn</div>
            <ul class="mt-1 list-disc space-y-1 pl-5 text-slate-600">
              <li>HDGSNN 1-2 điểm: 900 giờ</li>
              <li>HDGSNN &gt;= 1 điểm: 600 giờ</li>
              <li>Có ISSN/ISBN: 300 giờ</li>
            </ul>
          </div>
        </template>

        <template #default="{ readOnly }">
          <!-- A -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              A. Loại bài báo & giờ chuẩn
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Niên học</label
                >
                <select
                  v-model="form.academicYearId"
                  :disabled="readOnly"
                  :class="selectClass(formErrors.academicYearId)"
                >
                  <option :value="null">-- Chọn niên học --</option>
                  <option v-for="y in academicYears" :key="y.id" :value="y.id">
                    {{ y.code }}
                  </option>
                </select>
                <p
                  v-if="formErrors.academicYearId"
                  class="mt-1 text-xs text-rose-600"
                >
                  {{ formErrors.academicYearId }}
                </p>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Loại bài báo</label
                >
                <select
                  v-model="form.typeId"
                  :disabled="readOnly || isTypeLockedByJournal"
                  :class="selectClass(formErrors.typeId)"
                >
                  <option :value="null">-- Chọn loại --</option>
                  <option v-for="t in types" :key="t.id" :value="t.id">
                    {{ t.name }}
                  </option>
                </select>

                <div class="mt-1 text-xs text-slate-500">
                  Giờ chuẩn:
                  <span class="font-semibold text-slate-900">{{
                    baseHoursText
                  }}</span>
                </div>
                <div
                  v-if="isTypeLockedByJournal"
                  class="mt-1 text-xs text-sky-700"
                >
                  Loại bài báo được tự động xác định theo tạp chí đã chọn.
                </div>
                <p v-if="formErrors.typeId" class="mt-1 text-xs text-rose-600">
                  {{ formErrors.typeId }}
                </p>
              </div>
            </div>
          </div>

          <!-- B -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              B. Thông tin bài báo
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Tên bài báo <span class="text-rose-600">*</span></label
                >
                <input
                  v-model.trim="form.title"
                  :disabled="readOnly"
                  maxlength="500"
                  placeholder="VD: Ứng dụng AI trong phân tích dữ liệu giáo dục"
                  :class="inputClass(formErrors.title)"
                />
                <p v-if="formErrors.title" class="mt-1 text-xs text-rose-600">
                  {{ formErrors.title }}
                </p>
              </div>

              <div class="md:col-span-2">
                <JournalSelect
                  v-model="form.journalId"
                  v-model:journalName="form.journalName"
                  v-model:issn="form.issn"
                  :disabled="readOnly"
                  label="Tạp chí / Kỷ yếu"
                  hint="Gợi ý tìm và chọn từ danh mục (do QLKH/Hội đồng nhập)."
                  :search-fn="searchJournals"
                  :error="formErrors.journalName ?? undefined"
                  @select="onJournalSelect"
                  @clear="onJournalClear"
                />
                <p
                  v-if="!isJournalFromCatalog"
                  class="mt-2 text-xs text-amber-700"
                >
                  Lưu ý: Nếu tự nhập tên tạp chí, vui lòng chọn loại bài báo ở
                  trên để hệ thống tính giờ đúng.
                </p>
              </div>
              <div v-if="form.typeId" class="mt-2 text-xs text-slate-600">
                Giờ NCKH lấy theo loại công trình trong cấu hình quy đổi:
                <span class="font-semibold text-slate-900">{{
                  baseHoursText
                }}</span>
              </div>
              <!-- Keywords -->
              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Từ khóa (keywords)</label
                >
                <input
                  v-model.trim="keywords"
                  :disabled="readOnly"
                  maxlength="300"
                  placeholder="VD: AI, giáo dục, dữ liệu"
                  :class="inputClass()"
                />
                <p class="mt-1 text-xs text-slate-500">
                  Nhập các từ khóa, phân tách bằng dấu phẩy.
                </p>

                <div
                  v-if="keywordChips.length"
                  class="mt-2 flex flex-wrap gap-1"
                >
                  <span
                    v-for="k in keywordChips"
                    :key="k"
                    class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700"
                  >
                    {{ k }}
                  </span>
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600">ISSN</label>
                <input
                  v-model.trim="form.issn"
                  :disabled="readOnly"
                  maxlength="50"
                  placeholder="VD: 1234-5678"
                  :class="inputClass()"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600">DOI</label>
                <input
                  v-model.trim="form.doi"
                  :disabled="readOnly"
                  maxlength="100"
                  placeholder="VD: 10.1234/abcd.2025.001"
                  :class="inputClass()"
                />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Link public (URL)</label
                >
                <input
                  v-model.trim="form.articleUrl"
                  :disabled="readOnly"
                  maxlength="500"
                  placeholder="VD: https://journal.example.com/article/123"
                  :class="inputClass()"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Tập (volume)</label
                >
                <input
                  v-model.trim="form.volume"
                  :disabled="readOnly"
                  maxlength="50"
                  placeholder="VD: 12"
                  :class="inputClass()"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Số (issue)</label
                >
                <input
                  v-model.trim="form.issue"
                  :disabled="readOnly"
                  maxlength="50"
                  placeholder="VD: 3"
                  :class="inputClass()"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600">Năm</label>
                <input
                  v-model.number="form.year"
                  type="number"
                  min="1900"
                  max="2100"
                  :disabled="readOnly"
                  placeholder="VD: 2025"
                  :class="inputClass(formErrors.year)"
                />
                <p v-if="formErrors.year" class="mt-1 text-xs text-rose-600">
                  {{ formErrors.year }}
                </p>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Trang (start - end)</label
                >
                <div class="mt-1 grid grid-cols-2 gap-2">
                  <input
                    v-model.number="form.pageStart"
                    type="number"
                    min="1"
                    :disabled="readOnly"
                    placeholder="VD: 15"
                    :class="inputClass()"
                  />
                  <input
                    v-model.number="form.pageEnd"
                    type="number"
                    min="1"
                    :disabled="readOnly"
                    placeholder="VD: 27"
                    :class="inputClass()"
                  />
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Tóm tắt (abstract)</label
                >
                <textarea
                  v-model.trim="form.abstract"
                  :disabled="readOnly"
                  placeholder="Tóm tắt ngắn nội dung bài báo (tuỳ chọn)"
                  class="mt-1 min-h-24 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Ghi chú</label
                >
                <input
                  v-model.trim="form.notes"
                  :disabled="readOnly"
                  maxlength="500"
                  placeholder="VD: Bài báo thuộc danh mục... / ghi chú thêm (tuỳ chọn)"
                  :class="inputClass()"
                />
              </div>
            </div>
          </div>

          <!-- C -->
          <ParticipantsTable
            v-model="form.members"
            :lecturers="lecturers"
            :memberRoles="articleMemberRoles"
            :readOnly="readOnly"
            :currentLecturerId="currentLecturerId"
            :ownerFacultyId="ownerFacultyId"
            :hoursByLecturerId="hoursByLecturerId"
            @request-search="onSearchLecturers"
          />

          <!-- D -->
          <EvidenceUpload
            :existingFiles="existingEvidence"
            :existingLinks="existingEvidenceLinks"
            v-model:pendingFiles="pendingEvidenceFiles"
            v-model:pendingLinks="pendingEvidenceLinks"
            :fileTypes="evidenceFileTypes"
            :readOnly="readOnly"
            :deletingFileId="deletingEvidenceFileId"
            @remove-existing="onRemoveExistingEvidence"
            @remove-existing-link="onRemoveExistingEvidenceLink"
          />

          <!-- E -->
          <HoursSummaryPanel
            :total-hours="hours.total_hours"
            :current-lecturer-hours="hours.current_lecturer_hours"
            :members-distribution="hours.distribution"
            :external-members="externalMembers"
            :note="hoursNote"
          />
        </template>
      </DeclarationFormShell>
    </div>
  </div>

  <ActionResultModal
    :open="missingModalOpen"
    type="warning"
    title="Thiếu thông tin"
    :message="missingModalMessage"
    close-label="Đã hiểu"
    @close="closeMissingModal"
  />
</template>

<script setup lang="ts">
import axios from "axios";
import {
  computed,
  onMounted,
  reactive,
  ref,
  watch,
  type ComputedRef,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import { FileText } from "lucide-vue-next";
import ActionResultModal from "@/shared/components/ActionResultModal.vue";
import JournalSelect from "../../shared/components/JournalSelect.vue";

import DeclarationFormShell from "../../shared/components/DeclarationFormShell.vue";
import ParticipantsTable from "../../shared/components/ParticipantsTable.vue";
import EvidenceUpload from "../../shared/components/EvidenceUpload.vue";
import HoursSummaryPanel from "../../shared/components/HoursSummaryPanel.vue";

import { mapStatusCodeToUi } from "../../shared/contracts/declarationSharedContract";
import type {
  AcademicYearDto,
  ActivityTypeDto,
  LecturerOptionDto,
  MemberRoleDto,
  EvidenceFileTypeDto,
  EvidenceFileDto,
  EvidenceLinkDto,
} from "../../shared/contracts/declarationSharedContract";
import { useDeclarationFormShell } from "../../shared/composables/useDeclarationFormShell";
import { useDeclarationPageLoadFeedback } from "../../shared/composables/useDeclarationPageLoadFeedback";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import { mergeLecturerOptionsFromMembers } from "../../shared/utils/lecturerOptionMerge";
import {
  fetch_academic_years,
  fetch_activity_kinds,
  fetch_activity_types_by_kind,
  fetch_member_roles,
  fetch_evidence_file_types,
  search_lecturer_options,
  search_journals,
} from "../../shared/services/catalogs.service";
import {
  fetch_activity,
  fetch_current_lecturer_option,
  submit_activity,
  upsert_activity_base,
  upsert_members,
  upsert_paper_details,
  list_evidence_files,
  upload_evidence_file,
  delete_evidence_file,
  list_evidence_links,
  add_evidence_link,
  delete_evidence_link,
} from "../../shared/services/declarations.service";
import {
  computeArticleHours,
  type ArticleDeclarationFormModel,
  articleBaseHoursByTypeCode,
  articleAllowedMemberRoleCodes,
} from "../ArticleDeclarationContract";

const route = useRoute();
const router = useRouter();

const academicYears = ref<AcademicYearDto[]>([]);
const memberRoles = ref<MemberRoleDto[]>([]);
const evidenceFileTypes = ref<EvidenceFileTypeDto[]>([]);
const lecturers = ref<LecturerOptionDto[]>([]);
const types = ref<ActivityTypeDto[]>([]);
const kindId = ref<number>(0);

const currentLecturerId = ref<number>(0);
const selectedJournalClassification = ref<string | null>(null);
const selectedJournalResearchHours = ref<number | null>(null);

// Keywords (UI + persist)
const keywords = ref("");
const keywordChips = computed(() =>
  keywords.value
    .split(",")
    .map((x) => x.trim())
    .filter(Boolean),
);

const form = reactive<ArticleDeclarationFormModel>({
  activityId: null,
  academicYearId: null,
  kindId: 0,
  typeId: null,
  title: "",
  abstract: "",
  notes: "",
  journalId: null,
  journalName: "",
  journalResearchHours: null,
  issn: "",
  doi: "",
  articleUrl: "",
  volume: "",
  issue: "",
  year: null,
  pageStart: null,
  pageEnd: null,
  members: [],
});

const formErrors = reactive<{
  academicYearId: string | null;
  typeId: string | null;
  title: string | null;
  journalName: string | null;
  year: string | null;
}>({
  academicYearId: null,
  typeId: null,
  title: null,
  journalName: null,
  year: null,
});

const missingFields = ref<string[]>([]);
const missingModalOpen = ref(false);
const missingModalMessage = computed(() => {
  if (missingFields.value.length === 0) {
    return "Vui lòng bổ sung các trường bắt buộc.";
  }
  return `Vui lòng bổ sung các trường bắt buộc sau:\n• ${missingFields.value.join("\n• ")}`;
});
const submitNotice = ref<string | null>(null);
const submitNoticeTone = ref<"info" | "success">("info");

const filteredMemberRoles = computed(() => {
  const preferredCodes = new Set<string>(
    articleAllowedMemberRoleCodes as readonly string[],
  );
  const preferred = memberRoles.value.filter((r) => preferredCodes.has(r.code));
  if (preferred.length > 0) return preferred;

  const legacyCodes = new Set(["principal", "member"]);
  const legacy = memberRoles.value.filter((r) => legacyCodes.has(r.code));
  return legacy.length > 0 ? legacy : memberRoles.value;
});
const articleMemberRoles = computed(() =>
  filteredMemberRoles.value.map((r) => {
    if (r.code === "corresponding_author" || r.code === "principal") {
      return { ...r, name: "Tác giả chính" };
    }
    if (r.code === "coauthor" || r.code === "member") {
      return { ...r, name: "Đồng tác giả" };
    }
    return r;
  }),
);
const existingEvidence = ref<EvidenceFileDto[]>([]);
const existingEvidenceLinks = ref<EvidenceLinkDto[]>([]);
const pendingEvidenceFiles = ref<any[]>([]);
const pendingEvidenceLinks = ref<any[]>([]);
const deletingEvidenceFileId = ref<number | null>(null);

const typeCodeById = computed(() =>
  Object.fromEntries(types.value.map((t) => [t.id, t.code])),
);
const typeHoursById = computed(() =>
  Object.fromEntries(
    types.value.map((t) => [
      t.id,
      typeof t.research_hours === "number" ? t.research_hours : 0,
    ]),
  ),
);
const typeIdByCode = computed(() =>
  Object.fromEntries(types.value.map((t) => [t.code, t.id])),
);
const lecturerNameById = computed(() =>
  Object.fromEntries(lecturers.value.map((l) => [l.id, l.full_name])),
);
const ownerFacultyId = computed(
  () =>
    lecturers.value.find((l) => l.id === currentLecturerId.value)?.faculty_id ??
    null,
);

const hours = computed(() =>
  computeArticleHours(form, {
    typeCodeById: typeCodeById.value,
    typeHoursById: typeHoursById.value,
    lecturerNameById: lecturerNameById.value,
    memberRoleNameById: memberRoleNameById.value,
    currentLecturerId: currentLecturerId.value,
  }),
);

const hoursByLecturerId = computed(() => {
  const map: Record<number, number> = {};
  for (const d of hours.value.distribution) map[d.lecturer_id] = d.hours;
  return map;
});

const baseHoursText = computed(() => {
  const byTypeConfig =
    form.typeId && Number.isFinite(typeHoursById.value[form.typeId])
      ? Number(typeHoursById.value[form.typeId])
      : 0;
  if (byTypeConfig > 0) return `${byTypeConfig} giờ`;

  const code = form.typeId ? typeCodeById.value[form.typeId] : null;
  const byType = code ? (articleBaseHoursByTypeCode[code] ?? 0) : 0;
  return `${byType} giờ`;
});

const isJournalFromCatalog = computed(() => typeof form.journalId === "number");

const mappedTypeCodeFromJournal = computed(() => {
  const normalizedClassification = String(
    selectedJournalClassification.value ?? "",
  )
    .trim()
    .toUpperCase();

  const typeCodeByClassification: Record<string, string> = {
    HDGSNN_GE_2: "hdgsnn_900",
    POINT_GE_2: "hdgsnn_900",
    HDGSNN_GE_1: "hdgsnn_600",
    POINT_GE_1: "hdgsnn_600",
    ISSN_ISBN: "hdgsnn_300",
  };

  const mappedFromClassification =
    typeCodeByClassification[normalizedClassification];
  if (mappedFromClassification) return mappedFromClassification;

  const hours = selectedJournalResearchHours.value;
  if (typeof hours === "number") {
    if (hours >= 900) return "hdgsnn_900";
    if (hours >= 600) return "hdgsnn_600";
    if (hours > 0) return "hdgsnn_300";
  }

  return null;
});

const mappedTypeIdFromJournal = computed(() => {
  const mappedCode = mappedTypeCodeFromJournal.value;
  if (!mappedCode) return null;
  const mappedId = typeIdByCode.value[mappedCode];
  return typeof mappedId === "number" ? mappedId : null;
});

const isTypeLockedByJournal = computed(
  () => isJournalFromCatalog.value && mappedTypeIdFromJournal.value !== null,
);

const hoursNote = computed(() => {
  if (!form.typeId) return "Chọn loại bài báo để xác định giờ chuẩn.";
  if (hours.value.distribution.length === 0)
    return "Cần chọn danh sách tác giả để chia đều giờ.";
  return null;
});

const canSubmit = computed(() => {
  if (!form.academicYearId) return false;
  if (!form.typeId) return false;
  if (!form.title.trim()) return false;

  const validMembers = form.members.filter((m) => {
    const hasRole = typeof m.member_role_id === "number";
    if (!hasRole) return false;
    if (m.is_external) return Boolean(m.external_full_name?.trim());
    return typeof m.lecturer_id === "number";
  });
  if (validMembers.length === 0) return false;

  const invalidPending = pendingEvidenceFiles.value.some(
    (p: any) => !p.file_type_id,
  );
  if (invalidPending) return false;

  const invalidPendingLinks = pendingEvidenceLinks.value.some(
    (l: any) => !String(l?.url ?? "").trim() || !l?.file_type_id,
  );
  if (invalidPendingLinks) return false;

  return true;
});

function inputClass(error?: string | null) {
  return [
    "mt-1 w-full rounded-xl border bg-white px-3 py-2 text-sm focus:outline-none disabled:opacity-60",
    error
      ? "border-rose-300 focus:border-rose-400"
      : "border-slate-200 focus:border-slate-300",
  ];
}

function selectClass(error?: string | null) {
  return [
    "mt-1 w-full rounded-xl border bg-white px-3 py-2 text-sm focus:outline-none disabled:opacity-60",
    error
      ? "border-rose-300 focus:border-rose-400"
      : "border-slate-200 focus:border-slate-300",
  ];
}

function clearFieldError(field: keyof typeof formErrors) {
  if (formErrors[field]) formErrors[field] = null;
}

function resetErrors() {
  formErrors.academicYearId = null;
  formErrors.typeId = null;
  formErrors.title = null;
  formErrors.journalName = null;
  formErrors.year = null;
}

function applyValidationErrors(err: unknown): string | null {
  if (!axios.isAxiosError(err)) return null;
  if (err.response?.status !== 422) return null;

  const payload = err.response?.data;
  const errors = payload?.errors as Record<string, string[]> | undefined;
  if (!errors) return "Vui lòng kiểm tra các trường bắt buộc.";

  formErrors.academicYearId = errors.academic_year_id?.[0] ?? null;
  formErrors.typeId = errors.type_id?.[0] ?? null;
  formErrors.title = errors.title?.[0] ?? null;
  formErrors.journalName = errors.journal_name?.[0] ?? null;
  formErrors.year = errors.year?.[0] ?? null;

  return "Vui lòng kiểm tra các trường bắt buộc.";
}

function normalizeErrorMessage(err: unknown, fallback: string): string {
  if (axios.isAxiosError(err)) {
    const message = err.response?.data?.message;
    if (typeof message === "string" && message.trim()) return message;
  }
  return err instanceof Error ? err.message : fallback;
}

async function loadCatalogs() {
  const [currentLecturerOption, years, kinds, roles, fileTypes] =
    await Promise.all([
      fetch_current_lecturer_option(),
      fetch_academic_years(),
      fetch_activity_kinds(),
      fetch_member_roles(),
      fetch_evidence_file_types("paper"),
    ]);

  currentLecturerId.value = currentLecturerOption?.id ?? 0;
  lecturers.value = currentLecturerOption ? [currentLecturerOption] : [];

  academicYears.value = years;
  memberRoles.value = roles;
  evidenceFileTypes.value = fileTypes;

  kindId.value = kinds.find((k) => k.code === "paper")?.id ?? 0;
  form.kindId = kindId.value;

  types.value = await fetch_activity_types_by_kind(kindId.value);

  if (form.members.length === 0 && currentLecturerId.value) {
    const authorRole =
      roles.find((r) => r.code === "corresponding_author") ??
      roles.find((r) => r.code === "member");
    form.members.push({
      lecturer_id: currentLecturerId.value,
      member_role_id: authorRole?.id ?? null,
      member_role_code: authorRole?.code ?? null,
    });
  }
}

async function loadDraftFromQuery() {
  const raw = route.query.activity_id;
  const rawValue = Array.isArray(raw) ? raw[0] : raw;
  const activityId = rawValue ? Number(rawValue) : null;

  if (!activityId || Number.isNaN(activityId)) return;

  try {
    const data = await fetch_activity(activityId);
    const activity = data.activity;
    if (!activity) return;

    form.activityId = activity.id;
    form.academicYearId = activity.academic_year_id ?? null;
    form.kindId = activity.kind_id ?? form.kindId;
    form.typeId = activity.type_id ?? null;
    form.title = activity.title ?? "";
    form.abstract = activity.abstract ?? "";
    form.notes = activity.notes ?? "";

    if (data.detail_kind === "paper_details" && data.detail) {
      const detail = data.detail as any;
      form.journalName = detail.journal_name ?? "";
      form.issn = detail.issn ?? "";
      form.doi = detail.doi ?? "";
      form.articleUrl = detail.article_url ?? "";
      form.volume = detail.volume ?? "";
      form.issue = detail.issue ?? "";
      form.year = detail.year ?? null;
      form.pageStart = detail.page_start ?? null;
      form.pageEnd = detail.page_end ?? null;
      keywords.value = detail.keywords ?? "";
    }

    form.members = (data.members ?? []).map((m) => ({
      lecturer_id: m.lecturer_id ?? null,
      member_role_id: m.member_role_id,
      member_role_code: m.member_role_code ?? null,
      is_external: !!m.is_external,
      external_full_name: m.external_full_name ?? null,
      external_department_name: m.external_department_name ?? null,
    }));
    lecturers.value = mergeLecturerOptionsFromMembers(
      lecturers.value,
      data.members,
    );

    existingEvidence.value = data.evidence_files ?? [];
    existingEvidenceLinks.value =
      data.evidence_links ??
      (activity.id ? await list_evidence_links(activity.id) : []);
    pendingEvidenceFiles.value = [];
    pendingEvidenceLinks.value = [];

    const statusCode = (activity.status_code ?? "draft") as any;
    shell.status.value = mapStatusCodeToUi(statusCode) ?? "DRAFT";
  } catch (err) {
    shell.error_message.value = normalizeErrorMessage(
      err,
      "Không thể tải bản nháp. Dữ liệu đang nhập tạm thời vẫn được giữ lại.",
    );
  }
}

async function onSearchLecturers(q: string) {
  lecturers.value = await search_lecturer_options(q);
}

function onJournalSelect(option: {
  id: number;
  name: string;
  issn: string | null;
  classification?: string | null;
  researchHours?: number;
}) {
  form.journalId = option.id;
  form.journalName = option.name;
  form.issn = option.issn ?? "";
  form.journalResearchHours =
    typeof option.researchHours === "number" ? option.researchHours : null;

  selectedJournalClassification.value = option.classification ?? null;
  selectedJournalResearchHours.value =
    typeof option.researchHours === "number" ? option.researchHours : null;

  if (mappedTypeIdFromJournal.value !== null) {
    form.typeId = mappedTypeIdFromJournal.value;
  }

  clearFieldError("journalName");
}

function onJournalClear() {
  selectedJournalClassification.value = null;
  selectedJournalResearchHours.value = null;
  form.journalResearchHours = null;
}

const { runWithFeedback } = useActionFeedback();

async function onRemoveExistingEvidence(_id: number) {
  if (!form.activityId) {
    existingEvidence.value = existingEvidence.value.filter((x) => x.id !== _id);
    return;
  }

  if (deletingEvidenceFileId.value === _id) return;
  deletingEvidenceFileId.value = _id;

  try {
    await runWithFeedback(
      () => delete_evidence_file(form.activityId as number, _id),
      {
        loading: {
          enabled: true,
          title: "Đang xoá minh chứng",
          message: "Vui lòng đợi trong giây lát...",
          delayMs: 450,
          minShowMs: 250,
        },
        success: {
          enabled: true,
          title: "Thành công",
          message: "Đã xoá file minh chứng.",
        },
        error: {
          enabled: true,
          title: "Không thể xoá",
          message: "Không thể xoá file minh chứng. Vui lòng thử lại.",
        },
        rethrow: true,
      },
    );
    existingEvidence.value = existingEvidence.value.filter((x) => x.id !== _id);
  } catch (err) {
    shell.error_message.value = normalizeErrorMessage(
      err,
      "Không thể xóa file minh chứng.",
    );
  } finally {
    deletingEvidenceFileId.value = null;
  }
}

async function onRemoveExistingEvidenceLink(linkId: number) {
  if (!form.activityId) {
    existingEvidenceLinks.value = existingEvidenceLinks.value.filter(
      (x) => x.id !== linkId,
    );
    return;
  }

  try {
    await delete_evidence_link(form.activityId, linkId);
    existingEvidenceLinks.value = existingEvidenceLinks.value.filter(
      (x) => x.id !== linkId,
    );
  } catch (err) {
    shell.error_message.value = normalizeErrorMessage(
      err,
      "Không thể xóa link minh chứng.",
    );
  }
}

async function persistEvidenceDraft(activityId: number) {
  const failedFiles: any[] = [];
  const failedLinks: any[] = [];
  let firstError: string | null = null;

  const validFilePendings = pendingEvidenceFiles.value.filter(
    (pending) => pending?.file && pending?.file_type_id,
  );
  const invalidFilePendings = pendingEvidenceFiles.value.filter(
    (pending) => !pending?.file || !pending?.file_type_id,
  );
  failedFiles.push(...invalidFilePendings);

  const fileResults = await Promise.allSettled(
    validFilePendings.map((pending) =>
      upload_evidence_file(activityId, {
        file: pending.file,
        file_type_id: Number(pending.file_type_id),
      }),
    ),
  );

  fileResults.forEach((result, index) => {
    if (result.status === "fulfilled") return;
    failedFiles.push(validFilePendings[index]);
    if (!firstError) {
      firstError = normalizeErrorMessage(
        result.reason,
        "Không thể tải tệp minh chứng lên hệ thống.",
      );
    }
  });

  const validLinkPendings = pendingEvidenceLinks.value.filter(
    (pendingLink) =>
      String(pendingLink?.url ?? "").trim() !== "" &&
      Number(pendingLink?.file_type_id) > 0,
  );
  const linkResults = await Promise.allSettled(
    validLinkPendings.map((pendingLink) =>
      add_evidence_link(activityId, {
        url: String(pendingLink.url).trim(),
        file_type_id: Number(pendingLink.file_type_id),
      }),
    ),
  );

  linkResults.forEach((result, index) => {
    if (result.status === "fulfilled") return;
    failedLinks.push(validLinkPendings[index]);
    if (!firstError) {
      firstError = normalizeErrorMessage(
        result.reason,
        "Không thể lưu link minh chứng.",
      );
    }
  });

  const [savedFiles, savedLinks] = await Promise.all([
    list_evidence_files(activityId),
    list_evidence_links(activityId),
  ]);
  existingEvidence.value = savedFiles;
  existingEvidenceLinks.value = savedLinks;
  pendingEvidenceFiles.value = failedFiles;
  pendingEvidenceLinks.value = failedLinks;

  if (firstError) {
    throw new Error(firstError);
  }
}

function openMissingModal(fields: string[]) {
  missingFields.value = fields;
  missingModalOpen.value = true;
}

function closeMissingModal() {
  missingModalOpen.value = false;
}

function collectMissingFields(mode: "draft" | "submit") {
  const missing: string[] = [];

  if (!form.academicYearId) {
    missing.push("Niên học");
    formErrors.academicYearId = "Vui lòng chọn niên học.";
  }

  if (!form.title.trim()) {
    missing.push("Tên bài báo");
    formErrors.title = "Vui lòng nhập tên bài báo.";
  }

  if (mode === "submit") {
    if (!form.typeId) {
      missing.push("Loại bài báo");
      formErrors.typeId = "Vui lòng chọn loại bài báo.";
    }
    if (!form.journalName.trim()) {
      missing.push("Tạp chí / Kỷ yếu");
      formErrors.journalName = "Vui lòng chọn tạp chí/kỷ yếu.";
    }
    if (!form.year) {
      missing.push("Năm xuất bản");
      formErrors.year = "Vui lòng nhập năm xuất bản.";
    }

    const validMembers = form.members.filter(
      (m) =>
        typeof m.lecturer_id === "number" &&
        typeof m.member_role_id === "number",
    );
    if (validMembers.length === 0) {
      missing.push("Danh sách người tham gia");
    }
  }

  return missing;
}

function handleSaveDraft() {
  resetErrors();
  submitNotice.value = null;
  const missing = collectMissingFields("draft");
  if (missing.length > 0) {
    openMissingModal(missing);
    return;
  }
  shell.save_draft();
}

function handleSubmit() {
  resetErrors();
  submitNotice.value = null;
  const missing = collectMissingFields("submit");
  if (missing.length > 0) {
    openMissingModal(missing);
    return;
  }
  shell.submit_for_approval();
}

const shell = useDeclarationFormShell({
  initial_status: "DRAFT",
  on_save_draft: async () => {
    try {
      const saved = await upsert_activity_base({
        id: form.activityId ?? undefined,
        kind_id: form.kindId,
        type_id: form.typeId,
        academic_year_id: form.academicYearId ?? undefined,
        title: form.title,
        abstract: form.abstract || null,
        start_date: null,
        end_date: null,
        quantity: 1,
        notes: form.notes || null,
      });

      form.activityId = saved.id;

      if (saved.id) {
        await router.replace({
          query: { ...route.query, activity_id: String(saved.id) },
        });
      }

      await upsert_paper_details({
        activity_id: saved.id,
        journal_name: form.journalName || null,
        issn: form.issn || null,
        doi: form.doi || null,
        article_url: form.articleUrl || null,
        volume: form.volume || null,
        issue: form.issue || null,
        page_start: form.pageStart ?? null,
        page_end: form.pageEnd ?? null,
        year: form.year ?? null,
        keywords: keywords.value.trim() ? keywords.value.trim() : null,
      } as any);

      const upsertList = form.members
        .filter((m) => {
          if (typeof m.member_role_id !== "number") return false;
          if (m.is_external) return Boolean(m.external_full_name?.trim());
          return typeof m.lecturer_id === "number";
        })
        .map((m) => ({
          lecturer_id: m.is_external ? null : (m.lecturer_id as number),
          member_role_id: m.member_role_id as number,
          contribution_share: null,
          is_external: m.is_external ?? false,
          external_full_name: m.external_full_name ?? null,
          external_department_name: m.external_department_name ?? null,
        }));
      await upsert_members(saved.id, upsertList);

      await persistEvidenceDraft(saved.id);
    } catch (err) {
      const validation = applyValidationErrors(err);
      if (validation) throw new Error(validation);

      throw new Error(
        normalizeErrorMessage(err, "Không thể lưu bản nháp. Vui lòng thử lại."),
      );
    }
  },
  on_submit: async () => {
    resetErrors();
    try {
      if (!form.activityId) {
        await shell.save_draft({ silent_success: true });
      }
      if (!form.activityId) return;
      const submitResponse = await submit_activity(form.activityId);
      const nextStatusCode =
        submitResponse?.workflow?.status_code ??
        submitResponse?.data?.status_code ??
        "pending_faculty_review";

      if (nextStatusCode === "pending_member_confirm") {
        submitNoticeTone.value = "info";
        submitNotice.value =
          "Đã gửi lời mời tới thành viên, hệ thống đang chờ xác nhận trước khi chuyển Khoa duyệt.";
      } else if (nextStatusCode === "pending_faculty_review") {
        submitNoticeTone.value = "success";
        submitNotice.value = "Đã gửi công trình lên Khoa duyệt.";
      } else {
        submitNotice.value = null;
      }

      return (
        mapStatusCodeToUi(nextStatusCode as any) ?? "PENDING_FACULTY_REVIEW"
      );
    } catch (err) {
      const validation = applyValidationErrors(err);
      if (validation) throw new Error(validation);

      throw new Error(
        normalizeErrorMessage(err, "Không thể gửi duyệt. Vui lòng thử lại."),
      );
    }
  },
});

const { runPageLoad } = useDeclarationPageLoadFeedback();

const memberRoleNameById: ComputedRef<Record<number, string>> = computed(() => {
  const map: Record<number, string> = {};
  for (const r of memberRoles.value) map[r.id] = r.name;
  return map;
});

const externalMembers = computed(() => {
  return form.members.flatMap((r) => {
    if (!r.is_external) return [];

    const fullName = r.external_full_name?.trim();
    const roleId = r.member_role_id;

    if (!fullName) return [];
    if (typeof roleId !== "number") return [];

    return [
      {
        full_name: fullName,
        organization: r.external_department_name ?? null,
        member_role_name: memberRoleNameById.value[roleId] ?? "-",
      },
    ];
  });
});

watch(
  () => form.academicYearId,
  () => clearFieldError("academicYearId"),
);
watch(
  () => form.typeId,
  () => clearFieldError("typeId"),
);
watch(
  () => form.title,
  () => clearFieldError("title"),
);
watch(
  () => form.journalName,
  () => {
    clearFieldError("journalName");
    if (form.journalId === null) {
      selectedJournalClassification.value = null;
      selectedJournalResearchHours.value = null;
      form.journalResearchHours = null;
    }
  },
);

watch(
  () => mappedTypeIdFromJournal.value,
  (nextTypeId) => {
    if (isJournalFromCatalog.value && nextTypeId !== null) {
      form.typeId = nextTypeId;
    }
  },
);
watch(
  () => form.year,
  () => clearFieldError("year"),
);

onMounted(async () => {
  await runPageLoad(
    async () => {
      await loadCatalogs();
      await loadDraftFromQuery();
    },
    {
      onError: (message) => {
        shell.error_message.value = message;
      },
      fallbackMessage: "Không thể khởi tạo trang kê khai.",
    },
  );
});

// Fix TS2322: normalize address null -> ""
async function searchJournals(q: string) {
  type JournalRow = Awaited<ReturnType<typeof search_journals>>[number];
  type JournalSelectOptionDto = JournalRow & { address: string };

  const rows = await search_journals(q);
  return rows.map((r) => ({
    ...r,
    address: (r as any).address ?? "",
  })) as JournalSelectOptionDto[];
}
</script>
