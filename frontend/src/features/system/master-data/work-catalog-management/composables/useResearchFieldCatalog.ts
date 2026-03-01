import { computed, reactive, ref, watch } from "vue";
import { researchFieldService } from "../services/researchFields.service";
import {
  researchFieldFromDto,
  type ResearchField,
  type ResearchFieldUpsertDTO,
} from "../contracts/researchFields.contract";

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

  const researchFieldErrors = reactive<FormErrors<typeof researchFieldForm>>({});

  function clearErrors() {
    Object.keys(researchFieldErrors).forEach(
      (k) => delete researchFieldErrors[k as keyof typeof researchFieldErrors],
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
    const res = await researchFieldService.list({
      keyword: qResearchField.value.trim() || undefined,
      page: pageResearchField.value,
      per_page: pageSizeResearchField.value,
    });

    researchFields.value = res.items.map(researchFieldFromDto);
    researchFieldTotal.value = res.pagination.total;
  }

  const filteredResearchFields = computed(() => researchFields.value);
  const pagedResearchFields = computed(() => researchFields.value);

  watch(qResearchField, () => {
    if (pageResearchField.value !== 1) {
      pageResearchField.value = 1;
      return;
    }
    void load();
  });

  watch([pageResearchField, pageSizeResearchField], () => {
    void load();
  });

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

  function onUpdateResearchFieldForm(v: {
    id: number;
    code: string;
    name: string;
    description: string;
    isActive: boolean;
  }) {
    researchFieldForm.id = v.id;
    researchFieldForm.code = v.code;
    researchFieldForm.name = v.name;
    researchFieldForm.description = v.description;
    researchFieldForm.isActive = v.isActive;
  }

  async function saveResearchField(): Promise<void> {
    clearErrors();

    researchFieldErrors.code =
      validateOptional(researchFieldForm.code, 50) ?? undefined;
    researchFieldErrors.name =
      validateRequired(researchFieldForm.name, 255) ?? undefined;
    researchFieldErrors.description =
      validateOptional(researchFieldForm.description, 500) ?? undefined;

    if (
      researchFieldErrors.code ||
      researchFieldErrors.name ||
      researchFieldErrors.description
    ) {
      return;
    }

    const code = researchFieldForm.code.trim()
      ? researchFieldForm.code.trim().toUpperCase()
      : null;

    const payload: ResearchFieldUpsertDTO = {
      id: researchFieldForm.id,
      code,
      name: researchFieldForm.name.trim(),
      description: researchFieldForm.description.trim()
        ? researchFieldForm.description.trim()
        : null,
      is_active: researchFieldForm.isActive,
    };

    if (researchFieldForm.id === 0) {
      await researchFieldService.create(payload);
    } else {
      await researchFieldService.update(researchFieldForm.id, payload);
    }

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
    onUpdateResearchFieldForm,
  };
}

