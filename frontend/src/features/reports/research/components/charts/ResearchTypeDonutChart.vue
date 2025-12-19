<template>
  <div class="h-[320px]">
    <canvas ref="canvasRef" class="h-full w-full"></canvas>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { ChartConfiguration } from "chart.js/auto";
import { useChartJs } from "../../useChartJs";

const props = defineProps<{
  labels: string[];
  values: number[];
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);

const config = computed<ChartConfiguration>(() => ({
  type: "doughnut",
  data: {
    labels: props.labels,
    datasets: [
      {
        data: props.values,
        backgroundColor: [
          "rgba(15, 23, 42, 0.25)",
          "rgba(59, 130, 246, 0.22)",
          "rgba(100, 116, 139, 0.25)",
          "rgba(34, 197, 94, 0.18)",
          "rgba(245, 158, 11, 0.18)",
        ],
        borderColor: "rgba(255,255,255,0.9)",
        borderWidth: 2,
        hoverOffset: 4,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: "bottom" } },
    cutout: "62%",
  },
}));

useChartJs(canvasRef, () => config.value);
</script>
