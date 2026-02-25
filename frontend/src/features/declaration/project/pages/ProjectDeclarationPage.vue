<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <DeclarationFormShell
        title="Kê khai Đề tài KH&CN"
        description="Giờ đề tài được tính theo cấp đề tài và phân bổ theo vai trò/số thành viên."
        :icon="FolderKanban"
        :status="shell.status.value"
        :canSubmit="canSubmit"
        :pending="shell.pending.value"
        :errorMessage="shell.error_message.value"
        @save-draft="shell.save_draft"
        @submit="shell.submit_for_approval"
      >
        <template #intro>
          <div class="space-y-2 text-sm text-slate-700">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
              <div class="font-semibold text-slate-900">
                Nguyên tắc tính giờ
              </div>
              <ul class="mt-1 list-disc space-y-1 pl-5 text-slate-600">
                <li>Chọn cấp đề tài để xác định giờ chuẩn (baseHours).</li>
                <li>
                  Phân bổ theo vai trò: Chủ nhiệm / Thư ký / Thành viên
                  (real-time).
                </li>
                <li>Giảng viên không nhập giờ thủ công.</li>
              </ul>
            </div>
          </div>
        </template>

        <template #default="{ readOnly }">
          <!-- Section A -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              A. Thông tin phân loại / quy đổi
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Niên học</label
                >
                <!-- NOTE: nếu có option null thì nên bỏ .number (giống page bài báo) -->
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
                <div class="mt-1 text-xs text-slate-500">
                  Chọn niên học để ghi nhận đề tài theo năm.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Cấp đề tài</label
                >
                <select
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  v-model.number="form.typeId"
                  :disabled="readOnly"
                >
                  <option :value="null">— Chọn cấp đề tài —</option>
                  <option v-for="t in types" :key="t.id" :value="t.id">
                    {{ t.name }}
                  </option>
                </select>
                <div class="mt-1 text-xs text-slate-500">
                  Cấp đề tài sẽ quyết định giờ chuẩn và cách phân bổ.
                </div>
              </div>
            </div>
          </div>

          <!-- Section B -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              B. Thông tin đề tài
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600">
                  Tên đề tài <span class="text-rose-600">*</span>
                </label>
                <input
                  v-model.trim="form.title"
                  :disabled="readOnly"
                  maxlength="500"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: Nghiên cứu ứng dụng AI trong quản lý học tập"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: research_activities.title (VARCHAR500)
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Mã số đề tài</label
                >
                <input
                  v-model.trim="form.projectCode"
                  :disabled="readOnly"
                  maxlength="100"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: DT-2025-012 (nếu có)"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: project_details.project_code (VARCHAR100, nullable)
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Số quyết định</label
                >
                <input
                  v-model.trim="form.decisionNo"
                  :disabled="readOnly"
                  maxlength="255"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: 123/QĐ-ĐHXYZ (nếu có)"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: project_details.decision_no (VARCHAR100, nullable)
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Ngày quyết định</label
                >
                <input
                  v-model="form.decisionDate"
                  type="date"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: project_details.decision_date (DATE, nullable)
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Kinh phí</label
                >
                <input
                  v-model.number="form.funding"
                  type="number"
                  step="0.01"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: 150000000 (VND) (nếu có)"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: project_details.funding (DECIMAL12,2, nullable)
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Năm bắt đầu</label
                >
                <input
                  v-model.number="form.startYear"
                  type="number"
                  min="1900"
                  max="2100"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: 2024"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: sẽ map sang research_activities.start_date (DATE,
                  nullable)
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Năm kết thúc</label
                >
                <input
                  v-model.number="form.endYear"
                  type="number"
                  min="1900"
                  max="2100"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: 2025"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: sẽ map sang research_activities.end_date (DATE,
                  nullable)
                </div>
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
                  placeholder="VD: Đề tài đã nghiệm thu/đang triển khai... (tuỳ chọn)"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Gợi ý: research_activities.notes (VARCHAR500, nullable)
                </div>
              </div>
            </div>
          </div>

          <!-- Section C -->
          <ParticipantsTable
            v-model="form.members"
            :lecturers="lecturers"
            :memberRoles="filteredMemberRoles"
            :readOnly="readOnly"
            :currentLecturerId="currentLecturerId"
            :hoursByLecturerId="hoursByLecturerId"
            @request-search="onSearchLecturers"
          />

          <!-- Section D -->
          <EvidenceUpload
            :existingFiles="existingEvidence"
            v-model:pendingFiles="pendingEvidenceFiles"
            v-model:pendingLinks="pendingEvidenceLinks"
            :fileTypes="evidenceFileTypes"
            :readOnly="readOnly"
            @remove-existing="onRemoveExistingEvidence"
          />

          <!-- Section E -->
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
import { FolderKanban } from "lucide-vue-next";

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
  fetch_current_lecturer_id,
  submit_activity,
  upsert_activity_base,
  upsert_members,
  upsert_project_details,
  list_evidence_files,
} from "../../shared/services/declarations.service";
import {
  computeProjectHours,
  type ProjectDeclarationFormModel,
  projectAllowedMemberRoleCodes,
} from "../ProjectDeclarationContract";

const academicYears = ref<AcademicYearDto[]>([]);
const memberRoles = ref<MemberRoleDto[]>([]);
const evidenceFileTypes = ref<EvidenceFileTypeDto[]>([]);
const lecturers = ref<LecturerOptionDto[]>([]);
const types = ref<ActivityTypeDto[]>([]);
const kindId = ref<number>(0);

// TODO: from GET /api/profile/me
const currentLecturerId = ref<number>(0);

