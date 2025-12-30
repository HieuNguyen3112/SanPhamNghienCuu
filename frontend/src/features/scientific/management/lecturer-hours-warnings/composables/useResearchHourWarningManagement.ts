import { computed, onMounted, ref } from "vue";
import type {
  LecturerResearchHourShortfallWarningEntry,
  LecturerResearchHourWarningOverview,
  ResearchHourShortfallSeverityFilterCondition,
  WarningNotificationRequestStateFilterCondition,
  FacultyIdentifier,
  AcademicYearIdentifier,
  ResearchHourWarningReasonCode,
} from "../contracts/lecturerResearchHourWarning.contract";
import { overviewFromDto } from "../contracts/lecturerResearchHourWarning.contract";
import type { ResearchHourWarningService } from "../services/researchHourWarningService";

export interface ResearchHourWarningFilterState {
  selectedFacultyIdentifier: FacultyIdentifier | "ALL_FACULTIES";
  selectedAcademicYearIdentifier: AcademicYearIdentifier;

  severityFilter: ResearchHourShortfallSeverityFilterCondition;
  notificationStateFilter: WarningNotificationRequestStateFilterCondition;

  keyword: string;
}

export function useResearchHourWarningManagement(
  service: ResearchHourWarningService
) {
  const overview = ref<LecturerResearchHourWarningOverview | null>(null);

  const filter = ref<ResearchHourWarningFilterState>({
    selectedFacultyIdentifier: "ALL_FACULTIES",
    selectedAcademicYearIdentifier: "2024-2025",

    severityFilter: "ALL",
    notificationStateFilter: "ALL",

    keyword: "",
  });

  const loading = ref(false);
  const error = ref<string | null>(null);

  const detailOpen = ref(false);
  const selectedEntry = ref<LecturerResearchHourShortfallWarningEntry | null>(
    null
  );

  const submittingWarning = ref(false);
  const submitWarningError = ref<string | null>(null);

  const facultyOptions = computed(
    () => overview.value?.facultyOptionList ?? []
  );
  const academicYearOptions = computed(
    () => overview.value?.academicYearOptionList ?? []
  );
  const summaryStatistics = computed(
    () => overview.value?.summaryStatistics ?? null
  );

  const entryList = computed(() => overview.value?.entryList ?? []);

  const isFacultyLocked = computed(() => facultyOptions.value.length === 1);

  // Client-side filter for now; backend can paginate later.
  const filteredEntries = computed(() => entryList.value);

  async function loadOverview() {
    loading.value = true;
    error.value = null;

    try {
      const dto = await service.getOverview({
        faculty_identifier: filter.value.selectedFacultyIdentifier,
        academic_year_identifier: filter.value.selectedAcademicYearIdentifier,
        severity_filter: filter.value.severityFilter,
        notification_state_filter: filter.value.notificationStateFilter,
        keyword: filter.value.keyword,
      });

      overview.value = overviewFromDto(dto);

      // If this is a faculty-scoped view, lock to the single faculty.
      if (isFacultyLocked.value && overview.value.facultyOptionList[0]) {
        filter.value.selectedFacultyIdentifier =
          overview.value.facultyOptionList[0].facultyIdentifier;
      }

      // If current year is missing, fall back to the first option.
      const yearExists = overview.value.academicYearOptionList.some(
        (y) =>
          y.academicYearIdentifier ===
          filter.value.selectedAcademicYearIdentifier
      );
      if (!yearExists && overview.value.academicYearOptionList[0]) {
        filter.value.selectedAcademicYearIdentifier =
          overview.value.academicYearOptionList[0].academicYearIdentifier;
      }
    } catch (e) {
      error.value = e instanceof Error ? e.message : String(e);
    } finally {
      loading.value = false;
    }
  }

  function patchFilter(next: Partial<ResearchHourWarningFilterState>) {
    filter.value = { ...filter.value, ...next };
    void loadOverview();
  }

  function resetFilter() {
    filter.value = {
      selectedFacultyIdentifier: isFacultyLocked.value
        ? facultyOptions.value[0]?.facultyIdentifier ?? "ALL_FACULTIES"
        : "ALL_FACULTIES",
      selectedAcademicYearIdentifier:
        filter.value.selectedAcademicYearIdentifier,

      severityFilter: "ALL",
      notificationStateFilter: "ALL",

      keyword: "",
    };
    void loadOverview();
  }

  function openDetail(entry: LecturerResearchHourShortfallWarningEntry) {
    selectedEntry.value = entry;
    detailOpen.value = true;
    submitWarningError.value = null;
  }

  function closeDetail() {
    detailOpen.value = false;
    selectedEntry.value = null;
    submitWarningError.value = null;
  }

  async function requestWarning(payload: {
    lecturerIdentifier: string;
    academicYearIdentifier: AcademicYearIdentifier;
    reasonCode: ResearchHourWarningReasonCode;
    reasonNote: string | null;
  }) {
    submitWarningError.value = null;
    submittingWarning.value = true;

    try {
      await service.requestWarningNotification({
        lecturer_identifier: payload.lecturerIdentifier,
        academic_year_identifier: payload.academicYearIdentifier,
        reason_code: payload.reasonCode,
        reason_note: payload.reasonNote,
      });

      await loadOverview();

      if (selectedEntry.value) {
        const refreshed = entryList.value.find(
          (x) =>
            x.lecturerIdentifier === payload.lecturerIdentifier &&
            x.academicYearIdentifier === payload.academicYearIdentifier
        );
        if (refreshed) selectedEntry.value = refreshed;
      }
    } catch (e) {
      submitWarningError.value = e instanceof Error ? e.message : String(e);
    } finally {
      submittingWarning.value = false;
    }
  }

  onMounted(() => {
    void loadOverview();
  });

  return {
    overview,

    filter,
    patchFilter,
    resetFilter,

    loading,
    error,

    facultyOptions,
    academicYearOptions,
    summaryStatistics,

    filteredEntries,
    isFacultyLocked,

    detailOpen,
    selectedEntry,
    openDetail,
    closeDetail,

    submittingWarning,
    submitWarningError,
    requestWarning,

    reload: loadOverview,
  };
}