// File: src/features/lecturer/profile/components/LecturerContactInfoCard.vue
<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="text-sm font-semibold text-slate-900">
          Thông tin liên hệ
        </div>
        <p class="mt-1 text-sm text-slate-500">
          Bạn có thể cập nhật email cá nhân, số điện thoại, địa chỉ và các liên
          kết hồ sơ.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="!isEditing"
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
          :disabled="pending || loading"
          @click="startEdit"
        >
          <Pencil class="h-4 w-4" />
          Chỉnh sửa
        </button>

        <template v-else>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
            :disabled="pending"
            @click="cancelEdit"
          >
            <X class="h-4 w-4" />
            Hủy
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-slate-800 disabled:opacity-60"
            :disabled="pending || !canSave"
            @click="save"
          >
            <Save class="h-4 w-4" />
            Lưu
          </button>
        </template>
      </div>
    </div>

    <div class="mt-4 grid gap-3">
      <!-- View -->
      <div v-if="!isEditing" class="grid gap-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <LinkRow
            :icon="Mail"
            label="Email công vụ"
            :value="contact.officialEmail"
            :href="mailHref(contact.officialEmail)"
            :is-link="true"
          />
          <LinkRow
            :icon="Mail"
            label="Email cá nhân"
            :value="contact.personalEmail || '—'"
            :href="
              contact.personalEmail ? mailHref(contact.personalEmail) : null
            "
            :is-link="!!contact.personalEmail"
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <LinkRow
            :icon="Phone"
            label="Số điện thoại"
            :value="contact.phone || '—'"
            :href="contact.phone ? telHref(contact.phone) : null"
            :is-link="!!contact.phone"
          />
          <LinkRow
            :icon="MapPin"
            label="Địa chỉ liên hệ"
            :value="contact.address || '—'"
            :is-link="false"
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <LinkRow
            :icon="Globe"
            label="Website cá nhân"
            :value="contact.website || '—'"
            :href="contact.website || null"
            :is-link="!!contact.website"
          />
          <LinkRow
            :icon="ExternalLink"
            label="Google Scholar"
            :value="contact.googleScholar || '—'"
            :href="contact.googleScholar || null"
            :is-link="!!contact.googleScholar"
          />
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <LinkRow
            :icon="Link2"
            label="ORCID"
            :value="contact.orcid || '—'"
            :href="contact.orcid || null"
            :is-link="!!contact.orcid"
          />
          <div class="hidden sm:block" />
        </div>
      </div>

      <!-- Edit -->
      <div v-else class="grid gap-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600"
              >Email công vụ</label
            >
            <input
              :value="contact.officialEmail"
              disabled
              class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600"
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Email cá nhân</label
            >
            <input
              v-model.trim="draft.personalEmail"
              type="email"
              maxlength="255"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: your.name@gmail.com"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600"
              >Số điện thoại</label
            >
            <input
              v-model.trim="draft.phone"
              type="tel"
              maxlength="30"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: 0901 234 567"
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Địa chỉ liên hệ</label
            >
            <input
              v-model.trim="draft.address"
              maxlength="255"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: 12 Nguyễn Trãi, Q.1, TP.HCM"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600"
              >Website cá nhân</label
            >
            <input
              v-model.trim="draft.website"
              maxlength="500"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: https://your-site.com"
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Google Scholar</label
            >
            <input
              v-model.trim="draft.googleScholar"
              maxlength="500"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: https://scholar.google.com/citations?user=..."
            />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600">ORCID</label>
            <input
              v-model.trim="draft.orcid"
              maxlength="255"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: https://orcid.org/0000-0002-1825-0097"
            />
          </div>

          <div class="hidden sm:block" />
        </div>

        <div
          v-if="!canSave"
          class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
        >
          Email cá nhân không hợp lệ (nếu có nhập). Bạn có thể để trống.
        </div>
      </div>

      <div
        v-if="loading"
        class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600"
      >
        Đang tải dữ liệu...
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  reactive,
  ref,
  watch,
  defineComponent,
  h,
  type Component,
} from "vue";
import {
  ExternalLink,
  Globe,
  Link2,
  Mail,
  MapPin,
  Pencil,
  Phone,
  Save,
  X,
} from "lucide-vue-next";
import type { LecturerContactInfo } from "../composables/useLecturerProfile";

const props = defineProps<{
  contact: LecturerContactInfo;
  pending?: boolean;
  loading?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:contact", value: LecturerContactInfo): void;
}>();

const pending = computed(() => props.pending ?? false);
const loading = computed(() => props.loading ?? false);

const isEditing = ref(false);

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}
const draft = reactive<LecturerContactInfo>(clone(props.contact));

watch(
  () => props.contact,
  (next) => {
    if (!isEditing.value) Object.assign(draft, clone(next));
  },
  { deep: true }
);

const canSave = computed(() => {
  const email = draft.personalEmail.trim();
  if (!email) return true;
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
});

function startEdit() {
  Object.assign(draft, clone(props.contact));
  isEditing.value = true;
}
function cancelEdit() {
  Object.assign(draft, clone(props.contact));
  isEditing.value = false;
}
function save() {
  if (!canSave.value) return;
  isEditing.value = false;
  emit("update:contact", clone(draft));
}

function mailHref(email: string) {
  return `mailto:${email}`;
}
function telHref(phone: string) {
  return `tel:${phone.replace(/\s+/g, "")}`;
}

const LinkRow = defineComponent({
  name: "LinkRow",
  props: {
    icon: { type: [Object, Function], required: true },
    label: { type: String, required: true },
    value: { type: String, required: true },
    href: { type: String as () => string | null, default: null }, // ✅ allow null
    isLink: { type: Boolean, default: false },
  },
  setup(p) {
    return () =>
      h(
        "div",
        { class: "rounded-xl border border-slate-200 bg-slate-50 p-3" },
        [
          h("div", { class: "flex items-start gap-2" }, [
            h(p.icon as Component, { class: "mt-0.5 h-4 w-4 text-slate-500" }),
            h("div", { class: "min-w-0" }, [
              h("div", { class: "text-xs text-slate-500" }, p.label),
              p.isLink && p.href
                ? h(
                    "a",
                    {
                      class:
                        "mt-0.5 block truncate text-sm font-medium text-slate-900 hover:underline",
                      href: p.href,
                      target: p.href.startsWith("http") ? "_blank" : undefined,
                      rel: p.href.startsWith("http") ? "noreferrer" : undefined,
                    },
                    p.value
                  )
                : h(
                    "div",
                    {
                      class:
                        "mt-0.5 truncate text-sm font-medium text-slate-900",
                    },
                    p.value
                  ),
            ]),
          ]),
        ]
      );
  },
});
</script>
