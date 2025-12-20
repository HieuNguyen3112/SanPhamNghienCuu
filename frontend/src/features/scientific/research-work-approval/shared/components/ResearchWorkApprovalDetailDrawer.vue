<template>
  <div v-if="isOpen" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

    <aside
      class="absolute right-0 top-0 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[440px] lg:w-[560px] xl:w-[640px]"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex h-full flex-col">
        <!-- Header -->
        <div class="border-b border-slate-200 px-4 py-4 lg:px-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <h3 class="truncate text-sm font-semibold text-slate-900">
                  {{ drawerTitle }}
                </h3>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs font-medium text-slate-600">{{
                  drawerSubtitle
                }}</span>
              </div>

              <p class="mt-1 text-xs text-slate-600">
                <!-- WHY -->
                {{ drawerHelperText }}
              </p>
            </div>

            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              @click="emit('close')"
            >
              Đóng
            </button>
          </div>

          <div v-if="selectedResearchWorkApprovalEntry" class="mt-3">
            <div class="flex flex-wrap items-center gap-2">
              <span
                :class="
                  mapApprovalStatusToBadgeClass(
                    selectedResearchWorkApprovalEntry.approvalStatus
                  )
                "
              >
                {{
                  mapApprovalStatusToDisplayName(
                    selectedResearchWorkApprovalEntry.approvalStatus
                  )
                }}
              </span>

              <span class="text-xs text-slate-500">
                Năm học {{ selectedResearchWorkApprovalEntry.academicYear }}
              </span>

              <span class="text-xs text-slate-500">•</span>

              <span class="text-xs font-medium text-slate-700">
                {{
                  mapResearchWorkTypeToDisplayName(
                    selectedResearchWorkApprovalEntry.researchWorkType
                  )
                }}
              </span>
            </div>
          </div>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-4 py-4 lg:px-5">
          <div v-if="selectedResearchWorkApprovalEntry" class="space-y-4">
            <!-- 1. Thông tin chung -->
            <section
              class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4"
            >
              <div class="text-xs font-semibold text-slate-700">
                Thông tin chung
              </div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ selectedResearchWorkApprovalEntry.researchWorkTitle }}
              </div>

              <div class="mt-3 grid grid-cols-1 gap-2">
                <div
                  class="flex items-start justify-between gap-3 rounded-xl bg-white px-3 py-2"
                >
                  <div class="text-xs font-medium text-slate-600">
                    Người kê khai
                  </div>
                  <div class="text-sm font-semibold text-slate-900">
                    {{
                      selectedResearchWorkApprovalEntry.submittingLecturerDisplayName
                    }}
                  </div>
                </div>

                <div
                  class="flex items-start justify-between gap-3 rounded-xl bg-white px-3 py-2"
                >
                  <div class="text-xs font-medium text-slate-600">
                    Khoa / Đơn vị
                  </div>
                  <div class="text-sm font-semibold text-slate-900">
                    {{ selectedResearchWorkApprovalEntry.facultyDisplayName }}
                  </div>
                </div>

                <!-- Bảng tác giả -->
                <div
                  class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white"
                >
                  <div
                    class="flex items-center justify-between border-b border-slate-200 px-3 py-2"
                  >
                    <div class="text-xs font-semibold text-slate-700">
                      Tác giả
                    </div>
                    <div class="text-xs text-slate-500">
                      {{ authorRows.length }} thành viên
                    </div>
                  </div>

                  <div
                    v-if="authorRows.length === 0"
                    class="px-3 py-3 text-sm text-slate-600"
                  >
                    Không có tác giả.
                  </div>

                  <table v-else class="w-full text-left">
                    <thead class="bg-slate-50">
                      <tr class="text-xs font-semibold text-slate-600">
                        <th class="w-10 px-3 py-2">#</th>
                        <th class="px-3 py-2">Họ tên</th>
                        <th class="px-3 py-2">Vai trò</th>
                        <th class="px-3 py-2">Khoa/Đơn vị</th>
                      </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                      <tr
                        v-for="(a, i) in authorRows"
                        :key="a.authorIdentifier"
                        class="text-sm"
                      >
                        <td class="px-3 py-2 text-slate-500">{{ i + 1 }}</td>

                        <td class="px-3 py-2 font-semibold text-slate-900">
                          {{ a.authorDisplayName }}

                          <span
                            v-if="a.isSubmittingLecturer"
                            class="ml-2 inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-900"
                          >
                            Kê khai
                          </span>
                        </td>

                        <td class="px-3 py-2">
                          <span
                            v-if="a.isPrimaryAuthor"
                            class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-900"
                          >
                            Tác giả chính
                          </span>
                          <span
                            v-else
                            class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700"
                          >
                            Đồng tác giả
                          </span>
                        </td>

                        <td class="px-3 py-2 text-slate-700">
                          {{ a.authorFacultyDisplayName }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </section>

            <!-- 2. Minh chứng -->
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">
                  Minh chứng
                </div>
                <div class="text-xs text-slate-500">
                  {{
                    selectedResearchWorkApprovalEntry.evidenceAttachmentList
                      .length
                  }}
                  tệp
                </div>
              </div>

              <div
                v-if="
                  selectedResearchWorkApprovalEntry.evidenceAttachmentList
                    .length === 0
                "
                class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
              >
                Chưa có minh chứng.
              </div>

              <div v-else class="mt-3 space-y-2">
                <a
                  v-for="evidenceAttachment in selectedResearchWorkApprovalEntry.evidenceAttachmentList"
                  :key="evidenceAttachment.evidenceAttachmentIdentifier"
                  :href="evidenceAttachment.evidenceAttachmentPreviewUrl"
                  class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/40 px-3 py-2 hover:bg-slate-50"
                >
                  <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-slate-900">
                      {{ evidenceAttachment.evidenceAttachmentDisplayName }}
                    </div>
                    <div class="mt-0.5 text-xs text-slate-600">
                      Định dạng:
                      {{ evidenceAttachment.evidenceAttachmentFileType }}
                    </div>
                  </div>
                  <span class="text-xs font-semibold text-slate-600">Xem</span>
                </a>
              </div>
            </section>

            <!-- 3. Giờ NCKH -->
            <section
              :class="
                canFinalizeHours
                  ? 'rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4'
                  : 'rounded-2xl border border-slate-200 bg-white p-4'
              "
            >
              <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900">
                  Giờ NCKH theo thành viên
                </div>

                <span
                  v-if="canFinalizeHours"
                  class="inline-flex items-center rounded-full border border-indigo-200 bg-white px-2 py-0.5 text-xs font-semibold text-indigo-900"
                >
                  Cấp trường chốt giờ
                </span>

                <span
                  v-else
                  class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700"
                >
                  Cấp khoa xác minh hồ sơ
                </span>
              </div>

              <div
                class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white"
              >
                <div
                  class="flex items-center justify-between border-b border-slate-200 px-3 py-2"
                >
                  <div class="text-xs font-semibold text-slate-700">
                    Bảng phân bổ giờ
                  </div>
                  <div class="text-xs text-slate-500">
                    {{ authorHourRows.length }} thành viên
                  </div>
                </div>

                <div
                  v-if="authorHourRows.length === 0"
                  class="px-3 py-3 text-sm text-slate-600"
                >
                  Chưa có danh sách tác giả.
                </div>

                <table v-else class="w-full text-left">
                  <thead class="bg-slate-50">
                    <tr class="text-xs font-semibold text-slate-600">
                      <th class="w-10 px-3 py-2">#</th>
                      <th class="px-3 py-2">Thành viên</th>
                      <th class="px-3 py-2">Vai trò</th>
                      <th class="px-3 py-2">Giờ kê khai</th>
                      <th class="px-3 py-2">Giờ theo QĐ</th>
                      <th class="px-3 py-2">Giờ chính thức</th>
                    </tr>
                  </thead>

                  <tbody class="divide-y divide-slate-200">
                    <tr
                      v-for="(a, i) in authorHourRows"
                      :key="a.authorIdentifier"
                      class="text-sm"
                    >
                      <td class="px-3 py-2 text-slate-500">{{ i + 1 }}</td>

                      <td class="px-3 py-2">
                        <div class="font-semibold text-slate-900">
                          {{ a.authorDisplayName }}
                          <span
                            v-if="a.isSubmittingLecturer"
                            class="ml-2 inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-900"
                          >
                            Kê khai
                          </span>
                        </div>
                        <div class="text-xs text-slate-500">
                          {{ a.authorFacultyDisplayName }}
                        </div>
                      </td>

                      <td class="px-3 py-2">
                        <span
                          v-if="a.isPrimaryAuthor"
                          class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-900"
                        >
                          Tác giả chính
                        </span>
                        <span
                          v-else
                          class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700"
                        >
                          Đồng tác giả
                        </span>
                      </td>

                      <td class="px-3 py-2 font-semibold text-slate-900">
                        {{ formatHourValue(a.declaredHours) }}
                      </td>

                      <td class="px-3 py-2 font-semibold text-slate-900">
                        {{ formatHourValue(a.recommendedHoursByPolicy) }}
                      </td>

                      <td class="px-3 py-2">
                        <template v-if="canFinalizeHours">
                          <input
                            v-model.number="
                              officialHoursDraftByAuthorId[a.authorIdentifier]
                            "
                            type="number"
                            min="0"
                            step="1"
                            class="w-28 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-sm font-semibold text-slate-900 focus:border-indigo-300 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                          />
                        </template>

                        <template v-else>
                          <div class="font-semibold text-slate-700">
                            {{
                              a.officialHours == null
                                ? "Chờ chốt"
                                : formatHourValue(a.officialHours)
                            }}
                          </div>
                        </template>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div
                v-if="canFinalizeHours"
                class="mt-3 flex items-center justify-between"
              >
                <div class="text-xs text-slate-600">Tổng giờ chính thức</div>
                <div class="text-sm font-semibold text-slate-900">
                  {{ formatIntegerValue(totalOfficialHours) }}
                </div>
              </div>

              <div
                v-if="
                  canFinalizeHours &&
                  shouldShowOfficialHoursValidationHint &&
                  !isOfficialHoursValid
                "
                class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800"
              >
                Vui lòng nhập giờ chính thức hợp lệ (>= 0) cho tất cả thành
                viên.
              </div>

              <p class="mt-2 text-xs text-slate-500">
                Giờ theo từng thành viên sẽ được hệ thống tính theo quy định
                (bài báo/đề tài...); hiện UI đã sẵn sàng để đổ dữ liệu.
              </p>
            </section>

            <!-- 4. Lịch sử duyệt -->
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="text-sm font-semibold text-slate-900">
                Lịch sử duyệt
              </div>
              <div class="mt-3 space-y-2">
                <div
                  v-for="history in selectedResearchWorkApprovalEntry.approvalHistoryList"
                  :key="history.historyIdentifier"
                  class="rounded-xl border border-slate-200 bg-white px-3 py-2"
                >
                  <div
                    class="flex flex-wrap items-center justify-between gap-2"
                  >
                    <div class="text-sm font-semibold text-slate-900">
                      {{ history.reviewLevelDisplayName }} —
                      {{ history.reviewActionDisplayName }}
                    </div>
                    <div class="text-xs text-slate-500">
                      {{
                        formatDateTimeDisplayValue(
                          history.reviewedAtDateTimeString
                        )
                      }}
                    </div>
                  </div>
                  <div class="mt-1 text-sm text-slate-700">
                    {{ history.reviewNote }}
                  </div>
                </div>
              </div>
            </section>

            <!-- Rejection Panel -->
            <section
              v-if="isRejectionPanelVisible"
              ref="rejectionPanelElement"
              class="rounded-2xl border border-rose-200 bg-rose-50/60 p-4"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-rose-900">
                    Lý do từ chối (bắt buộc)
                  </div>
                  <p class="mt-1 text-sm text-rose-900/90">
                    Lý do rõ ràng giúp bên dưới điều chỉnh nhanh và đối soát
                    minh bạch.
                  </p>
                </div>

                <button
                  type="button"
                  class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-900 hover:bg-rose-50"
                  @click="cancelRejectionFlow"
                >
                  Hủy
                </button>
              </div>

              <div class="mt-3 space-y-2">
                <label
                  v-for="option in rejectionReasonOptionList"
                  :key="option.value"
                  class="flex cursor-pointer items-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2"
                >
                  <input
                    class="h-4 w-4"
                    type="radio"
                    name="rejectionReason"
                    :value="option.value"
                    v-model="selectedRejectionReasonType"
                  />
                  <span class="text-sm font-medium text-slate-800">{{
                    option.label
                  }}</span>
                </label>

                <div v-if="isOtherRejectionReasonSelected" class="mt-2">
                  <label class="text-xs font-semibold text-rose-900"
                    >Nội dung bổ sung</label
                  >
                  <textarea
                    v-model="rejectionReasonDetail"
                    rows="3"
                    class="mt-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-rose-300 focus:ring-0"
                    placeholder="Nhập lý do cụ thể..."
                  />
                </div>

                <div
                  v-if="shouldShowRejectionValidationHint"
                  class="mt-3 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs text-rose-900"
                >
                  Vui lòng chọn lý do. Nếu chọn “Khác”, cần nhập nội dung chi
                  tiết (>= 5 ký tự).
                </div>
              </div>
            </section>
          </div>

          <div
            v-else
            class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-700"
          >
            Chưa chọn công trình.
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-slate-200 px-4 py-3 lg:px-5">
          <div class="flex flex-wrap items-center justify-end gap-2">
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              @click="emit('close')"
            >
              Đóng
            </button>

            <button
              type="button"
              class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-100 disabled:opacity-60"
              :disabled="isApproveActionDisabled"
              @click="approveSelectedResearchWork"
            >
              {{ primaryActionButtonLabel }}
            </button>

            <button
              type="button"
              class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-900 hover:bg-rose-100 disabled:opacity-60"
              :disabled="isRejectActionDisabled"
              @click="onRejectActionButtonClicked"
            >
              {{ rejectActionButtonDisplayName }}
            </button>
          </div>

          <p class="mt-2 text-xs text-slate-600">
            {{ footerHelperText }}
          </p>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch, toRefs } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalScopeIdentifier,
  ResearchWorkRejectionReasonType,
} from "../models/researchWorkApprovalModels";
import { useResearchWorkApprovalDisplayMapping } from "../composables/useResearchWorkApprovalDisplayMapping";

