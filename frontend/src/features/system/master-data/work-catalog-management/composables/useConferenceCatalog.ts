import { computed, reactive, ref } from "vue";
import {
  conferenceFromDto,
  type Conference,
  type ConferenceDTO,
  type ConferenceLevel,
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

  const conferenceForm = reactive<{
    id: number;
    name: string;
    level: ConferenceLevel;
    notes: string;
    isActive: boolean;
  }>({
    id: 0,
    name: "",
    level: "FACULTY",
    notes: "",
    isActive: true,
  });

  const conferenceErrors = reactive<FormErrors<typeof conferenceForm>>({});

  function clearErrors() {
    Object.keys(conferenceErrors).forEach(
      (k) => delete conferenceErrors[k as keyof typeof conferenceErrors],
    );
  }

  function validateRequired(v: string, max: number) {
    if (!v.trim()) return "Trường này là bắt buộc.";
    if (v.length > max) return `Tối đa ${max} ký tự.`;
    return null;
  }
  function validateOptional(v: string, max: number) {
    if (v.length > max) return `Tối đa ${max} ký tự.`;
    return null;
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

  const pagedConferences = computed(() => conferences.value);
  const filteredConferences = computed(() => conferences.value);

  function openCreateConference() {
    modalMode.value = "create";
    conferenceForm.id = 0;
    conferenceForm.name = "";
    conferenceForm.level = "FACULTY";
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

  function onUpdateConferenceForm(v: {
    id: number;
    name: string;
    level: ConferenceLevel;
    notes: string;
    isActive: boolean;
  }) {
    conferenceForm.id = v.id;
    conferenceForm.name = v.name;
    conferenceForm.level = v.level;
    conferenceForm.notes = v.notes;
    conferenceForm.isActive = v.isActive;
  }

  async function saveConference(): Promise<void> {
    clearErrors();

    conferenceErrors.name =
      validateRequired(conferenceForm.name, 255) ?? undefined;
    conferenceErrors.notes =
      validateOptional(conferenceForm.notes, 255) ?? undefined;

    if (conferenceErrors.name || conferenceErrors.notes) return;

    const payload: ConferenceUpsertDTO = {
      name: conferenceForm.name.trim(),
      level: conferenceForm.level,
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
    conferenceForm,
    conferenceErrors,

    load,
    openCreateConference,
    openEditConference,
    onUpdateConferenceForm,
    saveConference,
  };
}
