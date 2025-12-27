// File: src/features/master-data/work-catalog-management/composables/useConferenceCatalog.ts
import { computed, reactive, ref, watch } from "vue";
import {
  conferenceFromDto,
  toLowerSafe,
  type Conference,
  type ConferenceDTO,
} from "../contracts/workCatalog.contract";
import { workCatalogService } from "../services/workCatalogService";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function useConferenceCatalog() {
  const conferences = ref<Conference[]>([]);

  const qConference = ref("");
  const pageConference = ref(1);
  const pageSizeConference = ref(10);

  const modalConferenceOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");

  const conferenceForm = reactive<{
    id: number;
    name: string;
    level: "FACULTY" | "UNIVERSITY" | "NATIONAL" | "INTERNATIONAL";
    notes: string;
    isActive: boolean;
  }>({
    id: 0,
    name: "",
    level: "UNIVERSITY",
    notes: "",
    isActive: true,
  });
  const conferenceErrors = reactive<FormErrors<typeof conferenceForm>>({});

  function clearErrors() {
    Object.keys(conferenceErrors).forEach(
      (k) => delete conferenceErrors[k as keyof typeof conferenceErrors]
    );
  }

  async function load(): Promise<void> {
    const dtos = await workCatalogService.listConferences();
    conferences.value = dtos.map(conferenceFromDto);
  }

  function paginate<T>(items: T[], page: number, pageSize: number): T[] {
    const start = (page - 1) * pageSize;
    return items.slice(start, start + pageSize);
  }

  const filteredConferences = computed(() => {
    const q = toLowerSafe(qConference.value);
    return conferences.value.filter((x) => toLowerSafe(x.name).includes(q));
  });

  const pagedConferences = computed(() =>
    paginate(
      filteredConferences.value,
      pageConference.value,
      pageSizeConference.value
    )
  );

  watch([qConference, pageSizeConference], () => (pageConference.value = 1));

  function openCreateConference() {
    modalMode.value = "create";
    conferenceForm.id = 0;
    conferenceForm.name = "";
    conferenceForm.level = "UNIVERSITY";
    conferenceForm.notes = "";
    conferenceForm.isActive = true;
    modalConferenceOpen.value = true;
  }

  function openEditConference(item: Conference) {
    modalMode.value = "edit";
    conferenceForm.id = item.id;
    conferenceForm.name = item.name;
    conferenceForm.level = item.level;
    conferenceForm.notes = item.notes ?? "";
    conferenceForm.isActive = item.isActive;
    modalConferenceOpen.value = true;
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

  async function saveConference(): Promise<void> {
    clearErrors();
    conferenceErrors.name =
      validateRequired(conferenceForm.name, 255) ?? undefined;
    conferenceErrors.notes =
      validateOptional(conferenceForm.notes, 255) ?? undefined;
    if (conferenceErrors.name) return;

    const payload: Omit<ConferenceDTO, "updated_at"> = {
      id: conferenceForm.id,
      name: conferenceForm.name.trim(),
      level: conferenceForm.level,
      notes: conferenceForm.notes.trim() ? conferenceForm.notes.trim() : null,
      is_active: conferenceForm.isActive,
    };

    await workCatalogService.upsertConference(payload);
    modalConferenceOpen.value = false;
    await load();
  }

  return {
    conferences,
    qConference,
    pageConference,
    pageSizeConference,
    filteredConferences,
    pagedConferences,

    modalMode,
    modalConferenceOpen,
    conferenceForm,
    conferenceErrors,

    load,
    openCreateConference,
    openEditConference,
    saveConference,
  };
}
