import { computed, ref } from "vue";
import type {
  FacultyOption,
  GlobalResearchWorkSearchFilter,
  LecturerSuggestion,
  ResearchWorkDetail,
  ResearchWorkSummary,
} from "../contracts/globalResearchWorkSearch.contract";
import {
  detailFromDto,
  filterToDto,
  summaryFromDto,
} from "../contracts/globalResearchWorkSearch.contract";
import {
  getResearchWorkDetailDTO,
  searchResearchWorksDTO,
} from "../services/globalResearchWorkSearch.service";
import {
  facultyOptionsMock,
  lecturerSuggestionsMock,
} from "../mock-data/globalResearchWorkSearch.mock";

function clampYearRange(filter: GlobalResearchWorkSearchFilter) {
  const from = filter.yearFrom;
  const to = filter.yearTo;
  if (from != null && to != null && from > to) {
    return { ...filter, yearFrom: to, yearTo: from };
  }
  return filter;
}

export function useGlobalResearchWorkSearch() {
  const facultyOptions = ref<FacultyOption[]>(facultyOptionsMock);
  const lecturerSuggestions = ref<LecturerSuggestion[]>(
    lecturerSuggestionsMock
  );

  const years = computed<number[]>(() => {
    const nowYear = new Date().getFullYear();
    const list: number[] = [];
    for (let y = nowYear; y >= nowYear - 10; y -= 1) list.push(y);
    return list;
  });

  // draft filter on UI
  const filterDraft = ref<GlobalResearchWorkSearchFilter>({
    keyword: "",
    lecturerKeyword: "",
    facultyId: null,
    typeKey: "all",
    roleKey: "all",
    yearFrom: null,
    yearTo: null,
    statusCode: "approved", // default: approved
    managementLevel: "all",
  });

  const appliedFilter = ref<GlobalResearchWorkSearchFilter>({
    ...filterDraft.value,
  });

  const rows = ref<ResearchWorkSummary[]>([]);
  const loadingList = ref(false);
  const errorList = ref<string | null>(null);

  const detailOpen = ref(false);
  const selectedWorkId = ref<number | null>(null);
  const detail = ref<ResearchWorkDetail | null>(null);
  const loadingDetail = ref(false);
  const errorDetail = ref<string | null>(null);

  const resultCountText = computed(() => {
    if (loadingList.value) return "";
    if (errorList.value) return "";
    return `Tìm thấy ${rows.value.length} công trình phù hợp`;
  });

  async function search() {
    loadingList.value = true;
    errorList.value = null;

    appliedFilter.value = clampYearRange({ ...filterDraft.value });

    try {
      const dto = await searchResearchWorksDTO(
        filterToDto(appliedFilter.value)
      );

      // mock faculty_id filtering by mapping id -> name in UI layer
      const facultyId = appliedFilter.value.facultyId;
      const facultyName =
        facultyId != null
          ? facultyOptions.value.find((f) => f.id === facultyId)?.name ?? null
          : null;

      const mapped = dto.map(summaryFromDto);

      rows.value = facultyName
        ? mapped.filter((r) => r.facultyName === facultyName)
        : mapped;
    } catch (e) {
      errorList.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingList.value = false;
    }
  }

  function reset() {
    filterDraft.value = {
      keyword: "",
      lecturerKeyword: "",
      facultyId: null,
      typeKey: "all",
      roleKey: "all",
      yearFrom: null,
      yearTo: null,
      statusCode: "approved",
      managementLevel: "all",
    };
    void search();
  }

  async function openDetail(workId: number) {
    detailOpen.value = true;
    selectedWorkId.value = workId;
    detail.value = null;
    errorDetail.value = null;
    loadingDetail.value = true;

    try {
      const dto = await getResearchWorkDetailDTO(workId);
      detail.value = detailFromDto(dto);
    } catch (e) {
      errorDetail.value = e instanceof Error ? e.message : String(e);
    } finally {
      loadingDetail.value = false;
    }
  }

  function closeDetail() {
    detailOpen.value = false;
    selectedWorkId.value = null;
    detail.value = null;
    errorDetail.value = null;
  }

  return {
    // options
    facultyOptions,
    lecturerSuggestions,
    years,

    // filter
    filterDraft,
    appliedFilter,

    // list
    rows,
    loadingList,
    errorList,
    resultCountText,

    // detail
    detailOpen,
    selectedWorkId,
    detail,
    loadingDetail,
    errorDetail,

    // actions
    search,
    reset,
    openDetail,
    closeDetail,
  };
}
