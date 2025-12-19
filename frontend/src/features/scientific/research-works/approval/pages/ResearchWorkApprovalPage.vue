<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-900">
        Duyệt công trình nghiên cứu
      </h1>
      <p class="mt-1 text-sm text-slate-600">
        Xem danh sách công trình do giảng viên kê khai và thực hiện xét duyệt để
        đưa vào hồ sơ khoa học.
      </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-end">
        <div class="lg:col-span-2">
          <label class="block text-xs font-medium text-slate-700"
            >Năm học</label
          >
          <select
            v-model="selectedAcademicYearFilterValue"
            class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
          >
            <option value="ALL">Tất cả</option>
            <option
              v-for="academicYearOption in academicYearOptionCollection"
              :key="academicYearOption"
              :value="academicYearOption"
            >
              {{ academicYearOption }}
            </option>
          </select>
        </div>

        <div class="lg:col-span-3">
          <label class="block text-xs font-medium text-slate-700"
            >Loại công trình</label
          >
          <select
            v-model="selectedResearchWorkTypeFilterValue"
            class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
          >
            <option value="ALL">Tất cả</option>
            <option value="JOURNAL_ARTICLE">Bài báo tạp chí</option>
            <option value="CONFERENCE_PAPER">Bài báo hội thảo</option>
            <option value="RESEARCH_PROJECT">Đề tài nghiên cứu</option>
            <option value="TEXTBOOK_OR_MONOGRAPH">Sách / giáo trình</option>
            <option value="OTHER_RESEARCH_WORK">Khác</option>
          </select>
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs font-medium text-slate-700"
            >Trạng thái</label
          >
          <select
            v-model="selectedResearchWorkApprovalStatusFilterValue"
            class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
          >
            <option value="PENDING">Chờ duyệt</option>
            <option value="APPROVED">Đã duyệt</option>
            <option value="REJECTED">Từ chối</option>
            <option value="ALL">Tất cả</option>
          </select>
        </div>

        <div class="lg:col-span-3">
          <label class="block text-xs font-medium text-slate-700">Khoa</label>

          <select
            v-if="approvalScopeType === 'UNIVERSITY'"
            v-model="selectedFacultyNameFilterValue"
            class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
          >
            <option value="ALL">Tất cả</option>
            <option
              v-for="facultyNameOption in facultyNameOptionCollection"
              :key="facultyNameOption"
              :value="facultyNameOption"
            >
              {{ facultyNameOption }}
            </option>
          </select>

          <div
            v-else
            class="mt-1 flex h-10 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800"
            aria-label="Khoa cố định theo phạm vi xét duyệt"
          >
            {{ fixedFacultyName ?? "—" }}
          </div>
        </div>

        <div class="lg:col-span-2">
          <label class="block text-xs font-medium text-slate-700"
            >Từ khoá</label
          >
          <input
            v-model="researchWorkSearchKeyword"
            type="text"
            placeholder="Tìm theo tên giảng viên hoặc tên công trình"
            class="mt-1 h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
            @keyup.enter="applyResearchWorkFilterConditions"
          />
        </div>

        <div class="lg:col-span-12">
          <div class="flex justify-end">
            <button
              type="button"
              class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
              @click="applyResearchWorkFilterConditions"
            >
              Áp dụng bộ lọc
            </button>
          </div>
        </div>
      </div>
    </div>

    <div
      class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          Có
          <span class="font-semibold text-slate-900">{{
            pendingResearchWorkCount
          }}</span>
          công trình đang ở trạng thái
          <span class="font-semibold text-slate-900">chờ duyệt</span>.
        </div>
        <div>
          Tổng số công trình (theo bộ lọc hiện tại):
          <span class="font-semibold text-slate-900">{{
            filteredResearchWorkInformationCollection.length
          }}</span>
        </div>
      </div>
    </div>

    <ResearchWorkApprovalTable
      :researchWorkInformationCollection="researchWorkInformationCollection"
      :isResearchWorkApprovalCollectionLoading="
        isResearchWorkApprovalCollectionLoading
      "
      :researchWorkApprovalCollectionErrorMessage="
        researchWorkApprovalCollectionErrorMessage
      "
      :isFacultyColumnVisible="isFacultyColumnVisible"
      @open-research-work-approval-drawer="openResearchWorkApprovalDrawer"
    />

    <ResearchWorkApprovalDrawer
      :selectedResearchWorkInformation="selectedResearchWorkInformation"
      :isResearchWorkApprovalDrawerVisible="isResearchWorkApprovalDrawerVisible"
      @close-research-work-approval-drawer="closeResearchWorkApprovalDrawer"
      @approve-research-work="approveResearchWork"
      @reject-research-work="rejectResearchWork"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import ResearchWorkApprovalDrawer from "../components/ResearchWorkApprovalDrawer.vue";
