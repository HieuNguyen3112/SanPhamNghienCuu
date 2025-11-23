<template>
  <div
    :class="[
      'flex items-center justify-center rounded-full bg-linear-to-br from-sky-500 to-sky-700 font-semibold uppercase text-white',
      sizeClasses,
    ]"
  >
    <span>{{ initials }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  name: string;
  size?: "sm" | "md" | "lg";
}>();

const initials = computed(() => {
  const cleaned = props.name?.trim() ?? "";
  if (!cleaned) return "?";

  const parts = cleaned.split(" ").filter(Boolean);
  if (!parts.length) return "?";

  if (parts.length === 1) {
    // chỉ có 1 từ, lấy ký tự đầu tiên
    return parts[0]?.[0]?.toUpperCase() ?? "?";
  }

  const first = parts[0]?.[0]?.toUpperCase() ?? "";
  const last = parts[parts.length - 1]?.[0]?.toUpperCase() ?? "";
  return `${first}${last}` || "?";
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case "sm":
      return "h-8 w-8 text-xs";
    case "lg":
      return "h-12 w-12 text-lg";
    default:
      return "h-10 w-10 text-sm";
  }
});
</script>
