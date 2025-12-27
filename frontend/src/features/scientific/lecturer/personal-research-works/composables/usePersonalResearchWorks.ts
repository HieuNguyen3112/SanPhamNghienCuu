// src/features/personal-research-works/composables/usePersonalResearchWorks.ts
import { computed, ref } from "vue";
import {
  mapper,
  type PersonalStats,
  type PersonalWorkDetail,
  type PersonalWorkFilterTab,
  type PersonalWorkRow,
} from "../contracts/personalResearchWorksContracts";
import { personalResearchWorksService } from "../services/personalResearchWorksService";

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

  const loadingList = ref(false);
  const errorList = ref<string | null>(null);

  const loadingDetail = ref(false);
  const errorDetail = ref<string | null>(null);

  const activeRow = computed(() => {
    if (!selectedWorkId.value) return null;
    return (
      rows.value.find((r) => r.activityId === selectedWorkId.value) ?? null
    );
  });

  async function loadStats() {
    const dto = await personalResearchWorksService.get_stats();
    stats.value = mapper.statsFromDto(dto);
  }

  async function loadWorks() {
    loadingList.value = true;
    errorList.value = null;

    try {
      const tabDto = mapper.tab.toDto(filterTab.value);
      const dtos = await personalResearchWorksService.get_works_by_tab(tabDto);
      rows.value = dtos.map(mapper.rowFromDto);
    } catch (err) {
      errorList.value = err instanceof Error ? err.message : "Unknown error";
    } finally {
      loadingList.value = false;
    }
  }

  async function changeTab(nextTab: PersonalWorkFilterTab) {
    filterTab.value = nextTab;
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
      const dto = await personalResearchWorksService.get_work_detail(workId);
      selectedWorkDetail.value = mapper.detailFromDto(dto);
    } catch (err) {
      errorDetail.value = err instanceof Error ? err.message : "Unknown error";
    } finally {
      loadingDetail.value = false;
    }
  }

  function goToEditDraft(workId: number) {
    // TODO(router): integrate vue-router:
    // router.push(`/ke-khai-cong-trinh/${workId}`)
    window.location.assign(`/ke-khai-cong-trinh/${workId}`);
  }

  async function copyFromRejected(workId: number) {
    // Create a new draft row from rejected one
    const copiedDto = await personalResearchWorksService.copy_rejected_work(
      workId
    );
    // refresh list + stats, and go to edit
    await Promise.all([loadStats(), loadWorks()]);
    goToEditDraft(copiedDto.activity_id);
  }

  async function createNewDraft() {
    const createdDto = await personalResearchWorksService.create_new_draft();
    await Promise.all([loadStats(), loadWorks()]);
    goToEditDraft(createdDto.activity_id);
  }

  return {
    // state
    stats,
    filterTab,
    rows,

    selectedWorkId,
    isDetailOpen,
    selectedWorkDetail,

    loadingList,
    errorList,
    loadingDetail,
    errorDetail,

    activeRow,

    // actions
    loadStats,
    loadWorks,
    changeTab,
    selectCard,
    openDetail,
    closeDetail,
    goToEditDraft,
    copyFromRejected,
    createNewDraft,
  };
}
