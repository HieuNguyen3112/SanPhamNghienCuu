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
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

export interface ResearchHourWarningFilterState {
  selectedFacultyIdentifier: FacultyIdentifier | "ALL_FACULTIES";
  selectedAcademicYearIdentifier: AcademicYearIdentifier;
  severityFilter: ResearchHourShortfallSeverityFilterCondition;
  notificationStateFilter: WarningNotificationRequestStateFilterCondition;
  keyword: string;
}

export function useResearchHourWarningManagement(
  service: ResearchHourWarningService,
) {
  const { runWithFeedback } = useActionFeedback();
  const { runPageLoad } = usePageLoadFeedback();
  const overview = ref<LecturerResearchHourWarningOverview | null>(null);

  const filter = ref<ResearchHourWarningFilterState>({
    selectedFacultyIdentifier: "ALL_FACULTIES",
    selectedAcademicYearIdentifier: "",
    severityFilter: "ALL",
    notificationStateFilter: "ALL",
    keyword: "",
  });

  const loading = ref(false);
  const error = ref<string | null>(null);
  const detailOpen = ref(false);
  const selectedEntry = ref<LecturerResearchHourShortfallWarningEntry | null>(
    null,
  );
  const submittingWarning = ref(false);
  const submitWarningError = ref<string | null>(null);

  const facultyOptions = computed(
    () => overview.value?.facultyOptionList ?? [],
  );
  const academicYearOptions = computed(
    () => overview.value?.academicYearOptionList ?? [],
  );
  const summaryStatistics = computed(
    () => overview.value?.summaryStatistics ?? null,
  );
  const entryList = computed(() => overview.value?.entryList ?? []);
  const isFacultyLocked = computed(() => facultyOptions.value.length === 1);
  const filteredEntries = computed(() => entryList.value);

  async function loadOverviewInternal() {
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

      if (isFacultyLocked.value && overview.value.facultyOptionList[0]) {
        filter.value.selectedFacultyIdentifier =
          overview.value.facultyOptionList[0].facultyIdentifier;
      }

      const yearExists = overview.value.academicYearOptionList.some(
        (y) =>
          y.academicYearIdentifier ===
          filter.value.selectedAcademicYearIdentifier,
      );
      const activeYearIdentifier =
        overview.value.academicYearOptionList.find((year) => year.isActive)
          ?.academicYearIdentifier ?? null;

      if (!yearExists && activeYearIdentifier) {
        filter.value.selectedAcademicYearIdentifier = activeYearIdentifier;
      } else if (!yearExists && overview.value.academicYearOptionList[0]) {
        filter.value.selectedAcademicYearIdentifier =
          overview.value.academicYearOptionList[0].academicYearIdentifier;
      }
    } catch (e) {
      console.error(e);
      error.value = "Không tải được dữ liệu. Vui lòng thử lại.";
    } finally {
      loading.value = false;
    }
  }

  async function loadOverview(options?: { withFeedback?: boolean }) {
    if (options?.withFeedback === false) {
      await loadOverviewInternal();
      return;
    }

    await runPageLoad(loadOverviewInternal, {
      loading: {
        title: "Đang tải cảnh báo giảng viên",
        message: "Hệ thống đang cập nhật danh sách cảnh báo giờ NCKH...",
      },
    });
  }

  function patchFilter(next: Partial<ResearchHourWarningFilterState>) {
    filter.value = { ...filter.value, ...next };
    const nextKeys = Object.keys(next);
    const keywordOnly = nextKeys.length === 1 && nextKeys[0] === "keyword";
    void loadOverview({ withFeedback: !keywordOnly });
  }

  function resetFilter() {
    filter.value = {
      selectedFacultyIdentifier: isFacultyLocked.value
        ? (facultyOptions.value[0]?.facultyIdentifier ?? "ALL_FACULTIES")
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
      await runWithFeedback(
        async () => {
          await service.requestWarningNotification({
            lecturer_identifier: payload.lecturerIdentifier,
            academic_year_identifier: payload.academicYearIdentifier,
            reason_code: payload.reasonCode,
            reason_note: payload.reasonNote,
          });
          closeDetail();
          await loadOverview({ withFeedback: false });
        },
        {
          loading: {
            title: "Đang gửi cảnh báo",
            message: "Hệ thống đang xử lý yêu cầu...",
          },
          success: {
            title: "Thành công",
            message: "Đã gửi cảnh báo đến giảng viên.",
          },
          error: {
            title: "Có lỗi xảy ra",
            message: "Không thể gửi cảnh báo. Vui lòng thử lại.",
          },
        },
      );
    } catch (e) {
      console.error(e);
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
