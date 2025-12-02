<!-- src/features/declarations/components/WorkEvidenceUpload.vue -->
<template>
  <div class="space-y-3">
    <div
      class="flex flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center"
    >
      <p class="text-sm font-medium text-slate-700">
        Tải lên minh chứng cho công trình
      </p>
      <p class="mt-1 text-xs text-slate-500">
        Có thể là file PDF, hình ảnh scan, quyết định, biên bản nghiệm thu, v.v.
      </p>

      <label
        class="mt-3 inline-flex cursor-pointer items-center rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-700"
      >
        Chọn file...
        <input type="file" class="hidden" multiple @change="onFilesSelected" />
      </label>
    </div>

    <ul v-if="files.length" class="space-y-2 text-xs text-slate-600">
      <li
        v-for="(file, index) in files"
        :key="file.id ?? index"
        class="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2"
      >
        <div class="min-w-0">
          <p class="truncate text-[13px] font-medium text-slate-800">
            {{ file.name }}
          </p>
          <p class="text-[11px] text-slate-400">
            {{ file.size ? (file.size / 1024).toFixed(1) : "?" }} KB
          </p>
        </div>

        <button
          type="button"
          class="ml-3 rounded-md px-2 py-1 text-[11px] text-red-600 hover:bg-red-50"
          @click="removeFile(index)"
        >
          Xóa
        </button>
      </li>
    </ul>

    <p v-else class="text-[11px] text-slate-400">Chưa có file nào được chọn.</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { WorkEvidenceFile } from "@/features/declarations/types";

interface Props {
  modelValue: WorkEvidenceFile[];
  workType: string; // nếu cần dùng sau này
}

const props = defineProps<Props>();
const emit = defineEmits<{
  (e: "update:modelValue", value: WorkEvidenceFile[]): void;
}>();

const files = computed({
  get: () => props.modelValue ?? [],
  set: (val: WorkEvidenceFile[]) => emit("update:modelValue", val),
});

function onFilesSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  if (!input.files?.length) return;

  const selectedFiles = Array.from(input.files).map((f) => ({
    id: crypto.randomUUID(),
    name: f.name,
    size: f.size,
    type: f.type,
    file: f,
  })) as WorkEvidenceFile[];

  files.value = [...files.value, ...selectedFiles];
  // clear input để lần sau chọn lại cùng tên vẫn nhận
  input.value = "";
}

function removeFile(index: number) {
  const clone = [...files.value];
  clone.splice(index, 1);
  files.value = clone;
}
</script>
