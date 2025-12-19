<!-- src/features/hours/components/HoursBatchDetailTable.vue -->
<template>
  <div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-sm font-semibold text-slate-700">
        Danh sách công trình trong đợt tính giờ {{ batchCode }}
      </h2>

      <button
        type="button"
        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
        @click="exportToPdf"
      >
        Xuất PDF
      </button>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-sm">
        <thead
          class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500"
        >
          <tr>
            <th class="px-3 py-2 text-center">#</th>
            <th class="px-3 py-2">Công trình</th>
            <th class="px-3 py-2">Loại</th>
            <th class="px-3 py-2">Vai trò</th>
            <th class="px-3 py-2 text-right">Hệ số</th>
            <th class="px-3 py-2 text-right">Giờ quy đổi</th>
            <th class="px-3 py-2">Ghi chú</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="!items.length">
            <td
              colspan="7"
              class="px-3 py-4 text-center text-sm text-slate-500"
            >
              Đợt tính giờ này chưa có công trình nào.
            </td>
          </tr>

          <tr v-for="(row, index) in items" :key="row.id">
            <td class="px-3 py-2 align-top text-center text-xs text-slate-500">
              {{ index + 1 }}
            </td>
            <td class="px-3 py-2 align-top">
              <p class="text-sm font-medium text-slate-800">
                {{ row.title }}
              </p>
              <p class="text-xs text-slate-400">Mã: {{ row.id }}</p>
            </td>
            <td class="px-3 py-2 align-top text-sm text-slate-700">
              {{ row.workTypeLabel }}
            </td>
            <td class="px-3 py-2 align-top text-sm text-slate-700">
              {{ row.roleLabel }}
            </td>
            <td class="px-3 py-2 align-top text-right text-sm text-slate-700">
              {{ row.coefficient }}
            </td>
            <td
              class="px-3 py-2 align-top text-right text-sm font-semibold text-emerald-700"
            >
              {{ row.hours }}
            </td>
            <td class="px-3 py-2 align-top text-xs text-slate-600">
              {{ row.note || "—" }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="text-[11px] text-slate-400">
      File PDF sẽ được tạo với bảng chi tiết giống như bảng ở trên, phục vụ in
      ấn hoặc lưu hồ sơ.
    </p>
  </div>
</template>

<script setup lang="ts">
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

interface BatchDetailRow {
  id: string;
  title: string;
  workTypeLabel: string;
  roleLabel: string;
  coefficient: number;
  hours: number;
  note?: string;
}

interface Props {
  batchCode: string;
  items: BatchDetailRow[];
}

const props = defineProps<Props>();

function exportToPdf() {
  const doc = new jsPDF();

  doc.setFontSize(13);
  doc.text(`Chi tiết giờ NCKH - Đợt ${props.batchCode}`, 14, 16);

  const body = props.items.map((item, index) => [
    index + 1,
    item.title,
    item.workTypeLabel,
    item.roleLabel,
    item.coefficient,
    item.hours,
    item.note ?? "",
  ]);

  autoTable(doc, {
    startY: 22,
    head: [
      ["#", "Công trình", "Loại", "Vai trò", "Hệ số", "Giờ quy đổi", "Ghi chú"],
    ],
    body,
    styles: {
      fontSize: 8,
    },
    headStyles: {
      fillColor: [241, 245, 249],
      textColor: [15, 23, 42],
    },
  });

  doc.save(`chi-tiet-gio-nckh-${props.batchCode}.pdf`);
}
</script>
