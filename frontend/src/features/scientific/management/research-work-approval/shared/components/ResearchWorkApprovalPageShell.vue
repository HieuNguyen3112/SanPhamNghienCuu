<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-9xl p-4 md:p-6">
      <!-- PAGE HEADER -->
      <section
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <div
          class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
        >
          <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">
              {{ pageTitle }}
            </h1>
            <p class="mt-1 text-sm text-slate-600">
              {{ pageSubtitle }}
            </p>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-900"
            >
              {{ pendingBadgeText }}
            </span>
          </div>
        </div>
      </section>

      <div class="mt-4 space-y-4">
        <!-- FILTER PANEL -->
        <ResearchWorkApprovalFilterPanel
          :is-department-filter-visible="
            uiConfiguration.isDepartmentFilterVisible
          "
          :academic-year-options="academicYearOptions"
          :department-options="departmentOptions"
          :research-work-type-options="researchWorkTypeOptions"
          :approval-status-option-list="approvalStatusOptionList"
          :filter-panel-helper-text="filterPanelHelperText"
          v-model:selectedAcademicYear="selectedAcademicYearProxy"
          v-model:selectedDepartmentIdentifier="
            selectedDepartmentIdentifierProxy
          "
          v-model:selectedResearchWorkType="selectedResearchWorkTypeProxy"
          v-model:selectedApprovalStatus="selectedApprovalStatusProxy"
          v-model:selectedLecturerOrResearchWorkKeyword="
            selectedLecturerOrResearchWorkKeywordProxy
          "
          @resetFilterConditions="resetFilterConditions"
        />

        <!-- SUMMARY STRIP -->
        <!-- <section
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
        >
          <p class="text-sm text-slate-700">
            {{ uiConfiguration.summaryStripText }}
          </p>
        </section> -->

        <!-- APPROVAL LIST -->
        <ResearchWorkApprovalTable
          :approval-scope-identifier="uiConfiguration.approvalScopeIdentifier"
          :research-work-approval-list="filteredResearchWorkApprovalList"
          :total-pending-research-work-count="totalPendingResearchWorkCount"
          :table-action-button-label="uiConfiguration.tableActionButtonLabel"
          @openResearchWorkDetailDrawer="openResearchWorkDetailDrawer"
        />
      </div>
    </div>

    <!-- RIGHT-SIDE DETAIL DRAWER -->
    <ResearchWorkApprovalDetailDrawer
      :approval-scope-identifier="uiConfiguration.approvalScopeIdentifier"
      :is-open="isDetailDrawerOpen"
      :selected-research-work-approval-entry="selectedResearchWorkApprovalEntry"
      :is-official-research-hours-editable="
        uiConfiguration.isOfficialResearchHoursEditable
      "
      :drawer-title="uiConfiguration.drawerTitle"
      :drawer-subtitle="uiConfiguration.drawerSubtitle"
      :drawer-helper-text="drawerHelperText"
      :primary-action-button-label="uiConfiguration.primaryActionButtonLabel"
      :danger-action-button-label="uiConfiguration.dangerActionButtonLabel"
      :rejection-reason-option-list="rejectionReasonOptionList"
      :footer-helper-text="drawerFooterHelperText"
      @close="closeResearchWorkDetailDrawer"
      @approve="approve"
      @reject="reject"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, toRefs } from "vue";
import type {
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalUiConfiguration,
  ResearchWorkRejectionReasonType,
  ResearchWorkType,
} from "../models/researchWorkApprovalModels";

import ResearchWorkApprovalFilterPanel from "./ResearchWorkApprovalFilterPanel.vue";
import ResearchWorkApprovalTable from "./ResearchWorkApprovalTable.vue";
import ResearchWorkApprovalDetailDrawer from "./ResearchWorkApprovalDetailDrawer.vue";

const componentProperties = defineProps<{
  uiConfiguration: ResearchWorkApprovalUiConfiguration;

  pageTitle: string;
  pageSubtitle: string;
  pendingBadgeText: string;

  filterPanelHelperText: string;

  academicYearOptions: string[];
  departmentOptions: {
    departmentIdentifier: string;
    departmentDisplayName: string;
  }[];
  researchWorkTypeOptions: ResearchWorkType[];
  approvalStatusOptionList: { value: string; label: string }[];

  selectedAcademicYear: string;
  selectedDepartmentIdentifier: string;
  selectedResearchWorkType: ResearchWorkType | "ALL_RESEARCH_WORK_TYPES";
  selectedApprovalStatus: string;
  selectedLecturerOrResearchWorkKeyword: string;

  filteredResearchWorkApprovalList: ResearchWorkApprovalEntry[];
  totalPendingResearchWorkCount: number;

  isDetailDrawerOpen: boolean;
  selectedResearchWorkApprovalEntry: ResearchWorkApprovalEntry | null;

  drawerHelperText: string;
  drawerFooterHelperText: string;

  rejectionReasonOptionList: {
    value: ResearchWorkRejectionReasonType;
    label: string;
  }[];
}>();

