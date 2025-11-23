<template>
  <div class="relative min-h-screen w-full">
    <div
      class="absolute inset-0 bg-cover bg-center"
      :style="backgroundStyle"
      aria-hidden="true"
    ></div>

    <div
      class="absolute inset-0 bg-linear-to-r from-black/80 via-black/50 to-black/20"
      aria-hidden="true"
    ></div>

    <main
      class="relative z-10 flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-16"
    >
      <section
        class="flex w-full max-w-6xl flex-col-reverse gap-10 lg:flex-row lg:items-center lg:gap-16"
      >
        <div class="flex-1">
          <slot name="hero" />
        </div>

        <div class="flex-1 flex justify-center lg:justify-end">
          <slot />
        </div>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";

interface AuthLayoutProps {
  backgroundImageUrl?: string;
}

const props = withDefaults(defineProps<AuthLayoutProps>(), {
  backgroundImageUrl: "/TruongDHSP.jpg",
});

const backgroundStyle = computed<Record<string, string>>(() => ({
  backgroundImage: `url('${props.backgroundImageUrl}')`,
}));
</script>
