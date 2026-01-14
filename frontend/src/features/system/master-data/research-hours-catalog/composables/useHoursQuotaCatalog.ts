import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import {
  getErrorMessage,
  hasErrors,
  parseDecimalToNumber,
  validateHoursQuotaDraft,
  type AcademicYearDTO,
  type HoursQuotaDraft,
  type HoursQuotaErrors,
  type HoursQuotaRow,
  type WorkloadQuotaDerivedDTO,
} from "../contracts/researchHoursCatalog.contract";
import { researchHoursCatalogService } from "../services/researchHoursCatalogService";

/**
 * TAB2 NOTE:
 * - Schema hiện tại thiếu dimension "đối tượng" + is_active/is_locked.
 */

type ModalMode = "create" | "edit";

export function useHoursQuotaCatalog() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref<string | null>(null);

  const academicYears = ref<AcademicYearDTO[]>([]);
  const rows = ref<HoursQuotaRow[]>([]);
  const page = ref(1);
  const pageSize = ref(10);
  const totalItems = ref(0);
  const totalPages = ref(1);

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
    requiredHours: null,
    notes: "",
  });

  const draftErrors = reactive<HoursQuotaErrors>({});

  const academicYearOptions = computed(() =>
    academicYears.value.map((y) => ({
      value: y.id,
      label: y.code,
      hint: `${y.start_date} → ${y.end_date}`,
    }))
  );

  const filteredRows = computed(() => rows.value);

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
    draft.requiredHours = null;
    draft.notes = "";
    resetDraftErrors();
  }

  function openEdit(row: HoursQuotaRow) {
    modal.open = true;
    modal.mode = "edit";
    modal.editingId = row.id;
    modal.isLocked = row.isLocked;

    draft.academicYearId = row.academicYearId;
    draft.requiredHours = row.requiredHours;
    draft.notes = row.notes;
    resetDraftErrors();
  }

  function closeModal() {
    modal.open = false;
  }

  function mapToRows(
    dto: WorkloadQuotaDerivedDTO[],
    years: AcademicYearDTO[]
  ): HoursQuotaRow[] {
    return dto.map((x) => {
      const year = years.find((y) => y.id === x.academic_year_id);

      return {
        id: x.id,
        academicYearId: x.academic_year_id,
        academicYearCode: year?.code ?? "—",
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
      const meta = await researchHoursCatalogService.getMeta();
      academicYears.value = meta.academic_years;

      const res = await researchHoursCatalogService.listWorkloadQuotas({
        academic_year_id:
          filter.academicYearId === 0 ? undefined : filter.academicYearId,
        status: filter.status === "ALL" ? undefined : filter.status,
        q: filter.q.trim() || undefined,
        page: page.value,
        per_page: pageSize.value,
      });

      rows.value = mapToRows(res.items, academicYears.value);
      totalItems.value = res.pagination.total;
      totalPages.value = res.pagination.last_page;
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
      const payload = {
        required_hours: draft.requiredHours ?? 0,
        notes: draft.notes.trim() || null,
      };

      if (modal.mode === "edit") {
        await researchHoursCatalogService.updateWorkloadQuota(
          modal.editingId,
          payload
        );
      } else {
        await researchHoursCatalogService.createWorkloadQuota({
          academic_year_id: draft.academicYearId as number,
          ...payload,
        });
      }
      closeModal();
      await fetch();
    } catch (e) {
      error.value = getErrorMessage(e, "Lưu định mức thất bại.");
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
    () => [filter.academicYearId, filter.status],
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

    academicYearOptions,

    modal,
    draft,
    draftErrors,

    fetch,
    openCreate,
    openEdit,
    closeModal,
    save,
    setPage,
    setPageSize,
  };
}
