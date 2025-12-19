<template>
  <Teleport to="body">
    <!-- Overlay: click để đóng Drawer -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isResearchWorkApprovalDrawerVisible"
        class="fixed inset-0 z-40 bg-slate-900/20"
        aria-hidden="true"
        @click="closeResearchWorkApprovalDrawer"
      />
    </Transition>

    <!-- Drawer: trượt từ phải -->
    <Transition
      enter-active-class="transition-transform duration-250 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="isResearchWorkApprovalDrawerVisible"
        ref="researchWorkApprovalDrawerElement"
        class="fixed right-0 top-0 z-50 h-dvh w-[520px] max-w-full border-l border-slate-200 bg-white shadow-xl"
        role="dialog"
        aria-modal="true"
        aria-labelledby="researchWorkApprovalDrawerTitle"
        aria-describedby="researchWorkApprovalDrawerSubtitle"
        @click.stop
        @keydown="trapKeyboardFocusInsideDrawer"
      >
        <div class="flex h-full flex-col">
          <!-- HEADER (fixed) -->
          <div class="flex-none border-b border-slate-200 bg-white px-5 py-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <h2
                  id="researchWorkApprovalDrawerTitle"
                  class="text-base font-semibold text-slate-900"
                >
                  Chi tiết công trình nghiên cứu
                </h2>
                <p
                  id="researchWorkApprovalDrawerSubtitle"
                  class="mt-0.5 text-sm text-slate-600"
                >
                  Xem và xét duyệt công trình
                </p>
              </div>

              <button
                ref="closeButtonElement"
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
                aria-label="Đóng bảng chi tiết công trình"
                @click="closeResearchWorkApprovalDrawer"
              >
                <span class="text-lg leading-none">✕</span>
              </button>
            </div>
          </div>

          <!-- BODY (scrollable) -->
          <div class="flex-1 overflow-y-auto px-5 py-4">
            <!-- SECTION A — THÔNG TIN CÔNG TRÌNH -->
            <section
              class="mb-4 rounded-xl border border-slate-200 bg-white p-4"
            >
              <div class="mb-3">
                <h3 class="text-sm font-semibold text-slate-900">
                  Thông tin công trình
                </h3>
                <p class="mt-0.5 text-xs text-slate-600">
                  Hiển thị dạng chỉ đọc để tránh vô tình sửa nội dung kê khai.
                </p>
              </div>

              <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <ReadOnlyField
                  label="Tên công trình"
                  :value="
                    selectedResearchWorkInformation?.researchWorkTitle ?? '—'
                  "
                />
                <ReadOnlyField
                  label="Loại công trình"
                  :value="
                    mapResearchWorkTypeToDisplayText(
                      selectedResearchWorkInformation?.researchWorkType
                    )
                  "
                />
                <ReadOnlyField
                  label="Giảng viên thực hiện"
                  :value="
                    selectedResearchWorkInformation?.lecturerFullName ?? '—'
                  "
                />
                <ReadOnlyField
                  label="Khoa"
                  :value="selectedResearchWorkInformation?.facultyName ?? '—'"
                />
                <ReadOnlyField
                  label="Đồng tác giả"
                  :value="
                    formatContributorInformationCollection(
                      selectedResearchWorkInformation?.contributorInformationCollection
                    )
                  "
                />
                <ReadOnlyField
                  label="Năm học"
                  :value="
                    selectedResearchWorkInformation?.academicYearDisplayName ??
                    '—'
                  "
                />
                <ReadOnlyField
                  label="Cấp công trình"
                  :value="
                    selectedResearchWorkInformation?.researchWorkLevelDisplayName ??
                    '—'
                  "
                />
                <ReadOnlyField
                  label="Trạng thái"
                  :value="
                    mapResearchWorkApprovalStatusToDisplayText(
                      selectedResearchWorkInformation?.approvalStatus
                    )
                  "
                />
              </div>
            </section>

            <!-- SECTION B — MINH CHỨNG -->
            <section
              class="mb-4 rounded-xl border border-slate-200 bg-white p-4"
            >
              <div class="mb-3">
                <h3 class="text-sm font-semibold text-slate-900">Minh chứng</h3>
                <p class="mt-0.5 text-xs text-slate-600">
                  Mở minh chứng ở tab mới để không mất bối cảnh bảng.
                </p>
              </div>

              <div class="space-y-3">
                <EvidenceRow
                  label="File minh chứng (PDF)"
                  :displayValue="
                    selectedResearchWorkInformation?.proofDocumentFileName ??
                    '—'
                  "
                  buttonText="Xem file"
                  :isButtonDisabled="
                    !selectedResearchWorkInformation?.proofDocumentWebAddress
                  "
                  @clickEvidenceButton="
                    openExternalWebAddress(
                      selectedResearchWorkInformation?.proofDocumentWebAddress
                    )
                  "
                />

                <EvidenceRow
                  label="Link DOI / URL"
                  :displayValue="
                    selectedResearchWorkInformation?.digitalObjectIdentifierOrPublicWebAddress ??
                    '—'
                  "
                  buttonText="Mở liên kết"
                  :isButtonDisabled="
                    !selectedResearchWorkInformation?.digitalObjectIdentifierOrPublicWebAddress
                  "
                  @clickEvidenceButton="
                    openExternalWebAddress(
                      selectedResearchWorkInformation?.digitalObjectIdentifierOrPublicWebAddress
                    )
                  "
                />

                <EvidenceRow
                  label="Quyết định đề tài (nếu có)"
                  :displayValue="
                    selectedResearchWorkInformation?.decisionDocumentFileName ??
                    '—'
                  "
                  buttonText="Xem file"
                  :isButtonDisabled="
                    !selectedResearchWorkInformation?.decisionDocumentWebAddress
                  "
                  @clickEvidenceButton="
                    openExternalWebAddress(
                      selectedResearchWorkInformation?.decisionDocumentWebAddress
                    )
                  "
                />
              </div>
            </section>

            <!-- SECTION C — GIỜ NCKH & QUY ĐỔI -->
            <section
              class="mb-4 rounded-xl border border-slate-200 bg-white p-4"
            >
              <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-sm font-semibold text-slate-900">
                    Giờ NCKH &amp; quy đổi
                  </h3>
                  <p class="mt-0.5 text-xs text-slate-600">
                    Giờ được duyệt ảnh hưởng đánh giá và tổng hợp; cần ràng buộc
                    rõ ràng.
                  </p>
                </div>

                <div class="relative">
                  <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
                    aria-label="Giải thích hệ số quy đổi"
                    @click="toggleConversionExplanationVisibility"
                    @blur="closeConversionExplanationIfVisible"
                  >
                    <span class="text-base">i</span>
                  </button>

                  <div
                    v-if="isConversionExplanationVisible"
                    class="absolute right-0 top-11 w-80 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-700 shadow-lg"
                    role="tooltip"
                  >
                    <div class="font-semibold text-slate-900">
                      Giải thích quy đổi
                    </div>
                    <div class="mt-1 leading-relaxed">
                      Hệ số quy đổi phản ánh mức độ/khối lượng công trình theo
                      quy định nội bộ. Giờ đề xuất là kết quả tính từ hồ sơ kê
                      khai; người duyệt có thể điều chỉnh để phù hợp minh chứng
                      và đảm bảo công bằng.
                    </div>
                  </div>
                </div>
              </div>

              <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <ReadOnlyField
                  label="Giờ đề xuất (read-only)"
                  :value="
                    formatResearchHourValue(
                      selectedResearchWorkInformation?.proposedResearchHourValue
                    )
                  "
                />
                <ReadOnlyField
                  label="Hệ số quy đổi (read-only)"
                  :value="
                    formatConversionCoefficientValue(
                      selectedResearchWorkInformation?.conversionCoefficientValue
                    )
                  "
                />
              </div>

              <div class="mt-3">
                <label
                  class="text-xs font-medium text-slate-700"
                  for="approvedResearchHourValueInput"
                >
                  Giờ được duyệt
                </label>
                <input
                  id="approvedResearchHourValueInput"
                  v-model="approvedResearchHourValueInputText"
                  type="number"
                  min="0"
                  inputmode="decimal"
                  class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                  :aria-invalid="
                    approvedResearchHourValueValidationMessage
                      ? 'true'
                      : 'false'
                  "
                  :aria-describedby="
                    approvedResearchHourValueValidationMessage
                      ? 'approvedResearchHourValueValidationMessage'
                      : undefined
                  "
                  @input="synchronizeApprovedResearchHourValueFromInputText"
                />
                <p
                  v-if="approvedResearchHourValueValidationMessage"
                  id="approvedResearchHourValueValidationMessage"
                  class="mt-1 text-xs text-slate-600"
                >
                  {{ approvedResearchHourValueValidationMessage }}
                </p>
              </div>
            </section>

            <!-- SECTION D — TỪ CHỐI -->
            <section
              v-if="isRejectionSectionVisible"
              class="mb-2 rounded-xl border border-slate-200 bg-white p-4"
            >
              <div class="mb-3">
                <h3 class="text-sm font-semibold text-slate-900">Từ chối</h3>
                <p class="mt-0.5 text-xs text-slate-600">
                  Lý do rõ ràng giúp giảng viên hoàn thiện hồ sơ và giảm vòng
                  trao đổi.
                </p>
              </div>

              <div class="space-y-3">
                <div>
                  <label
                    class="text-xs font-medium text-slate-700"
                    for="rejectionReasonSelect"
                  >
                    Lý do từ chối
                  </label>
                  <select
                    id="rejectionReasonSelect"
                    v-model="selectedRejectionReason"
                    class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                  >
                    <option value="">Chọn lý do</option>
                    <option value="Minh chứng không hợp lệ">
                      Minh chứng không hợp lệ
                    </option>
                    <option value="Công trình không đủ tiêu chí">
                      Công trình không đủ tiêu chí
                    </option>
                    <option value="Trùng lặp công trình">
                      Trùng lặp công trình
                    </option>
                    <option value="Sai loại công trình">
                      Sai loại công trình
                    </option>
                    <option value="Khác">Khác</option>
                  </select>
                </div>

                <div>
                  <label
                    class="text-xs font-medium text-slate-700"
                    for="customRejectionExplanationTextarea"
                  >
                    Giải thích chi tiết
                  </label>
                  <textarea
                    id="customRejectionExplanationTextarea"
                    v-model="customRejectionExplanation"
                    rows="4"
                    class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
                    :placeholder="
                      selectedRejectionReason === 'Khác'
                        ? 'Bắt buộc khi chọn Khác'
                        : 'Khuyến nghị ghi rõ để giảng viên dễ hoàn thiện'
                    "
                    :aria-invalid="
                      rejectionExplanationValidationMessage ? 'true' : 'false'
                    "
                    :aria-describedby="
                      rejectionExplanationValidationMessage
                        ? 'rejectionExplanationValidationMessage'
                        : undefined
                    "
                  />
                  <p
                    v-if="rejectionExplanationValidationMessage"
                    id="rejectionExplanationValidationMessage"
                    class="mt-1 text-xs text-slate-600"
                  >
                    {{ rejectionExplanationValidationMessage }}
                  </p>
                </div>
              </div>
            </section>
          </div>

          <!-- FOOTER (fixed) -->
          <div class="flex-none border-t border-slate-200 bg-white px-5 py-4">
            <div class="flex items-center justify-end gap-2">
              <button
                type="button"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
                :disabled="isRejectButtonDisabled"
                :class="isRejectButtonDisabled ? 'opacity-50' : ''"
                @click="rejectResearchWork"
              >
                ❌ Từ chối công trình
              </button>

              <button
                type="button"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-slate-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-300"
                :disabled="isApproveButtonDisabled"
                :class="isApproveButtonDisabled ? 'opacity-50' : ''"
                @click="approveResearchWork"
              >
                ✅ Duyệt công trình
              </button>
            </div>
          </div>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import type {
  ResearchWorkApprovalInformation,
  ResearchWorkApprovalStatus,
  ResearchWorkType,
} from "../researchWorkApprovalDomainTypes";

