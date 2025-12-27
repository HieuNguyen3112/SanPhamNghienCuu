// File:
src/features/lecturer-hours-overview/components/HoursDistributionSection.vue
<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-center gap-2">
      <PieChart class="h-4 w-4 text-slate-700" />
      <div class="text-sm font-semibold text-slate-900">Phân bổ giờ NCKH</div>
    </div>

    <div
      v-if="loading"
      class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
    >
      Đang tải phân bổ…
    </div>

    <div
      v-else-if="error"
      class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div v-else class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
      <!-- Donut -->
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div v-if="distribution.length === 0" class="text-sm text-slate-600">
          Chưa có dữ liệu phân bổ.
        </div>

        <div v-else class="flex items-center justify-center">
          <svg viewBox="0 0 120 120" class="h-60 w-60">
            <!-- background ring -->
            <circle
              cx="60"
              cy="60"
              r="42"
              fill="none"
              class="stroke-slate-200"
              stroke-width="16"
            />
            <!-- slices -->
            <g transform="rotate(-90 60 60)">
              <circle
                v-for="slice in donutSlices"
                :key="slice.key"
                cx="60"
                cy="60"
                r="42"
                fill="none"
                stroke-linecap="butt"
                stroke-width="16"
                :class="slice.strokeClass"
                :stroke-dasharray="slice.dashArray"
                :stroke-dashoffset="slice.dashOffset"
              />
            </g>

            <!-- center text -->
            <text
              x="60"
              y="56"
              text-anchor="middle"
              class="fill-slate-900"
              style="font-size: 10px; font-weight: 600"
            >
              Đã được tính
            </text>
            <text
              x="60"
              y="72"
              text-anchor="middle"
              class="fill-slate-900"
              style="font-size: 16px; font-weight: 700"
            >
              {{ approvedHoursText }}
            </text>
          </svg>
        </div>
      </div>

      <!-- Quick list -->
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="text-xs font-semibold text-slate-700">Thống kê nhanh</div>

        <div
          v-if="distribution.length === 0"
          class="mt-3 text-sm text-slate-600"
        >
          Chưa có dữ liệu.
        </div>

        <div v-else class="mt-3 space-y-2">
          <div
            v-for="(item, index) in distribution"
            :key="item.kindId"
            class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2"
          >
            <div class="flex items-center gap-2 min-w-0">
              <span class="h-2.5 w-2.5 rounded-full" :class="dotClass(index)" />
              <div class="truncate text-sm font-medium text-slate-900">
                {{ item.label }}
              </div>
            </div>

            <div class="shrink-0 text-right">
              <div class="text-sm font-semibold text-slate-900">
                {{ item.hours }} giờ
              </div>
              <div class="text-xs text-slate-500">{{ item.percentage }}%</div>
            </div>
          </div>

          <div class="mt-2 text-xs text-slate-500">
            * Phân bổ dựa trên giờ đã được tính (approved).
          </div>
        </div>
      </div>
    </div>

    <div v-if="!overview" class="mt-3 text-xs text-slate-500">
      TODO(BE): chọn năm học (hiện đang dùng năm học active).
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { PieChart } from "lucide-vue-next";
import type {
  HoursDistributionItem,
  HoursOverview,
} from "../contracts/HoursOverviewContracts";

const props = defineProps<{
  overview: HoursOverview | null;
  distribution: HoursDistributionItem[];
  loading: boolean;
  error: string | null;
}>();

const approvedHoursText = computed(() => {
  if (!props.overview) return "—";
  return `${props.overview.approvedHours} giờ`;
});

const paletteStroke = [
  "stroke-emerald-500",
  "stroke-blue-500",
  "stroke-amber-500",
  "stroke-violet-500",
  "stroke-slate-500",
] as const;

const paletteDot = [
  "bg-emerald-500",
  "bg-blue-500",
  "bg-amber-500",
  "bg-violet-500",
  "bg-slate-500",
] as const;

function dotClass(index: number): string {
  return paletteDot[index % paletteDot.length]!;
}

const donutSlices = computed(() => {
  // circle circumference
  const r = 42;
  const circumference = 2 * Math.PI * r;

  let offsetAcc = 0;

  return props.distribution.map((item, index) => {
    const ratio = Math.max(0, Math.min(1, item.percentage / 100));
    const length = circumference * ratio;

    const dashArray = `${length} ${circumference - length}`;
    const dashOffset = String(-offsetAcc);

    offsetAcc += length;

    return {
      key: `${item.kindId}-${index}`,
      strokeClass: paletteStroke[index % paletteStroke.length],
      dashArray,
      dashOffset,
    };
  });
});
</script>
