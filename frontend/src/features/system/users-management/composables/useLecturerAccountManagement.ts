import { computed, onBeforeUnmount, onMounted, ref } from "vue";
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

  const loading = ref(false);
  const error = ref<string | null>(null);
  const resultCount = computed(() => rowsDto.value.length);

  // toast
  const toastMessage = ref<string | null>(null);
  let toastTimer: number | null = null;

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

  function showToast(message: string) {
    toastMessage.value = message;
    if (toastTimer != null) window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
      toastMessage.value = null;
      toastTimer = null;
    }, 2500);
  }

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
      unitOptions.value = await service.getUnitOptionsDTO();
      await search();
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Không tải được dữ liệu.";
    } finally {
      loading.value = false;
    }
  }

  async function search() {
    loading.value = true;
    error.value = null;
    try {
      rowsDto.value = await service.searchLecturerAccountsDTO(filter.value);
    } catch (e) {
      error.value =
        e instanceof Error ? e.message : "Không tải được danh sách.";
    } finally {
      loading.value = false;
    }
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
      showToast("Đã cập nhật thông tin giảng viên.");
      closeAllModals();
      await search();
    } catch (e) {
      savingError.value = e instanceof Error ? e.message : "Không thể lưu.";
    } finally {
      savingEdit.value = false;
    }
  }

  async function saveRoles(payload: AssignRolesPayload) {
    savingRoles.value = true;
    savingError.value = null;
    try {
      await service.assignRolesDTO(payload);
      showToast("Đã lưu phân quyền.");
      closeAllModals();
      await search();
    } catch (e) {
      savingError.value = e instanceof Error ? e.message : "Không thể lưu.";
    } finally {
      savingRoles.value = false;
    }
  }

  async function confirmToggleStatus(payload: ToggleAccountStatusPayload) {
    savingDeactivate.value = true;
    savingError.value = null;
    try {
      await service.toggleAccountStatusDTO(payload);
      showToast("Đã cập nhật trạng thái tài khoản.");
      closeAllModals();
      await search();
    } catch (e) {
      savingError.value = e instanceof Error ? e.message : "Không thể lưu.";
    } finally {
      savingDeactivate.value = false;
    }
  }

  const unitOptionsUi = computed(() =>
    unitOptions.value.map(unitOptionFromDto)
  );

  onMounted(() => {
    void bootstrap();
  });

  onBeforeUnmount(() => {
    if (toastTimer != null) window.clearTimeout(toastTimer);
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

    // toast
    toastMessage,

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

    openEdit,
    openRoles,
    openDeactivate,
    closeAllModals,

    saveEdit,
    saveRoles,
    confirmToggleStatus,
  };
}
