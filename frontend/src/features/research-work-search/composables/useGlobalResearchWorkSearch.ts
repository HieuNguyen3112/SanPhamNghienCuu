import { computed, onBeforeUnmount, ref, watch } from "vue";
import { useUserStore } from "@/app/stores/userStore";
import type {
  AuthorRoleOption,
  DepartmentOption,
  FacultyOption,
  GlobalResearchWorkSearchFilter,
  LecturerSuggestion,
  ManagementLevelOption,
  ResearchWorkDetail,
  ResearchWorkFile,
  ResearchWorkSummary,
  StatusOption,
  WorkTypeOption,
} from "../contracts/globalResearchWorkSearch.contract";
import {
  detailFromDto,
  filterToDto,
  summaryFromDto,
} from "../contracts/globalResearchWorkSearch.contract";
import {
  downloadWorkAttachment,
  getLecturerSuggestions,
  getWorkDetail,
  getWorkSearchLookups,
  searchWorks,
  type ResearchWorkSearchScope,
} from "../api/researchWorkSearchApi";

function clampYearRange(filter: GlobalResearchWorkSearchFilter) {
  const from = filter.yearFrom;
  const to = filter.yearTo;
  if (from != null && to != null && from > to) {
    return { ...filter, yearFrom: to, yearTo: from };
  }
  return filter;
}

function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  link.click();
  window.URL.revokeObjectURL(url);
}

