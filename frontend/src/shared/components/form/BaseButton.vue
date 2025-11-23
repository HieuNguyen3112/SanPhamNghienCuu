<template>
  <button
    :type="type"
    class="inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold tracking-wide transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    :class="buttonClasses"
    :aria-label="ariaLabel"
    :disabled="disabled || loading"
  >
    <span
      v-if="loading"
      class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/60 border-t-transparent"
    ></span>
    <span><slot /></span>
  </button>
</template>

<script setup lang="ts">
import { computed } from "vue";

type ButtonVariant = "primary" | "secondary" | "ghost";

interface BaseButtonProps {
  type?: "button" | "submit" | "reset";
  variant?: ButtonVariant;
  fullWidth?: boolean;
  disabled?: boolean;
  loading?: boolean;
  ariaLabel?: string;
}

const props = withDefaults(defineProps<BaseButtonProps>(), {
  type: "button",
  variant: "primary",
  fullWidth: false,
  disabled: false,
  loading: false,
});

const buttonClasses = computed(() => {
  const base =
    "shadow-md hover:shadow-lg hover:scale-[1.01] focus-visible:ring-blue-600 focus-visible:ring-offset-transparent";

  const variants: Record<ButtonVariant, string> = {
    primary: "bg-blue-600 text-white hover:bg-blue-700",
    secondary:
      "border border-gray-300 bg-white/95 text-gray-700 hover:bg-white",
    ghost: "bg-transparent text-white hover:bg-white/10",
  };

  const width = props.fullWidth ? "w-full" : "w-auto";

  return `${base} ${variants[props.variant]} ${width}`;
});
</script>
