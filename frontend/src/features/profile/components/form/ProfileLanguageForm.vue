<!-- src/features/profile/components/ProfileLanguageForm.vue -->
<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <!-- Ngoại ngữ -->
      <div class="md:col-span-1">
        <label class="block text-sm font-medium text-slate-700">
          Ngoại ngữ <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.language"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: Tiếng Anh, Tiếng Nhật..."
        />
        <p v-if="errors.language" class="mt-1 text-xs text-red-600">
          {{ errors.language }}
        </p>
      </div>

      <!-- Trình độ -->
      <div class="md:col-span-1">
        <label class="block text-sm font-medium text-slate-700">
          Trình độ <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.level"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        >
          <option value="">-- Chọn trình độ --</option>
          <option
            v-for="option in levelOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.level" class="mt-1 text-xs text-red-600">
          {{ errors.level }}
        </p>
      </div>

      <!-- Tên chứng chỉ -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Tên chứng chỉ
        </label>
        <input
          v-model="form.certificateName"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: IELTS, TOEIC, JLPT N2..."
        />
      </div>

      <!-- Đơn vị cấp -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Đơn vị cấp
        </label>
        <input
          v-model="form.certificateIssuer"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: IDP, British Council..."
        />
      </div>

      <!-- Điểm / bậc -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Điểm / bậc chứng chỉ
        </label>
        <input
          v-model="form.certificateScore"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: 7.5, 800, N2..."
        />
      </div>

      <!-- Ngày cấp -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Ngày cấp
        </label>
        <input
          v-model="form.issueDate"
          type="date"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        />
      </div>

      <!-- Ngày hết hạn -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Ngày hết hạn
        </label>
        <input
          v-model="form.expireDate"
          type="date"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        />
        <p v-if="errors.expireDate" class="mt-1 text-xs text-red-600">
          {{ errors.expireDate }}
        </p>
      </div>

      <!-- File minh chứng -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">
          Minh chứng (bằng/chứng chỉ)
          <span class="text-xs text-slate-400">(PDF, JPG, PNG)</span>
        </label>
        <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
          <label
            class="inline-flex cursor-pointer items-center justify-center rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
          >
            <span>Chọn tệp</span>
            <input
              ref="fileInputRef"
              type="file"
              class="hidden"
              accept=".pdf,.jpg,.jpeg,.png"
              @change="onFileChange"
            />
          </label>
          <p class="text-xs text-slate-500">
            {{ fileLabel }}
          </p>
        </div>
        <p v-if="errors.file" class="mt-1 text-xs text-red-600">
          {{ errors.file }}
        </p>
      </div>

      <!-- Ghi chú -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">
          Ghi chú
        </label>
        <textarea
          v-model="form.note"
          rows="2"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="Các thông tin bổ sung (nếu có)"
        />
      </div>
    </div>

    <div class="flex items-center justify-end gap-2 pt-2">
      <button
        type="button"
        class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
        @click="onCancel"
      >
        Hủy
      </button>
      <button
        type="submit"
        class="inline-flex items-center rounded-md bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
      >
        Lưu
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, ref, computed, watch } from "vue";

export type LanguageLevel =
  | "A1"
  | "A2"
  | "B1"
  | "B2"
  | "C1"
  | "C2"
  | "BASIC"
  | "INTERMEDIATE"
  | "ADVANCED";

interface OptionItem<T extends string> {
  value: T;
  label: string;
}

interface LanguageFormState {
  language: string;
  level: LanguageLevel | "";
  certificateName: string;
  certificateIssuer: string;
  certificateScore: string;
  issueDate: string;
  expireDate: string;
  note: string;
  file: File | null;
}

export interface LanguageFormPayload {
  language: string;
  level: LanguageLevel;
  certificateName?: string;
  certificateIssuer?: string;
  certificateScore?: string;
  issueDate?: string;
  expireDate?: string;
  note?: string;
  file?: File;
}

const props = defineProps<{
  levelOptions: OptionItem<LanguageLevel>[];
  modelValue: LanguageFormPayload | null;
}>();

const emit = defineEmits<{
  (e: "cancel"): void;
  (e: "submit", payload: LanguageFormPayload): void;
}>();

const form = reactive<LanguageFormState>({
  language: "",
  level: "",
  certificateName: "",
  certificateIssuer: "",
  certificateScore: "",
  issueDate: "",
  expireDate: "",
  note: "",
  file: null,
});

const errors = reactive<Partial<Record<keyof LanguageFormState, string>>>({});

const fileInputRef = ref<HTMLInputElement | null>(null);

const fileLabel = computed(() => {
  if (form.file) {
    return `${form.file.name} (${Math.round(form.file.size / 1024)} KB)`;
  }
  return "Chưa chọn tệp minh chứng";
});

const resetErrors = () => {
  Object.keys(errors).forEach((key) => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (errors as any)[key] = "";
  });
};

const resetForm = () => {
  form.language = "";
  form.level = "";
  form.certificateName = "";
  form.certificateIssuer = "";
  form.certificateScore = "";
  form.issueDate = "";
  form.expireDate = "";
  form.note = "";
  form.file = null;
  resetErrors();
};

const fillFromModel = (model: LanguageFormPayload | null) => {
  if (!model) {
    resetForm();
    return;
  }

  form.language = model.language ?? "";
  form.level = (model.level as LanguageLevel) ?? "";
  form.certificateName = model.certificateName ?? "";
  form.certificateIssuer = model.certificateIssuer ?? "";
  form.certificateScore = model.certificateScore ?? "";
  form.issueDate = model.issueDate ?? "";
  form.expireDate = model.expireDate ?? "";
  form.note = model.note ?? "";
  form.file = null; // không auto-fill file
  resetErrors();
};

watch(
  () => props.modelValue,
  (val) => fillFromModel(val),
  { immediate: true }
);

const validateForm = (): boolean => {
  let valid = true;
  resetErrors();

  if (!form.language.trim()) {
    errors.language = "Vui lòng nhập ngoại ngữ.";
    valid = false;
  }

  if (!form.level) {
    errors.level = "Vui lòng chọn trình độ.";
    valid = false;
  }

  // Hạn dùng không bắt buộc, nhưng nếu nhập thì phải sau ngày cấp
  if (form.issueDate && form.expireDate && form.expireDate < form.issueDate) {
    errors.expireDate = "Ngày hết hạn phải sau ngày cấp.";
    valid = false;
  }

  return valid;
};

const onFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  form.file = file ?? null;
};

const onCancel = () => {
  emit("cancel");
};

const onSubmit = () => {
  if (!validateForm()) return;

  const payload: LanguageFormPayload = {
    language: form.language.trim(),
    level: form.level as LanguageLevel,
    certificateName: form.certificateName.trim() || undefined,
    certificateIssuer: form.certificateIssuer.trim() || undefined,
    certificateScore: form.certificateScore.trim() || undefined,
    issueDate: form.issueDate || undefined,
    expireDate: form.expireDate || undefined,
    note: form.note.trim() || undefined,
    file: form.file ?? undefined,
  };

  emit("submit", payload);
};
</script>
