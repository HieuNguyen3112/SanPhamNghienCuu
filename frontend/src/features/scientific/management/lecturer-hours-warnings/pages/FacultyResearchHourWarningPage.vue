<template>
  <ResearchHourWarningManagementPage :service="service" />
</template>

<script setup lang="ts">
import ResearchHourWarningManagementPage from "./ResearchHourWarningManagementPage.vue";
import { createFacultyResearchHourWarningService } from "../services/researchHourWarningService";

const service = createFacultyResearchHourWarningService();
</script>
