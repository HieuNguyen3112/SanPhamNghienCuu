import { computed, reactive, ref } from "vue";
import {
  getErrorMessage,
  statusLabel,
  type AcademicYearDTO,
  type AcademicYearPeriodRow,
  type AcademicYearPeriodDerivedDTO,
} from "../contracts/researchHoursCatalog.contract";
import { researchHoursCatalogService } from "../services/researchHoursCatalogService";

/**
 * TAB3 NOTE:
 * - academic_years có: code,start_date,end_date,is_active
 * - “Đợt” + “locked” là missing (P0) => UI mock + TODO backend
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
  const academicYears = ref<AcademicYearDTO[]>([]);
  const periods = ref<AcademicYearPeriodDerivedDTO[]>([]);

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

  const filteredRows = computed(() => {
    const q = filter.q.trim().toLowerCase();
    return rows.value.filter((r) => {
      const byKind = filter.kind === "ALL" ? true : r.kind === filter.kind;
      const byStatus =
        filter.status === "ALL" ? true : r.status === filter.status;
      const hay = `${r.name} ${r.startDate} ${r.endDate}`.toLowerCase();
      const byQ = q ? hay.includes(q) : true;
      return byKind && byStatus && byQ;
    });
  });

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

  function mapRows(
    years: AcademicYearDTO[],
    ps: AcademicYearPeriodDerivedDTO[]
  ): AcademicYearPeriodRow[] {
    const yearRows: AcademicYearPeriodRow[] = years.map((y) => ({
      id: y.id,
      kind: "academic_year",
      name: y.code,
      startDate: y.start_date,
      endDate: y.end_date,
      status: y.is_active ? "active" : "inactive",
      // schema missing locked -> derive false for years in this mock
      isLocked: false,
    }));

    const periodRows: AcademicYearPeriodRow[] = ps.map((p) => ({
      id: p.id,
      kind: "period",
      name: p.name,
      startDate: p.start_date,
      endDate: p.end_date,
      status: p.status,
      isLocked: p.is_locked,
    }));

    return [...yearRows, ...periodRows].sort((a, b) =>
      a.startDate < b.startDate ? 1 : -1
    );
  }

  async function fetch() {
    loading.value = true;
    error.value = null;
    try {
      const [years, ps] = await Promise.all([
        researchHoursCatalogService.getAcademicYears(),
        researchHoursCatalogService.getAcademicYearPeriods(),
      ]);

      academicYears.value = years;
      periods.value = ps;
      rows.value = mapRows(years, ps);
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
      // TODO: upsert academic_years
      await researchHoursCatalogService.upsertAcademicYear();
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
    // TODO: enforce single active year in backend
    saving.value = true;
    error.value = null;
    try {
      await researchHoursCatalogService.setAcademicYearActive();
      await fetch();
    } catch (e) {
      error.value = getErrorMessage(e, "Cập nhật trạng thái thất bại.");
      throw e;
    } finally {
      saving.value = false;
    }
  }

  return {
    loading,
    saving,
    error,

    rows,
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

    statusLabel,
  };
}
