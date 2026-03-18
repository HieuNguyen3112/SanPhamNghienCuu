import { computed, reactive, ref, watch } from "vue";
import type { Journal, JournalUpsertDTO } from "../contracts/journals.contract";
import { journalService } from "../services/journals.service";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function useJournalCatalog() {
  const journals = ref<Journal[]>([]);
  const journalTotal = ref(0);

  const qJournal = ref("");
  const pageJournal = ref(1);
  const pageSizeJournal = ref(10);

  const modalJournalOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");

  const journalForm = reactive<{
    id: number;
    name: string;
    issn: string;
    journalType: string;
    address: string;
    researchField: string;
    website: string;
    country: string;
    notes: string;
    publisher: string;
    isActive: boolean;
    sourceName: string;
    point: number | null;
  }>({
    id: 0,
    name: "",
    issn: "",
    journalType: "",
    address: "",
    researchField: "",
    website: "",
    country: "",
    notes: "",
    publisher: "",
    isActive: true,
    sourceName: "",
    point: null,
  });

  const journalErrors = reactive<FormErrors<typeof journalForm>>({});

  function clearErrors() {
    Object.keys(journalErrors).forEach(
      (k) => delete journalErrors[k as keyof typeof journalErrors],
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
    const res = await journalService.list({
      keyword: qJournal.value.trim() || undefined,
      page: pageJournal.value,
      per_page: pageSizeJournal.value,
    });

    journals.value = res.items;
    journalTotal.value = res.pagination.total;
  }

  const filteredJournals = computed(() => journals.value);
  const pagedJournals = computed(() => journals.value);

  watch(qJournal, () => {
    if (pageJournal.value !== 1) {
      pageJournal.value = 1;
      return;
    }
    void load();
  });

  watch([pageJournal, pageSizeJournal], () => {
    void load();
  });

  function openCreateJournal() {
    clearErrors();
    modalMode.value = "create";
    journalForm.id = 0;
    journalForm.name = "";
    journalForm.issn = "";
    journalForm.journalType = "";
    journalForm.address = "";
    journalForm.researchField = "";
    journalForm.website = "";
    journalForm.country = "";
    journalForm.notes = "";
    journalForm.publisher = "";
    journalForm.isActive = true;
    journalForm.sourceName = "";
    journalForm.point = null;
    modalJournalOpen.value = true;
  }

  function openEditJournal(item: Journal) {
    clearErrors();
    modalMode.value = "edit";
    journalForm.id = item.id;
    journalForm.name = item.name;
    journalForm.issn = item.issn ?? "";
    journalForm.journalType = item.journalType ?? "";
    journalForm.address = item.address ?? "";
    journalForm.researchField = item.researchField ?? "";
    journalForm.website = item.website ?? "";
    journalForm.country = item.country ?? "";
    journalForm.notes = item.notes ?? "";
    journalForm.publisher = item.publisher ?? "";
    journalForm.isActive = item.isActive;
    journalForm.sourceName = item.sourceName ?? "";
    journalForm.point = item.point ?? null;
    modalJournalOpen.value = true;
  }

  function onUpdateJournalForm(v: {
    id: number;
    name: string;
    issn: string;
    journalType: string;
    address: string;
    researchField: string;
    website: string;
    country: string;
    notes: string;
    publisher: string;
    isActive: boolean;
    sourceName: string;
    point: number | null;
  }) {
    journalForm.id = v.id;
    journalForm.name = v.name;
    journalForm.issn = v.issn;
    journalForm.journalType = v.journalType;
    journalForm.address = v.address;
    journalForm.researchField = v.researchField;
    journalForm.website = v.website;
    journalForm.country = v.country;
    journalForm.notes = v.notes;
    journalForm.publisher = v.publisher;
    journalForm.isActive = v.isActive;
    journalForm.sourceName = v.sourceName;
    journalForm.point = v.point;
  }

  function validateJournalForm(): boolean {
    clearErrors();

    journalErrors.name = validateRequired(journalForm.name, 255) ?? undefined;
    journalErrors.issn = validateRequired(journalForm.issn, 50) ?? undefined;
    journalErrors.address =
      validateRequired(journalForm.address, 255) ?? undefined;
    journalErrors.country =
      validateOptional(journalForm.country, 100) ?? undefined;
    journalErrors.website =
      validateOptional(journalForm.website, 255) ?? undefined;
    journalErrors.researchField =
      validateOptional(journalForm.researchField, 255) ?? undefined;
    journalErrors.journalType =
      validateOptional(journalForm.journalType, 100) ?? undefined;
    journalErrors.notes = validateOptional(journalForm.notes, 255) ?? undefined;
    journalErrors.publisher =
      validateRequired(journalForm.publisher, 255) ?? undefined;
    journalErrors.sourceName =
      validateRequired(journalForm.sourceName, 255) ?? undefined;

    if (journalForm.point === null) {
      journalErrors.point = "Trường này là bắt buộc.";
    }

    if (
      journalErrors.name ||
      journalErrors.issn ||
      journalErrors.address ||
      journalErrors.country ||
      journalErrors.website ||
      journalErrors.researchField ||
      journalErrors.journalType ||
      journalErrors.notes ||
      journalErrors.publisher ||
      journalErrors.sourceName ||
      journalErrors.point
    ) {
      return false;
    }

    return true;
  }

  async function saveJournal(): Promise<void> {
    if (!validateJournalForm()) {
      return;
    }

    const payload: JournalUpsertDTO = {
      id: journalForm.id,
      name: journalForm.name.trim(),
      issn: journalForm.issn.trim() ? journalForm.issn.trim() : null,
      journal_type: journalForm.journalType.trim()
        ? journalForm.journalType.trim()
        : null,
      research_field: journalForm.researchField.trim()
        ? journalForm.researchField.trim()
        : null,
      website: journalForm.website.trim() ? journalForm.website.trim() : null,
      address: journalForm.address.trim() ? journalForm.address.trim() : null,
      country: journalForm.country.trim() ? journalForm.country.trim() : null,
      notes: journalForm.notes.trim() ? journalForm.notes.trim() : null,
      publisher: journalForm.publisher.trim()
        ? journalForm.publisher.trim()
        : null,
      source_name: journalForm.sourceName.trim()
        ? journalForm.sourceName.trim()
        : null,
      point: journalForm.point ?? null,
      is_active: journalForm.isActive,
    };

    await journalService.upsert(payload);
    modalJournalOpen.value = false;
    await load();
  }

  return {
    journals,
    journalTotal,
    qJournal,
    pageJournal,
    pageSizeJournal,
    filteredJournals,
    pagedJournals,
    modalMode,
    modalJournalOpen,
    journalForm,
    journalErrors,
    load,
    openCreateJournal,
    openEditJournal,
    validateJournalForm,
    saveJournal,
    onUpdateJournalForm,
  };
}
