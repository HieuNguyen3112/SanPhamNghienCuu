import { computed, ref } from "vue";
import {
  mapper,
  type PersonalStats,
  type PersonalWorkDetail,
  type PersonalWorkFilterTab,
  type PersonalWorkRow,
} from "../contracts/personalResearchWorksContracts";
import { personalResearchWorksService } from "../services/personalResearchWorksService";

type SortKey = "updatedAt" | "title" | "workYear" | "roleName";
type SortOrder = "asc" | "desc";

const sortKeyMap: Record<SortKey, string> = {
  updatedAt: "updated_at",
  title: "title",
  workYear: "work_year",
  roleName: "role_name",
};

export function usePersonalResearchWorks() {
  const stats = ref<PersonalStats>({
    totalCount: 0,
    approvedCount: 0,
    pendingCount: 0,
    rejectedCount: 0,
    draftCount: 0,
  });

  const filterTab = ref<PersonalWorkFilterTab>("all");
  const rows = ref<PersonalWorkRow[]>([]);

  const selectedWorkId = ref<number | null>(null);
  const isDetailOpen = ref(false);
  const selectedWorkDetail = ref<PersonalWorkDetail | null>(null);

  const currentPageNumber = ref(1);
  const pageSize = ref(12);
  const totalItemCount = ref(0);

  const sortKey = ref<SortKey>("updatedAt");
  const sortOrder = ref<SortOrder>("desc");

  const loadingList = ref(false);
  const errorList = ref<string | null>(null);

  const loadingDetail = ref(false);
  const errorDetail = ref<string | null>(null);

  const activeRow = computed(() => {
    if (!selectedWorkId.value) return null;
    return rows.value.find((r) => r.activityId === selectedWorkId.value) ?? null;
  });

  const buildSortParam = () => {
    const key = sortKeyMap[sortKey.value] ?? "updated_at";
    const dir = sortOrder.value === "asc" ? "asc" : "desc";
    return `${key}:${dir}`;
  };

  async function loadWorks() {
    loadingList.value = true;
    errorList.value = null;

    try {
      const tabDto = mapper.tab.toDto(filterTab.value);
      const dto = await personalResearchWorksService.getIndex({
        status: tabDto,
        page: currentPageNumber.value,
        per_page: pageSize.value,
        sort: buildSortParam(),
      });

      stats.value = mapper.statsFromDto(dto.stats);
      rows.value = dto.items.map(mapper.rowFromDto);
      totalItemCount.value = dto.pagination.total;
    } catch (err) {
      errorList.value = err instanceof Error ? err.message : "Failed to load works.";
    } finally {
      loadingList.value = false;
    }
  }

  async function changeTab(nextTab: PersonalWorkFilterTab) {
    filterTab.value = nextTab;
    currentPageNumber.value = 1;
    await loadWorks();
  }

  async function selectCard(status: PersonalWorkFilterTab) {
    await changeTab(status);
  }

  async function openDetail(workId: number) {
    selectedWorkId.value = workId;
    isDetailOpen.value = true;
    await loadDetail(workId);
  }

  function closeDetail() {
    isDetailOpen.value = false;
    selectedWorkId.value = null;
    selectedWorkDetail.value = null;
    errorDetail.value = null;
  }

  async function loadDetail(workId: number) {
    loadingDetail.value = true;
    errorDetail.value = null;

    try {
      const dto = await personalResearchWorksService.getDetail(workId);
      selectedWorkDetail.value = mapper.detailFromDto(dto);
    } catch (err) {
      errorDetail.value = err instanceof Error ? err.message : "Failed to load work detail.";
    } finally {
      loadingDetail.value = false;
    }
  }

  function goToEditDraft(workId: number) {
    // TODO(router): integrate vue-router:
    // router.push(`/ke-khai-cong-trinh/${workId}`)
    window.location.assign(`/ke-khai-cong-trinh/${workId}`);
  }

  function copyFromRejected(workId: number) {
    goToEditDraft(workId);
  }

  async function handleSortChange(nextKey: SortKey, nextOrder: SortOrder) {
    sortKey.value = nextKey;
    sortOrder.value = nextOrder;
    currentPageNumber.value = 1;
    await loadWorks();
  }

  async function setPage(nextPage: number) {
    currentPageNumber.value = nextPage;
    await loadWorks();
  }

  async function setPageSize(nextSize: number) {
    pageSize.value = nextSize;
    currentPageNumber.value = 1;
    await loadWorks();
  }

  return {
    // state
    stats,
    filterTab,
    rows,
    totalItemCount,

    currentPageNumber,
    pageSize,
    sortKey,
    sortOrder,

    selectedWorkId,
    isDetailOpen,
    selectedWorkDetail,

    loadingList,
    errorList,
    loadingDetail,
    errorDetail,

    activeRow,

    // actions
    loadWorks,
    changeTab,
    selectCard,
    openDetail,
    closeDetail,
    goToEditDraft,
    copyFromRejected,
    handleSortChange,
    setPage,
    setPageSize,
  };
}
