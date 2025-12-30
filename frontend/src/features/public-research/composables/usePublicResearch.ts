import { computed, reactive, ref } from "vue";
import type {
  LoadingState,
  PublicResearchFilterState,
  PublicResearchItem,
  SelectOption,
} from "../models/publicResearchModels";
import type { PublicResearchListQueryDto } from "../dto/publicResearchDtos";
import { loadPublicResearchItemsService } from "../services/publicResearchService";
import {
  getPublicResearchStaticAcademicYears,
  getPublicResearchStaticFaculties,
  getPublicResearchStaticWorkTypes,
} from "../mock-data/publicResearchMockData";

const DEFAULT_PAGE_SIZE = 10;

export function usePublicResearch() {
  // ===== Required State =====
  const publicResearchItems = ref<PublicResearchItem[]>([]);
  const filterState = reactive<PublicResearchFilterState>({
    lecturerQuery: "",
    facultyId: null,
    workType: null,
    academicYearId: null,
    page: 1,
    pageSize: DEFAULT_PAGE_SIZE,
  });

  const selectedResearchId = ref<number | null>(null);
  const isResearchDrawerOpen = ref(false);

  const loadingState = ref<LoadingState>("idle");
  const errorState = ref<string | null>(null);

  // ===== Additional (needed for pagination) =====
  const totalItems = ref(0);

  // ===== Options for selects (derived from mock seeds) =====
  const facultyOptions = computed<SelectOption<number | null>[]>(() => {
    const base = [{ value: null, label: "Tất cả khoa" }];
    const rows = getPublicResearchStaticFaculties().map((f) => ({
      value: f.id,
      label: f.name,
    }));
    return [...base, ...rows];
  });

  const workTypeOptions = computed<SelectOption<string | null>[]>(() => {
    const base = [{ value: null, label: "Tất cả loại công trình" }];
    const rows = getPublicResearchStaticWorkTypes().map((t) => ({
      value: t,
      label:
        t === "ARTICLE"
          ? "Bài báo"
          : t === "BOOK"
            ? "Sách"
            : t === "PROJECT"
              ? "Đề tài"
              : t === "CONFERENCE"
                ? "Hội thảo"
                : "Khác",
    }));
    return [...base, ...rows];
  });

  const academicYearOptions = computed<SelectOption<number | null>[]>(() => {
    const base = [{ value: null, label: "Tất cả năm học" }];
    const rows = getPublicResearchStaticAcademicYears().map((y) => ({
      value: y.id,
      label: y.code,
    }));
    return [...base, ...rows];
  });

  const selectedResearchItem = computed<PublicResearchItem | null>(() => {
    if (selectedResearchId.value === null) return null;
    return (
      publicResearchItems.value.find((x) => x.id === selectedResearchId.value) ??
      null
    );
  });

  // ===== Required Actions =====
  async function loadPublicResearchItems() {
    loadingState.value = "loading";
    errorState.value = null;

    const queryDto: PublicResearchListQueryDto = {
      lecturer_query: filterState.lecturerQuery,
      faculty_id: filterState.facultyId,
      work_type: filterState.workType,
      academic_year_id: filterState.academicYearId,
      page: filterState.page,
      page_size: filterState.pageSize,
    };

    try {
      const result = await loadPublicResearchItemsService(queryDto);
      publicResearchItems.value = result.items;
      totalItems.value = result.total;
      loadingState.value = "success";
    } catch (error) {
      const message =
        typeof error === "object" && error && "message" in error
          ? String((error as { message: string }).message)
          : "Không thể tải dữ liệu tra cứu.";
      errorState.value = message;
      loadingState.value = "error";
    }
  }

  function updateFilterState(next: Partial<PublicResearchFilterState>) {
    Object.assign(filterState, next);
  }

  function resetFilterState() {
    filterState.lecturerQuery = "";
    filterState.facultyId = null;
    filterState.workType = null;
    filterState.academicYearId = null;
    filterState.page = 1;
    filterState.pageSize = DEFAULT_PAGE_SIZE;
  }

  function openResearchDrawer(researchId: number) {
    selectedResearchId.value = researchId;
    isResearchDrawerOpen.value = true;
  }

  function closeResearchDrawer() {
    isResearchDrawerOpen.value = false;
    selectedResearchId.value = null;
  }

  // ===== Derived pagination helpers =====
  const totalPages = computed(() => {
    const size = Math.max(1, filterState.pageSize);
    return Math.max(1, Math.ceil(totalItems.value / size));
  });

  const canGoPrev = computed(() => filterState.page > 1);
  const canGoNext = computed(() => filterState.page < totalPages.value);

  return {
    // Required State
    publicResearchItems,
    filterState,
    selectedResearchId,
    isResearchDrawerOpen,
    loadingState,
    errorState,

    // Helpful extras for UI (pagination/options/selected item)
    totalItems,
    totalPages,
    canGoPrev,
    canGoNext,
    facultyOptions,
    workTypeOptions,
    academicYearOptions,
    selectedResearchItem,

    // Required Actions
    loadPublicResearchItems,
    updateFilterState,
    resetFilterState,
    openResearchDrawer,
    closeResearchDrawer,
  };
}
