import { computed, onMounted, ref } from "vue";
import type {
  AssignRolesPayload,
  LecturerAccount,
  LecturerAccountDTO,
  LecturerAccountFilterState,
  LecturerAccountScope,
  RoleOption,
  ToggleAccountStatusPayload,
  UnitOptionDTO,
  UpdateLecturerAccountPayload,
} from "../contracts/lecturerAccountManagement.contract";
import {
  DEFAULT_ROLE_OPTIONS,
  defaultFilterState,
  lecturerAccountFromDto,
  unitOptionFromDto,
} from "../contracts/lecturerAccountManagement.contract";
import type { LecturerAccountManagementService } from "../services/lecturerAccountManagementService";
import { createLecturerAccountManagementService } from "../services/lecturerAccountManagementService";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

export interface UseLecturerAccountManagementOptions {
  scope?: LecturerAccountScope;
  /**
   * For FACULTY scope only: fixed unit id for the board.
   * TODO: derive from /me or auth context.
   */
  faculty_unit_id?: number;
  /**
   * Allow injecting a custom service (real API) instead of mock.
   * Useful for tests.
   */
  service?: LecturerAccountManagementService;
}

export function useLecturerAccountManagement(
  options?: UseLecturerAccountManagementOptions
) {
  const scope: LecturerAccountScope = options?.scope ?? "UNIVERSITY";
  const service =
    options?.service ??
    createLecturerAccountManagementService({
      scope,
      faculty_unit_id: options?.faculty_unit_id,
    });

  const {
    showSuccessModal,
    showErrorModal,
  } = useActionResultModal();

  // filter state
  const filter = ref<LecturerAccountFilterState>(defaultFilterState());

  // options
  const unitOptions = ref<UnitOptionDTO[]>([]);
  const roleOptions = ref<RoleOption[]>(DEFAULT_ROLE_OPTIONS);

  // data
  const rowsDto = ref<LecturerAccountDTO[]>([]);
  const rows = computed<LecturerAccount[]>(() =>
    rowsDto.value.map(lecturerAccountFromDto)
  );

  const currentPageNumber = ref(1);
  const pageSize = ref(12);
  const totalItems = ref(0);

  const loading = ref(false);
  const error = ref<string | null>(null);
  const resultCount = computed(() => totalItems.value);

  // modal states
  const editOpen = ref(false);
  const rolesOpen = ref(false);
  const deactivateOpen = ref(false);

  const selectedAccountId = ref<number | null>(null);
  const selectedAccount = computed(() =>
    selectedAccountId.value == null
      ? null
      : rows.value.find((x) => x.id === selectedAccountId.value) ?? null
  );

  const savingEdit = ref(false);
  const savingRoles = ref(false);
  const savingDeactivate = ref(false);
  const savingError = ref<string | null>(null);

  function updateFilter(next: LecturerAccountFilterState) {
    // Faculty scope: unit filter is locked (but we still keep it in state for UI consistency)
    if (scope === "FACULTY") {
      filter.value = {
        ...next,
        unitId: "ALL",
      };
      return;
    }
    filter.value = { ...next };
  }

  function resetFilter() {
    filter.value = defaultFilterState();
    if (scope === "FACULTY") {
      filter.value.unitId = "ALL";
    }
  }

  async function bootstrap() {
    loading.value = true;
    error.value = null;
    try {
      const lookups = await service.getLookupsDTO();
      unitOptions.value = lookups.units;
      roleOptions.value = lookups.roles.length
        ? lookups.roles
        : DEFAULT_ROLE_OPTIONS;
      await search({ resetPage: true });
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Không tải được dữ liệu.";
    } finally {
      loading.value = false;
    }
  }

  async function fetchList() {
    const response = await service.searchLecturerAccountsDTO(filter.value, {
      page: currentPageNumber.value,
      per_page: pageSize.value,
    });
    rowsDto.value = response.items;
    totalItems.value = response.pagination.total;
  }

  async function search(options?: { resetPage?: boolean }) {
    const resetPage = options?.resetPage ?? true;
    if (resetPage) currentPageNumber.value = 1;

    loading.value = true;
    error.value = null;
    try {
      await fetchList();
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Không tải được danh sách.";
    } finally {
      loading.value = false;
    }
  }

  async function updatePage(nextPage: number) {
    currentPageNumber.value = nextPage;
    await search({ resetPage: false });
  }

  function updatePageSize(nextPageSize: number) {
    pageSize.value = nextPageSize;
  }

  // modal open/close
  function openEdit(id: number) {
    selectedAccountId.value = id;
    editOpen.value = true;
    rolesOpen.value = false;
    deactivateOpen.value = false;
    savingError.value = null;
  }

  function openRoles(id: number) {
    selectedAccountId.value = id;
    editOpen.value = false;
    rolesOpen.value = true;
    deactivateOpen.value = false;
    savingError.value = null;
  }

  function openDeactivate(id: number) {
    selectedAccountId.value = id;
    editOpen.value = false;
    rolesOpen.value = false;
    deactivateOpen.value = true;
    savingError.value = null;
  }

  function closeAllModals() {
    editOpen.value = false;
    rolesOpen.value = false;
    deactivateOpen.value = false;
    savingError.value = null;
  }

  async function saveEdit(payload: UpdateLecturerAccountPayload) {
    savingEdit.value = true;
    savingError.value = null;
    try {
      await service.updateLecturerAccountDTO(payload);
      showSuccessModal("Đã cập nhật thông tin giảng viên.");
      closeAllModals();
      await search({ resetPage: false });
    } catch (e) {
      const message = e instanceof Error ? e.message : "Không thể lưu.";
      savingError.value = message;
      showErrorModal(message, "Cập nhật thất bại", e);
    } finally {
      savingEdit.value = false;
    }
  }

  async function saveRoles(payload: AssignRolesPayload) {
    savingRoles.value = true;
    savingError.value = null;
    try {
      await service.assignRolesDTO(payload);
      showSuccessModal("Đã lưu phân quyền.");
      closeAllModals();
      await search({ resetPage: false });
    } catch (e) {
      const message = e instanceof Error ? e.message : "Không thể lưu.";
      savingError.value = message;
      showErrorModal(message, "Lưu phân quyền thất bại", e);
    } finally {
      savingRoles.value = false;
    }
  }

  async function confirmToggleStatus(payload: ToggleAccountStatusPayload) {
    savingDeactivate.value = true;
    savingError.value = null;
    try {
      await service.toggleAccountStatusDTO(payload);
      showSuccessModal("Đã cập nhật trạng thái tài khoản.");
      closeAllModals();
      await search({ resetPage: false });
    } catch (e) {
      const message = e instanceof Error ? e.message : "Không thể lưu.";
      savingError.value = message;
      showErrorModal(message, "Cập nhật thất bại", e);
    } finally {
      savingDeactivate.value = false;
    }
  }

  const unitOptionsUi = computed(() => unitOptions.value.map(unitOptionFromDto));

  onMounted(() => {
    void bootstrap();
  });

  return {
    scope,

    // filter + options
    filter,
    unitOptions: unitOptionsUi,
    roleOptions,

    // table data
    rows,
    loading,
    error,
    resultCount,
    currentPageNumber,
    pageSize,
    totalItems,
    // modal state
    editOpen,
    rolesOpen,
    deactivateOpen,
    selectedAccount,

    // saving state
    savingEdit,
    savingRoles,
    savingDeactivate,
    savingError,

    // actions
    updateFilter,
    resetFilter,
    search,
    updatePage,
    updatePageSize,

    openEdit,
    openRoles,
    openDeactivate,
    closeAllModals,

    saveEdit,
    saveRoles,
    confirmToggleStatus,
  };
}
