<template>
  <!--
    Trang wrapper cho Phòng Quản lý Khoa học (phạm vi toàn trường).
    Lý do tách: tránh if/else theo vai trò lan rộng trong 1 page duy nhất.
  -->
  <ResearchWorkApprovalPage approvalScopeType="UNIVERSITY" />
</template>

<script setup lang="ts">
import ResearchWorkApprovalPage from "./ResearchWorkApprovalPage.vue";
</script>
