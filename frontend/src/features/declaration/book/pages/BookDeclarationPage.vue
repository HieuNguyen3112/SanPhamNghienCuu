<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <DeclarationFormShell
        title="Kê khai Giáo trình / Tài liệu tham khảo"
        description="Giờ tính theo loại tài liệu; Chủ biên nhận 1/5, 4/5 chia đều cho tất cả người tham gia."
        :icon="BookOpen"
        :status="shell.status.value"
        :canSubmit="canSubmit"
        :pending="shell.pending.value"
        :errorMessage="shell.error_message.value"
        :successVisible="shell.success_visible.value"
        :successMessage="shell.success_message.value"
        @save-draft="shell.save_draft"
        @submit="shell.submit_for_approval"
        @close-success="shell.close_success_modal"
      >
        <template #intro>
          <div
            class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
          >
            <div class="font-semibold text-slate-900">Rule phân bổ</div>
            <div class="mt-1 text-slate-600">
              Chủ biên nhận <span class="font-semibold">1/5</span> tổng giờ;
              phần còn lại <span class="font-semibold">4/5</span> chia đều cho
              toàn bộ người tham gia (kể cả chủ biên).
            </div>
          </div>
        </template>

        <template #default="{ readOnly }">
          <!-- A -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              A. Loại tài liệu & giờ chuẩn
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Niên học</label
                >
                <select
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  v-model.number="form.academicYearId"
                  :disabled="readOnly"
                >
                  <option :value="null">— Chọn niên học —</option>
                  <option v-for="y in academicYears" :key="y.id" :value="y.id">
                    {{ y.code }}
                  </option>
                </select>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Loại tài liệu</label
                >
                <select
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  v-model.number="form.typeId"
                  :disabled="readOnly"
                >
                  <option :value="null">— Chọn loại —</option>
                  <option v-for="t in types" :key="t.id" :value="t.id">
                    {{ t.name }}
                  </option>
                </select>
                <div class="mt-1 text-xs text-slate-500">
                  {{ baseHoursText }}
                </div>
              </div>
            </div>
          </div>

          <!-- B -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              B. Thông tin tài liệu
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Tên tài liệu <span class="text-rose-600">*</span></label
                >
                <input
                  v-model.trim="form.title"
                  :disabled="readOnly"
                  maxlength="500"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Nhà xuất bản <span class="text-rose-600">*</span></label
                >
                <input
                  v-model.trim="form.publisher"
                  :disabled="readOnly"
                  maxlength="255"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="book_details.publisher (required)"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Năm xuất bản</label
                >
                <input
                  v-model.number="form.year"
                  type="number"
                  min="1900"
                  max="2100"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600">ISBN</label>
                <input
                  v-model.trim="form.isbn"
                  :disabled="readOnly"
                  maxlength="50"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Số trang</label
                >
                <input
                  v-model.number="form.pages"
                  type="number"
                  min="1"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Số QĐ phê duyệt</label
                >
                <input
                  v-model.trim="form.approvalDecisionNo"
                  :disabled="readOnly"
                  maxlength="100"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Ngày QĐ phê duyệt</label
                >
                <input
                  v-model="form.approvalDecisionDate"
                  type="date"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>
            </div>
          </div>

          <!-- C -->
          <div class="space-y-3">
            <ParticipantsTable
              v-model="form.members"
              :lecturers="lecturers"
              :memberRoles="filteredMemberRoles"
              :readOnly="readOnly"
              :currentLecturerId="currentLecturerId"
              :ownerFacultyId="ownerFacultyId"
              :hoursByLecturerId="hoursByLecturerId"
              @request-search="onSearchLecturers"
            />

            <div
              v-if="chiefEditorWarning"
              class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
            >
              Cần có ít nhất <span class="font-semibold">1 Chủ biên</span> để
              đúng nghiệp vụ (member_roles.code =
              <span class="font-mono">chief_editor</span>).
            </div>
          </div>

          <!-- D -->
          <EvidenceUpload
            :existingFiles="existingEvidence"
            v-model:pendingFiles="pendingEvidenceFiles"
            v-model:pendingLinks="pendingEvidenceLinks"
            :fileTypes="evidenceFileTypes"
            :readOnly="readOnly"
            @remove-existing="onRemoveExistingEvidence"
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
</template>

<script setup lang="ts">
import axios from "axios";
import { computed, onMounted, reactive, ref, type ComputedRef } from "vue";
import { useRoute, useRouter } from "vue-router";
import { BookOpen } from "lucide-vue-next";

import DeclarationFormShell from "../../shared/components/DeclarationFormShell.vue";
import ParticipantsTable from "../../shared/components/ParticipantsTable.vue";
import EvidenceUpload from "../../shared/components/EvidenceUpload.vue";
import HoursSummaryPanel from "../../shared/components/HoursSummaryPanel.vue";