export function useGlobalResearchWorkSearch() {
  const userStore = useUserStore();
  const searchScope = computed<ResearchWorkSearchScope>(() =>
    userStore.role === "LECTURER" ? "lecturer" : "admin",
  );
  const isLecturer = computed(() => searchScope.value === "lecturer");

  const facultyOptions = ref<FacultyOption[]>([]);
  const departmentOptions = ref<DepartmentOption[]>([]);
  const workTypeOptions = ref<WorkTypeOption[]>([]);
  const authorRoleOptions = ref<AuthorRoleOption[]>([]);
  const statusOptions = ref<StatusOption[]>([]);
  const managementLevelOptions = ref<ManagementLevelOption[]>([]);
  const years = ref<number[]>([]);
  const lecturerSuggestions = ref<LecturerSuggestion[]>([]);

  const filterDraft = ref<GlobalResearchWorkSearchFilter>({
    keyword: "",
    lecturerKeyword: "",
    facultyId: null,
    departmentId: null,
    workTypeId: null,
    authorRole: null,
    yearFrom: null,
    yearTo: null,
    status: "approved",
    managementLevel: null,
  });

  const appliedFilter = ref<GlobalResearchWorkSearchFilter>({
    ...filterDraft.value,
  });

  const rows = ref<ResearchWorkSummary[]>([]);
  const loadingList = ref(false);
  const errorList = ref<string | null>(null);

  const pagination = ref({
    page: 1,
    perPage: 12,
    total: 0,
    lastPage: 1,
  });

  const detailOpen = ref(false);
  const selectedWorkId = ref<number | null>(null);
  const detail = ref<ResearchWorkDetail | null>(null);
  const loadingDetail = ref(false);
  const errorDetail = ref<string | null>(null);

  const resultCountText = computed(() => {
    if (loadingList.value || errorList.value) return "";
    return `Tìm thấy ${pagination.value.total} công trình phù hợp`;
  });

  async function loadLookups() {
    try {
      const lookups = await getWorkSearchLookups(searchScope.value);
      facultyOptions.value = lookups.faculties;
      departmentOptions.value = lookups.departments;
      workTypeOptions.value = lookups.work_types;
      authorRoleOptions.value = lookups.author_roles;
      if (isLecturer.value) {
        const approvedOnly = lookups.statuses.filter(
          (status) => status.code === "approved",
        );
        statusOptions.value = approvedOnly.length
          ? approvedOnly
          : lookups.statuses;
        filterDraft.value = { ...filterDraft.value, status: "approved" };
        appliedFilter.value = { ...appliedFilter.value, status: "approved" };
      } else {
        statusOptions.value = lookups.statuses;
      }
      managementLevelOptions.value = lookups.management_levels;
      years.value = lookups.years;
    } catch (e) {
      console.error(e);
    }
  }

  async function fetchList(nextPage?: number, nextPerPage?: number) {
    loadingList.value = true;
    errorList.value = null;

    const page = nextPage ?? pagination.value.page;
    const perPage = nextPerPage ?? pagination.value.perPage;
    const filter = clampYearRange({ ...appliedFilter.value });
    if (isLecturer.value) {
      filter.status = "approved";
    }

    try {
      const dto = await searchWorks(
        filterToDto(filter, page, perPage),
        searchScope.value,
      );
      rows.value = dto.table.items.map(summaryFromDto);
      pagination.value = {
        page: dto.table.pagination.page,
        perPage: dto.table.pagination.per_page,
        total: dto.table.pagination.total,
        lastPage: dto.table.pagination.last_page,
      };
    } catch (e) {
      console.error(e);
      errorList.value = "Không thể tải dữ liệu. Vui lòng thử lại.";
    } finally {
      loadingList.value = false;
    }
  }

  async function search() {
    const nextFilter = clampYearRange({ ...filterDraft.value });
    if (isLecturer.value) {
      nextFilter.status = "approved";
    }
    appliedFilter.value = nextFilter;
    await fetchList(1, pagination.value.perPage);
  }

  function reset() {
    filterDraft.value = {
      keyword: "",
      lecturerKeyword: "",
      facultyId: null,
      departmentId: null,
      workTypeId: null,
      authorRole: null,
      yearFrom: null,
      yearTo: null,
      status: "approved",
      managementLevel: null,
    };
    appliedFilter.value = { ...filterDraft.value };
    lecturerSuggestions.value = [];
    void fetchList(1, pagination.value.perPage);
  }

  async function openDetail(workId: number) {
    detailOpen.value = true;
    selectedWorkId.value = workId;
    detail.value = null;
    errorDetail.value = null;
    loadingDetail.value = true;

    try {
      const dto = await getWorkDetail(workId, searchScope.value);
      detail.value = detailFromDto(dto);
    } catch (e) {
      console.error(e);
      errorDetail.value = "Không thể tải chi tiết công trình.";
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

  async function downloadAttachment(file: ResearchWorkFile) {
    if (file.kind !== "file") {
      window.open(file.url, "_blank", "noopener");
      return;
    }

    try {
      const result = await downloadWorkAttachment(
        file.fileId,
        searchScope.value,
      );
      downloadBlob(result.blob, result.filename);
    } catch (e) {
      console.error(e);
      window.alert("Không thể tải tệp đính kèm. Vui lòng thử lại.");
    }
  }

  let lecturerTimer: number | null = null;
  watch(
    () => filterDraft.value.lecturerKeyword,
    (value) => {
      if (lecturerTimer) window.clearTimeout(lecturerTimer);

      const keyword = value.trim();
      if (!keyword) {
        lecturerSuggestions.value = [];
        return;
      }

      lecturerTimer = window.setTimeout(async () => {
        try {
          lecturerSuggestions.value = await getLecturerSuggestions(keyword);
        } catch (e) {
          console.error(e);
        }
      }, 300);
    },
    { immediate: false },
  );

  onBeforeUnmount(() => {
    if (lecturerTimer) window.clearTimeout(lecturerTimer);
  });

  return {
    facultyOptions,
    departmentOptions,
    workTypeOptions,
    authorRoleOptions,
    statusOptions,
    managementLevelOptions,
    years,
    lecturerSuggestions,

    filterDraft,
    appliedFilter,

    rows,
    loadingList,
    errorList,
    pagination,
    resultCountText,

    detailOpen,
    selectedWorkId,
    detail,
    loadingDetail,
    errorDetail,

    loadLookups,
    search,
    reset,
    fetchList,
    openDetail,
    closeDetail,
    downloadAttachment,
  };
}