const form = reactive<ProjectDeclarationFormModel>({
  activityId: null,
  academicYearId: null,
  kindId: 0,
  typeId: null,
  title: "",
  notes: "",
  startYear: null,
  endYear: null,
  projectCode: "",
  decisionNo: "",
  decisionDate: null,
  funding: null,
  members: [],
});

const existingEvidence = ref<EvidenceFileDto[]>([]);
const pendingEvidenceFiles = ref<any[]>([]);
const pendingEvidenceLinks = ref<any[]>([]);

const typeCodeById = computed(() =>
  Object.fromEntries(types.value.map((t) => [t.id, t.code]))
);
const lecturerNameById = computed(() =>
  Object.fromEntries(lecturers.value.map((l) => [l.id, l.full_name]))
);

const memberRoleCodeById = computed(() =>
  Object.fromEntries(memberRoles.value.map((r) => [r.id, r.code]))
);
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
  computeProjectHours(form, {
    typeCodeById: typeCodeById.value,
    lecturerNameById: lecturerNameById.value,
    memberRoleNameById: memberRoleNameById.value,
    memberRoleCodeById: memberRoleCodeById.value,
    currentLecturerId: currentLecturerId.value,
  })
);

const hoursByLecturerId = computed(() => {
  const map: Record<number, number> = {};
  for (const d of hours.value.distribution) map[d.lecturer_id] = d.hours;
  return map;
});

const hoursNote = computed(() => {
  if (!form.typeId) return "Chọn cấp đề tài để xác định giờ chuẩn.";
  if (hours.value.distribution.length === 0)
    return "Cần chọn danh sách người tham gia để phân bổ giờ.";
  return null;
});

const canSubmit = computed(() => {
  if (!form.academicYearId) return false;
  if (!form.typeId) return false;
  if (!form.title.trim()) return false;
  // members must be valid
  const validMembers = form.members.filter(
    (m) =>
      typeof m.lecturer_id === "number" && typeof m.member_role_id === "number"
  );
  if (validMembers.length === 0) return false;
  // evidence pending files should have file_type_id selected (if any)
  const invalidPending = pendingEvidenceFiles.value.some(
    (p: any) => !p.file_type_id
  );
  if (invalidPending) return false;
  return true;
});
const filteredMemberRoles = computed(() => {
  const allowed = new Set<string>(
    projectAllowedMemberRoleCodes as readonly string[]
  );
  const list = memberRoles.value.filter((r) => allowed.has(r.code));

  // fallback để dropdown không rỗng nếu backend chưa seed đúng code
  return list.length > 0 ? list : memberRoles.value;
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

  kindId.value = kinds.find((k) => k.code === "project")?.id ?? 0;
  form.kindId = kindId.value;

  types.value = await fetch_activity_types_by_kind(kindId.value);

  lecturers.value = await search_lecturer_options("");
  // default add current lecturer as member
  if (form.members.length === 0 && currentLecturerId.value) {
    const principalRole = roles.find((r) => r.code === "principal");
    form.members.push({
      lecturer_id: currentLecturerId.value,
      member_role_id: principalRole?.id ?? null,
      member_role_code: principalRole?.code ?? null,
    });
  }
}

async function onSearchLecturers(q: string) {
  lecturers.value = await search_lecturer_options(q);
}

async function onRemoveExistingEvidence(_id: number) {
  // TODO: DELETE endpoint
  // UI-only for now
  existingEvidence.value = existingEvidence.value.filter((x) => x.id !== _id);
}

function yearToDate(year: number | null): string | null {
  if (!year) return null;
  return `${year}-01-01`;
}

const shell = useDeclarationFormShell({
  initial_status: "DRAFT",
  on_save_draft: async () => {
    // upsert base research_activities
    const saved = await upsert_activity_base({
      id: form.activityId ?? undefined,
      owner_lecturer_id: currentLecturerId.value,
      kind_id: form.kindId,
      type_id: form.typeId,
      academic_year_id: form.academicYearId ?? 0,
      status_id: 100, // mock draft status id (from mock_statuses); TODO: lookup by code
      title: form.title,
      abstract: null,
      start_date: yearToDate(form.startYear),
      end_date: yearToDate(form.endYear),
      quantity: 1,
      notes: form.notes || null,
      submitted_at: null,
      approved_at: null,
      total_hours_calc: null,
    } as any);

    form.activityId = saved.id;

    // upsert details
    await upsert_project_details({
      activity_id: saved.id,
      project_code: form.projectCode || null,
      decision_no: form.decisionNo || null,
      decision_date: form.decisionDate || null,
      funding: form.funding ?? null,
      start_month: yearToDate(form.startYear),
      end_month: yearToDate(form.endYear),
    });

    // upsert members
    const upsertList = form.members
      .filter(
        (m) =>
          typeof m.lecturer_id === "number" &&
          typeof m.member_role_id === "number"
      )
      .map((m) => ({
        lecturer_id: m.lecturer_id as number,
        member_role_id: m.member_role_id as number,
        contribution_share: null,
      }));
    await upsert_members(saved.id, upsertList);

    // evidence list
    existingEvidence.value = await list_evidence_files(saved.id);

    // Evidence is submitted in the hours-approval workflow.
    // Do not block declaration draft save when pending files/links were picked here.
    pendingEvidenceFiles.value = [];
    pendingEvidenceLinks.value = [];
  },
  on_submit: async () => {
    try {
      if (!form.activityId) {
        await shell.save_draft();
      }
      if (!form.activityId) return;
      const submitResult = await submit_activity(form.activityId);
      const nextStatusCode =
        submitResult?.workflow?.status_code ??
        submitResult?.data?.status_code ??
        "pending_faculty_review";
      return mapStatusCodeToUi(nextStatusCode as any) ?? "PENDING_FACULTY_REVIEW";
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

onMounted(loadCatalogs);
</script>
