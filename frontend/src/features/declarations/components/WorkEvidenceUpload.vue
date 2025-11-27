<!-- src/features/declarations/components/WorkEvidenceUpload.vue -->
<template>
  <div class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-slate-700">
        Tệp minh chứng
      </label>
      <input
        type="file"
        class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-sky-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-sky-700 hover:file:bg-sky-100"
        :multiple="allowMultiple"
        :accept="accept"
        :disabled="disabled || uploading"
        @change="onFilesSelected"
      />
      <p class="mt-1 text-xs text-slate-400">
        {{ helperText }}
      </p>
    </div>

    <div v-if="files.length" class="space-y-2">
      <p class="text-xs font-medium uppercase text-slate-500">
        Đã chọn {{ files.length }} tệp
      </p>
      <ul class="space-y-1 text-xs text-slate-700">
        <li
          v-for="(file, index) in files"
          :key="file.name + file.lastModified"
          class="flex items-center justify-between rounded-md bg-slate-50 px-2 py-1"
        >
          <div class="min-w-0 flex-1">
            <p class="truncate">{{ file.name }}</p>
            <p class="text-[11px] text-slate-400">
              {{ Math.round(file.size / 1024) }} KB
            </p>
          </div>
          <button
            type="button"
            class="ml-2 text-[11px] text-red-500 hover:underline"
            :disabled="disabled || uploading"
            @click="removeFile(index)"
          >
            Xóa
          </button>
        </li>
      </ul>
    </div>

    <div v-if="files.length" class="pt-1">
      <button
        type="button"
        class="w-full rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="disabled || uploading"
        @click="handleUpload"
      >
        <span
          v-if="uploading"
          class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"
        />
        {{ uploading ? "Đang tải lên..." : "Tải lên minh chứng" }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { WorkType } from "@/features/declarations/types";

interface Props {
  workType: WorkType;
  /** Chấp nhận loại file nào, vd: 'application/pdf,image/*' */
  accept?: string;
  /** Tối đa bao nhiêu file (undefined = không giới hạn) */
  maxFiles?: number;
  disabled?: boolean;
  uploading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  accept: "",
  maxFiles: undefined,
  disabled: false,
  uploading: false,
});

const emit = defineEmits<{
  (e: "filesChange", payload: { workType: WorkType; files: File[] }): void;
  (e: "upload", payload: { workType: WorkType; files: File[] }): void;
}>();

const files = ref<File[]>([]);

const allowMultiple = computed(
  () => props.maxFiles === undefined || props.maxFiles > 1
);

const helperText = computed(() => {
  if (props.maxFiles && props.maxFiles > 0) {
    return `Có thể chọn tối đa ${props.maxFiles} tệp. Hãy chọn các minh chứng theo quy định (PDF, hình ảnh, v.v.).`;
  }
  return "Có thể chọn nhiều tệp minh chứng (PDF, hình ảnh, v.v.).";
});

function onFilesSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  if (!input.files) return;

  let selected = Array.from(input.files);

  if (props.maxFiles && selected.length > props.maxFiles) {
    selected = selected.slice(0, props.maxFiles);
  }

  files.value = selected;
  emit("filesChange", { workType: props.workType, files: files.value });

  // reset input để có thể chọn lại cùng tên file
  input.value = "";
}

function removeFile(index: number) {
  files.value.splice(index, 1);
  emit("filesChange", { workType: props.workType, files: files.value });
}

function handleUpload() {
  if (!files.value.length) return;
  emit("upload", { workType: props.workType, files: files.value });
}
</script>
