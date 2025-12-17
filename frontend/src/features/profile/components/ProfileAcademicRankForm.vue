<!-- src/features/profile/components/ProfileAcademicRankForm.vue -->
<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <!-- Học vị -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Học vị <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.highestDegree"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        >
          <option value="">-- Chọn học vị --</option>
          <option
            v-for="option in degreeOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <p v-if="errors.highestDegree" class="mt-1 text-xs text-red-600">
          {{ errors.highestDegree }}
        </p>
      </div>

      <!-- Chuyên ngành -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Chuyên ngành <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.degreeMajor"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: Công nghệ thông tin, Toán ứng dụng..."
        />
        <p v-if="errors.degreeMajor" class="mt-1 text-xs text-red-600">
          {{ errors.degreeMajor }}
        </p>
      </div>

      <!-- Nơi đào tạo -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Nơi đào tạo / bảo vệ <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.degreeInstitution"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="Tên trường / viện đào tạo"
        />
        <p v-if="errors.degreeInstitution" class="mt-1 text-xs text-red-600">
          {{ errors.degreeInstitution }}
        </p>
      </div>

      <!-- Quốc gia -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Quốc gia
        </label>
        <input
          v-model="form.degreeCountry"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: Việt Nam"
        />
      </div>

      <!-- Năm bảo vệ -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Năm bảo vệ / tốt nghiệp
        </label>
        <input
          v-model="form.degreeYear"
          type="number"
          min="1900"
          max="2100"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: 2018"
        />
        <p v-if="errors.degreeYear" class="mt-1 text-xs text-red-600">
          {{ errors.degreeYear }}
        </p>
      </div>

      <!-- Chức danh khoa học -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Chức danh khoa học
        </label>
        <select
          v-model="form.academicTitle"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        >
          <option value="NONE">Không</option>
          <option value="ASSOCIATE_PROFESSOR">Phó Giáo sư</option>
          <option value="PROFESSOR">Giáo sư</option>
        </select>
      </div>

      <!-- Năm phong chức danh -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Năm phong chức danh
        </label>
        <input
          v-model="form.academicTitleYear"
          type="number"
          min="1900"
          max="2100"
          :disabled="form.academicTitle === 'NONE'"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm disabled:bg-slate-50 disabled:text-slate-400 focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: 2022"
        />
        <p v-if="errors.academicTitleYear" class="mt-1 text-xs text-red-600">
          {{ errors.academicTitleYear }}
        </p>
      </div>

      <!-- Nơi phong chức danh -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">
          Nơi phong chức danh
        </label>
        <input
          v-model="form.academicTitleInstitution"
          type="text"
          :disabled="form.academicTitle === 'NONE'"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm disabled:bg-slate-50 disabled:text-slate-400 focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="Cơ quan/đơn vị ra quyết định phong PGS/GS"
        />
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

export type HighestDegree = "BACHELOR" | "MASTER" | "PHD" | "OTHER";
export type AcademicTitle = "NONE" | "ASSOCIATE_PROFESSOR" | "PROFESSOR";

export interface AcademicRankFormModel {
  highestDegree: HighestDegree;
  degreeMajor: string;
  degreeInstitution: string;
  degreeCountry?: string;
  degreeYear?: number | null;
  academicTitle: AcademicTitle;
  academicTitleYear?: number | null;
  academicTitleInstitution?: string;
  note?: string;
}

interface AcademicRankFormState {
  highestDegree: HighestDegree | "";
  degreeMajor: string;
  degreeInstitution: string;
  degreeCountry: string;
  degreeYear: string;
  academicTitle: AcademicTitle;
  academicTitleYear: string;
  academicTitleInstitution: string;
  note: string;
}

interface OptionItem<T extends string> {
  value: T;
  label: string;
}

const props = defineProps<{
  modelValue: AcademicRankFormModel | null;
}>();

const emit = defineEmits<{
  (e: "cancel"): void;
  (e: "submit", payload: AcademicRankFormModel): void;
}>();

