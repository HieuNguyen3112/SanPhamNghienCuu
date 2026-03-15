import { computed, reactive, ref, watch } from "vue";
import {
  publisherFromDto,
  type Publisher,
  type PublisherUpsertDTO,
} from "../contracts/publishers.contract";
import { publisherService } from "../services/publishers.service";

type FormErrors<T extends Record<string, unknown>> = Partial<
  Record<keyof T, string>
>;

export function usePublisherCatalog() {
  const publishers = ref<Publisher[]>([]);
  const publisherTotal = ref(0);

  const qPublisher = ref("");
  const pagePublisher = ref(1);
  const pageSizePublisher = ref(10);

  const modalPublisherOpen = ref(false);
  const modalMode = ref<"create" | "edit">("create");

  const publisherForm = reactive<{
    id: number;
    name: string;
    code: string;
    address: string;
    phone: string;
    email: string;
    website: string;
    isActive: boolean;
  }>({
    id: 0,
    name: "",
    code: "",
    address: "",
    phone: "",
    email: "",
    website: "",
    isActive: true,
  });

  const publisherErrors = reactive<FormErrors<typeof publisherForm>>({});

  function clearErrors() {
    Object.keys(publisherErrors).forEach(
      (k) => delete publisherErrors[k as keyof typeof publisherErrors],
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

  function validateEmail(v: string): string | null {
    const value = v.trim();
    if (!value) return null;
    if (value.length > 100) return "Tối đa 100 ký tự.";
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) return "Email không hợp lệ.";
    return null;
  }

  async function load(): Promise<void> {
    const res = await publisherService.list({
      keyword: qPublisher.value.trim() || undefined,
      page: pagePublisher.value,
      per_page: pageSizePublisher.value,
    });

    publishers.value = res.items.map(publisherFromDto);
    publisherTotal.value = res.pagination.total;
  }

  const pagedPublishers = computed(() => publishers.value);
  const filteredPublishers = computed(() => publishers.value);

  watch(qPublisher, () => {
    if (pagePublisher.value !== 1) {
      pagePublisher.value = 1;
      return;
    }
    void load();
  });

  watch([pagePublisher, pageSizePublisher], () => {
    void load();
  });

  function openCreatePublisher() {
    modalMode.value = "create";
    publisherForm.id = 0;
    publisherForm.name = "";
    publisherForm.code = "";
    publisherForm.address = "";
    publisherForm.phone = "";
    publisherForm.email = "";
    publisherForm.website = "";
    publisherForm.isActive = true;
    modalPublisherOpen.value = true;
  }

  function openEditPublisher(item: Publisher) {
    modalMode.value = "edit";
    publisherForm.id = item.id;
    publisherForm.name = item.name;
    publisherForm.code = item.code;
    publisherForm.address = item.address ?? "";
    publisherForm.phone = item.phone ?? "";
    publisherForm.email = item.email ?? "";
    publisherForm.website = item.website ?? "";
    publisherForm.isActive = item.isActive;
    modalPublisherOpen.value = true;
  }

  function onUpdatePublisherForm(v: {
    id: number;
    name: string;
    code: string;
    address: string;
    phone: string;
    email: string;
    website: string;
    isActive: boolean;
  }) {
    publisherForm.id = v.id;
    publisherForm.name = v.name;
    publisherForm.code = v.code;
    publisherForm.address = v.address;
    publisherForm.phone = v.phone;
    publisherForm.email = v.email;
    publisherForm.website = v.website;
    publisherForm.isActive = v.isActive;
  }

  async function savePublisher(): Promise<void> {
    clearErrors();

    publisherErrors.name =
      validateRequired(publisherForm.name, 255) ?? undefined;
    publisherErrors.code =
      validateRequired(publisherForm.code, 50) ?? undefined;
    publisherErrors.address =
      validateOptional(publisherForm.address, 255) ?? undefined;
    publisherErrors.phone =
      validateOptional(publisherForm.phone, 50) ?? undefined;
    publisherErrors.email = validateEmail(publisherForm.email) ?? undefined;
    publisherErrors.website =
      validateOptional(publisherForm.website, 255) ?? undefined;

    if (
      publisherErrors.name ||
      publisherErrors.code ||
      publisherErrors.address ||
      publisherErrors.phone ||
      publisherErrors.email ||
      publisherErrors.website
    ) {
      return;
    }

    const payload: PublisherUpsertDTO = {
      name: publisherForm.name.trim(),
      code: publisherForm.code.trim(),
      address: publisherForm.address.trim()
        ? publisherForm.address.trim()
        : null,
      phone: publisherForm.phone.trim() ? publisherForm.phone.trim() : null,
      email: publisherForm.email.trim() ? publisherForm.email.trim() : null,
      website: publisherForm.website.trim()
        ? publisherForm.website.trim()
        : null,
      is_active: publisherForm.isActive,
    };

    if (modalMode.value === "create") {
      await publisherService.create(payload);
    } else {
      await publisherService.update(publisherForm.id, payload);
    }

    modalPublisherOpen.value = false;
    await load();
  }

  return {
    publishers,
    publisherTotal,
    qPublisher,
    pagePublisher,
    pageSizePublisher,
    filteredPublishers,
    pagedPublishers,
    modalMode,
    modalPublisherOpen,
    publisherForm,
    publisherErrors,
    load,
    openCreatePublisher,
    openEditPublisher,
    onUpdatePublisherForm,
    savePublisher,
  };
}
