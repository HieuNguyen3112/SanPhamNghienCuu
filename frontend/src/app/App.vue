<template>
  <RouterView />
  <GlobalActionResultModal />
</template>

<script setup lang="ts">
import { RouterView } from "vue-router";
import GlobalActionResultModal from "@/shared/components/GlobalActionResultModal.vue";
</script>
