<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <!-- Header -->
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h2 class="text-sm font-semibold text-slate-900">Thông tin cá nhân</h2>
        <p class="mt-0.5 text-xs text-slate-500">
          Một số thông tin do hệ thống quản lý sẽ ở trạng thái chỉ xem.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="!isEditing"
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
          :disabled="pending"
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

    <!-- Top identity -->
    <div class="mt-4 flex items-start gap-4">
      <div class="relative">
        <div
          class="grid h-14 w-14 place-items-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-sm font-semibold text-slate-700"
        >
          <img
            v-if="profile.avatarUrl"
            :src="profile.avatarUrl"
            alt="Avatar"
            class="h-full w-full object-cover"
          />
          <span v-else>{{ initials }}</span>
        </div>

        <button
          v-if="isEditing"
          type="button"
          class="absolute -bottom-1 -right-1 inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
          title="Đổi ảnh (TODO)"
          :disabled="pending"
          @click="noop"
        >
          <Camera class="h-4 w-4" />
        </button>
      </div>

      <div class="min-w-0 flex-1">
        <div class="truncate text-base font-semibold text-slate-900">
          {{ profile.fullName || "—" }}
        </div>
        <div
          class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500"
        >
          <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
            {{ profile.lecturerCode }}
          </span>
          <span class="text-slate-300">•</span>
          <span>{{ profile.departmentName || "—" }}</span>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="mt-4 grid gap-3 md:grid-cols-2">
      <!-- Read-only -->
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <div class="text-xs text-slate-500">Mã giảng viên</div>
        <div class="mt-0.5 truncate text-sm font-medium text-slate-900">
          {{ profile.lecturerCode }}
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
        <div class="text-xs text-slate-500">Đơn vị công tác</div>
        <div class="mt-0.5 truncate text-sm font-medium text-slate-900">
          {{ profile.departmentName || "—" }}
        </div>
      </div>

      <!-- Editable fields -->
      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs font-medium text-slate-600">Họ và tên</div>
        <div
          v-if="!isEditing"
          class="mt-0.5 truncate text-sm font-medium text-slate-900"
        >
          {{ profile.fullName || "—" }}
        </div>
        <input
          v-else
          v-model.trim="draft.fullName"
          maxlength="255"
          placeholder="VD: Nguyễn Văn A"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
          :disabled="pending"
        />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs font-medium text-slate-600">Ngày sinh</div>
        <div
          v-if="!isEditing"
          class="mt-0.5 text-sm font-medium text-slate-900"
        >
          {{ profile.birthDate ? formatDate(profile.birthDate) : "—" }}
        </div>
        <input
          v-else
          v-model="draft.birthDate"
          type="date"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
          :disabled="pending"
        />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs font-medium text-slate-600">Giới tính</div>
        <div
          v-if="!isEditing"
          class="mt-0.5 text-sm font-medium text-slate-900"
        >
          {{ genderLabel(profile.gender) }}
        </div>
        <select
          v-else
          v-model="draft.gender"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
          :disabled="pending"
        >
          <option v-for="o in genderOptions" :key="o.value" :value="o.value">
            {{ o.label }}
          </option>
        </select>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs font-medium text-slate-600">
          Chức danh nghề nghiệp
        </div>
        <div
          v-if="!isEditing"
          class="mt-0.5 truncate text-sm font-medium text-slate-900"
        >
          {{ profile.jobTitle || "—" }}
        </div>
        <input
          v-else
          v-model.trim="draft.jobTitle"
          maxlength="255"
          placeholder="VD: Giảng viên / Giảng viên chính"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
          :disabled="pending"
        />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs font-medium text-slate-600">Loại cán bộ</div>
        <div
          v-if="!isEditing"
          class="mt-0.5 text-sm font-medium text-slate-900"
        >
          {{ employmentTypeLabel(profile.employmentType) }}
        </div>
        <select
          v-else
          v-model="draft.employmentType"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
          :disabled="pending"
        >
          <option
            v-for="o in employmentTypeOptions"
            :key="o.value"
            :value="o.value"
          >
            {{ o.label }}
          </option>
        </select>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs font-medium text-slate-600">
          Tình trạng công tác
        </div>
        <div
          v-if="!isEditing"
          class="mt-0.5 text-sm font-medium text-slate-900"
        >
          {{ workStatusLabel(profile.workStatus) }}
        </div>
        <select
          v-else
          v-model="draft.workStatus"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
          :disabled="pending"
        >
          <option
            v-for="o in workStatusOptions"
            :key="o.value"
            :value="o.value"
          >
            {{ o.label }}
          </option>
        </select>
      </div>
    </div>

    <div
      v-if="isEditing"
      class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600"
    >
      Lưu ý: Mã giảng viên / Đơn vị công tác là dữ liệu hệ thống (HR) nên chỉ
      xem.
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { Camera, Pencil, Save, X } from "lucide-vue-next";
import type {
  LecturerPersonalInfo,
  Gender,
  EmploymentType,
  WorkStatus,
} from "../composables/useLecturerProfile";