interface Props {
  selectedResearchWorkInformation: ResearchWorkApprovalInformation | null;
  isResearchWorkApprovalDrawerVisible: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (eventName: "close-research-work-approval-drawer"): void;
  (eventName: "approve-research-work", approvedResearchHourValue: number): void;
  (
    eventName: "reject-research-work",
    rejectionPayload: {
      selectedRejectionReason: string;
      customRejectionExplanation: string;
    }
  ): void;
}>();

/**
 * Lý do: Giữ dữ liệu nhập duyệt/từ chối ở Drawer để tránh “dính” trạng thái khi chuyển công trình.
 */
const approvedResearchHourValue = ref<number | null>(null);
const approvedResearchHourValueInputText = ref<string>("");

const selectedRejectionReason = ref<string>("");
const customRejectionExplanation = ref<string>("");

const isRejectionSectionVisible = ref<boolean>(false);
const isConversionExplanationVisible = ref<boolean>(false);

const researchWorkApprovalDrawerElement = ref<HTMLElement | null>(null);
const closeButtonElement = ref<HTMLButtonElement | null>(null);

let lastFocusedElementBeforeDrawerOpen: HTMLElement | null = null;

const approvedResearchHourValueValidationMessage = computed(() => {
  if (!props.isResearchWorkApprovalDrawerVisible) return "";
  if (approvedResearchHourValueInputText.value.trim() === "")
    return "Vui lòng nhập giờ được duyệt.";
  const numericValue = Number(approvedResearchHourValueInputText.value);
  if (!Number.isFinite(numericValue))
    return "Giờ được duyệt phải là một số hợp lệ.";
  if (numericValue < 0) return "Giờ được duyệt phải lớn hơn hoặc bằng 0.";
  return "";
});

