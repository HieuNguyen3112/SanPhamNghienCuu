// File: src/features/master-data/work-catalog-management/composables/useWorkCatalogs.ts
import { onBeforeUnmount, ref, watch } from "vue";
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

  function captureError(e: unknown) {
    errorMessage.value = e instanceof Error ? e.message : "Error occurred.";
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

  let workTypeSearchTimer: number | null = null;
  watch(workType.qWorkType, () => {
    if (activeTab.value !== "work_type") return;
    if (workTypeSearchTimer) window.clearTimeout(workTypeSearchTimer);
    workTypeSearchTimer = window.setTimeout(() => {
      workType.pageWorkType.value = 1;
      void loadActiveTab();
    }, 300);
  });
  watch(workType.pageWorkType, () => {
    if (activeTab.value !== "work_type") return;
    void loadActiveTab();
  });
  watch(workType.pageSizeWorkType, () => {
    if (activeTab.value !== "work_type") return;
    const shouldLoad = workType.pageWorkType.value === 1;
    workType.pageWorkType.value = 1;
    if (shouldLoad) void loadActiveTab();
  });

  let workLevelSearchTimer: number | null = null;
  watch(workLevel.qWorkLevel, () => {
    if (activeTab.value !== "work_level") return;
    if (workLevelSearchTimer) window.clearTimeout(workLevelSearchTimer);
    workLevelSearchTimer = window.setTimeout(() => {
      workLevel.pageWorkLevel.value = 1;
      void loadActiveTab();
    }, 300);
  });
  watch(workLevel.pageWorkLevel, () => {
    if (activeTab.value !== "work_level") return;
    void loadActiveTab();
  });
  watch(workLevel.pageSizeWorkLevel, () => {
    if (activeTab.value !== "work_level") return;
    const shouldLoad = workLevel.pageWorkLevel.value === 1;
    workLevel.pageWorkLevel.value = 1;
    if (shouldLoad) void loadActiveTab();
  });

  let journalSearchTimer: number | null = null;
  watch(journal.qJournal, () => {
    if (activeTab.value !== "journal") return;
    if (journalSearchTimer) window.clearTimeout(journalSearchTimer);
    journalSearchTimer = window.setTimeout(() => {
      journal.pageJournal.value = 1;
      void loadActiveTab();
    }, 300);
  });
  watch(journal.pageJournal, () => {
    if (activeTab.value !== "journal") return;
    void loadActiveTab();
  });
  watch(journal.pageSizeJournal, () => {
    if (activeTab.value !== "journal") return;
    const shouldLoad = journal.pageJournal.value === 1;
    journal.pageJournal.value = 1;
    if (shouldLoad) void loadActiveTab();
  });

  let conferenceSearchTimer: number | null = null;
  watch(conference.qConference, () => {
    if (activeTab.value !== "conference") return;
    if (conferenceSearchTimer) window.clearTimeout(conferenceSearchTimer);
    conferenceSearchTimer = window.setTimeout(() => {
      conference.pageConference.value = 1;
      void loadActiveTab();
    }, 300);
  });
  watch(conference.pageConference, () => {
    if (activeTab.value !== "conference") return;
    void loadActiveTab();
  });
  watch(conference.pageSizeConference, () => {
    if (activeTab.value !== "conference") return;
    const shouldLoad = conference.pageConference.value === 1;
    conference.pageConference.value = 1;
    if (shouldLoad) void loadActiveTab();
  });

  let researchFieldSearchTimer: number | null = null;
  watch(researchField.qResearchField, () => {
    if (activeTab.value !== "research_field") return;
    if (researchFieldSearchTimer) window.clearTimeout(researchFieldSearchTimer);
    researchFieldSearchTimer = window.setTimeout(() => {
      researchField.pageResearchField.value = 1;
      void loadActiveTab();
    }, 300);
  });
  watch(researchField.pageResearchField, () => {
    if (activeTab.value !== "research_field") return;
    void loadActiveTab();
  });
  watch(researchField.pageSizeResearchField, () => {
    if (activeTab.value !== "research_field") return;
    const shouldLoad = researchField.pageResearchField.value === 1;
    researchField.pageResearchField.value = 1;
    if (shouldLoad) void loadActiveTab();
  });

  onBeforeUnmount(() => {
    if (workTypeSearchTimer) window.clearTimeout(workTypeSearchTimer);
    if (workLevelSearchTimer) window.clearTimeout(workLevelSearchTimer);
    if (journalSearchTimer) window.clearTimeout(journalSearchTimer);
    if (conferenceSearchTimer) window.clearTimeout(conferenceSearchTimer);
    if (researchFieldSearchTimer) window.clearTimeout(researchFieldSearchTimer);
  });

  async function saveWorkType() {
    clearError();
    loading.value = true;
    try {
      await workType.saveWorkType();
    } catch (e) {
      captureError(e);
    } finally {
      loading.value = false;
    }
  }

  async function saveWorkLevel() {
    clearError();
    loading.value = true;
    try {
      await workLevel.saveWorkLevel();
    } catch (e) {
      captureError(e);
    } finally {
      loading.value = false;
    }
  }

  async function saveJournal() {
    clearError();
    loading.value = true;
    try {
      await journal.saveJournal();
    } catch (e) {
      captureError(e);
    } finally {
      loading.value = false;
    }
  }

  async function submitJournalRanking() {
    clearError();
    loading.value = true;
    try {
      await journal.submitJournalRanking();
    } catch (e) {
      captureError(e);
    } finally {
      loading.value = false;
    }
  }

  async function saveConference() {
    clearError();
    loading.value = true;
    try {
      await conference.saveConference();
    } catch (e) {
      captureError(e);
    } finally {
      loading.value = false;
    }
  }

  async function saveResearchField() {
    clearError();
    loading.value = true;
    try {
      await researchField.saveResearchField();
    } catch (e) {
      captureError(e);
    } finally {
      loading.value = false;
    }
  }

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

    saveWorkType,
    saveWorkLevel,
    saveJournal,
    submitJournalRanking,
    saveConference,
    saveResearchField,
  };
}
