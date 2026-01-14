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
  isi: number[];
  scopus: number[];
  conference: number[];
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);

const config = computed<ChartConfiguration>(() => ({
  type: "bar",
  data: {
    labels: props.labels,
    datasets: [
      {
        label: "ISI",
        data: props.isi,
        backgroundColor: "rgba(15, 23, 42, 0.25)",
        borderWidth: 0,
        stack: "stack-1",
      },
      {
        label: "Scopus",
        data: props.scopus,
        backgroundColor: "rgba(59, 130, 246, 0.22)",
        borderWidth: 0,
        stack: "stack-1",
      },
      {
        label: "Hội nghị",
        data: props.conference,
        backgroundColor: "rgba(100, 116, 139, 0.25)",
        borderWidth: 0,
        stack: "stack-1",
      },
    ],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: "bottom" },
      tooltip: { enabled: true },
    },
    scales: {
      x: { stacked: true, ticks: { maxRotation: 0, autoSkip: true } },
      y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
    },
  },
}));

useChartJs(canvasRef, () => config.value);
</script>
