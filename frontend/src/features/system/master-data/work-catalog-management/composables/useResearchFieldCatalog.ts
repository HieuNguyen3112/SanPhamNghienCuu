// File: src/features/master-data/work-catalog-management/composables/useResearchFieldCatalog.ts
import { computed, reactive, ref } from "vue";
import {
  researchFieldFromDto,
  type ResearchField,
  type ResearchFieldDTO,
} from "../contracts/workCatalog.contract";
import { workCatalogService } from "../services/workCatalogService";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function useResearchFieldCatalog() {
  const researchFields = ref<ResearchField[]>([]);
  const researchFieldTotal = ref(0);

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
    const response = await workCatalogService.listResearchFields({
      keyword: qResearchField.value.trim() || undefined,
      page: pageResearchField.value,
      per_page: pageSizeResearchField.value,
    });
    researchFields.value = response.items.map(researchFieldFromDto);
    researchFieldTotal.value = response.pagination.total;
  }

  const filteredResearchFields = computed(() => researchFields.value);
  const pagedResearchFields = computed(() => researchFields.value);

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
    researchFieldTotal,
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
