<!-- src/features/profile/components/ProfileResearchAreaForm.vue -->
<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div class="grid gap-4 md:grid-cols-2">
      <!-- Tên lĩnh vực -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">
          Tên lĩnh vực nghiên cứu <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.name"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: Trí tuệ nhân tạo, Thị giác máy tính, Hệ thống nhúng..."
        />
        <p v-if="errors.name" class="mt-1 text-xs text-red-600">
          {{ errors.name }}
        </p>
      </div>

      <!-- Loại / vai trò -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Vai trò <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.type"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
        >
          <option value="">-- Chọn vai trò --</option>
          <option value="PRIMARY">Chính (ưu tiên)</option>
          <option value="SECONDARY">Phụ</option>
        </select>
        <p v-if="errors.type" class="mt-1 text-xs text-red-600">
          {{ errors.type }}
        </p>
      </div>

      <!-- Năm bắt đầu -->
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Năm bắt đầu nghiên cứu
        </label>
        <input
          v-model="form.startYear"
          type="number"
          min="1900"
          max="2100"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: 2018"
        />
        <p v-if="errors.startYear" class="mt-1 text-xs text-red-600">
          {{ errors.startYear }}
        </p>
      </div>

      <!-- Từ khóa -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">
          Từ khóa liên quan
          <span class="text-xs text-slate-400">(ngăn cách bằng dấu phẩy)</span>
        </label>
        <input
          v-model="form.keywords"
          type="text"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="VD: machine learning, deep learning, NLP..."
        />
      </div>

      <!-- Mô tả -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-slate-700">
          Mô tả ngắn gọn
        </label>
        <textarea
          v-model="form.description"
          rows="3"
          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-[#234a74] focus:outline-none focus:ring-1 focus:ring-[#234a74]"
          placeholder="Mô tả định hướng, chủ đề chính trong lĩnh vực này..."
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

export type ResearchAreaType = "PRIMARY" | "SECONDARY";

export interface ResearchAreaFormModel {
  name: string;
  type: ResearchAreaType;
  startYear?: number | null;
  keywords?: string;
  description?: string;
}

interface ResearchAreaFormState {
  name: string;
  type: ResearchAreaType | "";
  startYear: string;
  keywords: string;
  description: string;
}

const props = defineProps<{
  modelValue: ResearchAreaFormModel | null;
}>();

const emit = defineEmits<{
  (e: "cancel"): void;
  (e: "submit", payload: ResearchAreaFormModel): void;
}>();

const form = reactive<ResearchAreaFormState>({
  name: "",
  type: "",
  startYear: "",
  keywords: "",
  description: "",
});

const errors = reactive<Partial<Record<keyof ResearchAreaFormState, string>>>(
  {}
);

const resetErrors = () => {
  Object.keys(errors).forEach((key) => {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    (errors as any)[key] = "";
  });
};

const fillFromModel = (model: ResearchAreaFormModel | null) => {
  if (!model) {
    form.name = "";
    form.type = "";
    form.startYear = "";
    form.keywords = "";
    form.description = "";
    resetErrors();
    return;
  }

  form.name = model.name;
  form.type = model.type;
  form.startYear = model.startYear ? String(model.startYear) : "";
  form.keywords = model.keywords ?? "";
  form.description = model.description ?? "";
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

  if (!form.name.trim()) {
    errors.name = "Vui lòng nhập tên lĩnh vực nghiên cứu.";
    valid = false;
  }

  if (!form.type) {
    errors.type = "Vui lòng chọn vai trò cho lĩnh vực này.";
    valid = false;
  }

  if (form.startYear) {
    const year = Number(form.startYear);
    const currentYear = new Date().getFullYear();
    if (Number.isNaN(year) || year < 1900 || year > currentYear + 1) {
      errors.startYear = "Năm bắt đầu nghiên cứu không hợp lệ.";
      valid = false;
    }
  }

  return valid;
};

const onCancel = () => {
  emit("cancel");
};

const onSubmit = () => {
  if (!validateForm()) return;

  const payload: ResearchAreaFormModel = {
    name: form.name.trim(),
    type: form.type as ResearchAreaType,
    startYear: form.startYear ? Number(form.startYear) : undefined,
    keywords: form.keywords.trim() || undefined,
    description: form.description.trim() || undefined,
  };

  emit("submit", payload);
};
</script>
