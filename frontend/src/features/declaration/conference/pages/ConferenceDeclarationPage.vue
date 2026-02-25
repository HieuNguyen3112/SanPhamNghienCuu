<!-- File: src/features/declaration/conference/pages/ConferenceDeclarationPage.vue -->
<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <DeclarationFormShell
        title="Kê khai Hội nghị – Hội thảo"
        description="Mỗi dòng = 1 lần tham gia. Báo cáo: 40 giờ/lần. Tham dự: 4 giờ/lần (tối đa 40 lần)."
        :icon="Users"
        :status="shell.status.value"
        :canSubmit="canSubmit"
        :pending="shell.pending.value"
        :errorMessage="shell.error_message.value"
        @save-draft="shell.save_draft"
        @submit="shell.submit_for_approval"
      >
        <template #intro>
          <div
            class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
          >
            <div class="font-semibold text-slate-900">Cách kê khai</div>
            <ul class="mt-1 list-disc space-y-1 pl-5 text-slate-600">
              <li>Thêm 1 dòng = thêm 1 lần tham gia.</li>
              <li>Báo cáo: 40 giờ/lần.</li>
              <li>Tham dự: 4 giờ/lần, tối đa 40 lần (160 giờ).</li>
              <li>Minh chứng: upload/link <b>theo từng dòng</b>.</li>
              <li class="text-amber-700">
                Mỗi dòng sẽ được lưu thành một công trình hội thảo riêng để
                gửi duyệt theo đúng quy trình hiện tại.
              </li>
            </ul>
          </div>
        </template>

        <template #default="{ readOnly }">
          <!-- A -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              A. Niên học & tổng hợp
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Niên học</label
                >
                <select
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  v-model="form.academicYearId"
                  :disabled="readOnly"
                >
                  <option :value="null">— Chọn niên học —</option>
                  <option v-for="y in academicYears" :key="y.id" :value="y.id">
                    {{ y.code }}
                  </option>
                </select>
              </div>

              <div
                class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
              >
                <div class="flex flex-wrap gap-2">
                  <span>
                    <b>Báo cáo:</b> {{ hours.result.report_count }} lần ({{
                      hours.result.report_count * 40
                    }}
                    giờ)
                  </span>
                  <span class="text-slate-300">•</span>
                  <span>
                    <b>Tham dự:</b> {{ hours.result.valid_attend_count }} /
                    {{ hours.result.attend_count }} lần ({{
                      hours.result.valid_attend_count * 4
                    }}
                    giờ)
                  </span>
                  <span class="text-slate-300">•</span>
                  <span><b>Tổng:</b> {{ hours.result.total_hours }} giờ</span>
                </div>
              </div>
            </div>

            <div
              v-if="warnings.length"
              class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
            >
              <div v-for="(w, i) in warnings" :key="i">{{ w }}</div>
            </div>
          </div>

          <!-- B -->
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900">
                  B. Danh sách lần tham gia
                </div>
                <p class="mt-1 text-xs text-slate-500">
                  Mỗi dòng là 1 lần tham gia. Chọn hình thức, nhập thông tin và
                  đính kèm minh chứng theo dòng.
                </p>
              </div>

              <button
                v-if="!readOnly"
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                @click="addRow"
              >
                <Plus class="h-4 w-4" />
                Thêm dòng
              </button>
            </div>

            <div
              class="mt-4 overflow-hidden rounded-xl border border-slate-200"
            >
              <div class="max-w-full overflow-x-auto">
                <table class="min-w-[1100px] w-full text-center text-sm">
                  <thead
                    class="sticky top-0 bg-slate-50 text-xs text-slate-600"
                  >
                    <tr>
                      <th class="px-3 py-2 font-semibold">Hình thức</th>
                      <th class="px-3 py-2 font-semibold">Tên hội nghị</th>
                      <th class="px-3 py-2 font-semibold">Ngày</th>
                      <th class="px-3 py-2 font-semibold">Địa điểm</th>
                      <th class="px-3 py-2 font-semibold">Ghi chú</th>
                      <th class="px-3 py-2 font-semibold">Minh chứng</th>
                      <th class="px-2 py-2 font-semibold">Thao tác</th>
                    </tr>
                  </thead>

                  <tbody class="divide-y divide-slate-200">
                    <template v-for="(row, idx) in form.items" :key="row.rowId">
                      <!-- main row -->
                      <tr class="hover:bg-slate-50">
                        <td class="px-2 py-2 align-top">
                          <select
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                            v-model="row.typeId"
                            :disabled="readOnly"
                          >
                            <option :value="null">— Chọn —</option>
                            <option
                              v-for="t in conferenceTypes"
                              :key="t.id"
                              :value="t.id"
                            >
                              {{ t.name }}
                            </option>
                          </select>

                          <div class="mt-1 text-xs text-slate-500">
                            {{ hoursHintByTypeId(row.typeId) }}
                          </div>
                        </td>

                        <td class="px-2 py-2 align-top">
                          <input
                            v-model.trim="row.conferenceName"
                            :disabled="readOnly"
                            maxlength="255"
                            placeholder="VD: Hội thảo Khoa học Quốc gia 2025"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                          />
                          <div
                            v-if="rowNameError(row, readOnly)"
                            class="mt-1 text-xs text-rose-600"
                          >
                            {{ rowNameError(row, readOnly) }}
                          </div>
                        </td>

                        <td class="px-2 py-2 align-top">
                          <input
                            v-model="row.heldOn"
                            type="date"
                            :disabled="readOnly"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                          />
                        </td>

                        <td class="px-2 py-2 align-top">
                          <input
                            v-model.trim="row.location"
                            :disabled="readOnly"
                            maxlength="255"
                            placeholder="VD: Hà Nội / Online"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                          />
                        </td>

                        <td class="px-2 py-2 align-top">
                          <input
                            v-model.trim="row.notes"
                            :disabled="readOnly"
                            maxlength="500"
                            placeholder="Ghi chú (tuỳ chọn)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                          />
                        </td>

                        <!-- Evidence per-row -->
                        <td class="px-3 py-2 align-top">
                          <button
                            type="button"
                            class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
                            :disabled="false"
                            @click="toggleEvidence(row.rowId)"
                          >
                            <span class="whitespace-nowrap">Minh chứng</span>

                            <span
                              class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-[11px] font-semibold text-slate-900"
                            >
                              {{ evidenceCount(row) }}
                            </span>
                          </button>
                        </td>

                        <td class="px-3 py-2 align-top">
                          <button
                            v-if="!readOnly"
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                            title="Xoá dòng"
                            @click="removeRow(idx)"
                          >
                            <Trash2 class="h-4 w-4" />
                          </button>
                        </td>
                      </tr>

                      <!-- evidence drawer row -->
                      <tr
                        v-if="openEvidenceRowId === row.rowId"
                        class="bg-slate-50"
                      >
                        <td class="px-3 py-3 text-left" :colspan="7">
                          <EvidenceUpload
                            :existingFiles="row.existingEvidenceFiles"
                            v-model:pendingFiles="row.pendingEvidenceFiles"
                            v-model:pendingLinks="row.pendingEvidenceLinks"
                            :fileTypes="evidenceFileTypes"
                            :readOnly="readOnly"
                            @remove-existing="
                              (fileId) =>
                                onRemoveRowExistingEvidence(row.rowId, fileId)
                            "
                          />
                        </td>
                      </tr>
                    </template>

                    <tr v-if="form.items.length === 0">
                      <td
                        class="px-3 py-6 text-center text-sm text-slate-500"
                        colspan="7"
                      >
                        Chưa có dòng nào. Bấm <b>Thêm dòng</b> để bắt đầu kê
                        khai.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- D -->
          <HoursSummaryPanel
            :totalHours="hours.result.total_hours"
            :currentLecturerHours="hours.result.current_lecturer_hours"
            :membersDistribution="hours.result.distribution"
            :note="hoursNote"
          />
        </template>
      </DeclarationFormShell>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { Users, Plus, Trash2 } from "lucide-vue-next";

