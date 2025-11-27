<!-- src/features/declarations/components/WorkForm.vue -->
<template>
  <form class="space-y-4" @submit.prevent="handleSubmit">
    <!-- Tên công trình -->
    <div>
      <label class="block text-sm font-medium text-slate-700">
        Tên công trình <span class="text-red-500">*</span>
      </label>
      <input
        v-model="localForm.title"
        type="text"
        required
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        :disabled="disabled"
      />
    </div>

    <!-- Một số field chung -->
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label class="block text-sm font-medium text-slate-700">
          Năm công bố / hoàn thành
        </label>
        <input
          v-model.number="localForm.year"
          type="number"
          min="1900"
          max="2100"
          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          :disabled="disabled"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700">
          Vai trò
        </label>
        <select
          v-model="localForm.role"
          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          :disabled="disabled"
        >
          <option value="">-- Chọn vai trò --</option>
          <option value="MAIN">Chủ trì / Tác giả chính</option>
          <option value="CO">Thành viên / Đồng tác giả</option>
        </select>
      </div>
    </div>

    <!-- Mô tả / ghi chú -->
    <div>
      <label class="block text-sm font-medium text-slate-700">
        Mô tả / ghi chú
      </label>
      <textarea
        v-model="localForm.note"
        rows="3"
        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        :disabled="disabled"
      />
    </div>

    <!-- Footer: nút hành động -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="disabled"
        @click="handleCancel"
      >
        Hủy / Làm mới
      </button>
      <button
        type="submit"
        class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="disabled || submitting"
      >
        <span
          v-if="submitting"
          class="mr-2 h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"
        />
        {{ mode === "edit" ? "Cập nhật kê khai" : "Lưu kê khai" }}
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, watch } from "vue";
import type { WorkType } from "@/features/declarations/types";

export type WorkFormMode = "create" | "edit";

export interface WorkFormValue {
  title: string;
  year: number | null;
  role: "" | "MAIN" | "CO";
  note: string;
}

interface Props {
  workType: WorkType;
  mode?: WorkFormMode;
  /** Dùng cho v-model */
  modelValue?: WorkFormValue;
  /** Disable toàn bộ form */
  disabled?: boolean;
  /** Đang submit (show loading + disable button) */
  submitting?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  mode: "create",
  disabled: false,
  submitting: false,
});

const emit = defineEmits<{
  (e: "update:modelValue", value: WorkFormValue): void;
  (e: "submit", payload: { workType: WorkType; value: WorkFormValue }): void;
  (e: "cancel"): void;
}>();

const createEmptyForm = (): WorkFormValue => ({
  title: "",
  year: new Date().getFullYear(),
  role: "",
  note: "",
});

const localForm = reactive<WorkFormValue>({
  ...createEmptyForm(),
  ...(props.modelValue ?? {}),
});

// Đồng bộ từ parent -> local
watch(
  () => props.modelValue,
  (val) => {
    if (!val) return;
    Object.assign(localForm, val);
  },
  { deep: true }
);

// Đồng bộ local -> parent (v-model)
watch(
  localForm,
  (val) => {
    emit("update:modelValue", { ...val });
  },
  { deep: true }
);

function handleSubmit() {
  emit("submit", {
    workType: props.workType,
    value: { ...localForm },
  });
}

function handleCancel() {
  Object.assign(localForm, createEmptyForm());
  emit("cancel");
}
</script>