import type {
  AcademicYearDto,
  ActivityTypeDto,
  LecturerOptionDto,
  MemberRoleDto,
  EvidenceFileTypeDto,
  EvidenceFileDto,
} from "../../shared/contracts/declarationSharedContract";
import { mapStatusCodeToUi } from "../../shared/contracts/declarationSharedContract";
import { useDeclarationFormShell } from "../../shared/composables/useDeclarationFormShell";
import {
  fetch_academic_years,
  fetch_activity_kinds,
  fetch_activity_types_by_kind,
  fetch_member_roles,
  fetch_evidence_file_types,
  search_lecturer_options,
} from "../../shared/services/catalogs.service";
import {
  fetch_activity,
  fetch_current_lecturer_id,
  submit_activity,
  upsert_activity_base,
  upsert_members,
  upsert_book_details,
  list_evidence_files,
} from "../../shared/services/declarations.service";
import {
  computeBookHours,
  type BookDeclarationFormModel,
  bookBaseHoursByTypeCode,
  bookAllowedMemberRoleCodes,
} from "../BookDeclarationContract";

const route = useRoute();
const router = useRouter();

// const academicYears = ref(await Promise.resolve([] as any[]));
const academicYears = ref<AcademicYearDto[]>([]);
const memberRoles = ref<MemberRoleDto[]>([]);
const evidenceFileTypes = ref<EvidenceFileTypeDto[]>([]);
const lecturers = ref<LecturerOptionDto[]>([]);
const types = ref<ActivityTypeDto[]>([]);
const kindId = ref<number>(0);

const currentLecturerId = ref<number>(0);

const form = reactive<BookDeclarationFormModel>({
  activityId: null,
  academicYearId: null,
  kindId: 0,
  typeId: null,
  title: "",
  notes: "",
  publisher: "",
  year: null,
  isbn: "",
  pages: null,
  approvalDecisionNo: "",
  approvalDecisionDate: null,
  members: [],
});

const existingEvidence = ref<EvidenceFileDto[]>([]);
const pendingEvidenceFiles = ref<any[]>([]);
const pendingEvidenceLinks = ref<any[]>([]);

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
const lecturerNameById = computed(() =>
  Object.fromEntries(lecturers.value.map((l) => [l.id, l.full_name])),
);
const ownerFacultyId = computed(
  () =>
    lecturers.value.find((l) => l.id === currentLecturerId.value)?.faculty_id ??
    null,
);

