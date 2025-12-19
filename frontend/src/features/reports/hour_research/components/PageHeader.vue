<template>
  <div
    class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
  >
    <div>
      <h1 class="text-xl font-semibold tracking-tight text-slate-900">
        Thống kê giờ nghiên cứu khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-600">
        Tổng quan tình hình thực hiện giờ NCKH của giảng viên
      </p>
    </div>

    <div class="flex flex-wrap gap-2">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
        @click="exportResearchHourStatisticsAsPdf"
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
        Xuất PDF
      </button>

      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
        @click="exportResearchHourStatisticsAsExcel"
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
        Xuất Excel
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
function exportResearchHourStatisticsAsPdf(): void {
  // UI-only: nút xuất file thường phụ thuộc backend hoặc thư viện xuất báo cáo.
  // Giữ nút để thể hiện luồng nghiệp vụ, nhưng không trộn logic xuất file vào màn thống kê.
  window.alert("Chức năng Xuất PDF (UI demo).");
}

function exportResearchHourStatisticsAsExcel(): void {
  window.alert("Chức năng Xuất Excel (UI demo).");
}
</script>
