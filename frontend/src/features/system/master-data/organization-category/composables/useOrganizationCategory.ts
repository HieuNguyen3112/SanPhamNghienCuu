// src/features/organization-category/composables/useOrganizationCategory.ts
import { computed, ref, watch } from "vue";
import type {
  TabKey,
  Faculty,
  Department,
} from "../contracts/organizationCategory.contract";
import {
  departmentFromDto,
  facultyFromDto,
} from "../contracts/organizationCategory.contract";
import { OrganizationCategoryService } from "../services/organizationCategoryService";

export function useOrganizationCategory() {
  const service = new OrganizationCategoryService();

  const activeTab = ref<TabKey>("faculties");

  const loading = ref(false);
  const error = ref<string | null>(null);

  const faculties = ref<Faculty[]>([]);
  const departments = ref<Department[]>([]);

  // Filters
  const facultySearch = ref("");
  const departmentSearch = ref("");
  const departmentFacultyId = ref<number | "ALL">("ALL");

  // Pagination (tách riêng cho UX tốt hơn)
  const facultyPageSize = ref(12);
  const facultyCurrentPageNumber = ref(1);

  const departmentPageSize = ref(12);
  const departmentCurrentPageNumber = ref(1);

  // Modals
  const facultyFormOpen = ref(false);
  const editingFaculty = ref<Faculty | null>(null);

  const departmentFormOpen = ref(false);
  const editingDepartment = ref<Department | null>(null);

  async function refreshAll() {
    loading.value = true;
    error.value = null;
    try {
      const [facDtos, depDtos] = await Promise.all([
        service.listFaculties(),
        service.listDepartments(),
      ]);

      const fac = facDtos.map(facultyFromDto);
      faculties.value = fac;

      const facNameById = new Map(fac.map((f) => [f.id, f.name]));
      departments.value = depDtos.map((d) => {
        const m = departmentFromDto(d);
        return { ...m, facultyName: facNameById.get(m.facultyId) ?? "—" };
      });

      facultyCurrentPageNumber.value = 1;
      departmentCurrentPageNumber.value = 1;
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Không tải được dữ liệu.";
    } finally {
      loading.value = false;
    }
  }

  // ===== Faculty list computed =====
  const filteredFaculties = computed(() => {
    const q = facultySearch.value.trim().toLowerCase();
    if (!q) return faculties.value;
    return faculties.value.filter(
      (x) =>
        x.code.toLowerCase().includes(q) || x.name.toLowerCase().includes(q)
    );
  });

  const facultyTotalItemCount = computed(() => filteredFaculties.value.length);

  const facultyPagedItems = computed(() => {
    const start = (facultyCurrentPageNumber.value - 1) * facultyPageSize.value;
    return filteredFaculties.value.slice(start, start + facultyPageSize.value);
  });

  // ===== Department list computed =====
  const filteredDepartments = computed(() => {
    const q = departmentSearch.value.trim().toLowerCase();
    const byFaculty =
      departmentFacultyId.value === "ALL"
        ? departments.value
        : departments.value.filter(
            (d) => d.facultyId === departmentFacultyId.value
          );

    if (!q) return byFaculty;

    return byFaculty.filter(
      (x) =>
        x.code.toLowerCase().includes(q) ||
        x.name.toLowerCase().includes(q) ||
        (x.facultyName ?? "").toLowerCase().includes(q)
    );
  });

  const departmentTotalItemCount = computed(
    () => filteredDepartments.value.length
  );

  const departmentPagedItems = computed(() => {
    const start =
      (departmentCurrentPageNumber.value - 1) * departmentPageSize.value;
    return filteredDepartments.value.slice(
      start,
      start + departmentPageSize.value
    );
  });

  // Reset page when filters change
  watch(facultySearch, () => {
    facultyCurrentPageNumber.value = 1;
  });

  watch([departmentSearch, departmentFacultyId], () => {
    departmentCurrentPageNumber.value = 1;
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
    loading.value = true;
    error.value = null;
    try {
      if (editingFaculty.value)
        await service.updateFaculty(editingFaculty.value.id, payload);
      else await service.createFaculty(payload);

      await refreshAll();
      closeFacultyForm();
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Lưu khoa thất bại.";
    } finally {
      loading.value = false;
    }
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
    loading.value = true;
    error.value = null;
    try {
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

      await refreshAll();
      closeDepartmentForm();
    } catch (e) {
      error.value = e instanceof Error ? e.message : "Lưu đơn vị thất bại.";
    } finally {
      loading.value = false;
    }
  }

  return {
    activeTab,
    loading,
    error,

    faculties,
    departments,

    // filters
    facultySearch,
    departmentSearch,
    departmentFacultyId,

    // pagination (đúng naming theo SharedPaginationControls)
    facultyPageSize,
    facultyCurrentPageNumber,
    facultyTotalItemCount,
    facultyPagedItems,

    departmentPageSize,
    departmentCurrentPageNumber,
    departmentTotalItemCount,
    departmentPagedItems,

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
  };
}
