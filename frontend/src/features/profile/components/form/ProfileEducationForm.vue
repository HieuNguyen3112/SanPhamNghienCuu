<!-- src/features/profile/components/ProfileEducationForm.vue -->
<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <!-- Bậc đào tạo -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Bậc đào tạo <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.degreeLevel"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        >
          <option value="">-- Chọn bậc đào tạo --</option>
          <option
            v-for="option in degreeOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.degreeLevel" class="mt-1 text-xs text-red-600">
          {{ errors.degreeLevel }}
        </p>
      </div>

      <!-- Chuyên ngành -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Chuyên ngành <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.major"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: Công nghệ thông tin, Toán ứng dụng..."
        />
        <p v-if="errors.major" class="mt-1 text-xs text-red-600">
          {{ errors.major }}
        </p>
      </div>

      <!-- Cơ sở đào tạo -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Cơ sở đào tạo <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.institution"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="Tên trường / viện đào tạo"
        />
        <p v-if="errors.institution" class="mt-1 text-xs text-red-600">
          {{ errors.institution }}
        </p>
      </div>

      <!-- Quốc gia -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Quốc gia <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.country"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: Việt Nam"
        />
        <p v-if="errors.country" class="mt-1 text-xs text-red-600">
          {{ errors.country }}
        </p>
      </div>

      <!-- Năm bắt đầu -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Năm bắt đầu <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.startYear"
          type="number"
          min="1900"
          max="2100"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: 2015"
        />
        <p v-if="errors.startYear" class="mt-1 text-xs text-red-600">
          {{ errors.startYear }}
        </p>
      </div>

      <!-- Năm kết thúc -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Năm kết thúc / tốt nghiệp <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.endYear"
          type="number"
          min="1900"
          max="2100"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: 2019"
        />
        <p v-if="errors.endYear" class="mt-1 text-xs text-red-600">
          {{ errors.endYear }}
        </p>
      </div>

      <!-- Hình thức đào tạo -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Hình thức đào tạo <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.trainingType"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        >
          <option value="">-- Chọn hình thức đào tạo --</option>
          <option
            v-for="option in trainingTypeOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.trainingType" class="mt-1 text-xs text-red-600">
          {{ errors.trainingType }}
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
import { reactive, watch } from "vue";

export type DegreeLevel =
  | "UNDERGRADUATE"
  | "MASTER"
  | "PHD"
  | "POSTDOC"
  | "OTHER";

export type TrainingType =
  | "FULL_TIME"
  | "PART_TIME"
  | "IN_SERVICE"
  | "DISTANCE"
  | "OTHER";

export interface EducationFormModel {
  degreeLevel: DegreeLevel;
  major: string;
  institution: string;
  country: string;
  startYear: number;
  endYear: number;
  trainingType: TrainingType;
  note?: string;
}

interface EducationFormState {
  degreeLevel: DegreeLevel | "";
  major: string;
  institution: string;
  country: string;
  startYear: string;
  endYear: string;
  trainingType: TrainingType | "";
  note: string;
}

interface OptionItem<T extends string> {
  value: T;
  label: string;
}

const props = defineProps<{
  modelValue: EducationFormModel | null;
  degreeOptions: OptionItem<DegreeLevel>[];
  trainingTypeOptions: OptionItem<TrainingType>[];
}>();

const emit = defineEmits<{
  (e: "cancel"): void;
  (e: "submit", payload: EducationFormModel): void;
}>();

const form = reactive<EducationFormState>({
  degreeLevel: "",
  major: "",
  institution: "",
  country: "Việt Nam",
  startYear: "",
  endYear: "",
  trainingType: "",
  note: "",
});

const errors = reactive<Partial<Record<keyof EducationFormState, string>>>({});

const resetErrors = () => {
  Object.keys(errors).forEach((key) => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (errors as any)[key] = "";
  });
};

const fillFromModel = (model: EducationFormModel | null) => {
  if (!model) {
    form.degreeLevel = "";
    form.major = "";
    form.institution = "";
    form.country = "Việt Nam";
    form.startYear = "";
    form.endYear = "";
    form.trainingType = "";
    form.note = "";
    resetErrors();
    return;
  }

  form.degreeLevel = model.degreeLevel;
  form.major = model.major;
  form.institution = model.institution;
  form.country = model.country;
  form.startYear = String(model.startYear ?? "");
  form.endYear = String(model.endYear ?? "");
  form.trainingType = model.trainingType;
  form.note = model.note ?? "";
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

  if (!form.degreeLevel) {
    errors.degreeLevel = "Vui lòng chọn bậc đào tạo.";
    valid = false;
  }
  if (!form.major?.trim()) {
    errors.major = "Vui lòng nhập chuyên ngành.";
    valid = false;
  }
  if (!form.institution?.trim()) {
    errors.institution = "Vui lòng nhập cơ sở đào tạo.";
    valid = false;
  }
  if (!form.country?.trim()) {
    errors.country = "Vui lòng nhập quốc gia.";
    valid = false;
  }

  const start = Number(form.startYear);
  const end = Number(form.endYear);
  const currentYear = new Date().getFullYear();

  if (!form.startYear) {
    errors.startYear = "Vui lòng nhập năm bắt đầu.";
    valid = false;
  } else if (Number.isNaN(start) || start < 1900 || start > currentYear + 1) {
    errors.startYear = "Năm bắt đầu không hợp lệ.";
    valid = false;
  }

  if (!form.endYear) {
    errors.endYear = "Vui lòng nhập năm kết thúc / tốt nghiệp.";
    valid = false;
  } else if (Number.isNaN(end) || end < 1900 || end > currentYear + 10) {
    errors.endYear = "Năm kết thúc / tốt nghiệp không hợp lệ.";
    valid = false;
  } else if (!Number.isNaN(start) && end < start) {
    errors.endYear = "Năm kết thúc phải lớn hơn hoặc bằng năm bắt đầu.";
    valid = false;
  }

  if (!form.trainingType) {
    errors.trainingType = "Vui lòng chọn hình thức đào tạo.";
    valid = false;
  }

  return valid;
};

const onCancel = () => {
  emit("cancel");
};

const onSubmit = () => {
  if (!validateForm()) return;

  const payload: EducationFormModel = {
    degreeLevel: form.degreeLevel as DegreeLevel,
    major: form.major.trim(),
    institution: form.institution.trim(),
    country: form.country.trim(),
    startYear: Number(form.startYear),
    endYear: Number(form.endYear),
    trainingType: form.trainingType as TrainingType,
    note: form.note?.trim() || undefined,
  };

  emit("submit", payload);
};
</script>
