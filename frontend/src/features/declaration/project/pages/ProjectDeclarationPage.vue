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
        :successVisible="shell.success_visible.value"
        :successMessage="shell.success_message.value"
        @save-draft="shell.save_draft"
        @submit="shell.submit_for_approval"
        @close-success="shell.close_success_modal"
      >
        <template #intro>
          <div class="space-y-2 text-sm text-slate-700">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
              <div class="font-semibold text-slate-900">
                Nguyên tắc tính giờ
              </div>
              <ul class="mt-1 list-disc space-y-1 pl-5 text-slate-600">
                <li>
                  Chọn cấp đề tài để áp dụng quy tắc giờ: cấp Bộ (720 + 480)
                  hoặc cấp Trường (600 + 240).
                </li>
                <li>
                  Chủ nhiệm nhận 100% phần chủ nhiệm, nhóm thành viên chia đều
                  quỹ giờ thành viên.
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
                  Cấp đề tài quyết định phần giờ Chủ nhiệm và quỹ giờ thành
                  viên.
                </div>
              </div>
            </div>

            <div
              v-if="selectedTypeRoleRule"
              class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-3"
            >
              <div class="text-xs font-semibold text-sky-900">
                Quy định giờ theo vai trò ({{
                  selectedTypeRoleRule.levelLabel
                }})
              </div>
              <div class="mt-2 grid gap-2 md:grid-cols-2">
                <div
                  class="rounded-lg border border-sky-200 bg-white px-3 py-2 text-sm"
                >
                  <div class="text-slate-600">Chủ nhiệm</div>
                  <div class="font-semibold text-slate-900">
                    {{ selectedTypeRoleRule.leaderHours.toFixed(2) }} giờ
                  </div>
                </div>
                <div
                  class="rounded-lg border border-sky-200 bg-white px-3 py-2 text-sm"
                >
                  <div class="text-slate-600">Quỹ giờ thành viên</div>
                  <div class="font-semibold text-slate-900">
                    {{ selectedTypeRoleRule.memberPoolHours.toFixed(2) }} giờ
                  </div>
                </div>
              </div>
              <div class="mt-2 text-xs text-slate-600">
                Giờ mỗi thành viên = quỹ giờ thành viên / số thành viên thực tế.
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
            :ownerFacultyId="ownerFacultyId"
            :hoursByLecturerId="hoursByLecturerId"
            @request-search="onSearchLecturers"
          />

          <!-- Section D -->
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

          <!-- Section E -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <h3 class="text-sm font-semibold text-slate-900">
                  Công thức tính giờ
                </h3>
                <p class="mt-1 text-xs text-slate-500">
                  Hiển thị theo cấp đề tài và vai trò trong nhóm tham gia.
                </p>
              </div>
              <span
                v-if="selectedTypeName"
                class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
              >
                {{ selectedTypeName }}
              </span>
            </div>

            <div
              v-if="previewLoading"
              class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
            >
              Đang cập nhật công thức từ hệ thống...
            </div>

            <div
              class="mt-4 overflow-hidden rounded-xl border border-slate-200"
            >
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                  <tr>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                    >
                      Vai trò
                    </th>
                    <th
                      class="px-4 py-3 text-right text-xs font-semibold text-slate-600"
                    >
                      Tổng giờ
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                    >
                      Cách tính
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                  <tr v-for="row in hours.formula_rows" :key="row.role_label">
                    <td class="px-4 py-3 font-medium text-slate-900">
                      {{ row.role_label }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-slate-900">
                      {{ row.total_hours.toFixed(2) }} giờ
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                      {{ row.formula_text }}
                    </td>
                  </tr>
                  <tr v-if="hours.formula_rows.length === 0">
                    <td
                      colspan="3"
                      class="px-4 py-6 text-center text-slate-500"
                    >
                      Chưa có đủ dữ liệu để xác định công thức.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div
              class="mt-4 overflow-hidden rounded-xl border border-slate-200"
            >
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                  <tr>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                    >
                      Thành viên
                    </th>
                    <th
                      class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                    >
                      Vai trò
                    </th>
                    <th
                      class="px-4 py-3 text-right text-xs font-semibold text-slate-600"
                    >
                      Giờ dự kiến
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                  <tr
                    v-for="member in hours.distribution"
                    :key="`split-${member.lecturer_id}`"
                  >
                    <td class="px-4 py-3 font-medium text-slate-900">
                      {{ member.lecturer_name }}
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                      {{ member.member_role_name }}
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-slate-900">
                      {{ member.hours.toFixed(2) }} giờ
                    </td>
                  </tr>
                  <tr v-if="hours.distribution.length === 0">
                    <td
                      colspan="3"
                      class="px-4 py-6 text-center text-slate-500"
                    >
                      Chưa có dữ liệu phân bổ cho nhóm hiện tại.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Section F -->
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
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
  type ComputedRef,
} from "vue";
import { useRoute, useRouter } from "vue-router";
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
  EvidenceLinkDto,
} from "../../shared/contracts/declarationSharedContract";
import { mapStatusCodeToUi } from "../../shared/contracts/declarationSharedContract";
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
} from "../../shared/services/catalogs.service";
import {
  fetch_activity,
  fetch_current_lecturer_option,
  submit_activity,
  upsert_activity_base,
  upsert_members,
  upsert_project_details,
  list_evidence_files,
  upload_evidence_file,
  delete_evidence_file,
  list_evidence_links,
  add_evidence_link,
  delete_evidence_link,
  preview_project_hours,
  type ProjectHoursPreviewResponseDto,
} from "../../shared/services/declarations.service";
import {
  computeProjectHours,
  type ProjectHoursComputationResult,
  type ProjectDeclarationFormModel,
  projectAllowedMemberRoleCodes,
} from "../ProjectDeclarationContract";

