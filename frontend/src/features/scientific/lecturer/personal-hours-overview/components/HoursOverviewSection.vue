// File:
src/features/lecturer-hours-overview/components/HoursOverviewSection.vue
<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-center gap-2">
      <div class="text-sm font-semibold text-slate-900">Tổng quan</div>
      <span v-if="overview" class="text-xs text-slate-400"
        >• {{ overview.academicYearCode }}</span
      >
    </div>

    <div
      v-if="loading"
      class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
    >
      Đang tải tổng quan…
    </div>

    <div
      v-else-if="error"
      class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div v-else-if="!overview" class="mt-4 text-sm text-slate-600">
      Không có dữ liệu tổng quan.
    </div>

    <div v-else class="mt-4 space-y-4">
      <!-- Progress -->
      <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="text-sm font-semibold text-slate-900">
              Giờ đã được tính: {{ overview.approvedHours }} /
              {{ overview.targetHours }} ({{ progressPercentText }}%)
            </div>
            <div class="mt-1 text-xs text-slate-600">
              Trong đó có
              <span class="font-semibold">{{ overview.pendingHours }}</span> giờ
              đang chờ duyệt
            </div>
          </div>

          <div class="shrink-0 text-right text-xs text-slate-500">
            <div>Định mức năm học</div>
            <div class="mt-0.5 font-semibold text-slate-900">
              {{ overview.targetHours }} giờ
            </div>
          </div>
        </div>

        <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-slate-200">
          <div
            class="h-full rounded-full bg-emerald-500 transition-[width] duration-300"
            :style="{ width: progressBarWidth }"
            aria-hidden="true"
          />
        </div>
      </div>

      <!-- Stat cards -->
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div
            class="flex items-center gap-2 text-xs font-semibold text-slate-600"
          >
            <Clock class="h-4 w-4" />
            Định mức
          </div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">
            {{ overview.targetHours }}
          </div>
          <div class="mt-1 text-xs text-slate-500">giờ / năm học</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div
            class="flex items-center gap-2 text-xs font-semibold text-slate-600"
          >
            <CheckCircle2 class="h-4 w-4 text-emerald-600" />
            Đã được tính
          </div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">
            {{ overview.approvedHours }}
          </div>
          <div class="mt-1 text-xs text-slate-500">giờ</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div
            class="flex items-center gap-2 text-xs font-semibold text-slate-600"
          >
            <CircleDot class="h-4 w-4 text-amber-600" />
            Chờ duyệt
          </div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">
            {{ overview.pendingHours }}
          </div>
          <div class="mt-1 text-xs text-slate-500">giờ</div>
        </div>

        <div
          v-if="overview.rejectedHours > 0"
          class="rounded-xl border border-slate-200 bg-white p-4"
        >
          <div
            class="flex items-center gap-2 text-xs font-semibold text-slate-600"
          >
            <XCircle class="h-4 w-4 text-rose-600" />
            Bị từ chối
          </div>
          <div class="mt-2 text-2xl font-semibold text-slate-900">
            {{ overview.rejectedHours }}
          </div>
          <div class="mt-1 text-xs text-slate-500">giờ</div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { CheckCircle2, CircleDot, Clock, XCircle } from "lucide-vue-next";
import type { HoursOverview } from "../contracts/HoursOverviewContracts";

const props = defineProps<{
  overview: HoursOverview | null;
  loading: boolean;
  error: string | null;
}>();

const progressPercent = computed(() => {
  const ov = props.overview;
  if (!ov || ov.targetHours <= 0) return 0;
  return Math.max(
    0,
    Math.min(100, Math.round((ov.approvedHours / ov.targetHours) * 100))
  );
});

const progressPercentText = computed(() => String(progressPercent.value));
const progressBarWidth = computed(() => `${progressPercent.value}%`);
</script>