const props = defineProps<{
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;

  isOpen: boolean;
  selectedResearchWorkApprovalEntry: ResearchWorkApprovalEntry | null;

  isOfficialResearchHoursEditable: boolean;

  drawerTitle: string;
  drawerSubtitle: string;
  drawerHelperText: string;

  primaryActionButtonLabel: string;
  dangerActionButtonLabel: string;

  rejectionReasonOptionList: {
    value: ResearchWorkRejectionReasonType;
    label: string;
  }[];

  footerHelperText: string;
}>();

const emit = defineEmits<{
  (eventName: "close"): void;
  (
    eventName: "approve",
    payload: {
      researchWorkIdentifier: number;
      officialResearchHours: number | null; // tạm thời giữ API cũ
    }
  ): void;
  (
    eventName: "reject",
    payload: {
      researchWorkIdentifier: number;
      rejectionReasonType: ResearchWorkRejectionReasonType;
      rejectionReasonDetail: string | null;
    }
  ): void;
}>();

// giữ reactivity khi bóc props
const {
  approvalScopeIdentifier,
  isOpen,
  selectedResearchWorkApprovalEntry,
  isOfficialResearchHoursEditable,
  drawerTitle,
  drawerSubtitle,
  drawerHelperText,
  primaryActionButtonLabel,
  dangerActionButtonLabel,
  rejectionReasonOptionList,
  footerHelperText,
} = toRefs(props);

