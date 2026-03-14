<template>
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div v-if="loading" class="p-4 text-sm text-slate-700">Đang tải danh sách...</div>

    <div v-else-if="error" class="p-4">
      <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
        <div class="text-sm font-medium text-rose-700">Không tải được dữ liệu</div>
        <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
          {{ error }}
        </div>
      </div>
    </div>

    <div v-else-if="rows.length === 0" class="p-6 text-center">
      <div class="text-sm font-medium text-slate-900">Không còn công trình thiếu minh chứng</div>
      <div class="mt-1 text-xs text-slate-500">
        Tất cả công trình trong bộ lọc hiện tại đã có tệp minh chứng.
      </div>
    </div>

    <div v-else class="max-h-[560px] overflow-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th>Tên công trình</th>
            <th>Loại</th>
            <th>Vai trò</th>
            <th class="text-right">Giờ dự kiến</th>
            <th class="text-center">Số minh chứng</th>
            <th class="text-right">Hành động</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr v-for="row in rows" :key="row.activityId" class="hover:bg-slate-50">
            <td class="px-3 py-2">
              <div class="font-medium text-slate-900">{{ row.title }}</div>
              <div class="mt-0.5 text-xs text-slate-500">
                {{ row.activityCode }} • {{ row.academicYearCode }}
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">{{ row.kindName }}</td>
            <td class="px-3 py-2 text-slate-700">{{ row.memberRoleName }}</td>
            <td class="px-3 py-2 text-right font-semibold text-slate-900">
              {{ formatHours(row.effectiveHoursDisplay) }} giờ
            </td>
            <td class="px-3 py-2 text-center text-slate-700">{{ row.evidenceCount }}</td>

            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                @click="emit('open-detail', row.activityId)"
              >
                Mở chi tiết
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { ApprovedWorkRow } from "../contracts/selectHoursRequest.contract";
import { formatHours } from "../contracts/selectHoursRequest.contract";

defineProps<{
  rows: ApprovedWorkRow[];
  loading: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "open-detail", activityId: number): void;
}>();
</script>
