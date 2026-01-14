import { computed, onBeforeUnmount, reactive, ref, watch } from "vue";
import {
  getErrorMessage,
  hasErrors,
  upsertHourRulePayloadFromDraft,
  validateWorkConversionDraft,
  workConversionRowFromDto,
  type AcademicYearDTO,
  type ActivityKindDTO,
  type ActivityTypeDTO,
  type WorkConversionDraft,
  type WorkConversionErrors,
  type WorkConversionRow,
} from "../contracts/researchHoursCatalog.contract";
import { researchHoursCatalogService } from "../services/researchHoursCatalogService";

type ModalMode = "create" | "edit";

export function useWorkConversionCatalog() {
  const loading = ref(false);
  const saving = ref(false);
  const error = ref<string | null>(null);

  const academicYears = ref<AcademicYearDTO[]>([]);
  const kinds = ref<ActivityKindDTO[]>([]);
  const types = ref<ActivityTypeDTO[]>([]);
  const rows = ref<WorkConversionRow[]>([]);
  const page = ref(1);
  const pageSize = ref(10);
  const totalItems = ref(0);
  const totalPages = ref(1);

  const filter = reactive({
    academicYearId: 0 as number, // 0=all
    status: "ALL" as "ALL" | "ACTIVE" | "INACTIVE",
    q: "",
  });

  const modal = reactive({
    open: false,
    mode: "create" as ModalMode,
    editingId: 0 as number,
    isLocked: false,
  });

  const draft = reactive<WorkConversionDraft>({
    academicYearId: null,
    kindId: 1,
    typeId: null,
    hours: null,
    isActive: true,
    notes: "",
  });

  const draftErrors = reactive<WorkConversionErrors>({});

  const kindOptions = computed(() =>
    kinds.value.map((k) => ({ value: k.id, label: k.name, hint: k.code }))
  );

  const academicYearOptions = computed(() =>
    academicYears.value.map((y) => ({
      value: y.id,
      label: y.code,
      hint: `${y.start_date} → ${y.end_date}`,
    }))
  );

  const typeOptions = computed(() => {
    const list = types.value.filter((t) => t.kind_id === draft.kindId);
    return [
      { value: 0, label: "— (không chọn)", hint: "type_id = null" },
    ].concat(list.map((t) => ({ value: t.id, label: t.name, hint: t.code })));
  });

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
    draft.kindId = kinds.value[0]?.id ?? 1;
    draft.typeId = null;
    draft.hours = null;
    draft.isActive = true;
    draft.notes = "";
    resetDraftErrors();
  }

  function openEdit(row: WorkConversionRow) {
    modal.open = true;
    modal.mode = "edit";
    modal.editingId = row.id;
    modal.isLocked = row.isLocked;

    draft.academicYearId = row.academicYearId;
    draft.kindId = row.kindId;
    draft.typeId = row.typeId;
    draft.hours = row.hours;
    draft.isActive = row.isActive;
    draft.notes = row.notes; // schema missing; stays UI-only
    resetDraftErrors();
  }

  function closeModal() {
    modal.open = false;
  }

  async function fetch() {
    loading.value = true;
    error.value = null;
    try {
      const meta = await researchHoursCatalogService.getMeta();
      academicYears.value = meta.academic_years;
      kinds.value = meta.activity_kinds;
      types.value = meta.activity_types;

      const res = await researchHoursCatalogService.listHourRules({
        academic_year_id:
          filter.academicYearId === 0 ? undefined : filter.academicYearId,
        status: filter.status === "ALL" ? undefined : filter.status,
        q: filter.q.trim() || undefined,
        page: page.value,
        per_page: pageSize.value,
      });

      rows.value = res.items.map((dto) =>
        workConversionRowFromDto(dto, academicYears.value, kinds.value, types.value)
      );
      totalItems.value = res.pagination.total;
      totalPages.value = res.pagination.last_page;
    } catch (e) {
      error.value = getErrorMessage(e, "Không tải được dữ liệu quy đổi giờ.");
    } finally {
      loading.value = false;
    }
  }

  async function save() {
    const nextErrors = validateWorkConversionDraft(draft);
    Object.assign(draftErrors, nextErrors);
    if (hasErrors(nextErrors)) return;

    // safety: locked rule => disallow hours edit (allow only status change)
    if (modal.mode === "edit" && modal.isLocked) {
      const original = rows.value.find((r) => r.id === modal.editingId) ?? null;
      if (original && original.hours !== draft.hours) {
        draftErrors.hours =
          "Cấu hình đã phát sinh tính giờ — không thể sửa “Số giờ”. Chỉ được ngừng áp dụng.";
        return;
      }
    }

    saving.value = true;
    error.value = null;
    try {
      const payload = upsertHourRulePayloadFromDraft(
        draft,
        academicYears.value
      );
      if (modal.mode === "edit") {
        await researchHoursCatalogService.updateHourRule(
          modal.editingId,
          payload
        );
      } else {
        await researchHoursCatalogService.createHourRule(payload);
      }

      closeModal();
      await fetch();
    } catch (e) {
      error.value = getErrorMessage(e, "Lưu quy đổi thất bại.");
      throw e;
    } finally {
      saving.value = false;
    }
  }

  async function setActiveWithConfirm(rowId: number, isActive: boolean) {
    saving.value = true;
    error.value = null;
    try {
      await researchHoursCatalogService.setHourRuleActive(rowId, isActive);
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

    academicYears,
    kinds,
    types,

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

    kindOptions,
    typeOptions,
    academicYearOptions,

    fetch,
    setPage,
    setPageSize,
    openCreate,
    openEdit,
    closeModal,
    save,
    setActiveWithConfirm,
  };
}
