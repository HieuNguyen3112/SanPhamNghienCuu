<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <DeclarationFormShell
        title="Kê khai Bài báo khoa học"
        description="Chọn loại bài báo để xác định giờ chuẩn; giờ chia đều cho số tác giả."
        :icon="FileText"
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
            <div class="font-semibold text-slate-900">Bảng giờ chuẩn</div>
            <ul class="mt-1 list-disc space-y-1 pl-5 text-slate-600">
              <li>HDGSNN 1–2 điểm: 900 giờ</li>
              <li>HDGSNN ≤ 1 điểm: 600 giờ</li>
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
                <!-- NOTE: bỏ .number vì có option null -->
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

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Loại bài báo</label
                >
                <!-- NOTE: bỏ .number vì có option null -->
                <select
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  v-model="form.typeId"
                  :disabled="readOnly"
                >
                  <option :value="null">— Chọn loại —</option>
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
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div class="md:col-span-2">
                <JournalSelect
                  v-model="(form as any).journalId"
                  v-model:journalName="form.journalName"
                  v-model:issn="form.issn"
                  :disabled="readOnly"
                  label="Tạp chí / Kỷ yếu"
                  hint="Gõ để tìm và chọn từ danh mục (do QLKH/Hội đồng nhập)."
                  :search-fn="searchJournals"
                  @select="() => {}"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600">ISSN</label>
                <input
                  v-model.trim="form.issn"
                  :disabled="readOnly"
                  maxlength="50"
                  placeholder="VD: 1234-5678"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600">DOI</label>
                <input
                  v-model.trim="form.doi"
                  :disabled="readOnly"
                  maxlength="100"
                  placeholder="VD: 10.1234/abcd.2025.001"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
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
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  />
                  <input
                    v-model.number="form.pageEnd"
                    type="number"
                    min="1"
                    :disabled="readOnly"
                    placeholder="VD: 27"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
            :hoursByLecturerId="hoursByLecturerId"
            @request-search="onSearchLecturers"
          />

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
import { computed, onMounted, reactive, ref, type ComputedRef } from "vue";
import { useRoute, useRouter } from "vue-router";
import { FileText } from "lucide-vue-next";
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
} from "../../shared/contracts/declarationSharedContract";
import { useDeclarationFormShell } from "../../shared/composables/useDeclarationFormShell";
import {
  fetch_academic_years,
  fetch_activity_kinds,
  fetch_activity_types_by_kind,
  fetch_member_roles,
  fetch_evidence_file_types,
  search_lecturer_options,
  fetch_activity_statuses,
} from "../../shared/services/catalogs.service";
import {
  fetch_activity,
  fetch_current_lecturer_id,
  submit_activity,
  upsert_activity_base,
  upsert_members,
  upsert_paper_details,
  list_evidence_files,
} from "../../shared/services/declarations.service";
import {
  computeArticleHours,
  type ArticleDeclarationFormModel,
  articleBaseHoursByTypeCode,
  articleAllowedMemberRoleCodes,
} from "../ArticleDeclarationContract";

const route = useRoute();
const router = useRouter();

// const academicYears = ref(await Promise.resolve([] as any[]));
const academicYears = ref<AcademicYearDto[]>([]);
const memberRoles = ref<MemberRoleDto[]>([]);
const evidenceFileTypes = ref<EvidenceFileTypeDto[]>([]);
const lecturers = ref<LecturerOptionDto[]>([]);
const types = ref<ActivityTypeDto[]>([]);
const kindId = ref<number>(0);
const submittedStatusId = ref<number>(0);

const currentLecturerId = ref<number>(0);

