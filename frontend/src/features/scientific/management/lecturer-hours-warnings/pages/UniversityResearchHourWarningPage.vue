<template>
  <ResearchHourWarningManagementPage :service="service" />
</template>

<script setup lang="ts">
import ResearchHourWarningManagementPage from "./ResearchHourWarningManagementPage.vue";
import { createUniversityResearchHourWarningService } from "../services/researchHourWarningService";

const service = createUniversityResearchHourWarningService();
</script>
