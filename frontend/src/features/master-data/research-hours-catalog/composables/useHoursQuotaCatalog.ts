import { computed, reactive, ref } from "vue";
import {
  getErrorMessage,
  hasErrors,
  parseDecimalToNumber,
  validateHoursQuotaDraft,
  type AcademicYearDTO,
  type HoursQuotaDraft,
  type HoursQuotaErrors,
  type HoursQuotaRow,
  type WorkloadQuotaRuleDerivedDTO,
} from "../contracts/researchHoursCatalog.contract";
import { researchHoursCatalogService } from "../services/researchHoursCatalogService";

/**
 * TAB2 NOTE:
 * - Schema hiện tại thiếu dimension "đối tượng" + is_active/is_locked.
 * - UI chạy mock + TODO(P0) backend/schema.
 */

type ModalMode = "create" | "edit";

interface RankOption {
  id: number;
  name: string;
}
const draftErrors = reactive<HoursQuotaErrors>({});
const RANKS: RankOption[] = [
  { id: 101, name: "Giảng viên" },
  { id: 102, name: "Giảng viên chính" },
  { id: 103, name: "PGS" },
  { id: 104, name: "GS" },
];

export function useHoursQuotaCatalog() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref<string | null>(null);

  const academicYears = ref<AcademicYearDTO[]>([]);
  const rows = ref<HoursQuotaRow[]>([]);

  const filter = reactive({
    academicYearId: 0 as number,
    status: "ALL" as "ALL" | "ACTIVE" | "INACTIVE",
    q: "",
  });

  const modal = reactive({
    open: false,
    mode: "create" as ModalMode,
    editingId: 0 as number,
    isLocked: false,
  });

  const draft = reactive<HoursQuotaDraft>({
    academicYearId: null,
    academicRankId: null,
    requiredHours: null,
    notes: "",
    isActive: true,
  });

  const draftErrors = reactive<HoursQuotaErrors>({});

  const academicYearOptions = computed(() =>
    academicYears.value.map((y) => ({
      value: y.id,
      label: y.code,
      hint: `${y.start_date} → ${y.end_date}`,
    }))
  );

  const rankOptions = computed(() =>
    RANKS.map((r) => ({ value: r.id, label: r.name }))
  );

  const filteredRows = computed(() => {
    const q = filter.q.trim().toLowerCase();
    return rows.value.filter((r) => {
      const byYear =
        filter.academicYearId === 0
          ? true
          : r.academicYearId === filter.academicYearId;
      const byStatus =
        filter.status === "ALL"
          ? true
          : filter.status === "ACTIVE"
          ? r.isActive
          : !r.isActive;
      const hay = `${r.academicYearCode} ${r.academicRankName}`.toLowerCase();
      const byQ = q ? hay.includes(q) : true;
      return byYear && byStatus && byQ;
    });
  });

  function resetDraftErrors() {
    Object.assign(draftErrors, {});
  }

  function openCreate() {
    modal.open = true;
    modal.mode = "create";
    modal.editingId = 0;
    modal.isLocked = false;

    draft.academicYearId =
      academicYears.value.find((y) => y.is_active)?.id ??
      academicYears.value[0]?.id ??
      null;
    draft.academicRankId = RANKS[0]?.id ?? null;
    draft.requiredHours = null;
    draft.notes = "";
    draft.isActive = true;
    resetDraftErrors();
  }

  function openEdit(row: HoursQuotaRow) {
    modal.open = true;
    modal.mode = "edit";
    modal.editingId = row.id;
    modal.isLocked = row.isLocked;

    draft.academicYearId = row.academicYearId;
    draft.academicRankId = row.academicRankId;
    draft.requiredHours = row.requiredHours;
    draft.notes = row.notes;
    draft.isActive = row.isActive;
    resetDraftErrors();
  }

  function closeModal() {
    modal.open = false;
  }

  function mapToRows(
    dto: WorkloadQuotaRuleDerivedDTO[],
    years: AcademicYearDTO[]
  ): HoursQuotaRow[] {
    return dto.map((x) => {
      const year = years.find((y) => y.id === x.academic_year_id);
      const rank = RANKS.find((r) => r.id === x.academic_rank_id);

      return {
        id: x.id,
        academicYearId: x.academic_year_id,
        academicYearCode: year?.code ?? "—",
        academicRankId: x.academic_rank_id,
        academicRankName: rank?.name ?? `#${x.academic_rank_id}`,
        requiredHours: parseDecimalToNumber(x.required_hours) ?? 0,
        notes: x.notes ?? "",
        isActive: x.is_active,
        isLocked: x.is_locked,
      };
    });
  }

  async function fetch() {
    loading.value = true;
    error.value = null;
    try {
      const [years, quotaRules] = await Promise.all([
        researchHoursCatalogService.getAcademicYears(),
        researchHoursCatalogService.getQuotaRules(),
      ]);

      academicYears.value = years;
      rows.value = mapToRows(quotaRules, years);
    } catch (e) {
      error.value = getErrorMessage(e, "Không tải được dữ liệu định mức.");
    } finally {
      loading.value = false;
    }
  }

  async function save() {
    const nextErrors = validateHoursQuotaDraft(draft);
    Object.assign(draftErrors, nextErrors);
    if (hasErrors(nextErrors)) return;

    // safety: locked => disallow requiredHours edit
    if (modal.mode === "edit" && modal.isLocked) {
      const original = rows.value.find((r) => r.id === modal.editingId) ?? null;
      if (
        original &&
        original.requiredHours !==
          (draft.requiredHours ?? original.requiredHours)
      ) {
        draftErrors.requiredHours =
          "Định mức đã được dùng tính KPI/hệ thống — không thể sửa. Chỉ được ngừng áp dụng.";
        return;
      }
    }

    saving.value = true;
    error.value = null;
    try {
      await researchHoursCatalogService.upsertQuotaRule();
      closeModal();
      await fetch();
    } catch (e) {
      error.value = getErrorMessage(e, "Lưu định mức thất bại.");
      throw e;
    } finally {
      saving.value = false;
    }
  }

  async function setActiveWithConfirm(_rowId: number, _isActive: boolean) {
    saving.value = true;
    error.value = null;
    try {
      await researchHoursCatalogService.setQuotaRuleActive();
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

    academicYearOptions,
    rankOptions,

    modal,
    draft,
    draftErrors,

    fetch,
    openCreate,
    openEdit,
    closeModal,
    save,
    setActiveWithConfirm,
  };
}