const rejectionExplanationValidationMessage = computed(() => {
  if (!isRejectionSectionVisible.value) return "";
  if (selectedRejectionReason.value !== "Khác") return "";
  if (customRejectionExplanation.value.trim().length === 0) {
    return "Vui lòng giải thích chi tiết khi chọn lý do “Khác”.";
  }
  return "";
});

const isApproveButtonDisabled = computed(() => {
  if (!props.selectedResearchWorkInformation) return true;
  if (approvedResearchHourValueValidationMessage.value.length > 0) return true;
  return approvedResearchHourValue.value === null;
});

const isRejectButtonDisabled = computed(() => {
  if (!props.selectedResearchWorkInformation) return true;

  // Bấm lần đầu để mở phần từ chối (không khoá nút ở bước 1).
  if (!isRejectionSectionVisible.value) return false;

  if (selectedRejectionReason.value.trim() === "") return true;
  if (rejectionExplanationValidationMessage.value.length > 0) return true;
  return false;
});

watch(
  () => props.isResearchWorkApprovalDrawerVisible,
  async (isVisible) => {
    if (isVisible) {
      lastFocusedElementBeforeDrawerOpen =
        document.activeElement as HTMLElement | null;

      // Lý do: Khi đang xét duyệt, nền không nên cuộn để tránh mất vị trí bảng.
      document.body.style.overflow = "hidden";

      // Lý do: Mặc định “giờ được duyệt” = “giờ đề xuất” để giảm thao tác nhập lại.
      if (props.selectedResearchWorkInformation) {
        approvedResearchHourValue.value =
          props.selectedResearchWorkInformation.proposedResearchHourValue;
        approvedResearchHourValueInputText.value = String(
          props.selectedResearchWorkInformation.proposedResearchHourValue
        );
      } else {
        approvedResearchHourValue.value = null;
        approvedResearchHourValueInputText.value = "";
      }

      selectedRejectionReason.value = "";
      customRejectionExplanation.value = "";
      isRejectionSectionVisible.value = false;
      isConversionExplanationVisible.value = false;

      await nextTick();
      closeButtonElement.value?.focus();
    } else {
      document.body.style.overflow = "";
      lastFocusedElementBeforeDrawerOpen?.focus();
    }
  }
);

