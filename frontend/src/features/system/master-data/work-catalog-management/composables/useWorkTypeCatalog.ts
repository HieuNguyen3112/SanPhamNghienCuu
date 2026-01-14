// File: src/features/master-data/work-catalog-management/composables/useWorkTypeCatalog.ts
import { computed, reactive, ref } from "vue";
import {
  formatDateTime,
  workTypeFromDto,
  type WorkType,
  type WorkTypeDTO,
} from "../contracts/workCatalog.contract";
import { workCatalogService } from "../services/workCatalogService";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function useWorkTypeCatalog() {
  const workTypes = ref<WorkType[]>([]);
  const workTypeTotal = ref(0);

  const qWorkType = ref("");
  const pageWorkType = ref(1);
  const pageSizeWorkType = ref(10);

  const modalWorkTypeOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");

  const workTypeForm = reactive<{
    id: number;
    name: string;
    description: string;
    isActive: boolean;
  }>({
    id: 0,
    name: "",
    description: "",
    isActive: true,
  });
  const workTypeErrors = reactive<FormErrors<typeof workTypeForm>>({});

  function clearErrors() {
    Object.keys(workTypeErrors).forEach(
      (k) => delete workTypeErrors[k as keyof typeof workTypeErrors]
    );
  }

  async function load(): Promise<void> {
    const response = await workCatalogService.listWorkTypes({
      keyword: qWorkType.value.trim() || undefined,
      page: pageWorkType.value,
      per_page: pageSizeWorkType.value,
    });
    workTypes.value = response.items.map(workTypeFromDto);
    workTypeTotal.value = response.pagination.total;
  }

  const filteredWorkTypes = computed(() => workTypes.value);
  const pagedWorkTypes = computed(() => workTypes.value);

  function openCreateWorkType() {
    modalMode.value = "create";
    workTypeForm.id = 0;
    workTypeForm.name = "";
    workTypeForm.description = "";
    workTypeForm.isActive = true;
    modalWorkTypeOpen.value = true;
  }

  function openEditWorkType(item: WorkType) {
    modalMode.value = "edit";
    workTypeForm.id = item.id;
    workTypeForm.name = item.name;
    workTypeForm.description = item.description ?? "";
    workTypeForm.isActive = item.isActive;
    modalWorkTypeOpen.value = true;
  }

  function validateRequired(value: string, max: number): string | null {
    if (!value.trim()) return "Trường này là bắt buộc.";
    if (value.length > max) return `Tối đa ${max} ký tự.`;
    return null;
  }
  function validateOptional(value: string, max: number): string | null {
    if (value.length > max) return `Tối đa ${max} ký tự.`;
    return null;
  }

  async function saveWorkType(): Promise<void> {
    clearErrors();
    workTypeErrors.name = validateRequired(workTypeForm.name, 255) ?? undefined;
    workTypeErrors.description =
      validateOptional(workTypeForm.description, 500) ?? undefined;

    if (workTypeErrors.name) return;

    const payload: Omit<WorkTypeDTO, "updated_at"> = {
      id: workTypeForm.id,
      name: workTypeForm.name.trim(),
      description: workTypeForm.description.trim()
        ? workTypeForm.description.trim()
        : null,
      is_active: workTypeForm.isActive,
    };

    await workCatalogService.upsertWorkType(payload);
    modalWorkTypeOpen.value = false;
    await load();
  }

  return {
    // data
    workTypes,
    workTypeTotal,

    // search/paging
    qWorkType,
    pageWorkType,
    pageSizeWorkType,
    filteredWorkTypes,
    pagedWorkTypes,

    // modal/form
    modalMode,
    modalWorkTypeOpen,
    workTypeForm,
    workTypeErrors,

    // actions
    load,
    openCreateWorkType,
    openEditWorkType,
    saveWorkType,

    // helper
    formatDateTime,
  };
}
