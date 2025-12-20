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
        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
        @click="emitPageHeaderEvent('exportPdfClicked')"
      >
        <svg
          viewBox="0 0 24 24"
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
        >
          <path
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"
          />
          <path
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M14 3v4a2 2 0 0 0 2 2h4"
          />
        </svg>
        {{ exportPdfLabel }}
      </button>

      <button
        v-if="showExportExcel"
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
        @click="emitPageHeaderEvent('exportExcelClicked')"
      >
        <svg
          viewBox="0 0 24 24"
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
        >
          <path
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M4 4h16v16H4z"
          />
          <path
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M8 8h8M8 12h8M8 16h8"
          />
        </svg>
        {{ exportExcelLabel }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PageHeaderProps {
  title: string;
  subtitle?: string;

  showExportPdf?: boolean;
  showExportExcel?: boolean;

  exportPdfLabel?: string;
  exportExcelLabel?: string;
}

withDefaults(defineProps<PageHeaderProps>(), {
  subtitle: "",
  showExportPdf: true,
  showExportExcel: true,
  exportPdfLabel: "Xuất PDF",
  exportExcelLabel: "Xuất Excel",
});

const emitPageHeaderEvent = defineEmits<{
  (eventName: "exportPdfClicked"): void;
  (eventName: "exportExcelClicked"): void;
}>();
</script>