onBeforeUnmount(() => {
  document.body.style.overflow = "";
});

function closeResearchWorkApprovalDrawer() {
  emit("close-research-work-approval-drawer");
}

function synchronizeApprovedResearchHourValueFromInputText() {
  if (approvedResearchHourValueInputText.value.trim() === "") {
    approvedResearchHourValue.value = null;
    return;
  }
  const numericValue = Number(approvedResearchHourValueInputText.value);
  approvedResearchHourValue.value = Number.isFinite(numericValue)
    ? numericValue
    : null;
}

function approveResearchWork() {
  synchronizeApprovedResearchHourValueFromInputText();
  if (isApproveButtonDisabled.value) return;

  const isConfirmed = window.confirm(
    "Bạn có chắc chắn muốn duyệt công trình này không?"
  );
  if (!isConfirmed) return;

  emit("approve-research-work", approvedResearchHourValue.value ?? 0);
}

function rejectResearchWork() {
  // Lý do: Bấm lần đầu chỉ mở phần lý do để tránh thao tác từ chối nhầm.
  if (!isRejectionSectionVisible.value) {
    isRejectionSectionVisible.value = true;
    nextTick(() => {
      const rejectionReasonSelectElement = document.getElementById(
        "rejectionReasonSelect"
      ) as HTMLSelectElement | null;
      rejectionReasonSelectElement?.focus();
    });
    return;
  }

  if (isRejectButtonDisabled.value) return;

  const isConfirmed = window.confirm(
    "Bạn có chắc chắn muốn từ chối công trình này không?"
  );
  if (!isConfirmed) return;

  emit("reject-research-work", {
    selectedRejectionReason: selectedRejectionReason.value,
    customRejectionExplanation: customRejectionExplanation.value.trim(),
  });
}

