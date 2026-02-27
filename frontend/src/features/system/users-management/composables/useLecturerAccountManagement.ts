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
import {
  resolveApiErrorMessage,
  useActionFeedback,
} from "@/shared/composables/useActionFeedback";

export interface UseLecturerAccountManagementOptions {
  scope?: LecturerAccountScope;
  faculty_unit_id?: number;
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
  const { runWithFeedback } = useActionFeedback();

  const filter = ref<LecturerAccountFilterState>(defaultFilterState());
  const unitOptions = ref<UnitOptionDTO[]>([]);
  const roleOptions = ref<RoleOption[]>(DEFAULT_ROLE_OPTIONS);
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

  function resolveFriendlyErrorMessage(error: unknown, fallback: string) {
    return resolveApiErrorMessage(error, fallback);
  }

  function updateFilter(next: LecturerAccountFilterState) {
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
      error.value = resolveFriendlyErrorMessage(
        e,
        "Không tải được danh sách tài khoản."
      );
    } finally {
      loading.value = false;
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
      error.value = resolveFriendlyErrorMessage(
        e,
        "Không tải được dữ liệu khởi tạo."
      );
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
      await runWithFeedback(
        async () => {
          await service.updateLecturerAccountDTO(payload);
          closeAllModals();
          await search({ resetPage: false });
        },
        {
          loading: {
            title: "Đang cập nhật",
            message: "Hệ thống đang lưu thông tin giảng viên...",
          },
          success: {
            title: "Thành công",
            message: "Đã cập nhật thông tin giảng viên.",
          },
          error: {
            title: "Cập nhật thất bại",
            message: (error) =>
              resolveFriendlyErrorMessage(error, "Không thể lưu thông tin."),
          },
          rethrow: false,
        }
      );
    } catch (e) {
      savingError.value = resolveFriendlyErrorMessage(e, "Không thể lưu thông tin.");
    } finally {
      savingEdit.value = false;
    }
  }

  async function saveRoles(payload: AssignRolesPayload) {
    savingRoles.value = true;
    savingError.value = null;
    try {
      await runWithFeedback(
        async () => {
          await service.assignRolesDTO(payload);
          closeAllModals();
          await search({ resetPage: false });
        },
        {
          loading: {
            title: "Đang lưu phân quyền",
            message: "Hệ thống đang cập nhật vai trò...",
          },
          success: {
            title: "Thành công",
            message: "Đã lưu phân quyền.",
          },
          error: {
            title: "Lưu phân quyền thất bại",
            message: (error) =>
              resolveFriendlyErrorMessage(error, "Không thể lưu phân quyền."),
          },
          rethrow: false,
        }
      );
    } catch (e) {
      savingError.value = resolveFriendlyErrorMessage(e, "Không thể lưu phân quyền.");
    } finally {
      savingRoles.value = false;
    }
  }

  async function confirmToggleStatus(payload: ToggleAccountStatusPayload) {
    savingDeactivate.value = true;
    savingError.value = null;
    try {
      await runWithFeedback(
        async () => {
          await service.toggleAccountStatusDTO(payload);
          closeAllModals();
          await search({ resetPage: false });
        },
        {
          loading: {
            title: "Đang cập nhật trạng thái",
            message: "Vui lòng đợi hệ thống xử lý...",
          },
          success: {
            title: "Thành công",
            message: "Đã cập nhật trạng thái tài khoản.",
          },
          error: {
            title: "Cập nhật thất bại",
            message: (error) =>
              resolveFriendlyErrorMessage(
                error,
                "Không thể cập nhật trạng thái tài khoản."
              ),
          },
          rethrow: false,
        }
      );
    } catch (e) {
      savingError.value = resolveFriendlyErrorMessage(
        e,
        "Không thể cập nhật trạng thái tài khoản."
      );
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
    filter,
    unitOptions: unitOptionsUi,
    roleOptions,
    rows,
    loading,
    error,
    resultCount,
    currentPageNumber,
    pageSize,
    totalItems,
    editOpen,
    rolesOpen,
    deactivateOpen,
    selectedAccount,
    savingEdit,
    savingRoles,
    savingDeactivate,
    savingError,
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