const memberRoleCodeById = computed(() =>
  Object.fromEntries(memberRoles.value.map((r) => [r.id, r.code])),
);
const filteredMemberRoles = computed(() => {
  const allowed = new Set<string>(
    bookAllowedMemberRoleCodes as readonly string[],
  );
  const list = memberRoles.value.filter((r) => allowed.has(r.code));
  return list.length > 0 ? list : memberRoles.value; // fallback nếu backend chưa seed đủ
});
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
        member_role_name: memberRoleNameById.value[roleId] ?? "—",
      },
    ];
  });
});
const hours = computed(() =>
  computeBookHours(form, {
    typeCodeById: typeCodeById.value,
    typeHoursById: typeHoursById.value,
    lecturerNameById: lecturerNameById.value,
    memberRoleNameById: memberRoleNameById.value,
    memberRoleCodeById: memberRoleCodeById.value,
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
  if (byTypeConfig > 0) {
    return `Giờ chuẩn: ${byTypeConfig} giờ (theo quy đổi công trình)`;
  }

  const code = form.typeId ? typeCodeById.value[form.typeId] : null;
  const base = code ? (bookBaseHoursByTypeCode[code] ?? 0) : 0;
  return `Giờ chuẩn: ${base} giờ`;
});

const chiefEditorWarning = computed(() => {
  const chiefId = memberRoles.value.find((r) => r.code === "chief_editor")?.id;
  if (!chiefId) return true; // P1: backend chưa seed role
  return !form.members.some((m) => m.member_role_id === chiefId);
});

const hoursNote = computed(() => {
  if (!form.typeId) return "Chọn loại tài liệu để xác định giờ chuẩn.";
  if (hours.value.distribution.length === 0)
    return "Cần danh sách người tham gia để phân bổ giờ.";
  return null;
});

const canSubmit = computed(() => {
  if (!form.academicYearId) return false;
  if (!form.typeId) return false;
  if (!form.title.trim()) return false;
  if (!form.publisher.trim()) return false; // book_details.publisher required
  const validMembers = form.members.filter(
    (m) =>
      typeof m.lecturer_id === "number" && typeof m.member_role_id === "number",
  );
  if (validMembers.length === 0) return false;
  if (chiefEditorWarning.value) return false;
  const invalidPending = pendingEvidenceFiles.value.some(
    (p: any) => !p.file_type_id,
  );
  if (invalidPending) return false;
  return true;
});

async function loadCatalogs() {
  currentLecturerId.value = (await fetch_current_lecturer_id()) ?? 0;
  const [years, kinds, roles, fileTypes] = await Promise.all([
    fetch_academic_years(),
    fetch_activity_kinds(),
    fetch_member_roles(),
    fetch_evidence_file_types(),
  ]);
  academicYears.value = years;
  memberRoles.value = roles;
  evidenceFileTypes.value = fileTypes;

  kindId.value = kinds.find((k) => k.code === "book")?.id ?? 0;
  form.kindId = kindId.value;

  types.value = await fetch_activity_types_by_kind(kindId.value);
  lecturers.value = await search_lecturer_options("");

  if (form.members.length === 0 && currentLecturerId.value) {
    const chiefRole = roles.find((r) => r.code === "chief_editor");
    form.members.push({
      lecturer_id: currentLecturerId.value,
      member_role_id: chiefRole?.id ?? null,
      member_role_code: chiefRole?.code ?? null,
    });
  }
}

async function onSearchLecturers(q: string) {
  lecturers.value = await search_lecturer_options(q);
}

async function onRemoveExistingEvidence(_id: number) {
  existingEvidence.value = existingEvidence.value.filter((x) => x.id !== _id);
}

async function loadDraftFromQuery() {
  const raw = route.query.activity_id;
  const rawValue = Array.isArray(raw) ? raw[0] : raw;
  const activityId = rawValue ? Number(rawValue) : null;

  if (!activityId || Number.isNaN(activityId)) return;

  const data = await fetch_activity(activityId);
  const activity = data.activity;
  if (!activity) return;

  form.activityId = activity.id;
  form.academicYearId = activity.academic_year_id ?? null;
  form.kindId = activity.kind_id ?? form.kindId;
  form.typeId = activity.type_id ?? null;
  form.title = activity.title ?? "";
  form.notes = activity.notes ?? "";

  if (data.detail_kind === "book_details" && data.detail) {
    const detail = data.detail as any;
    form.publisher = detail.publisher ?? "";
    form.approvalDecisionNo = detail.approval_decision_no ?? "";
    form.approvalDecisionDate = detail.approval_decision_date ?? null;
    form.isbn = detail.isbn ?? "";
    form.pages = detail.pages ?? null;
    form.year = detail.year ?? null;
  }

  form.members = (data.members ?? []).map((member) => ({
    lecturer_id: member.lecturer_id,
    member_role_id: member.member_role_id,
    member_role_code: member.member_role_code ?? null,
  }));

  existingEvidence.value = data.evidence_files ?? [];

  const statusCode = (activity.status_code ?? "draft") as any;
  shell.status.value = mapStatusCodeToUi(statusCode) ?? "DRAFT";
}

const shell = useDeclarationFormShell({
  initial_status: "DRAFT",
  on_save_draft: async () => {
    const saved = await upsert_activity_base({
      id: form.activityId ?? undefined,
      owner_lecturer_id: currentLecturerId.value,
      kind_id: form.kindId,
      type_id: form.typeId,
      academic_year_id: form.academicYearId ?? 0,
      status_id: 100,
      title: form.title,
      abstract: null,
      start_date: null,
      end_date: null,
      quantity: 1,
      notes: form.notes || null,
      submitted_at: null,
      approved_at: null,
      total_hours_calc: null,
    } as any);

    form.activityId = saved.id;
    await router.replace({
      query: { ...route.query, activity_id: String(saved.id) },
    });

    await upsert_book_details({
      activity_id: saved.id,
      publisher: form.publisher,
      approval_decision_no: form.approvalDecisionNo || null,
      approval_decision_date: form.approvalDecisionDate || null,
      isbn: form.isbn || null,
      pages: form.pages ?? null,
      year: form.year ?? null,
    });

    const upsertList = form.members
      .filter(
        (m) =>
          typeof m.lecturer_id === "number" &&
          typeof m.member_role_id === "number",
      )
      .map((m) => ({
        lecturer_id: m.lecturer_id as number,
        member_role_id: m.member_role_id as number,
        contribution_share: null,
      }));
    await upsert_members(saved.id, upsertList);

    existingEvidence.value = await list_evidence_files(saved.id);

    // Minh chứng chính thức được nộp ở bước duyệt giờ NCKH.
    // Không chặn lưu nháp kê khai nếu người dùng đã chọn file/link tại màn này.
    pendingEvidenceFiles.value = [];
    pendingEvidenceLinks.value = [];
  },
  on_submit: async () => {
    try {
      if (!form.activityId) {
        await shell.save_draft({ silent_success: true });
      }
      if (!form.activityId) return;
      const submitResult = await submit_activity(form.activityId);
      const nextStatusCode =
        submitResult?.workflow?.status_code ??
        submitResult?.data?.status_code ??
        "pending_faculty_review";
      return (
        mapStatusCodeToUi(nextStatusCode as any) ?? "PENDING_FACULTY_REVIEW"
      );
    } catch (error) {
      if (axios.isAxiosError(error)) {
        const code = error.response?.data?.code;
        if (code === "MEMBERS_REJECTED") {
          return "MEMBER_REJECTED";
        }
      }
      throw error;
    }
  },
});

onMounted(async () => {
  await loadCatalogs();
  await loadDraftFromQuery();
});
</script>