// mapping hiển thị
const displayMapping = useResearchWorkApprovalDisplayMapping({
  approvalScopeIdentifier: approvalScopeIdentifier.value,
});

const {
  mapResearchWorkTypeToDisplayName,
  mapApprovalStatusToDisplayName,
  mapApprovalStatusToBadgeClass,
  formatIntegerValue,
  formatDateTimeDisplayValue,
} = displayMapping;

// chỉ cấp trường mới được chốt giờ
const canFinalizeHours = computed(() => {
  return (
    approvalScopeIdentifier.value === "UNIVERSITY_SCOPE" &&
    isOfficialResearchHoursEditable.value
  );
});

/** =========================
 *  REJECT FLOW (CHỈ 1 LẦN)
 *  ========================= */
const isRejectionPanelVisible = ref(false);
const rejectionPanelElement = ref<HTMLElement | null>(null);
const selectedRejectionReasonType = ref<ResearchWorkRejectionReasonType | null>(
  null
);
const rejectionReasonDetail = ref("");
const shouldShowRejectionValidationHint = ref(false);

const isOtherRejectionReasonSelected = computed(
  () => selectedRejectionReasonType.value === "OTHER"
);

const isRejectionFormValid = computed(() => {
  if (!selectedRejectionReasonType.value) return false;
  if (selectedRejectionReasonType.value !== "OTHER") return true;
  return rejectionReasonDetail.value.trim().length >= 5;
});