const form = reactive<ArticleDeclarationFormModel>({
  activityId: null,
  academicYearId: null,
  kindId: 0,
  typeId: null,
  title: "",
  abstract: "",
  notes: "",
  journalName: "",
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
const filteredMemberRoles = computed(() => {
  const preferredCodes = new Set<string>(
    articleAllowedMemberRoleCodes as readonly string[]
  );
  const preferred = memberRoles.value.filter((r) =>
    preferredCodes.has(r.code)
  );
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
  })
);
const existingEvidence = ref<EvidenceFileDto[]>([]);
const pendingEvidenceFiles = ref<any[]>([]);
const pendingEvidenceLinks = ref<any[]>([]);

const typeCodeById = computed(() =>
  Object.fromEntries(types.value.map((t) => [t.id, t.code]))
);
const lecturerNameById = computed(() =>
  Object.fromEntries(lecturers.value.map((l) => [l.id, l.full_name]))
);

const hours = computed(() =>
  computeArticleHours(form, {
    typeCodeById: typeCodeById.value,
    lecturerNameById: lecturerNameById.value,
    memberRoleNameById: memberRoleNameById.value,
    currentLecturerId: currentLecturerId.value,
  })
);

const hoursByLecturerId = computed(() => {
  const map: Record<number, number> = {};
  for (const d of hours.value.distribution) map[d.lecturer_id] = d.hours;
  return map;
});

const baseHoursText = computed(() => {
  const code = form.typeId ? typeCodeById.value[form.typeId] : null;
  const base = code ? articleBaseHoursByTypeCode[code] ?? 0 : 0;
  return `${base} giờ`;
});

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
  const validMembers = form.members.filter(
    (m) =>
      typeof m.lecturer_id === "number" && typeof m.member_role_id === "number"
  );
  if (validMembers.length === 0) return false;
  const invalidPending = pendingEvidenceFiles.value.some(
    (p: any) => !p.file_type_id
  );
  if (invalidPending) return false;
  return true;
});

async function loadCatalogs() {
  currentLecturerId.value = (await fetch_current_lecturer_id()) ?? 0;
  const [years, kinds, roles, fileTypes, statuses] = await Promise.all([
    fetch_academic_years(),
    fetch_activity_kinds(),
    fetch_member_roles(),
    fetch_evidence_file_types(),
    fetch_activity_statuses(),
  ]);
  academicYears.value = years;
  memberRoles.value = roles;
  evidenceFileTypes.value = fileTypes;

  kindId.value = kinds.find((k) => k.code === "paper")?.id ?? 0;
  form.kindId = kindId.value;

  types.value = await fetch_activity_types_by_kind(kindId.value);
  submittedStatusId.value =
    statuses.find((s) => s.code === "submitted")?.id ?? 0;

  lecturers.value = await search_lecturer_options("");

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
  }

  form.members = (data.members ?? []).map((m) => ({
    lecturer_id: m.lecturer_id,
    member_role_id: m.member_role_id,
    member_role_code: m.member_role_code ?? null,
  }));

  existingEvidence.value = data.evidence_files ?? [];

  const statusCode = (activity.status_code ?? "draft") as any;
  shell.status.value = mapStatusCodeToUi(statusCode) ?? "DRAFT";
}

async function onSearchLecturers(q: string) {
  lecturers.value = await search_lecturer_options(q);
}

async function onRemoveExistingEvidence(_id: number) {
  existingEvidence.value = existingEvidence.value.filter((x) => x.id !== _id);
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
      status_id: 100, // mock draft status id
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
    });

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

    existingEvidence.value = await list_evidence_files(saved.id);

    if (pendingEvidenceFiles.value.length > 0) {
      throw new Error("TODO (P0): Upload evidence files endpoint chưa có.");
    }
    if (pendingEvidenceLinks.value.length > 0) {
      throw new Error("TODO (P0): Evidence links chưa có backend support.");
    }
  },
  on_submit: async () => {
    if (!form.activityId) {
      await shell.save_draft();
    }
    if (!form.activityId) return;
    await submit_activity(form.activityId);
  },
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

onMounted(async () => {
  await loadCatalogs();
  await loadDraftFromQuery();
});
type JournalRank = "Q1" | "Q2" | "Q3" | "Q4" | "Q5" | "OTHER";

async function searchJournals(q: string) {
  const list: Array<{
    id: number;
    name: string;
    address: string;
    issn: string | null;
    current_rank: JournalRank | null;
    current_rank_effective_from: string | null;
    is_active: boolean;
  }> = [
    {
      id: 1,
      name: "Tạp chí Khoa học Trường X",
      address: "123 Đường ABC, Q.1, TP.HCM",
      issn: "1234-5678",
      current_rank: "Q2",
      current_rank_effective_from: "2025-01-01",
      is_active: true,
    },
    {
      id: 2,
      name: "Proceedings of Education Data Science",
      address: "Online / International",
      issn: null,
      current_rank: "OTHER",
      current_rank_effective_from: "2024-09-01",
      is_active: true,
    },
  ];

  const qq = (q ?? "").trim().toLowerCase();
  if (!qq) return list;
  return list.filter((x) => x.name.toLowerCase().includes(qq));
}
</script>