const props = defineProps<{
  profile: LecturerPersonalInfo;
  pending?: boolean;
  onSave?: (patch: Partial<LecturerPersonalInfo>) => void | Promise<void>;
}>();

const emit = defineEmits<{
  (e: "update:profile", value: LecturerPersonalInfo): void;
}>();

const pending = computed(() => props.pending === true);

const isEditing = ref(false);

const draft = reactive<LecturerPersonalInfo>({
  ...props.profile,
});

watch(
  () => props.profile,
  (next) => {
    if (!isEditing.value) Object.assign(draft, next);
  },
  { deep: true }
);

const initials = computed(() => {
  const name = (props.profile.fullName || "").trim();
  if (!name) return "GV";
  const parts = name.split(/\s+/).filter(Boolean);
  const a = parts[0]?.[0] ?? "G";
  const b = parts.length > 1 ? parts[parts.length - 1]?.[0] ?? "V" : "V";
  return `${a}${b}`.toUpperCase();
});

const genderOptions: Array<{ value: Gender; label: string }> = [
  { value: "male", label: "Nam" },
  { value: "female", label: "Nữ" },
  { value: "other", label: "Khác" },
];

const employmentTypeOptions: Array<{ value: EmploymentType; label: string }> = [
  { value: "full_time", label: "Cơ hữu" },
  { value: "visiting", label: "Thỉnh giảng" },
];

const workStatusOptions: Array<{ value: WorkStatus; label: string }> = [
  { value: "active", label: "Đang công tác" },
  { value: "on_leave", label: "Nghỉ phép" },
  { value: "retired", label: "Nghỉ hưu" },
  { value: "resigned", label: "Thôi việc" },
];

function genderLabel(v: Gender) {
  return genderOptions.find((x) => x.value === v)?.label ?? "—";
}
function employmentTypeLabel(v: EmploymentType) {
  return employmentTypeOptions.find((x) => x.value === v)?.label ?? "—";
}
function workStatusLabel(v: WorkStatus) {
  return workStatusOptions.find((x) => x.value === v)?.label ?? "—";
}

function formatDate(iso: string) {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString();
}

const canSave = computed(() => {
  if (!draft.fullName.trim()) return false;
  if (draft.fullName.length > 255) return false;
  if (draft.jobTitle && draft.jobTitle.length > 255) return false;
  return true;
});

const editableKeys = [
  "fullName",
  "birthDate",
  "gender",
  "jobTitle",
  "employmentType",
  "workStatus",
  "avatarUrl",
] as const;

type EditableKey = (typeof editableKeys)[number];
type EditablePatch = Partial<Pick<LecturerPersonalInfo, EditableKey>>;

function startEdit() {
  Object.assign(draft, props.profile);
  isEditing.value = true;
}

function cancelEdit() {
  Object.assign(draft, props.profile);
  isEditing.value = false;
}

async function save() {
  const patch: EditablePatch = {};

  const setPatch = <K extends EditableKey>(
    k: K,
    v: LecturerPersonalInfo[K]
  ) => {
    patch[k] = v;
  };

  for (const k of editableKeys) {
    if (draft[k] !== props.profile[k]) {
      setPatch(k, draft[k]);
    }
  }

  if (Object.keys(patch).length === 0) {
    isEditing.value = false;
    return;
  }

  // patch (EditablePatch) vẫn assignable sang Partial<LecturerPersonalInfo>
  await props.onSave?.(patch);

  emit("update:profile", {
    ...props.profile,
    ...patch,
  });

  isEditing.value = false;
}

function noop() {
  // TODO: upload avatar
}
</script>
