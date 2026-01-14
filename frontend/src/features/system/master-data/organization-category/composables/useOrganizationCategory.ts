// src/features/organization-category/composables/useOrganizationCategory.ts
import { onBeforeUnmount, ref, watch } from "vue";
import type {
  TabKey,
  Faculty,
  Department,
  FacultyOption,
} from "../contracts/organizationCategory.contract";
import {
  departmentFromDto,
  facultyFromDto,
  facultyOptionFromDto,
} from "../contracts/organizationCategory.contract";
import { OrganizationCategoryService } from "../services/organizationCategoryService";

type Scope = "FACULTY" | "UNIVERSITY";

export function useOrganizationCategory(scope: Scope = "UNIVERSITY") {
  const isFacultyScope = scope === "FACULTY";
  const service = new OrganizationCategoryService(scope);

  const activeTab = ref<TabKey>("faculties");

  const loading = ref(false);
  const error = ref<string | null>(null);

  const faculties = ref<Faculty[]>([]);
  const facultyOptions = ref<FacultyOption[]>([]);
  const departments = ref<Department[]>([]);

  // Filters
  const facultySearch = ref("");
  const departmentSearch = ref("");
  const departmentFacultyId = ref<number | "ALL">("ALL");

  // Pagination
  const facultyPageSize = ref(12);
  const facultyCurrentPageNumber = ref(1);
  const facultyTotalItemCount = ref(0);

  const departmentPageSize = ref(12);
  const departmentCurrentPageNumber = ref(1);
  const departmentTotalItemCount = ref(0);

  // Modals
  const facultyFormOpen = ref(false);
  const editingFaculty = ref<Faculty | null>(null);

  const departmentFormOpen = ref(false);
  const editingDepartment = ref<Department | null>(null);

  let ignoreDepartmentFacultyWatch = false;

  async function withLoading(task: () => Promise<void>) {
    loading.value = true;
    error.value = null;
    try {
      await task();
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Không tải được dữ liệu.";
    } finally {
      loading.value = false;
    }
  }

  async function loadFacultyOptions() {
    const list = await service.listFacultyOptions();
    facultyOptions.value = list.map(facultyOptionFromDto);

    if (isFacultyScope && facultyOptions.value.length) {
      const scopedId = facultyOptions.value[0].id;
      if (departmentFacultyId.value !== scopedId) {
        ignoreDepartmentFacultyWatch = true;
        departmentFacultyId.value = scopedId;
        ignoreDepartmentFacultyWatch = false;
      }
    }
  }

  async function loadFaculties() {
    const response = await service.listFaculties({
      keyword: facultySearch.value.trim() || undefined,
      page: facultyCurrentPageNumber.value,
      per_page: facultyPageSize.value,
    });
    faculties.value = response.items.map(facultyFromDto);
    facultyTotalItemCount.value = response.pagination.total;
  }

  async function loadDepartments() {
    const response = await service.listDepartments({
      keyword: departmentSearch.value.trim() || undefined,
      faculty_id:
        departmentFacultyId.value === "ALL"
          ? undefined
          : departmentFacultyId.value,
      page: departmentCurrentPageNumber.value,
      per_page: departmentPageSize.value,
    });
    departments.value = response.items.map(departmentFromDto);
    departmentTotalItemCount.value = response.pagination.total;
  }

  async function refreshAll() {
    await withLoading(async () => {
      await loadFacultyOptions();
      await Promise.all([loadFaculties(), loadDepartments()]);
    });
  }

  async function refreshFaculties() {
    await withLoading(async () => {
      await loadFaculties();
    });
  }

  async function refreshDepartments() {
    await withLoading(async () => {
      await loadDepartments();
    });
  }

  // ===== Search debounce =====
  let facultySearchTimer: number | null = null;
  watch(facultySearch, () => {
    if (facultySearchTimer) window.clearTimeout(facultySearchTimer);
    facultySearchTimer = window.setTimeout(() => {
      facultyCurrentPageNumber.value = 1;
      void refreshFaculties();
    }, 300);
  });

  let departmentSearchTimer: number | null = null;
  watch(departmentSearch, () => {
    if (departmentSearchTimer) window.clearTimeout(departmentSearchTimer);
    departmentSearchTimer = window.setTimeout(() => {
      departmentCurrentPageNumber.value = 1;
      void refreshDepartments();
    }, 300);
  });

  watch(departmentFacultyId, () => {
    if (ignoreDepartmentFacultyWatch) return;
    departmentCurrentPageNumber.value = 1;
    void refreshDepartments();
  });

  onBeforeUnmount(() => {
    if (facultySearchTimer) window.clearTimeout(facultySearchTimer);
    if (departmentSearchTimer) window.clearTimeout(departmentSearchTimer);
  });

  // ===== Actions =====
  function openCreateFaculty() {
    editingFaculty.value = null;
    facultyFormOpen.value = true;
  }

  function openEditFaculty(item: Faculty) {
    editingFaculty.value = item;
    facultyFormOpen.value = true;
  }

  function closeFacultyForm() {
    facultyFormOpen.value = false;
    editingFaculty.value = null;
  }

  async function saveFaculty(payload: { code: string; name: string }) {
    await withLoading(async () => {
      if (editingFaculty.value) {
        await service.updateFaculty(editingFaculty.value.id, payload);
      } else {
        await service.createFaculty(payload);
      }
      await Promise.all([loadFacultyOptions(), loadFaculties(), loadDepartments()]);
      closeFacultyForm();
    });
  }

  function openCreateDepartment() {
    editingDepartment.value = null;
    departmentFormOpen.value = true;
  }

  function openEditDepartment(item: Department) {
    editingDepartment.value = item;
    departmentFormOpen.value = true;
  }

  function closeDepartmentForm() {
    departmentFormOpen.value = false;
    editingDepartment.value = null;
  }

  async function saveDepartment(payload: {
    facultyId: number;
    code: string;
    name: string;
  }) {
    await withLoading(async () => {
      if (editingDepartment.value) {
        await service.updateDepartment(editingDepartment.value.id, {
          faculty_id: payload.facultyId,
          code: payload.code,
          name: payload.name,
        });
      } else {
        await service.createDepartment({
          faculty_id: payload.facultyId,
          code: payload.code,
          name: payload.name,
        });
      }
      await Promise.all([loadFacultyOptions(), loadDepartments()]);
      closeDepartmentForm();
    });
  }

  async function updateFacultyPage(nextPage: number) {
    facultyCurrentPageNumber.value = nextPage;
    await refreshFaculties();
  }

  async function updateFacultyPageSize(nextPageSize: number) {
    facultyPageSize.value = nextPageSize;
    facultyCurrentPageNumber.value = 1;
    await refreshFaculties();
  }

  async function updateDepartmentPage(nextPage: number) {
    departmentCurrentPageNumber.value = nextPage;
    await refreshDepartments();
  }

  async function updateDepartmentPageSize(nextPageSize: number) {
    departmentPageSize.value = nextPageSize;
    departmentCurrentPageNumber.value = 1;
    await refreshDepartments();
  }

  return {
    activeTab,
    loading,
    error,

    faculties,
    facultyOptions,
    departments,

    // filters
    facultySearch,
    departmentSearch,
    departmentFacultyId,

    // pagination
    facultyPageSize,
    facultyCurrentPageNumber,
    facultyTotalItemCount,

    departmentPageSize,
    departmentCurrentPageNumber,
    departmentTotalItemCount,

    // modals
    facultyFormOpen,
    editingFaculty,
    departmentFormOpen,
    editingDepartment,

    // actions
    refreshAll,

    openCreateFaculty,
    openEditFaculty,
    closeFacultyForm,
    saveFaculty,

    openCreateDepartment,
    openEditDepartment,
    closeDepartmentForm,
    saveDepartment,

    updateFacultyPage,
    updateFacultyPageSize,
    updateDepartmentPage,
    updateDepartmentPageSize,
  };
}
