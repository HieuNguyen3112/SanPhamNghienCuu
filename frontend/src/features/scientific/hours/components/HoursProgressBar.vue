<!-- src/features/hours/components/HoursProgressBar.vue -->
<template>
  <div class="space-y-1">
    <div class="flex items-center justify-between text-xs text-slate-600">
      <span>{{ label }}</span>
      <span>
        <span class="font-medium text-slate-800">
          {{ displayCompleted }} / {{ displayTarget }}
        </span>
        <span class="ml-1 text-slate-400"> ({{ percent }}%) </span>
      </span>
    </div>

    <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
      <div
        class="h-full rounded-full bg-emerald-500 transition-all"
        :style="{ width: percent + '%' }"
      />
    </div>

    <p v-if="helperText" class="text-[11px] text-slate-400">
      {{ helperText }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

interface Props {
  current: number;
  target: number;
  label?: string;
  helperText?: string;
}

const props = withDefaults(defineProps<Props>(), {
  label: "Tiến độ",
  helperText: "",
});

const safeTarget = computed(() => (props.target <= 0 ? 1 : props.target));

const percent = computed(() => {
  const raw = (props.current / safeTarget.value) * 100;
  if (raw < 0) return 0;
  if (raw > 150) return 150; // cho phép overflow một chút
  return Math.round(raw);
});

const displayCompleted = computed(() => Math.max(0, Math.round(props.current)));
const displayTarget = computed(() => Math.max(0, Math.round(props.target)));
</script>
