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
    address: string;
    country: string;
    notes: string;
    isActive: boolean;
    sourceName: string;
    pointMin: number | null;
    pointMax: number | null;
  }>({
    id: 0,
    name: "",
    issn: "",
    address: "",
    country: "",
    notes: "",
    isActive: true,
    sourceName: "",
    pointMin: null,
    pointMax: null,
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
    modalMode.value = "create";
    journalForm.id = 0;
    journalForm.name = "";
    journalForm.issn = "";
    journalForm.address = "";
    journalForm.country = "";
    journalForm.notes = "";
    journalForm.isActive = true;
    journalForm.sourceName = "";
    journalForm.pointMin = null;
    journalForm.pointMax = null;
    modalJournalOpen.value = true;
  }

  function openEditJournal(item: Journal) {
    modalMode.value = "edit";
    journalForm.id = item.id;
    journalForm.name = item.name;
    journalForm.issn = item.issn ?? "";
    journalForm.address = item.address ?? "";
    journalForm.country = item.country ?? "";
    journalForm.notes = item.notes ?? "";
    journalForm.isActive = item.isActive;
    journalForm.sourceName = item.sourceName ?? "";
    journalForm.pointMin = item.pointMin ?? null;
    journalForm.pointMax = item.pointMax ?? null;
    modalJournalOpen.value = true;
  }

  function onUpdateJournalForm(v: {
    id: number;
    name: string;
    issn: string;
    address: string;
    country: string;
    notes: string;
    isActive: boolean;
    sourceName: string;
    pointMin: number | null;
    pointMax: number | null;
  }) {
    journalForm.id = v.id;
    journalForm.name = v.name;
    journalForm.issn = v.issn;
    journalForm.address = v.address;
    journalForm.country = v.country;
    journalForm.notes = v.notes;
    journalForm.isActive = v.isActive;
    journalForm.sourceName = v.sourceName;
    journalForm.pointMin = v.pointMin;
    journalForm.pointMax = v.pointMax;
  }

  async function saveJournal(): Promise<void> {
    clearErrors();

    journalErrors.name = validateRequired(journalForm.name, 255) ?? undefined;
    journalErrors.issn = validateOptional(journalForm.issn, 50) ?? undefined;
    journalErrors.address =
      validateOptional(journalForm.address, 255) ?? undefined;
    journalErrors.country =
      validateOptional(journalForm.country, 100) ?? undefined;
    journalErrors.notes = validateOptional(journalForm.notes, 255) ?? undefined;
    journalErrors.sourceName =
      validateOptional(journalForm.sourceName, 255) ?? undefined;

    if (
      journalErrors.name ||
      journalErrors.issn ||
      journalErrors.address ||
      journalErrors.country ||
      journalErrors.notes ||
      journalErrors.sourceName
    ) {
      return;
    }

    const payload: JournalUpsertDTO = {
      id: journalForm.id,
      name: journalForm.name.trim(),
      issn: journalForm.issn.trim() ? journalForm.issn.trim() : null,
      address: journalForm.address.trim() ? journalForm.address.trim() : null,
      country: journalForm.country.trim() ? journalForm.country.trim() : null,
      notes: journalForm.notes.trim() ? journalForm.notes.trim() : null,
      source_name: journalForm.sourceName.trim()
        ? journalForm.sourceName.trim()
        : null,
      point_min: journalForm.pointMin ?? null,
      point_max: journalForm.pointMax ?? null,
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
    saveJournal,
    onUpdateJournalForm,
  };
}

