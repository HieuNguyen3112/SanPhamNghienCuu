import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  mapper,
  type PersonalStats,
  type PersonalWorkDetail,
  type PersonalWorkFilterTab,
  type PersonalWorkRow,
} from "../contracts/personalResearchWorksContracts";
import { personalResearchWorksService } from "../services/personalResearchWorksService";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

type SortKey = "updatedAt" | "title" | "workYear" | "roleName";
type SortOrder = "asc" | "desc";

const sortKeyMap: Record<SortKey, string> = {
  updatedAt: "updated_at",
  title: "title",
  workYear: "work_year",
  roleName: "role_name",
};

const allowedTabs: PersonalWorkFilterTab[] = [
  "all",
  "approved",
  "pending",
  "rejected",
  "draft",
];

export function usePersonalResearchWorks() {
  const { runPageLoad } = usePageLoadFeedback();
  const route = useRoute();
  const router = useRouter();

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
  const noticeMessage = ref<string | null>(null);
  const noticeTone = ref<"success" | "info" | "error">("info");

  const activeRow = computed(() => {
    if (!selectedWorkId.value) return null;
    return rows.value.find((row) => row.activityId === selectedWorkId.value) ?? null;
  });

  const buildSortParam = () => {
    const key = sortKeyMap[sortKey.value] ?? "updated_at";
    const direction = sortOrder.value === "asc" ? "asc" : "desc";
    return `${key}:${direction}`;
  };

  function resolveRequestedTab(): PersonalWorkFilterTab | null {
    const raw = route.query.tab;
    if (typeof raw !== "string") return null;
    return allowedTabs.includes(raw as PersonalWorkFilterTab)
      ? (raw as PersonalWorkFilterTab)
      : null;
  }

  function resolveRequestedActivityId(): number | null {
    const raw = route.query.activity_id;
    if (typeof raw !== "string") return null;
    const parsed = Number(raw);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
  }

  async function clearHandledRouteContext(options: {
    clearTab?: boolean;
    clearActivityId?: boolean;
  }) {
    const nextQuery = { ...route.query };
    if (options.clearTab) {
      delete nextQuery.tab;
    }
    if (options.clearActivityId) {
      delete nextQuery.activity_id;
    }

    await router.replace({ path: route.path, query: nextQuery }).catch(() => undefined);
  }

  async function loadWorksInternal() {
    loadingList.value = true;
    errorList.value = null;

    try {
      const status = mapper.tab.toDto(filterTab.value);
      const dto = await personalResearchWorksService.getIndex({
        status,
        page: currentPageNumber.value,
        per_page: pageSize.value,
        sort: buildSortParam(),
      });

      stats.value = mapper.statsFromDto(dto.stats);
      rows.value = dto.items.map(mapper.rowFromDto);
      totalItemCount.value = dto.pagination.total;
    } catch (error) {
      errorList.value =
        error instanceof Error ? error.message : "Không tải được danh sách công trình.";
    } finally {
      loadingList.value = false;
    }
  }

  async function loadWorks(options?: { withFeedback?: boolean }) {
    if (options?.withFeedback === false) {
      await loadWorksInternal();
      return;
    }

    await runPageLoad(loadWorksInternal, {
      loading: {
        title: "Đang tải công trình của tôi",
        message: "Hệ thống đang cập nhật danh sách công trình nghiên cứu...",
      },
    });
  }

  async function bootstrap() {
    const requestedTab = resolveRequestedTab();
    if (requestedTab) {
      filterTab.value = requestedTab;
      currentPageNumber.value = 1;
    }

    await runPageLoad(loadWorksInternal, {
      loading: {
        title: "Đang khởi tạo công trình của tôi",
        message: "Hệ thống đang chuẩn bị dữ liệu công trình nghiên cứu cá nhân...",
      },
    });

    const requestedActivityId = resolveRequestedActivityId();
    if (requestedActivityId) {
      await openDetail(requestedActivityId);

      if (selectedWorkDetail.value?.activityId === requestedActivityId) {
        noticeTone.value = "info";
        noticeMessage.value =
          "Công trình bị khoa trả về đã được mở trong Công trình của tôi. Chủ nhiệm và các thành viên đã chấp nhận tham gia đều xem lý do và tiếp tục cập nhật tại đây.";
      }
    }

    if (requestedTab || requestedActivityId) {
      await clearHandledRouteContext({
        clearTab: requestedTab !== null,
        clearActivityId: requestedActivityId !== null,
      });
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
    } catch (error) {
      errorDetail.value =
        error instanceof Error ? error.message : "Không tải được chi tiết công trình.";
    } finally {
      loadingDetail.value = false;
    }
  }

  function findKindCode(workId: number): string | null {
    const rowKindCode = rows.value.find((row) => row.activityId === workId)?.kindCode;
    if (rowKindCode) return rowKindCode;
    if (selectedWorkDetail.value?.activityId === workId) {
      return selectedWorkDetail.value.kindCode;
    }
    return null;
  }

  function goToEditDraft(workId: number) {
    const kindCode = findKindCode(workId);
    const query = `?activity_id=${workId}`;

    if (kindCode === "book") {
      window.location.assign(`/declarations/books${query}`);
      return;
    }

    if (kindCode === "project") {
      window.location.assign(`/declarations/projects${query}`);
      return;
    }

    if (kindCode === "conference") {
      window.location.assign(`/declarations/others${query}`);
      return;
    }

    window.location.assign(`/declarations/articles${query}`);
  }

  function copyFromRejected(workId: number) {
    goToEditDraft(workId);
  }

  async function reinviteFromRow(workId: number) {
    noticeMessage.value = null;

    if (!selectedWorkDetail.value || selectedWorkDetail.value.activityId !== workId) {
      await openDetail(workId);
    }

    const detail = selectedWorkDetail.value;
    if (!detail) {
      noticeTone.value = "error";
      noticeMessage.value = "Không tải được danh sách thành viên bị từ chối.";
      return;
    }

    if (detail.rejectedMembers.length === 0) {
      noticeTone.value = "info";
      noticeMessage.value = "Không còn thành viên nào ở trạng thái từ chối.";
      return;
    }

    if (detail.rejectedMembers.length > 1) {
      noticeTone.value = "info";
      noticeMessage.value =
        "Công trình có nhiều thành viên từ chối. Vui lòng mở chi tiết và chọn người cần gửi lại yêu cầu.";
      isDetailOpen.value = true;
      return;
    }

    const memberId = detail.rejectedMembers[0]?.memberId;
    if (!memberId) return;
    await reinviteMember(memberId);
  }

  async function reinviteMember(memberId: number) {
    if (!selectedWorkDetail.value) return;

    noticeMessage.value = null;

    try {
      await runPageLoad(
        async () => {
          await personalResearchWorksService.reinviteMember(
            selectedWorkDetail.value!.activityId,
            memberId,
          );
        },
        {
          loading: {
            title: "Đang gửi lại yêu cầu xác nhận",
            message: "Hệ thống đang cập nhật trạng thái thành viên tham gia...",
          },
          rethrow: true,
        },
      );

      noticeTone.value = "success";
      noticeMessage.value =
        "Đã gửi lại yêu cầu xác nhận tham gia cho thành viên.";
      await Promise.all([
        loadWorks({ withFeedback: false }),
        loadDetail(selectedWorkDetail.value.activityId),
      ]);
    } catch (error) {
      noticeTone.value = "error";
      noticeMessage.value =
        error instanceof Error ? error.message : "Không thể gửi lại yêu cầu xác nhận.";
    }
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
    noticeMessage,
    noticeTone,

    activeRow,

    bootstrap,
    loadWorks,
    changeTab,
    selectCard,
    openDetail,
    closeDetail,
    goToEditDraft,
    copyFromRejected,
    reinviteFromRow,
    reinviteMember,
    handleSortChange,
    setPage,
    setPageSize,
  };
}