import DeclarationFormShell from "../../shared/components/DeclarationFormShell.vue";
import EvidenceUpload from "../../shared/components/EvidenceUpload.vue";
import HoursSummaryPanel from "../../shared/components/HoursSummaryPanel.vue";

import type {
  AcademicYearDto,
  EvidenceFileTypeDto,
  EvidenceFileDto,
  ActivityTypeDto,
} from "../../shared/contracts/declarationSharedContract";
import { mapStatusCodeToUi } from "../../shared/contracts/declarationSharedContract";
import { useDeclarationFormShell } from "../../shared/composables/useDeclarationFormShell";
import {
  fetch_academic_years,
  fetch_activity_kinds,
  fetch_activity_types_by_kind,
  fetch_evidence_file_types,
  fetch_member_roles,
} from "../../shared/services/catalogs.service";
import {
  fetch_current_lecturer_id,
  list_evidence_files,
  submit_activity,
  upsert_activity_base,
  upsert_conference_details,
  upsert_members,
} from "../../shared/services/declarations.service";

import {
  computeConferenceHours,
  createConferenceRowId,
  type ConferenceDeclarationFormModel,
  type ConferenceOccurrenceFormItem,
} from "../ConferenceDeclarationContract";

const academicYears = ref<AcademicYearDto[]>([]);
const evidenceFileTypes = ref<EvidenceFileTypeDto[]>([]);
const conferenceTypesAll = ref<ActivityTypeDto[]>([]);
const kindId = ref<number>(0);
const memberRoles = ref<{ id: number; code: string; name: string }[]>([]);

