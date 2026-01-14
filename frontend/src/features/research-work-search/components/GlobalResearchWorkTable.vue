<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
  >
    <div v-if="loading" class="p-4 text-sm text-slate-700">
      Đang tải danh sách...
    </div>

    <div v-else-if="error" class="p-4">
      <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
        <div class="text-sm font-medium text-rose-700">
          Không tải được dữ liệu
        </div>
        <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
          {{ error }}
        </div>
      </div>
    </div>

    <div v-else-if="rows.length === 0" class="p-6 text-center">
      <div class="text-sm font-medium text-slate-900">
        Không tìm thấy công trình nào phù hợp với điều kiện tra cứu.
      </div>
      <div class="mt-1 text-xs text-slate-500">
        Thử nới lỏng bộ lọc hoặc đổi từ khóa.
      </div>
    </div>

    <div v-else class="max-h-[560px] overflow-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th>Công trình</th>
            <th>Loại</th>
            <th>Giảng viên chính</th>
            <th>Đơn vị</th>
            <th class="text-right">Năm</th>
            <th class="text-center">Trạng thái</th>
            <th class="w-12 text-right"></th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="row in rows"
            :key="row.workId"
            class="cursor-pointer hover:bg-slate-50"
            @click="emit('open-detail', row.workId)"
          >
            <td class="px-3 py-2">
              <div class="flex items-start gap-2">
                <div
                  class="mt-0.5 rounded-lg bg-slate-100 p-1.5 text-slate-700"
                >
                  <component :is="typeIcon(row.typeKey)" class="h-4 w-4" />
                </div>

                <div class="min-w-0">
                  <div class="truncate font-semibold text-slate-900">
                    {{ row.title }}
                  </div>
                  <div class="mt-0.5 line-clamp-1 text-xs text-slate-500">
                    {{ row.subtitle }}
                  </div>
                </div>
              </div>
            </td>

            <td class="px-3 py-2">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                :class="typeBadgeClass(row.typeKey)"
              >
                {{ row.typeLabel }}
              </span>
            </td>

            <td class="px-3 py-2">
              <div class="flex items-center gap-2 text-slate-800">
                <User class="h-4 w-4 text-slate-400" />
                <div class="min-w-0">
                  <div class="truncate font-medium text-slate-900">
                    {{ row.mainLecturerName }}
                  </div>
                  <div class="mt-0.5 text-xs text-slate-500">
                    {{ row.mainLecturerCode }}
                  </div>
                </div>
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">
              {{ row.unitName || "Chưa rõ" }}
            </td>

            <td class="px-3 py-2 text-right font-semibold text-slate-900">
              {{ row.year ?? "-" }}
            </td>

            <td class="px-3 py-2 text-center">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                :class="statusPillClass(row.statusCode)"
              >
                {{ row.statusLabel }}
              </span>
            </td>

            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                title="Xem chi tiết"
                @click.stop="emit('open-detail', row.workId)"
              >
                <Eye class="h-4 w-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="!loading && !error && rows.length > 0 && pagination"
      class="border-t border-slate-200 px-4 py-3"
    >
      <SharedPaginationControls
        :total-item-count="pagination.total"
        :current-page-number="pagination.page"
        :page-size="pagination.perPage"
        display-mode="FULL"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="công trình"
        container-class-name="w-full"
        @update:currentPageNumber="emit('update:page', $event)"
        @update:pageSize="emit('update:pageSize', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import {
  BookOpen,
  Eye,
  FileText,
  Presentation,
  FlaskConical,
  User,
} from "lucide-vue-next";
import type {
  ResearchWorkSummary,
  ResearchWorkTypeKey,
} from "../contracts/globalResearchWorkSearch.contract";
import { statusPillClass, typeBadgeClass } from "../contracts/globalResearchWorkSearch.contract";

type PaginationMeta = {
  page: number;
  perPage: number;
  total: number;
  lastPage: number;
};

defineProps<{
  rows: ResearchWorkSummary[];
  loading: boolean;
  error: string | null;
  pagination: PaginationMeta | null;
}>();

const emit = defineEmits<{
  (e: "open-detail", workId: number): void;
  (e: "update:page", page: number): void;
  (e: "update:pageSize", pageSize: number): void;
}>();

function typeIcon(typeKey: ResearchWorkTypeKey | string) {
  if (typeKey === "paper") return FileText;
  if (typeKey === "project") return FlaskConical;
  if (typeKey === "book") return BookOpen;
  return Presentation;
}
</script>
