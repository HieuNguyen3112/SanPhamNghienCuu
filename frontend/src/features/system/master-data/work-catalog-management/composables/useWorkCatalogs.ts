import { computed, ref } from "vue";
import type { WorkCatalogTabKey } from "../contracts/workCatalogTabs.contract";

import { useWorkTypeCatalog } from "./useWorkTypeCatalog";
import { useWorkLevelCatalog } from "./useWorkLevelCatalog";
import { useJournalCatalog } from "./useJournalCatalog";
import { usePublisherCatalog } from "./usePublisherCatalog";
import { useConferenceCatalog } from "./useConferenceCatalog";
import { useResearchFieldCatalog } from "./useResearchFieldCatalog";

export function useWorkCatalogs() {
  const activeTab = ref<WorkCatalogTabKey>("work_type");
  const loading = ref(false);
  const errorMessage = ref<string | null>(null);

  const workType = useWorkTypeCatalog();
  const workLevel = useWorkLevelCatalog();
  const journal = useJournalCatalog();
  const publisher = usePublisherCatalog();
  const conference = useConferenceCatalog();
  const researchField = useResearchFieldCatalog();

  async function loadActiveTab(): Promise<void> {
    loading.value = true;
    errorMessage.value = null;

    try {
      if (activeTab.value === "work_type") await workType.load();
      else if (activeTab.value === "work_level") await workLevel.load();
      else if (activeTab.value === "journal") await journal.load();
      else if (activeTab.value === "publisher") await publisher.load();
      else if (activeTab.value === "conference") await conference.load();
      else await researchField.load();
    } catch (e: any) {
      errorMessage.value =
        e?.response?.data?.message ?? e?.message ?? "Có lỗi xảy ra.";
    } finally {
      loading.value = false;
    }
  }

  function formatDateTime(v: string | null | undefined): string {
    if (!v) return "";
    return v.replace("T", " ").replace(".000000Z", "");
  }

  const workTypes = computed(() => workType.workTypes.value);
  const workLevels = computed(() => workLevel.workLevels.value);
  const journals = computed(() => journal.journals.value);
  const publishers = computed(() => publisher.publishers.value);
  const conferences = computed(() => conference.conferences.value);
  const researchFields = computed(() => researchField.researchFields.value);

  return {
    activeTab,
    loading,
    errorMessage,
    loadActiveTab,
    formatDateTime,

    workTypes,
    workTypeTotal: workType.workTypeTotal,
    qWorkType: workType.qWorkType,
    pageWorkType: workType.pageWorkType,
    pageSizeWorkType: workType.pageSizeWorkType,
    filteredWorkTypes: workType.filteredWorkTypes,
    pagedWorkTypes: workType.pagedWorkTypes,
    modalWorkTypeOpen: workType.modalWorkTypeOpen,
    modalModeWorkType: workType.modalMode,
    workTypeForm: workType.workTypeForm,
    workTypeErrors: workType.workTypeErrors,
    openCreateWorkType: workType.openCreateWorkType,
    openEditWorkType: workType.openEditWorkType,
    saveWorkType: workType.saveWorkType,
    onUpdateWorkTypeForm: workType.onUpdateWorkTypeForm,

    workLevels,
    workLevelTotal: workLevel.workLevelTotal,
    qWorkLevel: workLevel.qWorkLevel,
    pageWorkLevel: workLevel.pageWorkLevel,
    pageSizeWorkLevel: workLevel.pageSizeWorkLevel,
    filteredWorkLevels: workLevel.filteredWorkLevels,
    pagedWorkLevels: workLevel.pagedWorkLevels,
    modalWorkLevelOpen: workLevel.modalWorkLevelOpen,
    modalModeWorkLevel: workLevel.modalMode,
    workLevelForm: workLevel.workLevelForm,
    workLevelErrors: workLevel.workLevelErrors,
    openCreateWorkLevel: workLevel.openCreateWorkLevel,
    openEditWorkLevel: workLevel.openEditWorkLevel,
    saveWorkLevel: workLevel.saveWorkLevel,
    onUpdateWorkLevelForm: workLevel.onUpdateWorkLevelForm,

    journals,
    journalTotal: journal.journalTotal,
    qJournal: journal.qJournal,
    pageJournal: journal.pageJournal,
    pageSizeJournal: journal.pageSizeJournal,
    filteredJournals: journal.filteredJournals,
    pagedJournals: journal.pagedJournals,
    modalJournalOpen: journal.modalJournalOpen,
    modalModeJournal: journal.modalMode,
    journalSuggestionModalOpen: journal.journalSuggestionModalOpen,
    journalSuggestionLoading: journal.journalSuggestionLoading,
    journalApprovingSuggestionId: journal.journalApprovingSuggestionId,
    journalSuggestions: journal.journalSuggestions,
    journalForm: journal.journalForm,
    journalErrors: journal.journalErrors,
    openCreateJournal: journal.openCreateJournal,
    openEditJournal: journal.openEditJournal,
    openJournalSuggestions: journal.openJournalSuggestions,
    closeJournalSuggestions: journal.closeJournalSuggestions,
    approveJournalSuggestion: journal.approveJournalSuggestion,
    rejectJournalSuggestion: journal.rejectJournalSuggestion,
    validateJournalForm: journal.validateJournalForm,
    saveJournal: journal.saveJournal,
    onUpdateJournalForm: journal.onUpdateJournalForm,

    publishers,
    publisherTotal: publisher.publisherTotal,
    qPublisher: publisher.qPublisher,
    pagePublisher: publisher.pagePublisher,
    pageSizePublisher: publisher.pageSizePublisher,
    filteredPublishers: publisher.filteredPublishers,
    pagedPublishers: publisher.pagedPublishers,
    modalPublisherOpen: publisher.modalPublisherOpen,
    modalModePublisher: publisher.modalMode,
    publisherSuggestionModalOpen: publisher.publisherSuggestionModalOpen,
    publisherSuggestionLoading: publisher.publisherSuggestionLoading,
    publisherApprovingSuggestionId: publisher.publisherApprovingSuggestionId,
    publisherSuggestions: publisher.publisherSuggestions,
    publisherForm: publisher.publisherForm,
    publisherErrors: publisher.publisherErrors,
    openCreatePublisher: publisher.openCreatePublisher,
    openEditPublisher: publisher.openEditPublisher,
    openPublisherSuggestions: publisher.openPublisherSuggestions,
    closePublisherSuggestions: publisher.closePublisherSuggestions,
    approvePublisherSuggestion: publisher.approvePublisherSuggestion,
    rejectPublisherSuggestion: publisher.rejectPublisherSuggestion,
    savePublisher: publisher.savePublisher,
    onUpdatePublisherForm: publisher.onUpdatePublisherForm,

    conferences,
    conferenceTotal: conference.conferenceTotal,
    qConference: conference.qConference,
    pageConference: conference.pageConference,
    pageSizeConference: conference.pageSizeConference,
    filteredConferences: conference.filteredConferences,
    pagedConferences: conference.pagedConferences,
    modalConferenceOpen: conference.modalConferenceOpen,
    modalModeConference: conference.modalMode,
    conferenceSuggestionModalOpen: conference.conferenceSuggestionModalOpen,
    conferenceSuggestionLoading: conference.conferenceSuggestionLoading,
    conferenceApprovingSuggestionId: conference.conferenceApprovingSuggestionId,
    conferenceSuggestions: conference.conferenceSuggestions,
    conferenceForm: conference.conferenceForm,
    conferenceErrors: conference.conferenceErrors,
    openCreateConference: conference.openCreateConference,
    openEditConference: conference.openEditConference,
    openConferenceSuggestions: conference.openConferenceSuggestions,
    closeConferenceSuggestions: conference.closeConferenceSuggestions,
    approveConferenceSuggestion: conference.approveConferenceSuggestion,
    rejectConferenceSuggestion: conference.rejectConferenceSuggestion,
    saveConference: conference.saveConference,
    onUpdateConferenceForm: conference.onUpdateConferenceForm,

    researchFields,
    researchFieldTotal: researchField.researchFieldTotal,
    qResearchField: researchField.qResearchField,
    pageResearchField: researchField.pageResearchField,
    pageSizeResearchField: researchField.pageSizeResearchField,
    filteredResearchFields: researchField.filteredResearchFields,
    pagedResearchFields: researchField.pagedResearchFields,
    modalResearchFieldOpen: researchField.modalResearchFieldOpen,
    modalModeResearchField: researchField.modalMode,
    researchFieldForm: researchField.researchFieldForm,
    researchFieldErrors: researchField.researchFieldErrors,
    openCreateResearchField: researchField.openCreateResearchField,
    openEditResearchField: researchField.openEditResearchField,
    saveResearchField: researchField.saveResearchField,
    onUpdateResearchFieldForm: researchField.onUpdateResearchFieldForm,
  };
}