import ResearchWorkApprovalTable from "../components/ResearchWorkApprovalTable.vue";
import type {
  ResearchWorkApprovalInformation,
  ResearchWorkApprovalScopeType,
  ResearchWorkApprovalStatus,
  ResearchWorkRejectionReason,
  ResearchWorkType,
} from "../researchWorkApprovalDomainTypes";

interface Props {
  approvalScopeType: ResearchWorkApprovalScopeType;
  /**
   * Lý do tách field này:
   * - Với phạm vi theo khoa, khoa đã “cố định theo tài khoản”, nên UI chỉ hiển thị read-only.
   */
  fixedFacultyName?: string;
}

const props = defineProps<Props>();

const selectedResearchWorkInformation =
  ref<ResearchWorkApprovalInformation | null>(null);
const isResearchWorkApprovalDrawerVisible = ref<boolean>(false);

const isResearchWorkApprovalCollectionLoading = ref<boolean>(false);
const researchWorkApprovalCollectionErrorMessage = ref<string | null>(null);

const researchWorkSearchKeyword = ref<string>("");
const selectedAcademicYearFilterValue = ref<string | "ALL">("ALL");
const selectedResearchWorkTypeFilterValue = ref<ResearchWorkType | "ALL">(
  "ALL"
);
const selectedResearchWorkApprovalStatusFilterValue = ref<
  ResearchWorkApprovalStatus | "ALL"
>("PENDING");
const selectedFacultyNameFilterValue = ref<string | "ALL">("ALL");

const researchWorkInformationCollection = ref<
  ResearchWorkApprovalInformation[]
