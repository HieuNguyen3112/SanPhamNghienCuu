// File: src/features/master-data/work-catalog-management/composables/useWorkCatalogs.ts
import { ref, watch } from "vue";
import type { WorkCatalogTabKey } from "../contracts/workCatalog.contract";

import { useWorkTypeCatalog } from "./useWorkTypeCatalog";
import { useWorkLevelCatalog } from "./useWorkLevelCatalog";
import { useJournalCatalog } from "./useJournalCatalog";
import { useConferenceCatalog } from "./useConferenceCatalog";
import { useResearchFieldCatalog } from "./useResearchFieldCatalog";

export function useWorkCatalogs() {
  const activeTab = ref<WorkCatalogTabKey>("work_type");

  const loading = ref(false);
  const errorMessage = ref("");

  const workType = useWorkTypeCatalog();
  const workLevel = useWorkLevelCatalog();
  const journal = useJournalCatalog();
  const conference = useConferenceCatalog();
  const researchField = useResearchFieldCatalog();

  function clearError() {
    errorMessage.value = "";
  }

  async function loadActiveTab(): Promise<void> {
    clearError();
    loading.value = true;
    try {
      switch (activeTab.value) {
        case "work_type":
          await workType.load();
          break;
        case "work_level":
          await workLevel.load();
          break;
        case "journal":
          await journal.load();
          break;
        case "conference":
          await conference.load();
          break;
        case "research_field":
          await researchField.load();
          break;
      }
    } catch (e) {
      errorMessage.value = e instanceof Error ? e.message : "Có lỗi xảy ra.";
    } finally {
      loading.value = false;
    }
  }

  watch(activeTab, loadActiveTab);

  return {
    // global
    activeTab,
    loading,
    errorMessage,
    loadActiveTab,

    // expose states (để page không phải sửa nhiều)
    ...workType,
    ...workLevel,
    ...journal,
    ...conference,
    ...researchField,
  };
}
