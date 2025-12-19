<template>
  <div>
    <div class="grid gap-4 md:grid-cols-3" :class="{ 'opacity-60': loading }">
      <div
        class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm"
      >
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Số giảng viên được thống kê
        </p>
        <p class="mt-1 text-2xl font-semibold text-slate-800">
          {{ stats?.lecturerCount ?? 0 }}
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Các giảng viên có công trình nghiên cứu.
        </p>
      </div>

      <div
        class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm"
      >
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Tổng số công trình
        </p>
        <p class="mt-1 text-2xl font-semibold text-emerald-700">
          {{ stats?.totalProjectsCount ?? 0 }}
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Bao gồm tất cả giảng viên trong phạm vi đang xem.
        </p>
      </div>

      <div
        class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm"
      >
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Công trình chờ duyệt
        </p>
        <p class="mt-1 text-2xl font-semibold text-amber-700">
          {{ stats?.pendingProjectsCount ?? 0 }}
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Sẽ được cộng vào giờ NCKH khi được duyệt.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { OverviewStats } from "@/features/scientific/research-works/types";

interface Props {
  stats: OverviewStats | null;
  loading?: boolean;
}

defineProps<Props>();
</script>
