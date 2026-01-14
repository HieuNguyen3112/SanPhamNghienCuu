// File: src/features/master-data/work-catalog-management/composables/useJournalCatalog.ts
import { computed, reactive, ref } from "vue";
import {
  journalFromDto,
  type Journal,
  type JournalDTO,
  type JournalRank,
  type JournalRankDTO,
} from "../contracts/workCatalog.contract";
import { workCatalogService } from "../services/workCatalogService";

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

    // legacy
    classification: "ISI" | "SCOPUS" | "OTHER";
    country: string;

    // NEW
    address: string;

    notes: string;
    isActive: boolean;
  }>({
    id: 0,
    name: "",
    issn: "",
    classification: "OTHER",
    country: "",
    address: "",
    notes: "",
    isActive: true,
  });
  const journalErrors = reactive<FormErrors<typeof journalForm>>({});

  // ===== Ranking modal state =====
  const rankingModalJournalOpen = ref(false);
  const rankingForm = reactive<{
    journalId: number;
    rank: JournalRank;
    effectiveFrom: string; // YYYY-MM-DD
    note: string;
  }>({
    journalId: 0,
    rank: "Q4",
    effectiveFrom: "",
    note: "",
  });
  const rankingErrors = reactive<
    Partial<Record<"rank" | "effectiveFrom" | "note", string>>
  >({});

  function clearJournalErrors() {
    Object.keys(journalErrors).forEach(
      (k) => delete journalErrors[k as keyof typeof journalErrors]
    );
  }
  function clearRankingErrors() {
    Object.keys(rankingErrors).forEach(
      (k) => delete rankingErrors[k as keyof typeof rankingErrors]
    );
  }

  async function load(): Promise<void> {
    const response = await workCatalogService.listJournals({
      keyword: qJournal.value.trim() || undefined,
      page: pageJournal.value,
      per_page: pageSizeJournal.value,
    });
    journals.value = response.items.map(journalFromDto);
    journalTotal.value = response.pagination.total;
  }

  const filteredJournals = computed(() => journals.value);
  const pagedJournals = computed(() => journals.value);

  function openCreateJournal() {
    modalMode.value = "create";
    journalForm.id = 0;
    journalForm.name = "";
    journalForm.issn = "";
    journalForm.classification = "OTHER";
    journalForm.country = "";
    journalForm.address = "";
    journalForm.notes = "";
    journalForm.isActive = true;
    modalJournalOpen.value = true;
  }

  function openEditJournal(item: Journal) {
    modalMode.value = "edit";
    journalForm.id = item.id;
    journalForm.name = item.name;
    journalForm.issn = item.issn ?? "";
    journalForm.classification = item.classification;
    journalForm.country = item.country ?? "";
    journalForm.address = item.address ?? ""; // ✅ NOW exists
    journalForm.notes = item.notes ?? "";
    journalForm.isActive = item.isActive;
    modalJournalOpen.value = true;
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

  async function saveJournal(): Promise<void> {
    clearJournalErrors();

    journalErrors.name = validateRequired(journalForm.name, 255) ?? undefined;
    journalErrors.issn = validateOptional(journalForm.issn, 50) ?? undefined;

    // legacy
    journalErrors.country =
      validateOptional(journalForm.country, 100) ?? undefined;

    // NEW: address required
    journalErrors.address =
      validateRequired(journalForm.address, 255) ?? undefined;

    journalErrors.notes = validateOptional(journalForm.notes, 255) ?? undefined;

    if (
      journalErrors.name ||
      journalErrors.issn ||
      journalErrors.country ||
      journalErrors.address
    )
      return;

    const payload: Omit<JournalDTO, "updated_at"> = {
      id: journalForm.id,
      name: journalForm.name.trim(),
      address: journalForm.address.trim() ? journalForm.address.trim() : null,
      issn: journalForm.issn.trim() ? journalForm.issn.trim() : null,

      // legacy (keep)
      classification: journalForm.classification,
      country: journalForm.country.trim() ? journalForm.country.trim() : null,

      // derived/current rank: service will compute, set null here
      current_rank: null,
      current_rank_effective_from: null,

      notes: journalForm.notes.trim() ? journalForm.notes.trim() : null,
      is_active: journalForm.isActive,
    };

    await workCatalogService.upsertJournal(payload);
    modalJournalOpen.value = false;
    await load();
  }

  // =========================
  // Ranking modal actions
  // =========================
  function openJournalRanking(journalId: number) {
    clearRankingErrors();
    rankingForm.journalId = journalId;

    // default effective date = today (YYYY-MM-DD)
    const d = new Date();
    const pad = (n: number) => String(n).padStart(2, "0");
    rankingForm.effectiveFrom = `${d.getFullYear()}-${pad(
      d.getMonth() + 1
    )}-${pad(d.getDate())}`;
    rankingForm.rank = "Q4";
    rankingForm.note = "";
    rankingModalJournalOpen.value = true;
  }

  function closeJournalRanking() {
    rankingModalJournalOpen.value = false;
  }

  function onUpdateRankingForm(v: {
    journalId: number;
    rank: JournalRank;
    effectiveFrom: string;
    note: string;
  }) {
    rankingForm.journalId = v.journalId;
    rankingForm.rank = v.rank;
    rankingForm.effectiveFrom = v.effectiveFrom;
    rankingForm.note = v.note;
  }

  async function submitJournalRanking(): Promise<void> {
    clearRankingErrors();

    if (!rankingForm.journalId) {
      rankingErrors.rank = "Thiếu tạp chí.";
      return;
    }
    if (!rankingForm.rank) {
      rankingErrors.rank = "Hạng là bắt buộc.";
      return;
    }
    if (!rankingForm.effectiveFrom.trim()) {
      rankingErrors.effectiveFrom = "Ngày áp dụng là bắt buộc.";
      return;
    }

    await workCatalogService.setJournalRanking({
      journal_id: rankingForm.journalId,
      rank: rankingForm.rank as JournalRankDTO,
      effective_from: rankingForm.effectiveFrom,
      note: rankingForm.note.trim() ? rankingForm.note.trim() : null,
    });

    rankingModalJournalOpen.value = false;
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

    // ranking (✅ cái này là phần bạn thiếu nên nút không chạy)
    rankingModalJournalOpen,
    rankingForm,
    rankingErrors,
    openJournalRanking,
    closeJournalRanking,
    submitJournalRanking,
    onUpdateRankingForm,
  };
}
