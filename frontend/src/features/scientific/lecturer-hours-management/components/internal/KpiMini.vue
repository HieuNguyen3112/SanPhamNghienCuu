<template>
  <div class="rounded-2xl border p-4" :class="containerClass">
    <div class="text-xs font-medium" :class="titleClass">{{ title }}</div>
    <div class="mt-1 text-lg font-semibold" :class="valueClass">
      {{ value }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

type Tone = "hit" | "near" | "miss" | "neutral";

interface Props {
  title: string;
  value: string;
  tone: Tone;
}

const props = defineProps<Props>();

const containerClass = computed(() => {
  if (props.tone === "hit") return "border-emerald-200 bg-emerald-50";
  if (props.tone === "near") return "border-amber-200 bg-amber-50";
  if (props.tone === "miss") return "border-rose-200 bg-rose-50";
  return "border-slate-200 bg-white";
});

const titleClass = computed(() => {
  if (props.tone === "hit") return "text-emerald-700";
  if (props.tone === "near") return "text-amber-700";
  if (props.tone === "miss") return "text-rose-700";
  return "text-slate-600";
});

const valueClass = computed(() => {
  if (props.tone === "hit") return "text-emerald-900";
  if (props.tone === "near") return "text-amber-900";
  if (props.tone === "miss") return "text-rose-900";
  return "text-slate-900";
});
</script>
