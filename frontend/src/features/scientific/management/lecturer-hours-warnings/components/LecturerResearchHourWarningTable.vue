<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
  >
    <div v-if="loading" class="p-4 text-sm text-slate-700">Đang tải...</div>

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
      <div class="text-sm font-medium text-slate-900">Không có dữ liệu</div>
      <div class="mt-1 text-xs text-slate-500">Thử đổi bộ lọc.</div>
    </div>

    <div v-else class="max-h-[560px] overflow-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th class="w-[220px]">Giảng viên</th>
            <th>Khoa</th>
            <th class="text-right">Hiện có</th>
            <th class="text-right">Cần</th>
            <th class="text-right">Cần thiếu</th>
            <th class="text-center">Mức thiếu</th>
            <th class="text-center">Cảnh báo</th>
            <th class="w-12 text-right"></th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="row in pagedRows"
            :key="row.lecturerIdentifier"
            class="cursor-pointer hover:bg-slate-50"
            @click="emit('open-detail', row)"
          >
            <td class="px-3 py-2">
              <div class="font-semibold text-slate-900">
                {{ row.lecturerFullName }}
              </div>
              <div class="mt-0.5 text-xs text-slate-500">
                {{ row.lecturerCode }}
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">{{ row.facultyShortName }}</td>

            <td class="px-3 py-2 text-right font-semibold text-slate-900">
              {{ formatHours(row.currentHours) }}
            </td>
            <td class="px-3 py-2 text-right text-slate-700">
              {{ formatHours(row.requiredHours) }}
            </td>
            <td class="px-3 py-2 text-right font-semibold text-slate-900">
              {{ formatHours(row.remainingHours) }}
            </td>

            <td class="px-3 py-2 text-center">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                :class="severityPillClass(row.severity)"
              >
                {{ severityLabel(row.severity) }}
              </span>
            </td>

            <td class="px-3 py-2 text-center">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                :class="warningRequestStatePillClass(row.hasRequestedWarning)"
              >
                {{ warningRequestStateLabel(row.hasRequestedWarning) }}
              </span>
              <div
                v-if="row.hasRequestedWarning"
                class="mt-0.5 text-[11px] text-slate-500"
              >
                {{ formatDateTimeVi(row.lastRequestedAt) }}
              </div>
            </td>

            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                title="Xem"
                @click.stop="emit('open-detail', row)"
              >
                <Eye class="h-4 w-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="!loading && !error && rows.length > 0"
      class="border-t border-slate-200 px-4 py-3"
    >
      <SharedPaginationControls
        :total-item-count="rows.length"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        record-summary-unit-label="giảng viên"
        @update:currentPageNumber="currentPageNumber = $event"
        @update:pageSize="pageSize = $event"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Eye } from "lucide-vue-next";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import type { LecturerResearchHourShortfallWarningEntry } from "../contracts/lecturerResearchHourWarning.contract";
import {
  formatDateTimeVi,
  formatHours,
  severityLabel,
  severityPillClass,
  warningRequestStateLabel,
  warningRequestStatePillClass,
} from "../contracts/lecturerResearchHourWarning.contract";

type Props = {
  rows: LecturerResearchHourShortfallWarningEntry[];
  loading: boolean;
  error: string | null;
};

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "open-detail", row: LecturerResearchHourShortfallWarningEntry): void;
}>();

const currentPageNumber = ref(1);
const pageSize = ref(10);

watch(
  () => [props.rows.length, pageSize.value],
  () => {
    currentPageNumber.value = 1;
  }
);

const pagedRows = computed(() => {
  const start = (currentPageNumber.value - 1) * pageSize.value;
  return props.rows.slice(start, start + pageSize.value);
});
</script>
