<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div v-if="loading" class="p-4 text-sm text-slate-700">
      Đang tải danh sách…
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

    <div v-else-if="overview.length === 0" class="p-6 text-center">
      <div class="text-sm font-medium text-slate-900">Không có dữ liệu</div>
      <div class="mt-1 text-xs text-slate-500">
        Thử đổi bộ lọc hoặc năm học.
      </div>
    </div>

    <div v-else class="max-h-[520px] overflow-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th>Giảng viên</th>
            <th>Khoa</th>

            <!-- ✅ thay 3 cột số bằng 1 cột năng lượng -->
            <th class="text-center">Tiến độ giờ NCKH</th>

            <th class="text-center">Trạng thái</th>
            <th class="text-right">Thao tác</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="row in pagedRows"
            :key="row.lecturerId"
            class="hover:bg-slate-50"
          >
            <td class="px-3 py-2">
              <div class="font-medium text-slate-900">
                {{ row.lecturerFullName }}
              </div>
              <div class="mt-0.5 text-xs text-slate-500">
                {{ row.lecturerCode }}
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">
              {{ row.facultyName }}
            </td>

            <!-- ✅ energy bar only (không show số) -->
            <td class="px-3 py-2">
              <div class="mx-auto w-[220px]">
                <HoursEnergyBar
                  :hours-total="row.hoursTotal"
                  :required-hours="row.requiredHours"
                  :show-text="false"
                  :show-percent="false"
                  size="md"
                />
              </div>
            </td>

            <td class="px-3 py-2 text-center">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                :class="statusPillClass(row)"
              >
                {{ statusLabel(row) }}
              </span>
            </td>

            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
                @click="emit('view', row.lecturerId)"
              >
                Xem
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ✅ Pagination footer -->
    <div
      v-if="!loading && !error && overview.length > 0"
      class="border-t border-slate-200 px-4 py-3"
    >
      <PaginationControl
        :total-item-count="overview.length"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="giảng viên"
        container-class-name="w-full"
        @update:currentPageNumber="currentPageNumber = $event"
        @update:pageSize="pageSize = $event"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { LecturerHoursOverview } from "../lecturerHours.contract";

import PaginationControl from "@/shared/components/SharedPaginationControls.vue";
import HoursEnergyBar from "./HoursEnergyBar.vue";

interface Props {
  overview: LecturerHoursOverview[];
  loading: boolean;
  error: string | null;
}

interface Emits {
  (e: "view", lecturerId: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const currentPageNumber = ref(1);
const pageSize = ref(12);

watch(
  () => [props.overview.length, pageSize.value],
  () => {
    currentPageNumber.value = 1;
  }
);

const pagedRows = computed(() => {
  const startIndex = (currentPageNumber.value - 1) * pageSize.value;
  return props.overview.slice(startIndex, startIndex + pageSize.value);
});

function computeDifference(row: LecturerHoursOverview) {
  return row.hoursTotal - row.requiredHours;
}

function tone(row: LecturerHoursOverview) {
  const diff = computeDifference(row);
  if (diff >= 0) return "hit";
  if (diff >= -50) return "near";
  return "miss";
}

function statusLabel(row: LecturerHoursOverview) {
  return computeDifference(row) >= 0 ? "Đạt" : "Thiếu";
}

function statusPillClass(row: LecturerHoursOverview) {
  const t = tone(row);
  if (t === "hit") return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (t === "near") return "bg-amber-50 text-amber-700 ring-amber-200";
  return "bg-rose-50 text-rose-700 ring-rose-200";
}
</script>
