<!-- ví dụ: src/features/declarations/pages/DeclarationProjectsView.vue -->
<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Tính giờ đề tài KH&CN
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Nhập thông tin đề tài, danh sách thành viên và minh chứng để hệ thống
        tính số giờ NCKH.
      </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <ProjectHoursForm @submit="handleSubmit" />
    </div>
  </div>
</template>

<script setup lang="ts">
import ProjectHoursForm from "@/features/declarations/components/form/ProjectHoursForm.vue";

function handleSubmit(payload: any) {
  console.log("PROJECT HOURS PAYLOAD", payload);
  // TODO: gửi payload lên API, áp dụng công thức thật từ file Word
}
</script>
