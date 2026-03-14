import { computed, onMounted, ref } from "vue";
import type {
  AssignRolesPayload,
  CreateLecturerAccountPayload,
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
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

export interface UseLecturerAccountManagementOptions {
  scope?: LecturerAccountScope;
  faculty_unit_id?: number;
  service?: LecturerAccountManagementService;
}

export function useLecturerAccountManagement(
  options?: UseLecturerAccountManagementOptions,
) {
  const scope: LecturerAccountScope = options?.scope ?? "UNIVERSITY";
  const service =
    options?.service ??
    createLecturerAccountManagementService({
      scope,
      faculty_unit_id: options?.faculty_unit_id,
    });
  const { runWithFeedback } = useActionFeedback();
  const { runPageLoad } = usePageLoadFeedback();

  const filter = ref<LecturerAccountFilterState>(defaultFilterState());
  const unitOptions = ref<UnitOptionDTO[]>([]);
  const facultyOptions = ref<UnitOptionDTO[]>([]);
  const roleOptions = ref<RoleOption[]>(DEFAULT_ROLE_OPTIONS);
  const rowsDto = ref<LecturerAccountDTO[]>([]);
  const rows = computed<LecturerAccount[]>(() =>
    rowsDto.value.map(lecturerAccountFromDto),
  );

  const currentPageNumber = ref(1);
  const pageSize = ref(12);
  const totalItems = ref(0);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const resultCount = computed(() => totalItems.value);

  const editOpen = ref(false);
  const createOpen = ref(false);
  const rolesOpen = ref(false);
  const deactivateOpen = ref(false);
  const selectedAccountId = ref<number | null>(null);
  const selectedAccount = computed(() =>
    selectedAccountId.value == null
      ? null
      : (rows.value.find((x) => x.id === selectedAccountId.value) ?? null),
  );

  const savingEdit = ref(false);
  const savingRoles = ref(false);
  const savingDeactivate = ref(false);
  const savingError = ref<string | null>(null);

  function resolveFriendlyErrorMessage(error: unknown, fallback: string) {
    return resolveApiErrorMessage(error, fallback);
  }

  function updateFilter(next: LecturerAccountFilterState) {
    filter.value = { ...next };
  }

  function resetFilter() {
    filter.value = defaultFilterState();
  }

  async function fetchList() {
    const response = await service.searchLecturerAccountsDTO(filter.value, {
      page: currentPageNumber.value,
      per_page: pageSize.value,
    });
    rowsDto.value = response.items;
    totalItems.value = response.pagination.total;
  }

  async function performSearch(resetPage: boolean) {
    if (resetPage) currentPageNumber.value = 1;

    loading.value = true;
    error.value = null;
    try {
      await fetchList();
    } catch (e) {
      error.value = resolveFriendlyErrorMessage(
        e,
        "Không tải được danh sách tài khoản.",
      );
    } finally {
      loading.value = false;
    }
  }

  async function search(options?: {
    resetPage?: boolean;
    withFeedback?: boolean;
  }) {
    const resetPage = options?.resetPage ?? true;
    if (options?.withFeedback === false) {
      await performSearch(resetPage);
      return;
    }

    await runPageLoad(() => performSearch(resetPage), {
      loading: {
        title: "Đang tải danh sách tài khoản",
        message: "Hệ thống đang cập nhật dữ liệu tài khoản...",
      },
    });
  }

  async function performBootstrap() {
    loading.value = true;
    error.value = null;
    try {
      const lookups = await service.getLookupsDTO();
      unitOptions.value = lookups.units;
      facultyOptions.value = lookups.faculties ?? [];
      roleOptions.value = lookups.roles.length
        ? lookups.roles
        : DEFAULT_ROLE_OPTIONS;
      await performSearch(true);
    } catch (e) {
      error.value = resolveFriendlyErrorMessage(
        e,
        "Không tải được dữ liệu khởi tạo.",
      );
    } finally {
      loading.value = false;
    }
  }

  async function bootstrap() {
    await runPageLoad(performBootstrap, {
      loading: {
        title: "Đang khởi tạo trang tài khoản",
        message: "Hệ thống đang chuẩn bị danh sách tài khoản giảng viên...",
      },
    });
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

  function openCreate() {
    createOpen.value = true;
    editOpen.value = false;
    rolesOpen.value = false;
    deactivateOpen.value = false;
    savingError.value = null;
  }

  function openRoles(id: number) {
    if (scope === "FACULTY") {
      return;
    }

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
    createOpen.value = false;
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
          await search({ resetPage: false, withFeedback: false });
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
        },
      );
    } catch (e) {
      savingError.value = resolveFriendlyErrorMessage(
        e,
        "Không thể lưu thông tin.",
      );
    } finally {
      savingEdit.value = false;
    }
  }

  async function saveCreate(payload: CreateLecturerAccountPayload) {
    savingEdit.value = true;
    savingError.value = null;
    try {
      await runWithFeedback(
        async () => {
          await service.createLecturerAccountDTO(payload);
          closeAllModals();
          currentPageNumber.value = 1;
          await search({ resetPage: false, withFeedback: false });
        },
        {
          loading: {
            title: "Đang tạo tài khoản",
            message: "Hệ thống đang khởi tạo tài khoản giảng viên...",
          },
          success: {
            title: "Thành công",
            message:
              scope === "FACULTY"
                ? "Đã tạo tài khoản giảng viên. Giảng viên có thể đổi mật khẩu sau khi đăng nhập (không bắt buộc lần đầu)."
                : "Đã tạo tài khoản BCN khoa.",
          },
          error: {
            title: "Tạo tài khoản thất bại",
            message: (error) =>
              resolveFriendlyErrorMessage(
                error,
                "Không thể tạo tài khoản giảng viên.",
              ),
          },
          rethrow: false,
        },
      );
    } catch (e) {
      savingError.value = resolveFriendlyErrorMessage(
        e,
        "Không thể tạo tài khoản giảng viên.",
      );
    } finally {
      savingEdit.value = false;
    }
  }

  async function saveRoles(payload: AssignRolesPayload) {
    if (scope === "FACULTY") {
      savingError.value = "Khoa không có quyền phân vai trò.";
      return;
    }

    savingRoles.value = true;
    savingError.value = null;
    try {
      await runWithFeedback(
        async () => {
          await service.assignRolesDTO(payload);
          closeAllModals();
          await search({ resetPage: false, withFeedback: false });
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
        },
      );
    } catch (e) {
      savingError.value = resolveFriendlyErrorMessage(
        e,
        "Không thể lưu phân quyền.",
      );
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
          await search({ resetPage: false, withFeedback: false });
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
                "Không thể cập nhật trạng thái tài khoản.",
              ),
          },
          rethrow: false,
        },
      );
    } catch (e) {
      savingError.value = resolveFriendlyErrorMessage(
        e,
        "Không thể cập nhật trạng thái tài khoản.",
      );
    } finally {
      savingDeactivate.value = false;
    }
  }

  const unitOptionsUi = computed(() =>
    unitOptions.value.map(unitOptionFromDto),
  );
  const facultyOptionsUi = computed(() =>
    facultyOptions.value.map(unitOptionFromDto),
  );
  const filterOptions = computed(() =>
    scope === "FACULTY" ? unitOptionsUi.value : facultyOptionsUi.value,
  );

  onMounted(() => {
    void bootstrap();
  });

  return {
    scope,
    filter,
    filterOptions,
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
    createOpen,
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
    openCreate,
    openRoles,
    openDeactivate,
    closeAllModals,
    saveEdit,
    saveCreate,
    saveRoles,
    confirmToggleStatus,
  };
}