const currentLecturerId = ref<number>(0);
const currentLecturerName = ref<string>("Giảng viên");

const form = reactive<ConferenceDeclarationFormModel>({
  activityIds: [],
  academicYearId: null,
  kindId: 0,
  items: [],
});

const openEvidenceRowId = ref<string | null>(null);

const typeCodeById = computed(() =>
  Object.fromEntries(conferenceTypesAll.value.map((t) => [t.id, t.code])),
);

const conferenceTypes = computed(() =>
  conferenceTypesAll.value.filter(
    (t) => t.code === "report" || t.code === "attend",
  ),
);

const hours = computed(() =>
  computeConferenceHours(form, {
    currentLecturerId: currentLecturerId.value,
    currentLecturerName: currentLecturerName.value,
    typeCodeById: typeCodeById.value,
  }),
);

const warnings = computed(() => hours.value.warnings);

const hoursNote = computed(() => {
  if (!form.academicYearId) return "Chọn niên học để kê khai.";
  if (form.items.length === 0) return "Thêm ít nhất 1 dòng để tính giờ.";
  if (hours.value.result.total_hours === 0)
    return "Chọn hình thức (Báo cáo/Tham dự) cho từng dòng để tính giờ.";
  return null;
});

function rowNameError(
  row: ConferenceOccurrenceFormItem,
  readOnly: boolean,
): string | null {
  if (readOnly) return null;
  if (!row.conferenceName.trim()) return "Tên hội nghị là bắt buộc.";
  if (row.conferenceName.length > 255) return "Tối đa 255 ký tự.";
  return null;
}

function hoursHintByTypeId(typeId: number | null): string {
  if (!typeId) return "";
  const code = typeCodeById.value[typeId];
  if (code === "report") return "40 giờ / lần";
  if (code === "attend") return "4 giờ / lần (tối đa 40 lần tính giờ)";
  return "";
}

function evidenceCount(row: ConferenceOccurrenceFormItem): number {
  return (
    (row.existingEvidenceFiles?.length ?? 0) +
    (row.pendingEvidenceFiles?.length ?? 0) +
    (row.pendingEvidenceLinks?.length ?? 0)
  );
}

function toggleEvidence(rowId: string) {
  openEvidenceRowId.value = openEvidenceRowId.value === rowId ? null : rowId;
}

function addRow() {
  form.items.push({
    rowId: createConferenceRowId(),
    typeId: null,
    conferenceName: "",
    location: "",
    heldOn: null,
    notes: "",
    existingEvidenceFiles: [] as EvidenceFileDto[],
    pendingEvidenceFiles: [] as any[],
    pendingEvidenceLinks: [] as any[],
  });
}

function removeRow(index: number) {
  const row = form.items[index];
  if (row && openEvidenceRowId.value === row.rowId)
    openEvidenceRowId.value = null;
  form.items.splice(index, 1);
}

function onRemoveRowExistingEvidence(rowId: string, fileId: number) {
  const row = form.items.find((x) => x.rowId === rowId);
  if (!row) return;
  row.existingEvidenceFiles = row.existingEvidenceFiles.filter(
    (f) => f.id !== fileId,
  );
}

const canSubmit = computed(() => {
  if (!form.academicYearId) return false;
  if (form.items.length === 0) return false;

  const allValid = form.items.every((row) => {
    if (!row.typeId) return false;
    if (!row.conferenceName.trim()) return false;
    if (row.conferenceName.length > 255) return false;
    if (row.location.length > 255) return false;
    if (row.notes.length > 500) return false;

    // pending evidence validation (nếu có thì phải chọn loại file_type_id)
    const invalidPendingFiles = (row.pendingEvidenceFiles ?? []).some(
      (p: any) => !p.file_type_id,
    );
    if (invalidPendingFiles) return false;

    const invalidPendingLinks = (row.pendingEvidenceLinks ?? []).some(
      (l: any) => !l.file_type_id || !String(l.url ?? "").trim(),
    );
    if (invalidPendingLinks) return false;

    return true;
  });

  return allValid;
});

