<template>
  <div
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
  >
    <div
      class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500"
    >
      <div class="grid grid-cols-12 gap-3">
        <div class="col-span-4">Công trình</div>
        <div class="col-span-2">Loại</div>
        <div class="col-span-3">Giảng viên</div>
        <div v-if="isFacultyColumnVisible" class="col-span-2">Khoa</div>
        <div class="col-span-1 text-right">Thao tác</div>
      </div>
    </div>

    <div
      v-if="isResearchWorkApprovalCollectionLoading"
      class="px-4 py-10 text-sm text-slate-600"
    >
      Đang tải danh sách công trình...
    </div>

    <div
      v-else-if="researchWorkApprovalCollectionErrorMessage"
      class="px-4 py-10 text-sm text-slate-700"
    >
      {{ researchWorkApprovalCollectionErrorMessage }}
    </div>

    <div
      v-else-if="researchWorkInformationCollection.length === 0"
      class="px-4 py-10 text-sm text-slate-600"
    >
      Không có công trình nào phù hợp với bộ lọc hiện tại.
    </div>

    <div v-else class="divide-y divide-slate-100">
      <div
        v-for="researchWorkInformation in researchWorkInformationCollection"
        :key="researchWorkInformation.researchWorkIdentifier"
        class="px-4 py-4 text-sm hover:bg-slate-50"
      >
        <div class="grid grid-cols-12 items-start gap-3">
          <div class="col-span-4">
            <div class="font-semibold text-slate-900">
              {{ researchWorkInformation.researchWorkTitle }}
            </div>
            <div class="mt-0.5 text-xs text-slate-600">
              Năm học: {{ researchWorkInformation.academicYearDisplayName }}
              <span class="text-slate-400">·</span>
              Trạng thái:
              <span
                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                :class="
                  mapResearchWorkApprovalStatusToBadgeClass(
                    researchWorkInformation.approvalStatus
                  )
                "
              >
                {{
                  mapResearchWorkApprovalStatusToDisplayText(
                    researchWorkInformation.approvalStatus
                  )
                }}
              </span>
            </div>
          </div>

          <div class="col-span-2 text-xs text-slate-700">
            {{
              mapResearchWorkTypeToDisplayText(
                researchWorkInformation.researchWorkType
              )
            }}
          </div>

          <div class="col-span-3">
            <div class="font-medium text-slate-900">
              {{ researchWorkInformation.lecturerFullName }}
            </div>
            <div class="mt-0.5 text-xs text-slate-600">
              Cấp công trình:
              {{ researchWorkInformation.researchWorkLevelDisplayName }}
            </div>
          </div>

          <div
            v-if="isFacultyColumnVisible"
            class="col-span-2 text-xs text-slate-700"
          >
            {{ researchWorkInformation.facultyName }}
          </div>

          <div class="col-span-1 text-right">
            <button
              type="button"
              class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300"
              :aria-label="`Xem và xét duyệt công trình ${researchWorkInformation.researchWorkTitle}`"
              @click="
                emitOpenResearchWorkApprovalDrawer(researchWorkInformation)
              "
            >
              Xem
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  ResearchWorkApprovalInformation,
  ResearchWorkApprovalStatus,
  ResearchWorkType,
} from "./researchWorkApprovalDomainTypes";

interface Props {
  researchWorkInformationCollection: ResearchWorkApprovalInformation[];
  isResearchWorkApprovalCollectionLoading: boolean;
  researchWorkApprovalCollectionErrorMessage?: string | null;
  isFacultyColumnVisible: boolean;
}

defineProps<Props>();

const emit = defineEmits<{
  (
    eventName: "open-research-work-approval-drawer",
    selectedResearchWorkInformation: ResearchWorkApprovalInformation
  ): void;
}>();

function emitOpenResearchWorkApprovalDrawer(
  selectedResearchWorkInformation: ResearchWorkApprovalInformation
) {
  emit("open-research-work-approval-drawer", selectedResearchWorkInformation);
}

function mapResearchWorkTypeToDisplayText(researchWorkType: ResearchWorkType) {
  switch (researchWorkType) {
    case "JOURNAL_ARTICLE":
      return "Bài báo tạp chí";
    case "CONFERENCE_PAPER":
      return "Bài báo hội thảo";
    case "RESEARCH_PROJECT":
      return "Đề tài nghiên cứu";
    case "TEXTBOOK_OR_MONOGRAPH":
      return "Sách / giáo trình";
    case "OTHER_RESEARCH_WORK":
    default:
      return "Khác";
  }
}

function mapResearchWorkApprovalStatusToDisplayText(
  researchWorkApprovalStatus: ResearchWorkApprovalStatus
) {
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

function mapResearchWorkApprovalStatusToBadgeClass(
  researchWorkApprovalStatus: ResearchWorkApprovalStatus
) {
  // Lý do: Dùng tông trung tính để phù hợp giao diện quản trị học thuật, tránh màu gắt.
  switch (researchWorkApprovalStatus) {
    case "PENDING":
      return "border-slate-200 bg-slate-50 text-slate-800";
    case "APPROVED":
      return "border-slate-200 bg-white text-slate-800";
    case "REJECTED":
      return "border-slate-200 bg-slate-100 text-slate-700";
    default:
      return "border-slate-200 bg-slate-50 text-slate-700";
  }
}
</script>
