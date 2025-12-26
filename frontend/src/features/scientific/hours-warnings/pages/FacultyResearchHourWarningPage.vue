<template>
  <ResearchHourWarningManagementPage :service="service" />
</template>

<script setup lang="ts">
import ResearchHourWarningManagementPage from "./ResearchHourWarningManagementPage.vue";
import { createFacultyResearchHourWarningService } from "../services/researchHourWarningService";

// TODO: lấy facultyIdentifier từ auth/session (claims) thay vì hardcode
const service = createFacultyResearchHourWarningService({
  facultyIdentifierLocked: "FACULTY_IT",
});
</script>
