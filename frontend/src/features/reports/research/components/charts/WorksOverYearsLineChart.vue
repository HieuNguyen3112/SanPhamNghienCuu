<template>
  <div class="h-[280px]">
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
  type: "line",
  data: {
    labels: props.labels,
    datasets: [
      {
        label: "Số công trình",
        data: props.values,
        borderColor: "rgba(15, 23, 42, 0.7)",
        backgroundColor: "rgba(15, 23, 42, 0.1)",
        tension: 0.35,
        fill: true,
        pointRadius: 3,
        pointHoverRadius: 4,
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: "bottom" } },
    scales: {
      y: { beginAtZero: true, ticks: { precision: 0 } },
    },
  },
}));

useChartJs(canvasRef, () => config.value);
</script>