>([
  {
    researchWorkIdentifier: "RW-2025-0001",
    researchWorkTitle:
      "Mô hình dự báo kết quả học tập dựa trên dữ liệu hành vi học tập",
    researchWorkType: "JOURNAL_ARTICLE",
    lecturerFullName: "Nguyễn Văn An",
    facultyName: "Khoa Công nghệ Thông tin",
    contributorInformationCollection: [
      {
        contributorIdentifier: "C-01",
        contributorFullName: "Nguyễn Văn An",
        contributorRole: "Tác giả chính",
      },
      {
        contributorIdentifier: "C-02",
        contributorFullName: "Trần Thị Bình",
        contributorRole: "Đồng tác giả",
      },
    ],
    academicYearDisplayName: "2024–2025",
    researchWorkLevelDisplayName: "Cấp trường",
    proofDocumentFileName: "minhchung_RW-2025-0001.pdf",
    proofDocumentWebAddress: "https://example.com/mock-proof-document.pdf",
    digitalObjectIdentifierOrPublicWebAddress:
      "https://doi.org/10.0000/mock-doi-0001",
    decisionDocumentFileName: "quyetdinh_RW-2025-0001.pdf",
    decisionDocumentWebAddress:
      "https://example.com/mock-decision-document.pdf",
    proposedResearchHourValue: 60,
    conversionCoefficientValue: 1.2,
    approvalStatus: "PENDING",
  },
  {
    researchWorkIdentifier: "RW-2025-0002",
    researchWorkTitle:
      "Khảo sát yếu tố ảnh hưởng đến năng lực nghiên cứu của giảng viên trẻ",
    researchWorkType: "RESEARCH_PROJECT",
    lecturerFullName: "Phạm Thị Duyên",
    facultyName: "Khoa Kinh tế",
    contributorInformationCollection: [
      {
        contributorIdentifier: "C-03",
        contributorFullName: "Phạm Thị Duyên",
        contributorRole: "Chủ nhiệm đề tài",
      },
    ],
    academicYearDisplayName: "2024–2025",
    researchWorkLevelDisplayName: "Cấp khoa",
    proofDocumentFileName: "minhchung_RW-2025-0002.pdf",
    proofDocumentWebAddress: "https://example.com/mock-proof-document-2.pdf",
    digitalObjectIdentifierOrPublicWebAddress: "",
    decisionDocumentFileName: "",
    decisionDocumentWebAddress: "",
    proposedResearchHourValue: 80,
    conversionCoefficientValue: 1.0,
    approvedResearchHourValue: 80,
    approvalStatus: "APPROVED",
  },
  {
    researchWorkIdentifier: "RW-2025-0003",
    researchWorkTitle:
      "Đánh giá trùng lặp nội dung trong báo cáo nghiên cứu bằng phương pháp so khớp ngữ nghĩa",
    researchWorkType: "CONFERENCE_PAPER",
    lecturerFullName: "Võ Hoàng Giang",
    facultyName: "Khoa Công nghệ Thông tin",
    contributorInformationCollection: [
      {
        contributorIdentifier: "C-04",
        contributorFullName: "Võ Hoàng Giang",
        contributorRole: "Tác giả chính",
      },
      {
        contributorIdentifier: "C-05",
        contributorFullName: "Đặng Ngọc Hân",
        contributorRole: "Đồng tác giả",
      },
    ],
    academicYearDisplayName: "2024–2025",
    researchWorkLevelDisplayName: "Cấp bộ",
    proofDocumentFileName: "minhchung_RW-2025-0003.pdf",
    proofDocumentWebAddress: "https://example.com/mock-proof-document-3.pdf",
    digitalObjectIdentifierOrPublicWebAddress:
      "https://example.com/mock-publication-page",
    decisionDocumentFileName: "",
    decisionDocumentWebAddress: "",
    proposedResearchHourValue: 40,
    conversionCoefficientValue: 1.3,
    rejectionReason: "Trùng lặp công trình",
    rejectionExplanation:
      "Hồ sơ trùng với công trình đã nộp trước đó, cần làm rõ phiên bản.",
    approvalStatus: "REJECTED",
  },
]);

const academicYearOptionCollection = computed(() => {
  const academicYearValueCollection = new Set(
    researchWorkInformationCollection.value.map(
      (researchWorkInformation) =>
        researchWorkInformation.academicYearDisplayName
    )
  );
  return Array.from(academicYearValueCollection).sort();
});

const facultyNameOptionCollection = computed(() => {
  const facultyNameValueCollection = new Set(
    researchWorkInformationCollection.value.map(
      (researchWorkInformation) => researchWorkInformation.facultyName
    )
  );
  return Array.from(facultyNameValueCollection).sort();
});

const pendingResearchWorkCount = computed(
  () =>
    researchWorkInformationCollection.value.filter(
      (researchWorkInformation) =>
        researchWorkInformation.approvalStatus === "PENDING"
    ).length
);