const route = useRoute();
const router = useRouter();

const PROJECT_TYPE_LABELS: Record<string, string> = {
  bo: "Đề tài cấp Bộ (2 năm)",
  ministry: "Đề tài cấp Bộ (2 năm)",
  coso: "Đề tài cấp Trường (1 năm)",
  university: "Đề tài cấp Trường (1 năm)",
};
const ALLOWED_PROJECT_TYPE_CODES = new Set(
  Object.keys(PROJECT_TYPE_LABELS).map((code) => code.toLowerCase()),
);

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
const existingEvidenceLinks = ref<EvidenceLinkDto[]>([]);
const pendingEvidenceFiles = ref<any[]>([]);
const pendingEvidenceLinks = ref<any[]>([]);
const deletingEvidenceFileId = ref<number | null>(null);

const typeCodeById = computed(() =>
  Object.fromEntries(types.value.map((t) => [t.id, t.code])),
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
const localHours = computed(() =>
  computeProjectHours(form, {
    typeCodeById: typeCodeById.value,
    lecturerNameById: lecturerNameById.value,
    memberRoleNameById: memberRoleNameById.value,
    memberRoleCodeById: memberRoleCodeById.value,
    currentLecturerId: currentLecturerId.value,
  }),
);

const serverProjectPreview = ref<ProjectHoursComputationResult | null>(null);
const previewLoading = ref(false);
const previewError = ref<string | null>(null);
let previewDebounceTimer: ReturnType<typeof setTimeout> | null = null;
const allowPreviewAutoRefresh = ref(false);

const selectedTypeName = computed(() => {
  const selectedType = types.value.find((type) => type.id === form.typeId);
  return selectedType?.name ?? null;
});

const selectedTypeRoleRule = computed(() => {
  if (!form.typeId) return null;

  const source = serverProjectPreview.value ?? localHours.value;
  if (!source || source.leader_hours <= 0) return null;

  return {
    levelLabel: source.rule_label ?? selectedTypeName.value ?? "Đề tài",
    leaderHours: source.leader_hours,
    memberPoolHours: source.member_pool_hours,
  };
});

function normalizeServerPreview(
  preview: ProjectHoursPreviewResponseDto,
): ProjectHoursComputationResult {
  return {
    total_hours: preview.formula.total_hours_allocated ?? 0,
    current_lecturer_hours: preview.current_lecturer_hours ?? 0,
    distribution: (preview.members ?? []).map((member) => ({
      lecturer_id: member.lecturer_id,
      lecturer_name: member.lecturer_full_name,
      member_role_id:
        memberRoles.value.find((role) => role.code === member.member_role_code)
          ?.id ?? 0,
      member_role_name: member.member_role_name ?? "—",
      hours: member.hours_assigned ?? 0,
    })),
    has_rule: !!preview.distribution_strategy,
    rule_type_code: preview.type_code,
    rule_label: preview.level_label ?? preview.type_name,
    leader_hours: preview.formula.leader_hours ?? 0,
    member_pool_hours: preview.formula.member_pool_hours ?? 0,
    member_pool_count: preview.formula.member_pool_count ?? 0,
    member_pool_each: preview.formula.member_pool_each ?? 0,
    formula_rows: preview.formula_rows?.map((row) => ({
      role_label: row.role_label,
      total_hours: row.total_hours ?? 0,
      formula_text: row.formula_text,
    })) ?? [
      {
        role_label: "Chủ nhiệm",
        total_hours: preview.formula.leader_hours ?? 0,
        formula_text: `${(preview.formula.leader_hours ?? 0).toFixed(
          2,
        )} giờ (100%)`,
      },
      {
        role_label: "Nhóm thành viên",
        total_hours: preview.formula.member_pool_hours ?? 0,
        formula_text:
          (preview.formula.member_pool_count ?? 0) > 0
            ? `${(preview.formula.member_pool_hours ?? 0).toFixed(2)} / ${
                preview.formula.member_pool_count
              } = ${(preview.formula.member_pool_each ?? 0).toFixed(
                2,
              )} giờ/người`
            : `${(preview.formula.member_pool_hours ?? 0).toFixed(
                2,
              )} / 0 = 0 giờ/người (chưa có thành viên)`,
      },
    ],
    progress_note: preview.formula.progress_note,
  };
}

async function requestServerProjectHoursPreview() {
  if (!form.typeId) {
    serverProjectPreview.value = null;
    previewError.value = null;
    return;
  }

  const membersPayload = form.members
    .filter(
      (member) =>
        !member.is_external &&
        typeof member.lecturer_id === "number" &&
        typeof member.member_role_id === "number",
    )
    .map((member) => ({
      lecturer_id: member.lecturer_id as number,
      member_role_id: member.member_role_id as number,
    }));

  if (membersPayload.length === 0) {
    serverProjectPreview.value = null;
    previewError.value = null;
    return;
  }

  previewLoading.value = true;
  previewError.value = null;
  try {
    const response = await preview_project_hours({
      academic_year_id: form.academicYearId ?? null,
      type_id: form.typeId ?? null,
      quantity: 1,
      members: membersPayload,
    });
    serverProjectPreview.value = normalizeServerPreview(response);
  } catch (_error) {
    serverProjectPreview.value = null;
    previewError.value =
      "Không thể tải công thức từ hệ thống. Đang hiển thị bản tạm tính cục bộ.";
  } finally {
    previewLoading.value = false;
  }
}

function scheduleProjectPreviewRefresh() {
  if (previewDebounceTimer) {
    clearTimeout(previewDebounceTimer);
  }
  previewDebounceTimer = setTimeout(() => {
    void requestServerProjectHoursPreview();
  }, 250);
}

watch(
  () => ({
    academicYearId: form.academicYearId,
    typeId: form.typeId,
    members: form.members.map((member) => ({
      lecturer_id: member.lecturer_id ?? null,
      member_role_id: member.member_role_id ?? null,
      is_external: !!member.is_external,
    })),
  }),
  () => {
    if (!allowPreviewAutoRefresh.value) return;
    scheduleProjectPreviewRefresh();
  },
  { deep: true },
);

const hours = computed<ProjectHoursComputationResult>(() => {
  return serverProjectPreview.value ?? localHours.value;
});

const hoursByLecturerId = computed(() => {
  const map: Record<number, number> = {};
  for (const d of hours.value.distribution) map[d.lecturer_id] = d.hours;
  return map;
});

const hoursNote = computed(() => {
  if (!form.typeId) return "Chọn cấp đề tài để xác định công thức chia giờ.";
  if (!hours.value.has_rule)
    return "Chưa có quy tắc quy đổi cho loại đề tài này. Vui lòng liên hệ Phòng Quản lý khoa học.";
  if (hours.value.distribution.length === 0)
    return "Cần thêm thành viên để hiển thị phân bổ giờ dự kiến.";
  if (previewError.value) return previewError.value;
  return hours.value.progress_note;
});

const canSubmit = computed(() => {
  if (!form.academicYearId) return false;
  if (!form.typeId) return false;
  if (!form.title.trim()) return false;
  // members must be valid
  const validMembers = form.members.filter(
    (m) =>
      typeof m.lecturer_id === "number" && typeof m.member_role_id === "number",
  );
  if (validMembers.length === 0) return false;
  // evidence pending files should have file_type_id selected (if any)
  const invalidPending = pendingEvidenceFiles.value.some(
    (p: any) => !p.file_type_id,
  );
  if (invalidPending) return false;
  const invalidPendingLinks = pendingEvidenceLinks.value.some(
    (l: any) => !String(l?.url ?? "").trim(),
  );
  if (invalidPendingLinks) return false;
  return true;
});
const filteredMemberRoles = computed(() => {
  const allowed = new Set<string>(
    projectAllowedMemberRoleCodes as readonly string[],
  );
  const list = memberRoles.value.filter((r) => allowed.has(r.code));

  // fallback để dropdown không rỗng nếu backend chưa seed đúng code
  return list.length > 0 ? list : memberRoles.value;
});
async function loadCatalogs() {
  const [currentLecturerOption, years, kinds, roles, fileTypes] =
    await Promise.all([
      fetch_current_lecturer_option(),
      fetch_academic_years(),
      fetch_activity_kinds(),
      fetch_member_roles(),
      fetch_evidence_file_types(),
    ]);
  currentLecturerId.value = currentLecturerOption?.id ?? 0;
  lecturers.value = currentLecturerOption ? [currentLecturerOption] : [];
  academicYears.value = years;
  memberRoles.value = roles;
  evidenceFileTypes.value = fileTypes;

  kindId.value = kinds.find((k) => k.code === "project")?.id ?? 0;
  form.kindId = kindId.value;

  const fetchedProjectTypes = await fetch_activity_types_by_kind(kindId.value);
  types.value = fetchedProjectTypes
    .filter((type) => ALLOWED_PROJECT_TYPE_CODES.has(type.code.toLowerCase()))
    .map((type) => ({
      ...type,
      name: PROJECT_TYPE_LABELS[type.code.toLowerCase()] ?? type.name,
    }))
    .sort((a, b) => a.name.localeCompare(b.name, "vi"));

  if (form.typeId && !types.value.some((type) => type.id === form.typeId)) {
    form.typeId = null;
  }

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

function normalizeErrorMessage(err: unknown, fallback: string): string {
  if (axios.isAxiosError(err)) {
    const message = err.response?.data?.message;
    if (typeof message === "string" && message.trim()) return message;
  }
  return err instanceof Error && err.message.trim() ? err.message : fallback;
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
    await runWithFeedback(() => delete_evidence_file(form.activityId as number, _id), {
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
    });
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
    (pendingLink) => String(pendingLink?.url ?? "").trim() !== "",
  );
  const linkResults = await Promise.allSettled(
    validLinkPendings.map((pendingLink) =>
      add_evidence_link(activityId, { url: String(pendingLink.url).trim() }),
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

function yearToDate(year: number | null): string | null {
  if (!year) return null;
  return `${year}-01-01`;
}

function dateToYear(value: string | null | undefined): number | null {
  if (!value) return null;
  const normalized = String(value).trim();
  if (!normalized) return null;

  const directYear = Number(normalized.slice(0, 4));
  if (
    Number.isInteger(directYear) &&
    directYear >= 1900 &&
    directYear <= 2100
  ) {
    return directYear;
  }

  const parsed = new Date(normalized);
  if (!Number.isNaN(parsed.getTime())) {
    return parsed.getFullYear();
  }

  return null;
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
    form.notes = activity.notes ?? "";
    form.startYear = dateToYear(activity.start_date);
    form.endYear = dateToYear(activity.end_date);

    if (data.detail_kind === "project_details" && data.detail) {
      const detail = data.detail as any;
      form.projectCode = detail.project_code ?? "";
      form.decisionNo = detail.decision_no ?? "";
      form.decisionDate = detail.decision_date ?? null;
      form.funding = detail.funding ?? null;
      form.startYear = dateToYear(detail.start_month ?? activity.start_date);
      form.endYear = dateToYear(detail.end_month ?? activity.end_date);
    }

    form.members = (data.members ?? []).map((member) => ({
      lecturer_id: member.lecturer_id,
      member_role_id: member.member_role_id,
      member_role_code: member.member_role_code ?? null,
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

const shell = useDeclarationFormShell({
  initial_status: "DRAFT",
  on_save_draft: async () => {
    try {
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
      await router.replace({
        query: { ...route.query, activity_id: String(saved.id) },
      });

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
            typeof m.member_role_id === "number",
        )
        .map((m) => ({
          lecturer_id: m.lecturer_id as number,
          member_role_id: m.member_role_id as number,
          contribution_share: null,
        }));
      await upsert_members(saved.id, upsertList);

      await persistEvidenceDraft(saved.id);
    } catch (err) {
      throw new Error(
        normalizeErrorMessage(err, "Không thể lưu bản nháp. Vui lòng thử lại."),
      );
    }
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

const { runPageLoad } = useDeclarationPageLoadFeedback();

onBeforeUnmount(() => {
  if (!previewDebounceTimer) return;
  clearTimeout(previewDebounceTimer);
  previewDebounceTimer = null;
});

onMounted(async () => {
  await runPageLoad(async () => {
    await loadCatalogs();
    await loadDraftFromQuery();
    allowPreviewAutoRefresh.value = true;
    scheduleProjectPreviewRefresh();
  }, {
    onError: (message) => {
      shell.error_message.value = message;
    },
    fallbackMessage: "Không thể khởi tạo trang kê khai.",
  });
});
</script>