async function loadCatalogs() {
  const [years, kinds, fileTypes, roles, lecturerId] = await Promise.all([
    fetch_academic_years(),
    fetch_activity_kinds(),
    fetch_evidence_file_types(),
    fetch_member_roles(),
    fetch_current_lecturer_id(),
  ]);

  academicYears.value = years;
  evidenceFileTypes.value = fileTypes;
  memberRoles.value = roles;
  currentLecturerId.value = lecturerId ?? 0;

  kindId.value = kinds.find((k) => k.code === "conference")?.id ?? 0;
  form.kindId = kindId.value;

  if (kindId.value) {
    conferenceTypesAll.value = await fetch_activity_types_by_kind(kindId.value);
  }

  if (form.items.length === 0) addRow();
}

function resolveDefaultMemberRoleId(): number {
  const preferred =
    memberRoles.value.find((role) => role.code === "member") ??
    memberRoles.value.find((role) => role.code === "principal") ??
    memberRoles.value[0];

  if (!preferred) {
    throw new Error("Không tìm thấy vai trò thành viên để lưu kê khai.");
  }

  return preferred.id;
}

const shell = useDeclarationFormShell({
  initial_status: "DRAFT",
  on_save_draft: async () => {
    const hasAnyPendingEvidence = form.items.some(
      (r) =>
        (r.pendingEvidenceFiles?.length ?? 0) > 0 ||
        (r.pendingEvidenceLinks?.length ?? 0) > 0,
    );

    if (hasAnyPendingEvidence) {
      throw new Error(
        "Hiện chưa hỗ trợ tải minh chứng ngay ở màn kê khai hội thảo. Vui lòng gửi minh chứng tại bước duyệt giờ NCKH.",
      );
    }

    if (!form.academicYearId) {
      throw new Error("Vui lòng chọn niên học trước khi lưu.");
    }
    if (kindId.value <= 0) {
      throw new Error("Không tìm thấy loại công trình hội nghị/hội thảo.");
    }
    if (!currentLecturerId.value) {
      throw new Error("Không xác định được giảng viên hiện tại.");
    }

    const memberRoleId = resolveDefaultMemberRoleId();
    const createdIds: number[] = [];

    for (const row of form.items) {
      if (!row.typeId || !row.conferenceName.trim()) {
        continue;
      }

      const activity = await upsert_activity_base({
        kind_id: kindId.value,
        type_id: row.typeId,
        academic_year_id: form.academicYearId,
        title: row.conferenceName.trim(),
        abstract: null,
        start_date: row.heldOn ?? null,
        end_date: row.heldOn ?? null,
        quantity: 1,
        notes: row.notes?.trim() ? row.notes.trim() : null,
      });

      await upsert_conference_details({
        activity_id: activity.id,
        conference_name: row.conferenceName.trim(),
        location: row.location?.trim() ? row.location.trim() : null,
        held_on: row.heldOn ?? null,
      });

      await upsert_members(activity.id, [
        {
          lecturer_id: currentLecturerId.value,
          member_role_id: memberRoleId,
          contribution_share: null,
        },
      ]);

      row.existingEvidenceFiles = await list_evidence_files(activity.id);
      createdIds.push(activity.id);
    }

    form.activityIds = createdIds;
  },
  on_submit: async () => {
    await shell.save_draft();
    if (form.activityIds.length === 0) {
      throw new Error("Không có dòng hội thảo hợp lệ để gửi duyệt.");
    }

    let nextStatusCode = "pending_faculty_review";
    for (const activityId of form.activityIds) {
      const submitResult = await submit_activity(activityId);
      const activityStatusCode =
        submitResult?.workflow?.status_code ??
        submitResult?.data?.status_code ??
        "pending_faculty_review";

      if (activityStatusCode === "pending_member_confirm") {
        nextStatusCode = "pending_member_confirm";
      }
    }

    return mapStatusCodeToUi(nextStatusCode as any) ?? "PENDING_FACULTY_REVIEW";
  },
});

onMounted(loadCatalogs);
</script>
