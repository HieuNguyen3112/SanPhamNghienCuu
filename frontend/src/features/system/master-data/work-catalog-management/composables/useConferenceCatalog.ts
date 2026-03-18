import { computed, reactive, ref, watch } from "vue";
import {
  conferenceFromDto,
  type Conference,
  type ConferenceLevel,
  type ConferenceSuggestion,
  type ConferenceUpsertDTO,
} from "../contracts/conferences.contract";
import { conferenceService } from "../services/conferences.service";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function useConferenceCatalog() {
  const conferences = ref<Conference[]>([]);
  const conferenceTotal = ref(0);

  const qConference = ref("");
  const pageConference = ref(1);
  const pageSizeConference = ref(10);

  const modalConferenceOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");
  const conferenceSuggestionModalOpen = ref(false);
  const conferenceSuggestions = ref<ConferenceSuggestion[]>([]);
  const conferenceSuggestionLoading = ref(false);
  const conferenceApprovingSuggestionId = ref<number | null>(null);

  const conferenceForm = reactive<{
    id: number;
    name: string;
    level: ConferenceLevel;
    researchField: string;
    year: string;
    organization: string;
    hasProceedings: boolean;
    hasIsbn: boolean;
    isbn: string;
    point: string;
    notes: string;
    isActive: boolean;
  }>({
    id: 0,
    name: "",
    level: "NATIONAL",
    researchField: "",
    year: "",
    organization: "",
    hasProceedings: false,
    hasIsbn: false,
    isbn: "",
    point: "",
    notes: "",
    isActive: true,
  });

  const conferenceErrors = reactive<FormErrors<typeof conferenceForm>>({});

  function clearErrors() {
    Object.keys(conferenceErrors).forEach(
      (k) => delete conferenceErrors[k as keyof typeof conferenceErrors],
    );
  }

  function validateRequired(v: string, max: number): string | null {
    if (!v.trim()) return "Trường này là bắt buộc.";
    if (v.length > max) return `Tối đa ${max} ký tự.`;
    return null;
  }

  function validateOptional(v: string, max: number): string | null {
    if (v.length > max) return `Tối đa ${max} ký tự.`;
    return null;
  }

  function normalizeYear(value: string): number | null {
    const raw = value.trim();
    if (!raw) return null;
    if (!/^\d{4}$/.test(raw)) return Number.NaN;
    const year = Number(raw);
    if (!Number.isInteger(year) || year < 1900 || year > 2100)
      return Number.NaN;
    return year;
  }

  function normalizePoint(value: string): number | null {
    const raw = value.trim().replace(",", ".");
    if (!raw) return null;
    if (!/^\d+(\.\d{1,2})?$/.test(raw)) return Number.NaN;
    const point = Number(raw);
    if (!Number.isFinite(point) || point < 0 || point > 99.99)
      return Number.NaN;
    return Number(point.toFixed(2));
  }

  async function load(): Promise<void> {
    const res = await conferenceService.list({
      keyword: qConference.value.trim() || undefined,
      page: pageConference.value,
      per_page: pageSizeConference.value,
    });

    conferences.value = res.items.map(conferenceFromDto);
    conferenceTotal.value = res.pagination.total;
  }

  async function loadSuggestions(): Promise<void> {
    conferenceSuggestionLoading.value = true;
    try {
      const res = await conferenceService.listSuggestions({
        page: 1,
        per_page: 100,
      });
      conferenceSuggestions.value = res.items;
    } finally {
      conferenceSuggestionLoading.value = false;
    }
  }

  async function openConferenceSuggestions(): Promise<void> {
    conferenceSuggestionModalOpen.value = true;
    await loadSuggestions();
  }

  function closeConferenceSuggestions() {
    conferenceSuggestionModalOpen.value = false;
  }

  async function approveConferenceSuggestion(
    id: number,
    reviewNote?: string,
  ): Promise<void> {
    conferenceApprovingSuggestionId.value = id;
    try {
      await conferenceService.approveSuggestion(id, reviewNote);
      await Promise.all([loadSuggestions(), load()]);
    } finally {
      conferenceApprovingSuggestionId.value = null;
    }
  }

  async function rejectConferenceSuggestion(
    id: number,
    reviewNote?: string,
  ): Promise<void> {
    conferenceApprovingSuggestionId.value = id;
    try {
      await conferenceService.rejectSuggestion(id, reviewNote);
      await Promise.all([loadSuggestions(), load()]);
    } finally {
      conferenceApprovingSuggestionId.value = null;
    }
  }

  const pagedConferences = computed(() => conferences.value);
  const filteredConferences = computed(() => conferences.value);

  watch(qConference, () => {
    if (pageConference.value !== 1) {
      pageConference.value = 1;
      return;
    }
    void load();
  });

  watch([pageConference, pageSizeConference], () => {
    void load();
  });

  function openCreateConference() {
    modalMode.value = "create";
    conferenceForm.id = 0;
    conferenceForm.name = "";
    conferenceForm.level = "NATIONAL";
    conferenceForm.researchField = "";
    conferenceForm.year = "";
    conferenceForm.organization = "";
    conferenceForm.hasProceedings = false;
    conferenceForm.hasIsbn = false;
    conferenceForm.isbn = "";
    conferenceForm.point = "";
    conferenceForm.notes = "";
    conferenceForm.isActive = true;
    modalConferenceOpen.value = true;
  }

  function openEditConference(item: Conference) {
    modalMode.value = "edit";
    conferenceForm.id = item.id;
    conferenceForm.name = item.name;
    conferenceForm.level =
      item.level === "INTERNATIONAL" ? "INTERNATIONAL" : "NATIONAL";
    conferenceForm.researchField = item.researchField ?? "";
    conferenceForm.year =
      item.year !== null && item.year !== undefined ? String(item.year) : "";
    conferenceForm.organization = item.organization ?? "";
    conferenceForm.hasProceedings = Boolean(item.hasProceedings);
    conferenceForm.hasIsbn = Boolean(item.hasIsbn);
    conferenceForm.isbn = item.isbn ?? "";
    conferenceForm.point =
      item.point !== null && item.point !== undefined ? String(item.point) : "";
    conferenceForm.notes = item.notes ?? "";
    conferenceForm.isActive = item.isActive;
    modalConferenceOpen.value = true;
  }

  function onUpdateConferenceForm(v: {
    id: number;
    name: string;
    level: ConferenceLevel;
    researchField: string;
    year: string;
    organization: string;
    hasProceedings: boolean;
    hasIsbn: boolean;
    isbn: string;
    point: string;
    notes: string;
    isActive: boolean;
  }) {
    conferenceForm.id = v.id;
    conferenceForm.name = v.name;
    conferenceForm.level =
      v.level === "INTERNATIONAL" ? "INTERNATIONAL" : "NATIONAL";
    conferenceForm.researchField = v.researchField;
    conferenceForm.year = v.year;
    conferenceForm.organization = v.organization;
    conferenceForm.hasProceedings = v.hasProceedings;
    conferenceForm.hasIsbn = v.hasIsbn;
    conferenceForm.isbn = v.isbn;
    conferenceForm.point = v.point;
    conferenceForm.notes = v.notes;
    conferenceForm.isActive = v.isActive;
  }

  async function saveConference(): Promise<void> {
    clearErrors();

    conferenceErrors.name =
      validateRequired(conferenceForm.name, 255) ?? undefined;
    conferenceErrors.researchField =
      validateOptional(conferenceForm.researchField.trim(), 255) ?? undefined;
    conferenceErrors.organization =
      validateOptional(conferenceForm.organization.trim(), 255) ?? undefined;
    const normalizedYear = normalizeYear(conferenceForm.year);
    if (Number.isNaN(normalizedYear)) {
      conferenceErrors.year = "Năm tổ chức phải từ 1900 đến 2100.";
    }
    const normalizedPoint = normalizePoint(conferenceForm.point);
    if (Number.isNaN(normalizedPoint)) {
      conferenceErrors.point =
        "Điểm quy đổi phải là số từ 0 đến 99.99 (tối đa 2 chữ số thập phân).";
    }
    const normalizedIsbn = conferenceForm.isbn.trim();
    if (conferenceForm.hasIsbn) {
      if (!normalizedIsbn) {
        conferenceErrors.isbn = "Vui lòng nhập ISBN khi đã bật Có ISBN.";
      } else if (normalizedIsbn.length > 50) {
        conferenceErrors.isbn = "ISBN tối đa 50 ký tự.";
      }
    }
    conferenceErrors.notes =
      validateOptional(conferenceForm.notes, 255) ?? undefined;

    if (Object.values(conferenceErrors).some(Boolean)) return;

    const payload: ConferenceUpsertDTO = {
      name: conferenceForm.name.trim(),
      level: conferenceForm.level,
      research_field: conferenceForm.researchField.trim() || null,
      year: normalizedYear,
      organization: conferenceForm.organization.trim() || null,
      has_proceedings: conferenceForm.hasProceedings,
      has_isbn: conferenceForm.hasIsbn,
      isbn: conferenceForm.hasIsbn ? normalizedIsbn : null,
      point: normalizedPoint,
      notes: conferenceForm.notes.trim() ? conferenceForm.notes.trim() : null,
      is_active: conferenceForm.isActive,
    };

    if (modalMode.value === "create") {
      await conferenceService.create(payload);
    } else {
      await conferenceService.update(conferenceForm.id, payload);
    }

    modalConferenceOpen.value = false;
    await load();
  }

  return {
    conferences,
    conferenceTotal,

    qConference,
    pageConference,
    pageSizeConference,

    filteredConferences,
    pagedConferences,

    modalMode,
    modalConferenceOpen,
    conferenceSuggestionModalOpen,
    conferenceSuggestions,
    conferenceSuggestionLoading,
    conferenceApprovingSuggestionId,
    conferenceForm,
    conferenceErrors,

    load,
    loadSuggestions,
    openCreateConference,
    openEditConference,
    openConferenceSuggestions,
    closeConferenceSuggestions,
    approveConferenceSuggestion,
    rejectConferenceSuggestion,
    onUpdateConferenceForm,
    saveConference,
  };
}
