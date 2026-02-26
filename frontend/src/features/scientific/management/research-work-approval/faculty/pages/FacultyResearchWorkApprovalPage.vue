<template>
  <div>
    <ResearchWorkApprovalPageShell
      :ui-configuration="facultyResearchWorkApprovalProvider.uiConfiguration"
      :page-title="facultyResearchWorkApprovalProvider.pageTitle"
      :page-subtitle="facultyResearchWorkApprovalProvider.pageSubtitle"
      :pending-badge-text="facultyResearchWorkApprovalProvider.pendingBadgeText"
      :filter-panel-helper-text="
        facultyResearchWorkApprovalProvider.filterPanelHelperText
      "
      :academic-year-options="
        facultyResearchWorkApprovalProvider.academicYearOptions
      "
      :department-options="facultyResearchWorkApprovalProvider.departmentOptions"
      :research-work-type-options="
        facultyResearchWorkApprovalProvider.researchWorkTypeOptions
      "
      :approval-status-option-list="
        facultyResearchWorkApprovalProvider.approvalStatusOptionList
      "
      v-model:selectedAcademicYear="
        facultyResearchWorkApprovalProvider.selectedAcademicYear
      "
      v-model:selectedDepartmentIdentifier="
        facultyResearchWorkApprovalProvider.selectedDepartmentIdentifier
      "
      v-model:selectedResearchWorkType="
        facultyResearchWorkApprovalProvider.selectedResearchWorkType
      "
      v-model:selectedApprovalStatus="
        facultyResearchWorkApprovalProvider.selectedApprovalStatus
      "
      v-model:selectedLecturerOrResearchWorkKeyword="
        facultyResearchWorkApprovalProvider.selectedLecturerOrResearchWorkKeyword
      "
      :filtered-research-work-approval-list="
        facultyResearchWorkApprovalProvider.filteredResearchWorkApprovalList
      "
      :total-pending-research-work-count="
        facultyResearchWorkApprovalProvider.totalPendingResearchWorkCount
      "
      :is-detail-drawer-open="
        facultyResearchWorkApprovalProvider.isDetailDrawerOpen
      "
      :selected-research-work-approval-entry="
        facultyResearchWorkApprovalProvider.selectedResearchWorkApprovalEntry
      "
      :drawer-helper-text="facultyResearchWorkApprovalProvider.drawerHelperText"
      :drawer-footer-helper-text="
        facultyResearchWorkApprovalProvider.drawerFooterHelperText
      "
      :rejection-reason-option-list="
        facultyResearchWorkApprovalProvider.rejectionReasonOptionList
      "
      @resetFilterConditions="
        facultyResearchWorkApprovalProvider.resetFilterConditions
      "
      @openResearchWorkDetailDrawer="
        facultyResearchWorkApprovalProvider.openResearchWorkDetailDrawer
      "
      @closeResearchWorkDetailDrawer="
        facultyResearchWorkApprovalProvider.closeResearchWorkDetailDrawer
      "
      @approve="
        (payload) =>
          facultyResearchWorkApprovalProvider.approve({
            researchWorkIdentifier: payload.researchWorkIdentifier,
            officialResearchHours: null,
            memberHours: payload.memberHours,
          })
      "
      @reject="facultyResearchWorkApprovalProvider.reject"
    />
  </div>
</template>

<script setup lang="ts">
import { proxyRefs } from "vue";
import ResearchWorkApprovalPageShell from "../../shared/components/ResearchWorkApprovalPageShell.vue";
import { useFacultyResearchWorkApprovalProvider } from "../composables/useFacultyResearchWorkApprovalProvider";

/**
 * WHY: provider là object chứa ref/computed.
 * proxyRefs giúp unwrap cấp 1 (vừa runtime vừa type) => hết lỗi Volar/TS.
 */
const facultyResearchWorkApprovalProvider = proxyRefs(
  useFacultyResearchWorkApprovalProvider()
);
</script>