function toggleConversionExplanationVisibility() {
  isConversionExplanationVisible.value = !isConversionExplanationVisible.value;
}

function closeConversionExplanationIfVisible() {
  if (isConversionExplanationVisible.value) {
    isConversionExplanationVisible.value = false;
  }
}

function trapKeyboardFocusInsideDrawer(keyboardEvent: KeyboardEvent) {
  if (keyboardEvent.key === "Escape") {
    keyboardEvent.preventDefault();
    closeResearchWorkApprovalDrawer();
    return;
  }

  if (keyboardEvent.key !== "Tab") return;

  const focusableElementCollection = getFocusableElementCollection();
  if (focusableElementCollection.length === 0) return;

  const firstFocusableElement = focusableElementCollection[0];
  const lastFocusableElement =
    focusableElementCollection[focusableElementCollection.length - 1];

  if (!firstFocusableElement || !lastFocusableElement) return;

  const activeElement = document.activeElement as HTMLElement | null;

  if (keyboardEvent.shiftKey) {
    if (!activeElement || activeElement === firstFocusableElement) {
      keyboardEvent.preventDefault();
      lastFocusableElement.focus();
    }
    return;
  }

  if (!activeElement || activeElement === lastFocusableElement) {
    keyboardEvent.preventDefault();
    firstFocusableElement.focus();
  }
}

