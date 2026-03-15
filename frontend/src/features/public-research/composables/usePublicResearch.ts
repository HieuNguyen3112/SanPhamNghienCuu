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

const STATIC_WORK_TYPES = [
  "ARTICLE",
  "BOOK",
  "PROJECT",
  "CONFERENCE",
  "OTHER",
] as const;

type PublicResearchOverviewStats = {
  lecturerCount: number;
  articleCount: number;
  projectCount: number;
  bookCount: number;
  conferenceCount: number;
};

type PublicLecturerListRes = {
  success: boolean;
  data: {
    items: unknown[];
    pagination: {
      page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  };
};

async function fetchPublicLecturerTotal(): Promise<number> {
  const sp = new URLSearchParams();
  sp.set("page", "1");
  sp.set("per_page", "1");

  const url = `/api/public/lecturers?${sp.toString()}`;

  const res = await fetch(url, {
    method: "GET",
    headers: { Accept: "application/json" },
  });

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    console.error("fetchPublicLecturerTotal failed:", {
      url,
      status: res.status,
      body: text,
    });
    throw new Error(text || `HTTP ${res.status}`);
  }

  const json = (await res.json()) as PublicLecturerListRes;
  console.log("fetchPublicLecturerTotal success:", json);

  return Number(json.data?.pagination?.total ?? 0);
}

export function usePublicResearch() {
  const publicResearchItems = ref<PublicResearchItem[]>([]);

  const filterState = reactive<PublicResearchFilterState>({
    keyword: "",
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

  const overviewStats = ref<PublicResearchOverviewStats>({
    lecturerCount: 0,
    articleCount: 0,
    projectCount: 0,
    bookCount: 0,
    conferenceCount: 0,
  });

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
    } catch (error) {
      console.error("ensureLookupsLoaded failed:", error);
      lookupsLoaded.value = false;
    } finally {
      lookupsLoading.value = false;
    }
  }

  const selectedResearchItem = computed<PublicResearchItem | null>(() => {
    if (selectedResearchId.value === null) return null;
    return (
      publicResearchItems.value.find((x) => x.id === selectedResearchId.value) ??
      null
    );
  });

  async function loadPublicResearchItems() {
    await ensureLookupsLoaded();

    loadingState.value = "loading";
    errorState.value = null;

    const queryDto: PublicResearchListQueryDto = {
      q: filterState.keyword?.trim() || null,
      lecturer_query: filterState.lecturerQuery,
      faculty_id: filterState.facultyId,
      work_type: filterState.workType,
      academic_year_id: filterState.academicYearId,
      page: filterState.page,
      page_size: filterState.pageSize,
    };

    try {
      const result = await loadPublicResearchItemsService(queryDto);
      console.log("loadPublicResearchItems result:", result);

      publicResearchItems.value = result.items;
      totalItems.value = Number(result.total ?? 0);
      loadingState.value = "success";
    } catch (error) {
      console.error("loadPublicResearchItems failed:", error);

      const message =
        typeof error === "object" && error && "message" in error
          ? String((error as { message: string }).message)
          : "Không thể tải dữ liệu tra cứu.";

      errorState.value = message;
      loadingState.value = "error";
    }
  }

  async function loadOverviewStats() {
    const nextStats: PublicResearchOverviewStats = {
      lecturerCount: 0,
      articleCount: 0,
      projectCount: 0,
      bookCount: 0,
      conferenceCount: 0,
    };

    const results = await Promise.allSettled([
      fetchPublicLecturerTotal(),
      loadPublicResearchItemsService({
        q: null,
        lecturer_query: "",
        faculty_id: null,
        work_type: "ARTICLE",
        academic_year_id: null,
        page: 1,
        page_size: 1,
      }),
      loadPublicResearchItemsService({
        q: null,
        lecturer_query: "",
        faculty_id: null,
        work_type: "PROJECT",
        academic_year_id: null,
        page: 1,
        page_size: 1,
      }),
      loadPublicResearchItemsService({
        q: null,
        lecturer_query: "",
        faculty_id: null,
        work_type: "BOOK",
        academic_year_id: null,
        page: 1,
        page_size: 1,
      }),
      loadPublicResearchItemsService({
        q: null,
        lecturer_query: "",
        faculty_id: null,
        work_type: "CONFERENCE",
        academic_year_id: null,
        page: 1,
        page_size: 1,
      }),
    ]);

    if (results[0].status === "fulfilled") {
      nextStats.lecturerCount = Number(results[0].value ?? 0);
    } else {
      console.error("loadOverviewStats lecturer failed:", results[0].reason);
    }

    if (results[1].status === "fulfilled") {
      console.log("overview article raw:", results[1].value);
      nextStats.articleCount = Number(results[1].value?.total ?? 0);
    } else {
      console.error("loadOverviewStats article failed:", results[1].reason);
    }

    if (results[2].status === "fulfilled") {
      console.log("overview project raw:", results[2].value);
      nextStats.projectCount = Number(results[2].value?.total ?? 0);
    } else {
      console.error("loadOverviewStats project failed:", results[2].reason);
    }

    if (results[3].status === "fulfilled") {
      console.log("overview book raw:", results[3].value);
      nextStats.bookCount = Number(results[3].value?.total ?? 0);
    } else {
      console.error("loadOverviewStats book failed:", results[3].reason);
    }

    if (results[4].status === "fulfilled") {
      console.log("overview conference raw:", results[4].value);
      nextStats.conferenceCount = Number(results[4].value?.total ?? 0);
    } else {
      console.error("loadOverviewStats conference failed:", results[4].reason);
    }

    overviewStats.value = nextStats;
    console.log("overviewStats final:", overviewStats.value);
  }

  function updateFilterState(next: Partial<PublicResearchFilterState>) {
    Object.assign(filterState, next);
  }

  function resetFilterState() {
    filterState.keyword = "";
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
    overviewStats,

    loadPublicResearchItems,
    loadOverviewStats,
    updateFilterState,
    resetFilterState,
    openResearchDrawer,
    closeResearchDrawer,
  };
}