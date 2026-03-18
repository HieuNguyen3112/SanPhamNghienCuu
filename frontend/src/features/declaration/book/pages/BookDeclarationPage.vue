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
                  >Tóm tắt (abstract)</label
                >
                <textarea
                  v-model.trim="form.abstract"
                  :disabled="readOnly"
                  placeholder="Tóm tắt ngắn nội dung tài liệu (tuỳ chọn)"
                  class="mt-1 min-h-24 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div class="md:col-span-2">
                <div class="mb-2 flex items-center justify-between gap-2">
                  <label class="text-xs font-medium text-slate-600"
                    >Nhà xuất bản <span class="text-rose-600">*</span></label
                  >
                  <button
                    v-if="!readOnly"
                    type="button"
                    class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                    @click="toggleManualPublisherForm"
                  >
                    {{
                      showManualPublisherForm
                        ? "Tắt nhập nhà xuất bản ngoài danh mục"
                        : "Nhập nhà xuất bản ngoài danh mục"
                    }}
                  </button>
                </div>

                <PublisherSelect
                  v-model="selectedPublisherId"
                  v-model:publisherName="form.publisher"
                  :disabled="readOnly || showManualPublisherForm"
                  label=""
                  :required="true"
                  hint="Gợi ý tìm và chọn từ danh mục nhà xuất bản."
                  :search-fn="searchPublishers"
                  @select="onPublisherSelect"
                  @clear="onPublisherClear"
                />

                <div
                  v-if="showManualPublisherForm"
                  class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
                >
                  <div class="grid gap-3 md:grid-cols-2">
                    <div class="md:col-span-2">
                      <label class="text-xs font-medium text-slate-600"
                        >Tên nhà xuất bản ngoài danh mục
                        <span class="text-rose-600">*</span></label
                      >
                      <input
                        v-model.trim="form.publisher"
                        :disabled="readOnly"
                        maxlength="255"
                        placeholder="VD: Nhà xuất bản ABC"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      />
                    </div>

                    <div>
                      <label class="text-xs font-medium text-slate-600"
                        >Địa chỉ <span class="text-rose-600">*</span></label
                      >
                      <input
                        v-model.trim="form.publisherAddress"
                        :disabled="readOnly"
                        maxlength="255"
                        placeholder="VD: 280 An Dương Vương, Q.5, TP.HCM"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      />
                    </div>

                    <div>
                      <label class="text-xs font-medium text-slate-600"
                        >Điện thoại <span class="text-rose-600">*</span></label
                      >
                      <input
                        v-model.trim="form.publisherPhone"
                        :disabled="readOnly"
                        maxlength="50"
                        placeholder="VD: 02838293829"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      />
                    </div>

                    <div>
                      <label class="text-xs font-medium text-slate-600"
                        >Email <span class="text-rose-600">*</span></label
                      >
                      <input
                        v-model.trim="form.publisherEmail"
                        :disabled="readOnly"
                        maxlength="100"
                        placeholder="VD: contact@nxb.vn"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      />
                    </div>

                    <div>
                      <label class="text-xs font-medium text-slate-600"
                        >Website <span class="text-rose-600">*</span></label
                      >
                      <input
                        v-model.trim="form.publisherWebsite"
                        :disabled="readOnly"
                        maxlength="255"
                        placeholder="VD: https://nxb.vn"
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      />
                    </div>
                  </div>
                  <p class="mt-2 text-xs text-slate-500">
                    Khi gửi duyệt, tên mới sẽ tự động được tạo đề xuất để quản
                    trị viên thêm vào danh mục.
                  </p>
                </div>
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
import PublisherSelect from "../../shared/components/PublisherSelect.vue";

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
  search_publishers,
} from "../../shared/services/catalogs.service";
import {
  fetch_activity,
  fetch_current_lecturer_option,
  submit_activity,
  upsert_activity_base,
  upsert_members,
  upsert_book_details,
  list_evidence_files,
  upload_evidence_file,
  delete_evidence_file,
  list_evidence_links,
  add_evidence_link,
  delete_evidence_link,
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
const selectedPublisherId = ref<number | null>(null);
const showManualPublisherForm = ref(false);

const currentLecturerId = ref<number>(0);

const form = reactive<BookDeclarationFormModel>({
  activityId: null,
  academicYearId: null,
  kindId: 0,
  typeId: null,
  title: "",
  abstract: "",
  notes: "",
  publisher: "",
  publisherAddress: "",
  publisherPhone: "",
  publisherEmail: "",
  publisherWebsite: "",
  year: null,
  isbn: "",
  pages: null,
  approvalDecisionNo: "",
  approvalDecisionDate: null,
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

function isValidEmail(value: string) {
  const normalized = value.trim();
  if (!normalized) return false;
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized);
}

const canSubmit = computed(() => {
  if (!form.academicYearId) return false;
  if (!form.typeId) return false;
  if (!form.title.trim()) return false;
  if (!form.publisher.trim()) return false; // book_details.publisher required
  if (!showManualPublisherForm.value && !selectedPublisherId.value) return false;
  if (showManualPublisherForm.value) {
    if (!form.publisherAddress.trim()) return false;
    if (!form.publisherPhone.trim()) return false;
    if (!form.publisherWebsite.trim()) return false;
    if (!isValidEmail(form.publisherEmail)) return false;
  }
  const validMembers = form.members.filter((m) => {
    const hasRole = typeof m.member_role_id === "number";
    if (!hasRole) return false;
    if (m.is_external) return Boolean(m.external_full_name?.trim());
    return typeof m.lecturer_id === "number";
  });
  if (validMembers.length === 0) return false;
  if (chiefEditorWarning.value) return false;
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

async function loadCatalogs() {
  const [currentLecturerOption, lecturerOptions, years, kinds, roles, fileTypes] =
    await Promise.all([
      fetch_current_lecturer_option(),
      search_lecturer_options(""),
      fetch_academic_years(),
      fetch_activity_kinds(),
      fetch_member_roles(),
      fetch_evidence_file_types("book"),
    ]);
  currentLecturerId.value = currentLecturerOption?.id ?? 0;
  lecturers.value = currentLecturerOption
    ? [
        currentLecturerOption,
        ...lecturerOptions.filter(
          (lecturer) => lecturer.id !== currentLecturerOption.id,
        ),
      ]
    : lecturerOptions;
  academicYears.value = years;
  memberRoles.value = roles;
  evidenceFileTypes.value = fileTypes;

  kindId.value = kinds.find((k) => k.code === "book")?.id ?? 0;
  form.kindId = kindId.value;

  types.value = await fetch_activity_types_by_kind(kindId.value);

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

async function searchPublishers(q: string) {
  return await search_publishers(q);
}

function clearPublisherSelection() {
  selectedPublisherId.value = null;
  form.publisher = "";
  form.publisherAddress = "";
  form.publisherPhone = "";
  form.publisherEmail = "";
  form.publisherWebsite = "";
}

function onPublisherSelect() {
  showManualPublisherForm.value = false;
  form.publisherAddress = "";
  form.publisherPhone = "";
  form.publisherEmail = "";
  form.publisherWebsite = "";
}

function onPublisherClear() {
  selectedPublisherId.value = null;
}

function toggleManualPublisherForm() {
  showManualPublisherForm.value = !showManualPublisherForm.value;
  if (showManualPublisherForm.value) {
    clearPublisherSelection();
  }
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

    if (data.detail_kind === "book_details" && data.detail) {
      const detail = data.detail as any;
      form.publisher = detail.publisher ?? "";
      selectedPublisherId.value = null;
      form.publisherAddress = detail.publisher_address ?? "";
      form.publisherPhone = detail.publisher_phone ?? "";
      form.publisherEmail = detail.publisher_email ?? "";
      form.publisherWebsite = detail.publisher_website ?? "";

      const publisherName = form.publisher.trim();
      if (publisherName) {
        const options = await searchPublishers(publisherName);
        const matched = options.find(
          (option) =>
            option.name.trim().toLowerCase() === publisherName.toLowerCase(),
        );
        selectedPublisherId.value = matched?.id ?? null;
      }

      showManualPublisherForm.value =
        !selectedPublisherId.value &&
        Boolean(
          publisherName ||
            form.publisherAddress.trim() ||
            form.publisherPhone.trim() ||
            form.publisherEmail.trim() ||
            form.publisherWebsite.trim(),
        );
      form.approvalDecisionNo = detail.approval_decision_no ?? "";
      form.approvalDecisionDate = detail.approval_decision_date ?? null;
      form.isbn = detail.isbn ?? "";
      form.pages = detail.pages ?? null;
      form.year = detail.year ?? null;
    }

    form.members = (data.members ?? []).map((member) => ({
      lecturer_id: member.lecturer_id ?? null,
      member_role_id: member.member_role_id,
      member_role_code: member.member_role_code ?? null,
      is_external: !!member.is_external,
      external_full_name: member.external_full_name ?? null,
      external_department_name: member.external_department_name ?? null,
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
      if (!form.academicYearId) {
        throw new Error("Vui lòng chọn năm học trước khi lưu bản nháp.");
      }

      const saved = await upsert_activity_base({
        id: form.activityId ?? undefined,
        owner_lecturer_id: currentLecturerId.value,
        kind_id: form.kindId,
        type_id: form.typeId,
        academic_year_id: form.academicYearId,
        status_id: 100,
        title: form.title,
        abstract: form.abstract || null,
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
        publisher_address: form.publisherAddress || null,
        publisher_phone: form.publisherPhone || null,
        publisher_email: form.publisherEmail || null,
        publisher_website: form.publisherWebsite || null,
        approval_decision_no: form.approvalDecisionNo || null,
        approval_decision_date: form.approvalDecisionDate || null,
        isbn: form.isbn || null,
        pages: form.pages ?? null,
        year: form.year ?? null,
      });

      const upsertList = form.members
        .filter((m) => {
          if (typeof m.member_role_id !== "number") return false;
          if (m.is_external) return Boolean(m.external_full_name?.trim());
          return typeof m.lecturer_id === "number";
        })
        .map((m) => ({
          lecturer_id: m.is_external ? null : (m.lecturer_id as number),
          member_role_id: m.member_role_id as number,
          is_external: !!m.is_external,
          external_full_name: m.is_external
            ? (m.external_full_name?.trim() ?? null)
            : null,
          external_department_name: m.is_external
            ? (m.external_department_name?.trim() ?? null)
            : null,
          contribution_share: null,
        }));
      await upsert_members(saved.id, upsertList);

      await persistEvidenceDraft(saved.id);
      await loadDraftFromQuery();
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
</script>