const componentEvents = defineEmits<{
  (eventName: "update:selectedAcademicYear", value: string): void;
  (eventName: "update:selectedDepartmentIdentifier", value: string): void;
  (
    eventName: "update:selectedResearchWorkType",
    value: ResearchWorkType | "ALL_RESEARCH_WORK_TYPES",
  ): void;
  (eventName: "update:selectedApprovalStatus", value: string): void;
  (
    eventName: "update:selectedLecturerOrResearchWorkKeyword",
    value: string,
  ): void;

  (eventName: "resetFilterConditions"): void;

  (
    eventName: "openResearchWorkDetailDrawer",
    entry: ResearchWorkApprovalEntry,
  ): void;
  (eventName: "closeResearchWorkDetailDrawer"): void;

  (
    eventName: "approve",
    payload: {
      researchWorkIdentifier: number;
      officialResearchHours: number | null;
      memberHours?: { authorIdentifier: number; officialHours: number }[];
    },
  ): void;
  (
    eventName: "reject",
    payload: {
      researchWorkIdentifier: number;
      decision?: "reject" | "return_for_revision";
      rejectionReasonType: ResearchWorkRejectionReasonType;
      rejectionReasonDetail: string | null;
    },
  ): void;
}>();

/**
 * WHY: Props readonly -> không thể dùng v-model trực tiếp lên props.
 * Dùng computed getter/setter để forward update lên container/page.
 */
const selectedAcademicYearProxy = computed<string>({
  get: () => componentProperties.selectedAcademicYear,
  set: (nextValue) => componentEvents("update:selectedAcademicYear", nextValue),
});

const selectedDepartmentIdentifierProxy = computed<string>({
  get: () => componentProperties.selectedDepartmentIdentifier,
  set: (nextValue) =>
    componentEvents("update:selectedDepartmentIdentifier", nextValue),
});

const selectedResearchWorkTypeProxy = computed<
  ResearchWorkType | "ALL_RESEARCH_WORK_TYPES"
>({
  get: () => componentProperties.selectedResearchWorkType,
  set: (nextValue) =>
    componentEvents("update:selectedResearchWorkType", nextValue),
});

const selectedApprovalStatusProxy = computed<string>({
  get: () => componentProperties.selectedApprovalStatus,
  set: (nextValue) =>
    componentEvents("update:selectedApprovalStatus", nextValue),
});

const selectedLecturerOrResearchWorkKeywordProxy = computed<string>({
  get: () => componentProperties.selectedLecturerOrResearchWorkKeyword,
  set: (nextValue) =>
    componentEvents("update:selectedLecturerOrResearchWorkKeyword", nextValue),
});

function resetFilterConditions(): void {
  componentEvents("resetFilterConditions");
}

function openResearchWorkDetailDrawer(entry: ResearchWorkApprovalEntry): void {
  componentEvents("openResearchWorkDetailDrawer", entry);
}

function closeResearchWorkDetailDrawer(): void {
  componentEvents("closeResearchWorkDetailDrawer");
}

function approve(payload: {
  researchWorkIdentifier: number;
  officialResearchHours: number | null;
}): void {
  componentEvents("approve", payload);
}

function reject(payload: {
  researchWorkIdentifier: number;
  decision?: "reject" | "return_for_revision";
  rejectionReasonType: ResearchWorkRejectionReasonType;
  rejectionReasonDetail: string | null;
}): void {
  componentEvents("reject", payload);
}

const {
  uiConfiguration,
  pageTitle,
  pageSubtitle,
  pendingBadgeText,
  filterPanelHelperText,

  academicYearOptions,
  departmentOptions,
  researchWorkTypeOptions,
  approvalStatusOptionList,

  filteredResearchWorkApprovalList,
  totalPendingResearchWorkCount,

  isDetailDrawerOpen,
  selectedResearchWorkApprovalEntry,

  drawerHelperText,
  drawerFooterHelperText,

  rejectionReasonOptionList,
} = toRefs(componentProperties);
</script>