const degreeOptions: OptionItem<HighestDegree>[] = [
  { value: "BACHELOR", label: "Cử nhân / Kỹ sư" },
  { value: "MASTER", label: "Thạc sĩ" },
  { value: "PHD", label: "Tiến sĩ" },
  { value: "OTHER", label: "Khác" },
];

const form = reactive<AcademicRankFormState>({
  highestDegree: "",
  degreeMajor: "",
  degreeInstitution: "",
  degreeCountry: "Việt Nam",
  degreeYear: "",
  academicTitle: "NONE",
  academicTitleYear: "",
  academicTitleInstitution: "",
  note: "",
});

const errors = reactive<Partial<Record<keyof AcademicRankFormState, string>>>(
  {}
);

const resetErrors = () => {
  Object.keys(errors).forEach((key) => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (errors as any)[key] = "";
  });
};

const fillFromModel = (model: AcademicRankFormModel | null) => {
  if (!model) {
    form.highestDegree = "";
    form.degreeMajor = "";
    form.degreeInstitution = "";
    form.degreeCountry = "Việt Nam";
    form.degreeYear = "";
    form.academicTitle = "NONE";
    form.academicTitleYear = "";
    form.academicTitleInstitution = "";
    form.note = "";
    resetErrors();
    return;
  }

  form.highestDegree = model.highestDegree;
  form.degreeMajor = model.degreeMajor;
  form.degreeInstitution = model.degreeInstitution;
  form.degreeCountry = model.degreeCountry || "Việt Nam";
  form.degreeYear = model.degreeYear ? String(model.degreeYear) : "";
  form.academicTitle = model.academicTitle || "NONE";
  form.academicTitleYear = model.academicTitleYear
    ? String(model.academicTitleYear)
    : "";
  form.academicTitleInstitution = model.academicTitleInstitution || "";
  form.note = model.note || "";
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

  if (!form.highestDegree) {
    errors.highestDegree = "Vui lòng chọn học vị.";
    valid = false;
  }

  if (!form.degreeMajor.trim()) {
    errors.degreeMajor = "Vui lòng nhập chuyên ngành.";
    valid = false;
  }

  if (!form.degreeInstitution.trim()) {
    errors.degreeInstitution = "Vui lòng nhập nơi đào tạo/bảo vệ.";
    valid = false;
  }

  const currentYear = new Date().getFullYear();

  if (form.degreeYear) {
    const year = Number(form.degreeYear);
    if (Number.isNaN(year) || year < 1900 || year > currentYear + 1) {
      errors.degreeYear = "Năm bảo vệ / tốt nghiệp không hợp lệ.";
      valid = false;
    }
  }

  if (form.academicTitle !== "NONE") {
    if (!form.academicTitleYear) {
      errors.academicTitleYear =
        'Vui lòng nhập năm phong chức danh hoặc chọn "Không".';
      valid = false;
    } else {
      const titleYear = Number(form.academicTitleYear);
      if (
        Number.isNaN(titleYear) ||
        titleYear < 1900 ||
        titleYear > currentYear + 1
      ) {
        errors.academicTitleYear = "Năm phong chức danh không hợp lệ.";
        valid = false;
      }
    }
  }

  return valid;
};

const onCancel = () => {
  emit("cancel");
};

const onSubmit = () => {
  if (!validateForm()) return;

  const payload: AcademicRankFormModel = {
    highestDegree: form.highestDegree as HighestDegree,
    degreeMajor: form.degreeMajor.trim(),
    degreeInstitution: form.degreeInstitution.trim(),
    degreeCountry: form.degreeCountry.trim() || undefined,
    degreeYear: form.degreeYear ? Number(form.degreeYear) : undefined,
    academicTitle: form.academicTitle,
    academicTitleYear: form.academicTitleYear
      ? Number(form.academicTitleYear)
      : undefined,
    academicTitleInstitution: form.academicTitleInstitution.trim() || undefined,
    note: form.note.trim() || undefined,
  };

  emit("submit", payload);
};
</script>
