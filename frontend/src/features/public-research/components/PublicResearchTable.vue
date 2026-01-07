<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="truncate text-sm font-semibold text-slate-900">Danh sách công trình</div>
        <div class="mt-1 text-xs text-slate-500">
          Hiển thị <span class="font-medium text-slate-700">{{ items.length }}</span> / {{ totalItems }} kết quả
        </div>
      </div>

      <div v-if="loadingState === 'loading'" class="text-xs text-slate-500">Đang tải…</div>
    </div>

    <div
      v-if="errorState"
      class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
    >
      {{ errorState }}
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
      <div class="max-h-[60vh] overflow-auto">
        <ul class="divide-y divide-slate-200">
          <li
            v-for="row in items"
            :key="row.id"
            class="group cursor-pointer px-4 py-4 hover:bg-slate-50"
            @click="$emit('row-click', row.id)"
          >
            <div class="flex items-start justify-between gap-4">
              <!-- LEFT -->
              <div class="min-w-0 flex-1">
                <div class="text-[13px] font-bold text-[#1d4ed8] group-hover:underline">
                  {{ row.title }}
                </div>

                <div class="mt-1 text-sm font-semibold text-slate-900">
                  {{ row.lecturerName }}
                </div>

                <div class="mt-1 text-xs text-slate-500">
                  <span class="font-medium text-slate-600">{{ row.facultyName }}</span>
                  <span class="mx-2 text-slate-300">•</span>
                  <span>{{ workTypeLabel(row.workType) }}</span>
                  <span class="mx-2 text-slate-300">•</span>
                  <span>{{ row.academicYearCode }}</span>
                </div>
              </div>

              <!-- RIGHT -->
              <div class="flex shrink-0 flex-col items-end gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                  @click.stop="onPdfClick(row)"
                >
                  PDF
                </button>

                <div class="text-xs text-slate-400">
                  {{ row.lecturerCode }}
                </div>
              </div>
            </div>
          </li>

          <li v-if="items.length === 0 && loadingState !== 'loading'" class="px-4 py-10 text-center text-sm text-slate-500">
            Không có kết quả phù hợp.
          </li>
        </ul>
      </div>
    </div>

    <!-- Pagination (giữ wiring như bạn đang dùng) -->
    <div class="mt-4 flex items-center justify-between gap-3">
      <div class="text-xs text-slate-500">
        Trang {{ page }} / {{ totalPages }}
      </div>

      <div class="flex items-center gap-2">
        <label class="text-xs text-slate-500">Hiển thị</label>
        <select
          class="h-9 rounded-md border border-slate-200 bg-white px-2 text-sm"
          :value="pageSize"
          @change="$emit('update-page-size', Number(($event.target as HTMLSelectElement).value))"
        >
          <option :value="10">10/trang</option>
          <option :value="20">20/trang</option>
          <option :value="50">50/trang</option>
        </select>

        <button
          type="button"
          class="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm hover:bg-slate-50 disabled:opacity-50"
          :disabled="page <= 1"
          @click="$emit('update-page', page - 1)"
        >
          Trước
        </button>

        <button
          type="button"
          class="h-9 rounded-md border border-slate-200 bg-white px-3 text-sm hover:bg-slate-50 disabled:opacity-50"
          :disabled="page >= totalPages"
          @click="$emit('update-page', page + 1)"
        >
          Sau
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { LoadingState, PublicResearchItem } from "../models/publicResearchModels";
import { computed } from "vue";

type PublicResearchTableProps = {
  items: PublicResearchItem[];
  totalItems: number;
  page: number;
  pageSize: number;
  loadingState: LoadingState;
  errorState: string | null;
};

type PublicResearchTableEmits = {
  (e: "row-click", researchId: number): void;
  (e: "update-page", page: number): void;
  (e: "update-page-size", pageSize: number): void;
};

const props = defineProps<PublicResearchTableProps>();
defineEmits<PublicResearchTableEmits>();

function workTypeLabel(type: PublicResearchItem["workType"]): string {
  if (type === "ARTICLE") return "Bài báo";
  if (type === "BOOK") return "Sách";
  if (type === "PROJECT") return "Đề tài";
  if (type === "CONFERENCE") return "Hội thảo";
  return "Khác";
}

const totalPages = computed(() => {
  const size = Math.max(1, props.pageSize);
  return Math.max(1, Math.ceil(props.totalItems / size));
});

function onPdfClick(_row: PublicResearchItem) {
  // Nếu bạn chưa có pdfUrl thì cứ để noop.
  // Sau này BE trả pdf_url thì mở new tab tại đây.
  // window.open(row.pdfUrl, "_blank")
  alert("Chưa có file PDF (mock). Khi nối BE, map pdf_url vào đây là mở được.");
}
</script>
