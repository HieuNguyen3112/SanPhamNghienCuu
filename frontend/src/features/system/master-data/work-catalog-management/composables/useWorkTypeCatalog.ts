import { computed, reactive, ref } from "vue";
import type {
  WorkType,
  WorkTypeUpsertDTO,
} from "../contracts/workTypes.contract";
import { workTypeService } from "../services/workTypes.service";

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
      (k) => delete workTypeErrors[k as keyof typeof workTypeErrors],
    );
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

  async function load(): Promise<void> {
    const res = await workTypeService.list({
      keyword: qWorkType.value.trim() || undefined,
      page: pageWorkType.value,
      per_page: pageSizeWorkType.value,
    });
    workTypes.value = res.items;
    workTypeTotal.value = res.pagination.total;
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

  function onUpdateWorkTypeForm(v: {
    id: number;
    name: string;
    description: string;
    isActive: boolean;
  }) {
    workTypeForm.id = v.id;
    workTypeForm.name = v.name;
    workTypeForm.description = v.description;
    workTypeForm.isActive = v.isActive;
  }

  async function saveWorkType(): Promise<void> {
    clearErrors();

    workTypeErrors.name = validateRequired(workTypeForm.name, 255) ?? undefined;
    workTypeErrors.description =
      validateOptional(workTypeForm.description, 500) ?? undefined;
    if (workTypeErrors.name || workTypeErrors.description) return;

    const payload: WorkTypeUpsertDTO = {
      id: workTypeForm.id,
      name: workTypeForm.name.trim(),
      description: workTypeForm.description.trim()
        ? workTypeForm.description.trim()
        : null,
      is_active: workTypeForm.isActive,
    };

    await workTypeService.upsert(payload);
    modalWorkTypeOpen.value = false;
    await load();
  }

  return {
    workTypes,
    workTypeTotal,
    qWorkType,
    pageWorkType,
    pageSizeWorkType,
    filteredWorkTypes,
    pagedWorkTypes,
    modalMode,
    modalWorkTypeOpen,
    workTypeForm,
    workTypeErrors,
    load,
    openCreateWorkType,
    openEditWorkType,
    saveWorkType,
    onUpdateWorkTypeForm,
  };
}