function getFocusableElementCollection(): HTMLElement[] {
  const drawerElement = researchWorkApprovalDrawerElement.value;
  if (!drawerElement) return [];

  const focusableSelector =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

  return Array.from(
    drawerElement.querySelectorAll<HTMLElement>(focusableSelector)
  ).filter(
    (element) =>
      !element.hasAttribute("disabled") && !element.getAttribute("aria-hidden")
  );
}

function openExternalWebAddress(externalWebAddress: string | undefined) {
  if (!externalWebAddress) return;
  window.open(externalWebAddress, "_blank", "noopener,noreferrer");
}

function formatContributorInformationCollection(
  contributorInformationCollection:
    | ResearchWorkApprovalInformation["contributorInformationCollection"]
    | undefined
) {
  if (
    !contributorInformationCollection ||
    contributorInformationCollection.length === 0
  )
    return "Không có";
  return contributorInformationCollection
    .map((contributorInformation) => contributorInformation.contributorFullName)
    .join(", ");
}

function formatResearchHourValue(researchHourValue: number | undefined) {
  if (researchHourValue === undefined || researchHourValue === null) return "—";
  return `${researchHourValue} giờ`;
}

function formatConversionCoefficientValue(
  conversionCoefficientValue: number | undefined
) {
  if (
    conversionCoefficientValue === undefined ||
    conversionCoefficientValue === null
  )
    return "—";
  return `${conversionCoefficientValue}`;
}

function mapResearchWorkTypeToDisplayText(
  researchWorkType: ResearchWorkType | undefined
) {
  if (!researchWorkType) return "—";
  switch (researchWorkType) {
    case "JOURNAL_ARTICLE":
      return "Bài báo tạp chí";
    case "CONFERENCE_PAPER":
      return "Bài báo hội thảo";
    case "RESEARCH_PROJECT":
      return "Đề tài nghiên cứu";
    case "TEXTBOOK_OR_MONOGRAPH":
      return "Sách / giáo trình";
    default:
      return "Khác";
  }
}

function mapResearchWorkApprovalStatusToDisplayText(
  researchWorkApprovalStatus: ResearchWorkApprovalStatus | undefined
) {
  if (!researchWorkApprovalStatus) return "—";
  switch (researchWorkApprovalStatus) {
    case "PENDING":
      return "Chờ duyệt";
    case "APPROVED":
      return "Đã duyệt";
    case "REJECTED":
      return "Từ chối";
    default:
      return researchWorkApprovalStatus;
  }
}

/**
 * Component con: cố tình “đóng gói” style chỉ đọc để giữ hierarchy sạch và đồng nhất.
 */
const ReadOnlyField = defineReadOnlyFieldComponent();
const EvidenceRow = defineEvidenceRowComponent();

function defineReadOnlyFieldComponent() {
  return {
    name: "ReadOnlyField",
    props: {
      label: { type: String, required: true },
      value: { type: String, required: true },
    },
    template: `
      <div class="rounded-lg border border-slate-200 bg-white p-3">
        <div class="text-xs font-medium text-slate-700">{{ label }}</div>
        <div class="mt-1 break-words text-sm text-slate-900">{{ value }}</div>
      </div>
    `,
  };
}

function defineEvidenceRowComponent() {
  return {
    name: "EvidenceRow",
    emits: ["clickEvidenceButton"],
    props: {
      label: { type: String, required: true },
      displayValue: { type: String, required: true },
      buttonText: { type: String, required: true },
      isButtonDisabled: { type: Boolean, required: true },
    },
    template: `
      <div class="rounded-lg border border-slate-200 p-3">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="text-xs font-medium text-slate-700">{{ label }}</div>
            <div class="mt-1 truncate text-sm text-slate-900">{{ displayValue }}</div>
          </div>
          <button
            type="button"
            class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
            :disabled="isButtonDisabled"
            :class="isButtonDisabled ? 'opacity-50' : ''"
            @click="$emit('clickEvidenceButton')"
          >
            {{ buttonText }}
          </button>
        </div>
      </div>
    `,
  };
}
</script>