const rejectActionButtonDisplayName = computed(() => {
  return isRejectionPanelVisible.value
    ? "Xác nhận từ chối"
    : dangerActionButtonLabel.value;
});

function resetRejectionFlowState(): void {
  isRejectionPanelVisible.value = false;
  selectedRejectionReasonType.value = null;
  rejectionReasonDetail.value = "";
  shouldShowRejectionValidationHint.value = false;
}

function cancelRejectionFlow(): void {
  resetRejectionFlowState();
}

/** =========================
 *  AUTHORS (table 1)
 *  ========================= */
type AuthorEntry = NonNullable<
  ResearchWorkApprovalEntry["researchWorkAuthorList"]
>[number];

const authorRows = computed<AuthorEntry[]>(() => {
  const list =
    selectedResearchWorkApprovalEntry.value?.researchWorkAuthorList ?? [];
  return [...list].sort(
    (a, b) =>
      Number(b.isPrimaryAuthor) - Number(a.isPrimaryAuthor) ||
      Number(b.isSubmittingLecturer) - Number(a.isSubmittingLecturer) ||
      a.authorDisplayName.localeCompare(b.authorDisplayName)
  );
});

/** =========================
 *  HOURS TABLE (table 2) - UI only
 *  ========================= */
type AuthorHourRow = AuthorEntry & {
  declaredHours: number | null;
  recommendedHoursByPolicy: number | null;
  officialHours: number | null;
};

