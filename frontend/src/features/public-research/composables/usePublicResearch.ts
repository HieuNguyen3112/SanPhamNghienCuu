import { computed, reactive, ref } from "vue";
import type {
  LoadingState,
  PublicResearchFilterState,
  PublicResearchItem,
  SelectOption,
} from "../models/publicResearchModels";
import type { PublicResearchListQueryDto } from "../dto/publicResearchDtos";
import { loadPublicResearchItemsService } from "../services/publicResearchService";
import { fetchPublicResearchLookupsApi } from "../api/publicResearchLookupsApi";

const DEFAULT_PAGE_SIZE = 10;

const STATIC_WORK_TYPES = ["ARTICLE", "BOOK", "PROJECT", "CONFERENCE", "OTHER"] as const;

export function usePublicResearch() {
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

  const totalItems = ref(0);

  // ✅ lookups state
  const lookupsLoaded = ref(false);
  const lookupsLoading = ref(false);

  const facultyOptions = ref<SelectOption<number | null>[]>([
    { value: null, label: "Tất cả khoa" },
  ]);

  const academicYearOptions = ref<SelectOption<number | null>[]>([
    { value: null, label: "Tất cả năm học" },
  ]);

  const workTypeOptions = computed<SelectOption<string | null>[]>(() => {
    const base = [{ value: null, label: "Tất cả loại công trình" }];
    const rows = STATIC_WORK_TYPES.map((t) => ({
      value: t,
      label:
        t === "ARTICLE"
          ? "Bài báo"
          : t === "BOOK"
            ? "Sách - Giáo trình"
            : t === "PROJECT"
              ? "Đề tài"
              : t === "CONFERENCE"
                ? "Hội thảo"
                : "Khác",
    }));
    return [...base, ...rows];
  });

  async function ensureLookupsLoaded() {
    if (lookupsLoaded.value || lookupsLoading.value) return;

    lookupsLoading.value = true;
    try {
      const lookups = await fetchPublicResearchLookupsApi();

      facultyOptions.value = [
        { value: null, label: "Tất cả khoa" },
        ...lookups.faculties.map((f) => ({ value: f.id, label: f.name })),
      ];

      academicYearOptions.value = [
        { value: null, label: "Tất cả năm học" },
        ...lookups.academic_years.map((y) => ({ value: y.id, label: y.code })),
      ];

      lookupsLoaded.value = true;
    } catch {
      // fallback: vẫn giữ base option
      lookupsLoaded.value = false;
    } finally {
      lookupsLoading.value = false;
    }
  }

  const selectedResearchItem = computed<PublicResearchItem | null>(() => {
    if (selectedResearchId.value === null) return null;
    return publicResearchItems.value.find((x) => x.id === selectedResearchId.value) ?? null;
  });

  async function loadPublicResearchItems() {
    // ✅ đảm bảo dropdown khoa/năm học có data thật
    await ensureLookupsLoaded();

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

  const totalPages = computed(() => {
    const size = Math.max(1, filterState.pageSize);
    return Math.max(1, Math.ceil(totalItems.value / size));
  });

  const canGoPrev = computed(() => filterState.page > 1);
  const canGoNext = computed(() => filterState.page < totalPages.value);

  // ✅ auto fire lookups để Home dropdown có data ngay
  void ensureLookupsLoaded();

  return {
    publicResearchItems,
    filterState,
    selectedResearchId,
    isResearchDrawerOpen,
    loadingState,
    errorState,

    totalItems,
    totalPages,
    canGoPrev,
    canGoNext,
    facultyOptions,
    workTypeOptions,
    academicYearOptions,
    selectedResearchItem,

    loadPublicResearchItems,
    updateFilterState,
    resetFilterState,
    openResearchDrawer,
    closeResearchDrawer,
  };
}