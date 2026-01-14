// File: src/features/master-data/work-catalog-management/composables/useWorkLevelCatalog.ts
import { computed, reactive, ref } from "vue";
import {
  workLevelFromDto,
  type WorkLevel,
  type WorkLevelDTO,
} from "../contracts/workCatalog.contract";
import { workCatalogService } from "../services/workCatalogService";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function useWorkLevelCatalog() {
  const workLevels = ref<WorkLevel[]>([]);
  const workLevelTotal = ref(0);

  const qWorkLevel = ref("");
  const pageWorkLevel = ref(1);
  const pageSizeWorkLevel = ref(10);

  const modalWorkLevelOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");

  const workLevelForm = reactive<{
    id: number;
    name: string;
    priority: number;
    notes: string;
    isActive: boolean;
  }>({
    id: 0,
    name: "",
    priority: 1,
    notes: "",
    isActive: true,
  });
  const workLevelErrors = reactive<FormErrors<typeof workLevelForm>>({});

  function clearErrors() {
    Object.keys(workLevelErrors).forEach(
      (k) => delete workLevelErrors[k as keyof typeof workLevelErrors]
    );
  }

  async function load(): Promise<void> {
    const response = await workCatalogService.listWorkLevels({
      keyword: qWorkLevel.value.trim() || undefined,
      page: pageWorkLevel.value,
      per_page: pageSizeWorkLevel.value,
    });
    workLevels.value = response.items.map(workLevelFromDto);
    workLevelTotal.value = response.pagination.total;
  }

  const filteredWorkLevels = computed(() => workLevels.value);
  const pagedWorkLevels = computed(() => workLevels.value);

  function openCreateWorkLevel() {
    modalMode.value = "create";
    workLevelForm.id = 0;
    workLevelForm.name = "";
    workLevelForm.priority = 1;
    workLevelForm.notes = "";
    workLevelForm.isActive = true;
    modalWorkLevelOpen.value = true;
  }

  function openEditWorkLevel(item: WorkLevel) {
    modalMode.value = "edit";
    workLevelForm.id = item.id;
    workLevelForm.name = item.name;
    workLevelForm.priority = item.priority;
    workLevelForm.notes = item.notes ?? "";
    workLevelForm.isActive = item.isActive;
    modalWorkLevelOpen.value = true;
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

  async function saveWorkLevel(): Promise<void> {
    clearErrors();
    workLevelErrors.name =
      validateRequired(workLevelForm.name, 255) ?? undefined;

    if (workLevelForm.priority < 1)
      workLevelErrors.priority = "Ưu tiên phải >= 1.";

    workLevelErrors.notes =
      validateOptional(workLevelForm.notes, 255) ?? undefined;

    if (workLevelErrors.name || workLevelErrors.priority) return;

    const payload: Omit<WorkLevelDTO, "updated_at"> = {
      id: workLevelForm.id,
      name: workLevelForm.name.trim(),
      priority: workLevelForm.priority,
      notes: workLevelForm.notes.trim() ? workLevelForm.notes.trim() : null,
      is_active: workLevelForm.isActive,
    };

    await workCatalogService.upsertWorkLevel(payload);
    modalWorkLevelOpen.value = false;
    await load();
  }

  return {
    workLevels,
    workLevelTotal,
    qWorkLevel,
    pageWorkLevel,
    pageSizeWorkLevel,
    filteredWorkLevels,
    pagedWorkLevels,

    modalMode,
    modalWorkLevelOpen,
    workLevelForm,
    workLevelErrors,

    load,
    openCreateWorkLevel,
    openEditWorkLevel,
    saveWorkLevel,
  };
}
