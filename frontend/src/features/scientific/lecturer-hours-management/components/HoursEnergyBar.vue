<template>
  <div class="min-w-0">
    <!-- Header -->
    <div class="mb-1 flex items-center justify-between gap-3">
      <div class="min-w-0 text-sm text-slate-600">
        {{ formatInt(hoursTotal) }}/{{ formatInt(requiredHours) }}
        {{ unitLabel }}
      </div>

      <div class="shrink-0 text-sm font-semibold text-slate-900">
        {{ formatInt(hoursTotal) }} / {{ formatInt(requiredHours) }}
        <span class="text-slate-500"> ({{ percentDisplay }}%)</span>
      </div>
    </div>

    <!-- Bar -->
    <div
      class="h-3 w-full overflow-hidden rounded-full bg-slate-100"
      role="progressbar"
      :aria-valuenow="ariaNow"
      aria-valuemin="0"
      :aria-valuemax="ariaMax"
      :title="titleText"
    >
      <div
        class="h-full rounded-full transition-all duration-300"
        :class="fillClassName"
        :style="{ width: `${percentClamped}%` }"
      />
    </div>

    <!-- Footer note (default: còn thiếu / đã đạt) -->
    <div class="mt-1 text-xs" :class="noteClassName">
      <template v-if="noteText">
        {{ noteText }}
      </template>

      <template v-else>
        <span v-if="missingHours > 0">
          Còn thiếu: {{ formatInt(missingHours) }} {{ unitLabel }}
        </span>
        <span v-else class="font-semibold"> Đã đạt định mức </span>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

type Tone = "hit" | "good" | "warn" | "risk" | "bad";

interface Props {
  hoursTotal: number;
  requiredHours: number;

  /** default: "giờ" */
  unitLabel?: string;

  /** nếu muốn override footer (vd: "Chờ duyệt: 10 giờ") */
  noteText?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
  unitLabel: "giờ",
  noteText: null,
});

const percentRaw = computed(() => {
  if (!Number.isFinite(props.requiredHours) || props.requiredHours <= 0)
    return 0;
  return (props.hoursTotal / props.requiredHours) * 100;
});

const percentClamped = computed(() =>
  Math.max(0, Math.min(100, percentRaw.value))
);
const percentDisplay = computed(() =>
  Math.max(0, Math.round(percentRaw.value))
);

const missingHours = computed(() => {
  if (!Number.isFinite(props.requiredHours) || props.requiredHours <= 0)
    return 0;
  return Math.max(0, props.requiredHours - props.hoursTotal);
});

/**
 * ✅ Màu theo độ thiếu (theo % hoàn thành)
 * - >=100%: hit (xanh đậm)
 * - >=80% : good (xanh)
 * - >=60% : warn (vàng)
 * - >=40% : risk (cam)
 * - <40%  : bad (đỏ)
 *
 * (Bạn muốn ngưỡng khác thì đổi tại đây)
 */
const tone = computed<Tone>(() => {
  const p = percentRaw.value;
  if (p >= 100) return "hit";
  if (p >= 80) return "good";
  if (p >= 60) return "warn";
  if (p >= 40) return "risk";
  return "bad";
});

const fillClassName = computed(() => {
  if (tone.value === "hit") return "bg-emerald-700";
  if (tone.value === "good") return "bg-emerald-600";
  if (tone.value === "warn") return "bg-amber-500";
  if (tone.value === "risk") return "bg-orange-500";
  return "bg-rose-600";
});

const noteClassName = computed(() => {
  // nếu bạn truyền noteText kiểu "Chờ duyệt: ..." thì mình dùng màu xanh dương giống screenshot
  if (props.noteText) return "text-sky-600";

  if (missingHours.value <= 0) return "text-emerald-700";
  if (tone.value === "good") return "text-emerald-700";
  if (tone.value === "warn") return "text-amber-700";
  if (tone.value === "risk") return "text-orange-700";
  return "text-rose-700";
});

function formatInt(value: number) {
  return Number.isFinite(value) ? value.toFixed(0) : "0";
}

const titleText = computed(() => {
  const total = formatInt(props.hoursTotal);
  const req = formatInt(props.requiredHours);
  const miss = formatInt(missingHours.value);
  return miss === "0"
    ? `Tiến độ: ${total}/${req} (${percentDisplay.value}%) - Đã đạt`
    : `Tiến độ: ${total}/${req} (${percentDisplay.value}%) - Thiếu ${miss} ${props.unitLabel}`;
});

const ariaMax = computed(() => Math.max(1, Math.floor(props.requiredHours)));
const ariaNow = computed(() => Math.max(0, Math.floor(props.hoursTotal)));
</script>
