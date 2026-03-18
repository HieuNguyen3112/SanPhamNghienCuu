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
                  Đề tài cấp Trường: không có thành viên thì Chủ nhiệm 600 giờ;
                  có thành viên thì Chủ nhiệm 360 giờ, nhóm thành viên chia đều
                  240 giờ.
                </li>
                <li>
                  Đề tài cấp Bộ: không có thành viên thì Chủ nhiệm 720 giờ; có
                  thành viên thì Chủ nhiệm 240 giờ, nhóm thành viên chia đều 480
                  giờ.
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
                  >Cấp đề tài <span class="text-rose-600">*</span></label
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
                  Nhập đúng tên đầy đủ của đề tài.
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Tóm tắt (abstract)</label
                >
                <textarea
                  v-model.trim="form.abstract"
                  :disabled="readOnly"
                  placeholder="Tóm tắt ngắn mục tiêu/nội dung đề tài (tuỳ chọn)"
                  class="mt-1 min-h-24 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Có thể mô tả ngắn mục tiêu và phạm vi đề tài.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Mã đề tài <span class="text-rose-600">*</span></label
                >
                <input
                  v-model.trim="form.projectCode"
                  :disabled="readOnly"
                  maxlength="100"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: DT-2025-012"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Bắt buộc: mã nhận diện đề tài.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Loại hình đề tài</label
                >
                <input
                  v-model.trim="form.projectCategory"
                  :disabled="readOnly"
                  maxlength="255"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: Nghiên cứu ứng dụng / Nghiên cứu cơ bản"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Nên có: dùng để phân nhóm đề tài.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Đơn vị thực hiện <span class="text-rose-600">*</span></label
                >
                <input
                  v-model.trim="form.implementingUnit"
                  :disabled="readOnly"
                  maxlength="255"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: Khoa Công nghệ thông tin"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Bắt buộc: đơn vị trực tiếp triển khai.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Lĩnh vực</label
                >
                <input
                  v-model.trim="form.researchField"
                  :disabled="readOnly"
                  maxlength="255"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: Trí tuệ nhân tạo"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Nên có: giúp tra cứu theo lĩnh vực nghiên cứu.
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
                  Nếu có quyết định giao đề tài thì nhập số quyết định.
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
                  Ngày ban hành quyết định (nếu có).
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Kinh phí</label
                >
                <input
                  v-model="fundingInput"
                  type="text"
                  inputmode="numeric"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: 150000000 (VND) (nếu có)"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Nhập nhanh dạng số, hệ thống tự định dạng: 1.500.000.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Thời gian bắt đầu <span class="text-rose-600">*</span></label
                >
                <input
                  v-model="form.startDate"
                  type="date"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Chọn ngày bắt đầu thực hiện đề tài.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Thời gian kết thúc
                  <span class="text-rose-600">*</span></label
                >
                <input
                  v-model="form.endDate"
                  type="date"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Chọn ngày kết thúc hoặc nghiệm thu dự kiến.
                </div>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Tình trạng <span class="text-rose-600">*</span></label
                >
                <select
                  v-model="form.projectStatus"
                  :disabled="readOnly"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                >
                  <option value="">— Chọn tình trạng —</option>
                  <option
                    v-for="status in PROJECT_STATUS_OPTIONS"
                    :key="status.value"
                    :value="status.value"
                  >
                    {{ status.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="text-xs font-medium text-slate-600"
                  >Chủ nhiệm <span class="text-rose-600">*</span></label
                >
                <input
                  :value="principalDisplayName"
                  disabled
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Chọn vai trò <b>Chủ nhiệm</b> trong danh sách thành viên ở
                  phần bên dưới.
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Mục tiêu</label
                >
                <textarea
                  v-model.trim="form.objectives"
                  :disabled="readOnly"
                  placeholder="Nêu mục tiêu chính của đề tài (khuyến nghị)"
                  class="mt-1 min-h-20 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Nội dung</label
                >
                <textarea
                  v-model.trim="form.contentSummary"
                  :disabled="readOnly"
                  placeholder="Mô tả nội dung chính của đề tài (khuyến nghị)"
                  class="mt-1 min-h-20 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Kết quả chính <span class="text-rose-600">*</span></label
                >
                <textarea
                  v-model.trim="form.mainResults"
                  :disabled="readOnly"
                  placeholder="Tóm tắt kết quả chính đạt được của đề tài"
                  class="mt-1 min-h-20 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                />
              </div>

              <div class="md:col-span-2">
                <label class="text-xs font-medium text-slate-600"
                  >Địa chỉ ứng dụng</label
                >
                <input
                  v-model.trim="form.applicationAddress"
                  :disabled="readOnly"
                  maxlength="500"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  placeholder="VD: Trung tâm A/Bộ phận B hoặc URL ứng dụng"
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
                  placeholder="VD: Đề tài đã nghiệm thu/đang triển khai... (tuỳ chọn)"
                />
                <div class="mt-1 text-xs text-slate-500">
                  Ghi chú thêm nếu cần.
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
          <div
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
          >
            <div class="text-sm font-semibold text-slate-900">
              D. Minh chứng và link liên quan
            </div>
            <div class="mt-1 text-xs text-slate-500">
              <span class="text-rose-600">*</span> Bắt buộc có ít nhất 1 file
              minh chứng. Link liên quan là khuyến nghị.
            </div>
          </div>
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
const PROJECT_STATUS_OPTIONS = [
  { value: "planning", label: "Chuẩn bị" },
  { value: "ongoing", label: "Đang thực hiện" },
  { value: "completed", label: "Đã hoàn thành" },
  { value: "accepted", label: "Đã nghiệm thu/công nhận" },
] as const;

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
  abstract: "",
  notes: "",
  startDate: null,
  endDate: null,
  projectCode: "",
  projectCategory: "",
  researchField: "",
  objectives: "",
  contentSummary: "",
  applicationAddress: "",
  implementingUnit: "",
  projectStatus: "",
  mainResults: "",
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
const hasPrincipalMember = computed(() =>
  form.members.some((member) => {
    if (typeof member.member_role_id !== "number") return false;
    return memberRoleCodeById.value[member.member_role_id] === "principal";
  }),
);
const fundingInput = computed({
  get: () => {
    if (form.funding === null || !Number.isFinite(form.funding)) return "";
    return new Intl.NumberFormat("vi-VN", {
      maximumFractionDigits: 0,
    }).format(Math.round(form.funding));
  },
  set: (value: string) => {
    const digits = String(value ?? "").replace(/[^\d]/g, "");
    form.funding = digits ? Number(digits) : null;
  },
});
const principalDisplayName = computed(() => {
  const principal = form.members.find((member) => {
    if (typeof member.member_role_id !== "number") return false;
    return memberRoleCodeById.value[member.member_role_id] === "principal";
  });

  if (!principal) return "Chưa chọn chủ nhiệm";
  if (principal.is_external) {
    return principal.external_full_name?.trim() || "Chủ nhiệm ngoài trường";
  }

  const lecturerId = principal.lecturer_id;
  if (typeof lecturerId !== "number") return "Chưa chọn chủ nhiệm";
  return lecturerNameById.value[lecturerId] ?? `lecturer_id=${lecturerId}`;
});

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

function normalizeServerPreview(
  preview: ProjectHoursPreviewResponseDto,
): ProjectHoursComputationResult {
  const distribution: ProjectHoursComputationResult["distribution"] = (
    preview.members ?? []
  ).map((member) => ({
    lecturer_id: member.lecturer_id,
    lecturer_name: member.lecturer_full_name,
    member_role_id:
      memberRoles.value.find((role) => role.code === member.member_role_code)
        ?.id ?? 0,
    member_role_name: member.member_role_name ?? "—",
    hours: member.hours_assigned ?? 0,
  }));

  const currentLecturerHours =
    distribution.find(
      (member) => member.lecturer_id === currentLecturerId.value,
    )?.hours ?? 0;

  return {
    total_hours: preview.formula.total_hours_allocated ?? 0,
    current_lecturer_hours: currentLecturerHours,
    distribution,
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
  if (!form.projectCode.trim()) return false;
  if (!form.implementingUnit.trim()) return false;
  if (!form.projectStatus.trim()) return false;
  if (!form.mainResults.trim()) return false;
  if (!form.startDate || !form.endDate) return false;
  if (new Date(form.endDate).getTime() < new Date(form.startDate).getTime()) {
    return false;
  }
  // members must be valid
  const validMembers = form.members.filter((m) => {
    const hasRole = typeof m.member_role_id === "number";
    if (!hasRole) return false;
    if (m.is_external) return Boolean(m.external_full_name?.trim());
    return typeof m.lecturer_id === "number";
  });
  if (validMembers.length === 0) return false;
  if (!hasPrincipalMember.value) return false;
  // evidence pending files should have file_type_id selected (if any)
  const invalidPending = pendingEvidenceFiles.value.some(
    (p: any) => !p.file_type_id,
  );
  if (invalidPending) return false;
  const hasEvidenceFile =
    existingEvidence.value.length > 0 ||
    pendingEvidenceFiles.value.some((p: any) => p?.file && p?.file_type_id);
  if (!hasEvidenceFile) return false;
  const invalidPendingLinks = pendingEvidenceLinks.value.some(
    (l: any) => !String(l?.url ?? "").trim() || !l?.file_type_id,
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
  const [
    currentLecturerOption,
    years,
    kinds,
    roles,
    fileTypes,
    initialLecturers,
  ] = await Promise.all([
    fetch_current_lecturer_option(),
    fetch_academic_years(),
    fetch_activity_kinds(),
    fetch_member_roles(),
    fetch_evidence_file_types("project"),
    search_lecturer_options(""),
  ]);
  currentLecturerId.value = currentLecturerOption?.id ?? 0;

  const baseLecturers = currentLecturerOption
    ? [
        currentLecturerOption,
        ...initialLecturers.filter(
          (lecturer) => lecturer.id !== currentLecturerOption.id,
        ),
      ]
    : initialLecturers;
  lecturers.value = mergeLecturerOptionsFromMembers(
    baseLecturers,
    form.members,
  );
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

  // Mặc định form mới luôn có người kê khai trong danh sách thành viên.
  if (
    !form.activityId &&
    form.members.length === 0 &&
    currentLecturerId.value
  ) {
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
    form.startDate = activity.start_date ?? null;
    form.endDate = activity.end_date ?? null;

    if (data.detail_kind === "project_details" && data.detail) {
      const detail = data.detail as any;
      form.projectCode = detail.project_code ?? "";
      form.projectCategory = detail.project_category ?? "";
      form.researchField = detail.research_field ?? "";
      form.objectives = detail.objectives ?? "";
      form.contentSummary = detail.content_summary ?? "";
      form.applicationAddress = detail.application_address ?? "";
      form.implementingUnit = detail.implementing_unit ?? "";
      form.projectStatus = detail.project_status ?? "";
      form.mainResults = detail.main_results ?? "";
      form.decisionNo = detail.decision_no ?? "";
      form.decisionDate = detail.decision_date ?? null;
      form.funding = detail.funding ?? null;
      form.startDate = detail.start_month ?? activity.start_date ?? null;
      form.endDate = detail.end_month ?? activity.end_date ?? null;
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

      // upsert base research_activities
      const saved = await upsert_activity_base({
        id: form.activityId ?? undefined,
        owner_lecturer_id: currentLecturerId.value,
        kind_id: form.kindId,
        type_id: form.typeId,
        academic_year_id: form.academicYearId,
        status_id: 100, // mock draft status id (from mock_statuses); TODO: lookup by code
        title: form.title,
        abstract: form.abstract || null,
        start_date: form.startDate,
        end_date: form.endDate,
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
        project_code: form.projectCode,
        project_category: form.projectCategory || null,
        research_field: form.researchField || null,
        objectives: form.objectives || null,
        content_summary: form.contentSummary || null,
        application_address: form.applicationAddress || null,
        implementing_unit: form.implementingUnit,
        project_status: form.projectStatus,
        main_results: form.mainResults,
        decision_no: form.decisionNo || null,
        decision_date: form.decisionDate || null,
        funding: form.funding ?? null,
        start_month: form.startDate as string,
        end_month: form.endDate as string,
      });

      // upsert members
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

onBeforeUnmount(() => {
  if (!previewDebounceTimer) return;
  clearTimeout(previewDebounceTimer);
  previewDebounceTimer = null;
});

onMounted(async () => {
  await runPageLoad(
    async () => {
      await loadCatalogs();
      await loadDraftFromQuery();
      allowPreviewAutoRefresh.value = true;
      scheduleProjectPreviewRefresh();
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
