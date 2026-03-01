// File: src/shared/components/PageHeader.vue
<template>
  <div
    class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
  >
    <div class="min-w-0">
      <h1 class="text-xl font-semibold tracking-tight text-slate-900">
        {{ title }}
      </h1>
      <p v-if="subtitle" class="mt-1 text-sm text-slate-600">
        {{ subtitle }}
      </p>
    </div>

    <div class="flex flex-wrap gap-2">
      <button
        v-if="showExportPdf"
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
        :disabled="exportPdfDisabled"
        @click="emit('exportPdfClicked')"
      >
        <FileText class="h-4 w-4" />
        {{ exportPdfLabel }}
      </button>

      <button
        v-if="showExportExcel"
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
        :disabled="exportExcelDisabled"
        @click="emit('exportExcelClicked')"
      >
        <Sheet class="h-4 w-4" />
        {{ exportExcelLabel }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FileText, Sheet } from "lucide-vue-next";

interface PageHeaderProps {
  title: string;
  subtitle?: string;

  showExportPdf?: boolean;
  showExportExcel?: boolean;
  exportPdfDisabled?: boolean;
  exportExcelDisabled?: boolean;

  exportPdfLabel?: string;
  exportExcelLabel?: string;
}

withDefaults(defineProps<PageHeaderProps>(), {
  subtitle: "",
  showExportPdf: true,
  showExportExcel: true,
  exportPdfDisabled: false,
  exportExcelDisabled: false,
  exportPdfLabel: "Xuất PDF",
  exportExcelLabel: "Xuất Excel",
});

const emit = defineEmits<{
  (e: "exportPdfClicked"): void;
  (e: "exportExcelClicked"): void;
}>();
</script>
