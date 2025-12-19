<template>
  <form
    class="rounded-3xl border border-slate-200 bg-white px-4 py-3 shadow-sm md:px-6 md:py-4"
    @submit.prevent="onSubmit"
  >
    <div class="grid gap-3 md:grid-cols-5 md:items-end">
      <!-- Năm học -->
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-slate-600">
          Năm học <span class="text-rose-500">*</span>
        </label>
        <select
          v-model="localFilter.academicYear"
          class="block w-full rounded-full border bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
          :class="yearError ? 'border-rose-400' : 'border-slate-300'"
        >
          <option value="" disabled>Chọn năm học</option>
          <option
            v-for="option in yearOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
        <p v-if="yearError" class="text-xs text-rose-500">
          Vui lòng chọn năm học.
        </p>
      </div>

      <!-- Học kỳ -->
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-slate-600">Học kỳ</label>
        <select
          v-model="localFilter.semester"
          class="block w-full rounded-full border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
        >
          <option
            v-for="option in semesterOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>

      <!-- Phạm vi -->
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-slate-600">
          Phạm vi <span class="text-rose-500">*</span>
        </label>
        <select
          v-model="localFilter.scope"
          class="block w-full rounded-full border bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
          :class="scopeError ? 'border-rose-400' : 'border-slate-300'"
        >
          <option value="" disabled>Chọn phạm vi</option>
          <option
            v-for="option in scopeOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>

      <!-- Bộ môn -->
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-slate-600">Bộ môn</label>
        <select
          v-model="localFilter.departmentId"
          class="block w-full rounded-full border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
        >
          <option value="all">Tất cả bộ môn</option>
          <option
            v-for="option in departmentOptions"
            :key="option.id"
            :value="option.id"
          >
            {{ option.name }}
          </option>
        </select>
      </div>

      <!-- Search + Lọc -->
      <div class="flex flex-col gap-1">
        <label class="text-xs font-medium text-slate-600">
          Tìm theo tên giảng viên
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model.trim="localFilter.lecturerName"
            type="text"
            placeholder="Nhập tên giảng viên..."
            class="flex-1 rounded-full border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
            @keyup.enter.prevent="onSubmit"
          />
          <button
            type="submit"
            class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
            :disabled="loading"
          >
            Lọc
          </button>
        </div>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type {
  DepartmentOption,
  ProjectsFilter,
  ScopeOption,
  SelectOption,
} from "@/features/scientific/research-works/types";

interface Props {
  modelValue: ProjectsFilter;
  loading?: boolean;
  scopeOptions: ScopeOption[];
  yearOptions: SelectOption<string>[];
  semesterOptions: SelectOption<string>[];
  departmentOptions: DepartmentOption[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "update:modelValue", value: ProjectsFilter): void;
  (e: "apply"): void;
}>();

const localFilter = ref<ProjectsFilter>({ ...props.modelValue });

watch(
  () => props.modelValue,
  (val) => {
    localFilter.value = { ...val };
  }
);

watch(
  () => localFilter.value,
  (val) => {
    emit("update:modelValue", val);
  },
  { deep: true }
);

const touched = ref(false);

const yearError = computed(
  () => touched.value && !localFilter.value.academicYear
);
const scopeError = computed(() => touched.value && !localFilter.value.scope);

function onSubmit() {
  touched.value = true;
  if (yearError.value || scopeError.value) return;
  emit("apply");
}
</script>