const filteredResearchWorkInformationCollection = computed(() => {
  const baseResearchWorkInformationCollection =
    researchWorkInformationCollection.value;

  return baseResearchWorkInformationCollection.filter(
    (researchWorkInformation) => {
      const isAcademicYearMatched =
        selectedAcademicYearFilterValue.value === "ALL" ||
        researchWorkInformation.academicYearDisplayName ===
          selectedAcademicYearFilterValue.value;

      const isResearchWorkTypeMatched =
        selectedResearchWorkTypeFilterValue.value === "ALL" ||
        researchWorkInformation.researchWorkType ===
          selectedResearchWorkTypeFilterValue.value;

      const isApprovalStatusMatched =
        selectedResearchWorkApprovalStatusFilterValue.value === "ALL" ||
        researchWorkInformation.approvalStatus ===
          selectedResearchWorkApprovalStatusFilterValue.value;

      const isSearchKeywordMatched = (() => {
        if (!researchWorkSearchKeyword.value.trim()) return true;
        const normalizedKeyword = researchWorkSearchKeyword.value
          .trim()
          .toLowerCase();
        return (
          researchWorkInformation.researchWorkTitle
            .toLowerCase()
            .includes(normalizedKeyword) ||
          researchWorkInformation.lecturerFullName
            .toLowerCase()
            .includes(normalizedKeyword)
        );
      })();

      const isFacultyMatched = (() => {
        if (props.approvalScopeType === "FACULTY") {
          return (
            !!props.fixedFacultyName &&
            researchWorkInformation.facultyName === props.fixedFacultyName
          );
        }
        return (
          selectedFacultyNameFilterValue.value === "ALL" ||
          researchWorkInformation.facultyName ===
            selectedFacultyNameFilterValue.value
        );
      })();

      return (
        isAcademicYearMatched &&
        isResearchWorkTypeMatched &&
        isApprovalStatusMatched &&
        isSearchKeywordMatched &&
        isFacultyMatched
      );
    }
  );
});

const approvalScopeType = computed(() => props.approvalScopeType);
const fixedFacultyName = computed(() => props.fixedFacultyName);
const isFacultyColumnVisible = computed(
  () => props.approvalScopeType === "UNIVERSITY"
);

function applyResearchWorkFilterConditions() {
  // Lý do: mô phỏng cảm giác “đang áp dụng bộ lọc” trước khi nối backend.
  researchWorkApprovalCollectionErrorMessage.value = null;
  isResearchWorkApprovalCollectionLoading.value = true;
  window.setTimeout(() => {
    isResearchWorkApprovalCollectionLoading.value = false;
  }, 250);
}

function openResearchWorkApprovalDrawer(
  selectedResearchWorkInformationValue: ResearchWorkApprovalInformation
) {
  selectedResearchWorkInformation.value = selectedResearchWorkInformationValue;
  isResearchWorkApprovalDrawerVisible.value = true;
}

function closeResearchWorkApprovalDrawer() {
  isResearchWorkApprovalDrawerVisible.value = false;
  selectedResearchWorkInformation.value = null;
}

function approveResearchWork(approvedResearchHourValue: number) {
  if (!selectedResearchWorkInformation.value) return;

  const approvedResearchWorkIdentifier =
    selectedResearchWorkInformation.value.researchWorkIdentifier;
  researchWorkInformationCollection.value =
    researchWorkInformationCollection.value.map((researchWorkInformation) => {
      if (
        researchWorkInformation.researchWorkIdentifier !==
        approvedResearchWorkIdentifier
      )
        return researchWorkInformation;
      return {
        ...researchWorkInformation,
        approvalStatus: "APPROVED",
        approvedResearchHourValue,
        rejectionReason: undefined,
        rejectionExplanation: undefined,
      };
    });

  closeResearchWorkApprovalDrawer();
}

function rejectResearchWork(rejectionPayload: {
  selectedRejectionReason: string;
  customRejectionExplanation: string;
}) {
  if (!selectedResearchWorkInformation.value) return;

  const rejectedResearchWorkIdentifier =
    selectedResearchWorkInformation.value.researchWorkIdentifier;
  researchWorkInformationCollection.value =
    researchWorkInformationCollection.value.map((researchWorkInformation) => {
      if (
        researchWorkInformation.researchWorkIdentifier !==
        rejectedResearchWorkIdentifier
      )
        return researchWorkInformation;
      return {
        ...researchWorkInformation,
        approvalStatus: "REJECTED",
        rejectionReason:
          rejectionPayload.selectedRejectionReason as ResearchWorkRejectionReason,
        rejectionExplanation:
          rejectionPayload.customRejectionExplanation || undefined,
        approvedResearchHourValue: undefined,
      };
    });

  closeResearchWorkApprovalDrawer();
}
</script>