const authorHourRows = computed<AuthorHourRow[]>(() => {
  // hiện mock author chưa có giờ => null; sau này đổ data thật
  return authorRows.value.map((a) => {
    const anyA = a as any;
    return {
      ...a,
      declaredHours: anyA.declaredHours ?? null,
      recommendedHoursByPolicy: anyA.recommendedHoursByPolicy ?? null,
      officialHours: anyA.officialHours ?? null,
    };
  });
});

function formatHourValue(v: number | null): string {
  return v == null ? "—" : formatIntegerValue(v);
}

/** draft nhập giờ chính thức theo từng người (chỉ cấp trường) */
const officialHoursDraftByAuthorId = ref<Record<number, number | null>>({});
const shouldShowOfficialHoursValidationHint = ref(false);

/** ✅ watch đặt SAU khi mọi ref/computed cần dùng đã có */
watch(
  () => selectedResearchWorkApprovalEntry.value?.researchWorkIdentifier,
  () => {
    const map: Record<number, number | null> = {};
    for (const r of authorHourRows.value) {
      map[r.authorIdentifier] =
        r.officialHours ??
        r.recommendedHoursByPolicy ??
        r.declaredHours ??
        null;
    }
    officialHoursDraftByAuthorId.value = map;

    resetRejectionFlowState();
    shouldShowOfficialHoursValidationHint.value = false;
  },
  { immediate: true }
);

const totalOfficialHours = computed<number>(() => {
  return authorHourRows.value.reduce((sum, r) => {
    const v = officialHoursDraftByAuthorId.value[r.authorIdentifier];
    return sum + (Number.isFinite(v as number) ? (v as number) : 0);
  }, 0);
});

const isOfficialHoursValid = computed<boolean>(() => {
  if (!canFinalizeHours.value) return true;
  if (authorHourRows.value.length === 0) return false;

  return authorHourRows.value.every((r) => {
    const v = officialHoursDraftByAuthorId.value[r.authorIdentifier];
    return Number.isFinite(v as number) && (v as number) >= 0;
  });
});

/** =========================
 *  APPROVE / REJECT LOGIC
 *  ========================= */
function getPendingStatusValue() {
  return approvalScopeIdentifier.value === "FACULTY_SCOPE"
    ? "PENDING_FACULTY_APPROVAL"
    : "PENDING_UNIVERSITY_APPROVAL";
}

const isApproveActionDisabled = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return true;
  return entry.approvalStatus !== getPendingStatusValue();
});

const isRejectActionDisabled = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return true;
  return entry.approvalStatus !== getPendingStatusValue();
});

function approveSelectedResearchWork(): void {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return;

  if (canFinalizeHours.value) {
    shouldShowOfficialHoursValidationHint.value = true;
    if (!isOfficialHoursValid.value) return;
  }

  const ok = window.confirm(
    canFinalizeHours.value
      ? "Xác nhận duyệt & chốt giờ?"
      : "Xác nhận hồ sơ hợp lệ và chuyển lên cấp trường?"
  );
  if (!ok) return;

  emit("approve", {
    researchWorkIdentifier: entry.researchWorkIdentifier,
    // tạm thời giữ API cũ: chốt = tổng giờ; khoa = null
    officialResearchHours: canFinalizeHours.value
      ? Math.round(totalOfficialHours.value)
      : null,
  });
}

async function onRejectActionButtonClicked(): Promise<void> {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return;

  if (!isRejectionPanelVisible.value) {
    isRejectionPanelVisible.value = true;
    await nextTick();
    rejectionPanelElement.value?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
    return;
  }

  shouldShowRejectionValidationHint.value = true;
  if (!isRejectionFormValid.value || !selectedRejectionReasonType.value) return;

  if (!window.confirm("Xác nhận từ chối công trình này? (Demo UI)")) return;

  emit("reject", {
    researchWorkIdentifier: entry.researchWorkIdentifier,
    rejectionReasonType: selectedRejectionReasonType.value,
    rejectionReasonDetail:
      selectedRejectionReasonType.value === "OTHER"
        ? rejectionReasonDetail.value.trim()
        : null,
  });

  resetRejectionFlowState();
}
</script>
