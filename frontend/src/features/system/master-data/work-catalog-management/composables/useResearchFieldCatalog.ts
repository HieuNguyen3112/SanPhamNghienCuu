// File: src/features/master-data/work-catalog-management/composables/useResearchFieldCatalog.ts
import { computed, reactive, ref, watch } from "vue";
import {
  researchFieldFromDto,
  toLowerSafe,
  type ResearchField,
  type ResearchFieldDTO,
} from "../contracts/workCatalog.contract";
import { workCatalogService } from "../services/workCatalogService";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function useResearchFieldCatalog() {
  const researchFields = ref<ResearchField[]>([]);

  const qResearchField = ref("");
  const pageResearchField = ref(1);
  const pageSizeResearchField = ref(10);

  const modalResearchFieldOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");

  const researchFieldForm = reactive<{
    id: number;
    code: string;
    name: string;
    description: string;
    isActive: boolean;
  }>({
    id: 0,
    code: "",
    name: "",
    description: "",
    isActive: true,
  });
  const researchFieldErrors = reactive<FormErrors<typeof researchFieldForm>>(
    {}
  );

  function clearErrors() {
    Object.keys(researchFieldErrors).forEach(
      (k) => delete researchFieldErrors[k as keyof typeof researchFieldErrors]
    );
  }

  async function load(): Promise<void> {
    const dtos = await workCatalogService.listResearchFields();
    researchFields.value = dtos.map(researchFieldFromDto);
  }

  function paginate<T>(items: T[], page: number, pageSize: number): T[] {
    const start = (page - 1) * pageSize;
    return items.slice(start, start + pageSize);
  }

  const filteredResearchFields = computed(() => {
    const q = toLowerSafe(qResearchField.value);
    return researchFields.value.filter(
      (x) =>
        toLowerSafe(x.name).includes(q) || toLowerSafe(x.code ?? "").includes(q)
    );
  });

  const pagedResearchFields = computed(() =>
    paginate(
      filteredResearchFields.value,
      pageResearchField.value,
      pageSizeResearchField.value
    )
  );

  watch(
    [qResearchField, pageSizeResearchField],
    () => (pageResearchField.value = 1)
  );

  function openCreateResearchField() {
    modalMode.value = "create";
    researchFieldForm.id = 0;
    researchFieldForm.code = "";
    researchFieldForm.name = "";
    researchFieldForm.description = "";
    researchFieldForm.isActive = true;
    modalResearchFieldOpen.value = true;
  }

  function openEditResearchField(item: ResearchField) {
    modalMode.value = "edit";
    researchFieldForm.id = item.id;
    researchFieldForm.code = item.code ?? "";
    researchFieldForm.name = item.name;
    researchFieldForm.description = item.description ?? "";
    researchFieldForm.isActive = item.isActive;
    modalResearchFieldOpen.value = true;
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

  async function saveResearchField(): Promise<void> {
    clearErrors();

    researchFieldErrors.name =
      validateRequired(researchFieldForm.name, 255) ?? undefined;
    researchFieldErrors.code =
      validateOptional(researchFieldForm.code, 50) ?? undefined;
    researchFieldErrors.description =
      validateOptional(researchFieldForm.description, 500) ?? undefined;

    if (researchFieldErrors.name || researchFieldErrors.code) return;

    const payload: Omit<ResearchFieldDTO, "updated_at"> = {
      id: researchFieldForm.id,
      code: researchFieldForm.code.trim()
        ? researchFieldForm.code.trim()
        : null,
      name: researchFieldForm.name.trim(),
      description: researchFieldForm.description.trim()
        ? researchFieldForm.description.trim()
        : null,
      is_active: researchFieldForm.isActive,
    };

    await workCatalogService.upsertResearchField(payload);
    modalResearchFieldOpen.value = false;
    await load();
  }

  return {
    researchFields,
    qResearchField,
    pageResearchField,
    pageSizeResearchField,
    filteredResearchFields,
    pagedResearchFields,

    modalMode,
    modalResearchFieldOpen,
    researchFieldForm,
    researchFieldErrors,

    load,
    openCreateResearchField,
    openEditResearchField,
    saveResearchField,
  };
}
