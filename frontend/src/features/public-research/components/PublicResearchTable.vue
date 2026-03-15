<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="truncate text-sm font-semibold text-slate-900">Danh sách công trình</div>
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

              <div class="flex shrink-0 flex-col items-end gap-2">
                <div class="text-xs text-slate-400">
                  {{ row.lecturerCode }}
                </div>
              </div>
            </div>
          </li>

          <li
            v-if="items.length === 0 && loadingState !== 'loading'"
            class="px-4 py-10 text-center text-sm text-slate-500"
          >
            Không có kết quả phù hợp.
          </li>
        </ul>
      </div>
    </div>

    <SharedPaginationControls
      container-class-name="mt-4"
      :total-item-count="totalItems"
      :current-page-number="page"
      :page-size="pageSize"
      display-mode="FULL"
      :page-size-option-list="[10, 20, 50]"
      page-size-label="Dòng / trang"
      :show-record-summary="true"
      record-summary-mode="PAGE_COUNT"
      record-summary-unit-label="kết quả"
      @update:currentPageNumber="$emit('update-page', $event)"
      @update:pageSize="$emit('update-page-size', $event)"
    />
  </section>
</template>

<script setup lang="ts">
import type {
  LoadingState,
  PublicResearchItem,
} from "../models/publicResearchModels";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";

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

defineProps<PublicResearchTableProps>();
defineEmits<PublicResearchTableEmits>();

function workTypeLabel(type: PublicResearchItem["workType"]): string {
  if (type === "ARTICLE") return "Bài báo";
  if (type === "BOOK") return "Sách";
  if (type === "PROJECT") return "Đề tài";
  if (type === "CONFERENCE") return "Hội thảo";
  return "Khác";
}
</script>