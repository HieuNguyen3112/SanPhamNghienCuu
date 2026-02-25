import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import {
  getErrorMessage,
  statusLabel,
  type AcademicYearPeriodRow,
  type AcademicYearDerivedDTO,
} from "../contracts/researchHoursCatalog.contract";
import { researchHoursCatalogService } from "../services/researchHoursCatalogService";

/**
 * TAB3 NOTE:
 * - academic_years: code,start_date,end_date,is_active
 * - Đợt tính giờ chưa có dữ liệu; UI hiện chỉ quản lý năm học.
 */

type ModalMode = "create" | "edit";

interface AcademicYearDraft {
  code: string;
  startDate: string;
  endDate: string;
  status: "active" | "inactive";
}

interface AcademicYearErrors {
  code?: string;
  startDate?: string;
  endDate?: string;
}

export function useAcademicYearPeriodCatalog() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref<string | null>(null);

  const rows = ref<AcademicYearPeriodRow[]>([]);
  const page = ref(1);
  const pageSize = ref(10);
  const totalItems = ref(0);
  const totalPages = ref(1);

  const filter = reactive({
    kind: "ALL" as "ALL" | "academic_year" | "period",
    status: "ALL" as "ALL" | "active" | "inactive" | "locked",
    q: "",
  });

  const modal = reactive({
    open: false,
    mode: "create" as ModalMode,
    editingId: 0 as number,
    editingKind: "academic_year" as "academic_year" | "period",
    isLocked: false,
  });

  const draft = reactive<AcademicYearDraft>({
    code: "",
    startDate: "",
    endDate: "",
    status: "inactive",
  });

  const draftErrors = reactive<AcademicYearErrors>({});

  const filteredRows = computed(() => rows.value);

  function resetDraftErrors() {
    Object.assign(draftErrors, {});
  }

  function openCreateYear() {
    modal.open = true;
    modal.mode = "create";
    modal.editingId = 0;
    modal.editingKind = "academic_year";
    modal.isLocked = false;

    draft.code = "";
    draft.startDate = "";
    draft.endDate = "";
    draft.status = "inactive";
    resetDraftErrors();
  }

  function openEditYear(row: AcademicYearPeriodRow) {
    modal.open = true;
    modal.mode = "edit";
    modal.editingId = row.id;
    modal.editingKind = row.kind;
    modal.isLocked = row.isLocked;

    draft.code = row.name;
    draft.startDate = row.startDate;
    draft.endDate = row.endDate;
    draft.status = row.status === "active" ? "active" : "inactive";
    resetDraftErrors();
  }

  function closeModal() {
    modal.open = false;
  }

  function validateYearDraft(): boolean {
    Object.assign(draftErrors, {});
    if (!draft.code.trim()) draftErrors.code = "Tên năm học là bắt buộc.";
    if (draft.code.trim().length > 9)
      draftErrors.code = "Tối đa 9 ký tự (VD: 2024-2025).";
    if (!draft.startDate) draftErrors.startDate = "Ngày bắt đầu là bắt buộc.";
    if (!draft.endDate) draftErrors.endDate = "Ngày kết thúc là bắt buộc.";
    if (draft.startDate && draft.endDate && draft.startDate > draft.endDate) {
      draftErrors.endDate = "Ngày kết thúc phải >= ngày bắt đầu.";
    }
    return !Object.values(draftErrors).some(Boolean);
  }

  function mapRows(years: AcademicYearDerivedDTO[]): AcademicYearPeriodRow[] {
    return years
      .map((y) => ({
        id: y.id,
        kind: "academic_year",
        name: y.code,
        startDate: y.start_date,
        endDate: y.end_date,
        status: y.is_active ? "active" : y.is_locked ? "locked" : "inactive",
        isLocked: y.is_locked,
      }))
      .sort((a, b) => (a.startDate < b.startDate ? 1 : -1));
  }

  async function fetch() {
    loading.value = true;
    error.value = null;
    try {
      if (filter.kind === "period") {
        rows.value = [];
        totalItems.value = 0;
        totalPages.value = 1;
        return;
      }

      const statusParam =
        filter.status === "ALL" || filter.status === "locked"
          ? undefined
          : filter.status;

      const perPage = filter.status === "locked" ? 100 : pageSize.value;
      const res = await researchHoursCatalogService.listAcademicYears({
        status: statusParam,
        q: filter.q.trim() || undefined,
        page: page.value,
        per_page: perPage,
      });

      let mapped = mapRows(res.items);
      if (filter.status === "locked") {
        mapped = mapped.filter((r) => r.isLocked);
        rows.value = mapped;
        totalItems.value = mapped.length;
        totalPages.value = 1;
        return;
      }

      rows.value = mapped;
      totalItems.value = res.pagination.total;
      totalPages.value = res.pagination.last_page;
    } catch (e) {
      error.value = getErrorMessage(e, "Không tải được năm học/đợt.");
    } finally {
      loading.value = false;
    }
  }

  async function saveYear() {
    if (!validateYearDraft()) return;

    // safety: locked -> cannot edit
    if (modal.mode === "edit" && modal.isLocked) {
      error.value = "Bản ghi đã khóa — không thể chỉnh sửa.";
      return;
    }

    saving.value = true;
    error.value = null;
    try {
      const payload = {
        code: draft.code.trim(),
        start_date: draft.startDate,
        end_date: draft.endDate,
        is_active: draft.status === "active",
      };

      if (modal.mode === "edit") {
        await researchHoursCatalogService.updateAcademicYear(
          modal.editingId,
          payload
        );
      } else {
        await researchHoursCatalogService.createAcademicYear(payload);
      }
      await researchHoursCatalogService.refreshMeta();
      closeModal();
      await fetch();
    } catch (e) {
      error.value = getErrorMessage(e, "Lưu năm học thất bại.");
      throw e;
    } finally {
      saving.value = false;
    }
  }

  async function setActiveYearWithConfirm(_id: number) {
    saving.value = true;
    error.value = null;
    try {
      await researchHoursCatalogService.applyAcademicYear(_id);
      await researchHoursCatalogService.refreshMeta();
      await fetch();
    } catch (e) {
      error.value = getErrorMessage(e, "Cập nhật trạng thái thất bại.");
      throw e;
    } finally {
      saving.value = false;
    }
  }

  function setPage(next: number) {
    page.value = Math.max(1, Math.min(totalPages.value, next));
    void fetch();
  }

  function setPageSize(next: number) {
    pageSize.value = next;
    page.value = 1;
    void fetch();
  }

  let searchTimer: number | null = null;
  watch(
    () => filter.q,
    () => {
      if (searchTimer) window.clearTimeout(searchTimer);
      searchTimer = window.setTimeout(() => {
        page.value = 1;
        void fetch();
      }, 300);
    }
  );

  watch(
    () => [filter.kind, filter.status],
    () => {
      page.value = 1;
      void fetch();
    }
  );

  onBeforeUnmount(() => {
    if (searchTimer) window.clearTimeout(searchTimer);
  });

  return {
    loading,
    saving,
    error,

    rows,
    page,
    pageSize,
    totalItems,
    totalPages,
    filter,
    filteredRows,

    modal,
    draft,
    draftErrors,

    fetch,
    openCreateYear,
    openEditYear,
    closeModal,
    saveYear,
    setActiveYearWithConfirm,
    setPage,
    setPageSize,

    statusLabel,
  };
}
