<template>
  <!--
    Trang wrapper cho Ban Chủ nhiệm khoa (phạm vi theo khoa).
    Lý do tách: khoa được cố định theo tài khoản, UI không cần dropdown khoa.
  -->
  <ResearchWorkApprovalPage
    approvalScopeType="FACULTY"
    :fixedFacultyName="fixedFacultyName"
  />
</template>

<script setup lang="ts">
import { ref } from "vue";
import ResearchWorkApprovalPage from "./ResearchWorkApprovalPage.vue";

// Mock: sau này lấy từ thông tin đăng nhập.
const fixedFacultyName = ref<string>("Khoa Công nghệ Thông tin");
</script>
