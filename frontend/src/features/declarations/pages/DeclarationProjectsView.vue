// src/features/declarations/pages/DeclarationProjectsView.vue
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Kê khai đề tài nghiên cứu khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Nhập thông tin đề tài: cấp quản lý, mã số, thời gian thực hiện, vai trò
        tham gia,...
      </p>
    </div>

    <!-- Badge loại công trình -->
    <WorkTypeBadge label="Đề tài NCKH" type="PROJECT" />

    <!-- Nội dung chính -->
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Form -->
      <div class="lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Thông tin đề tài
          </h2>

          <WorkForm mode="create" work-type="PROJECT" />
        </div>
      </div>

      <!-- Tài liệu minh chứng -->
      <div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Tài liệu minh chứng
          </h2>

          <WorkEvidenceUpload work-type="PROJECT" />
        </div>

        <p class="mt-3 text-xs text-slate-400">
          ⚠️ Bắt buộc đính kèm quyết định giao đề tài, biên bản nghiệm thu và
          các minh chứng liên quan.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import WorkForm from "@/features/declarations/components/WorkForm.vue";
import WorkEvidenceUpload from "@/features/declarations/components/WorkEvidenceUpload.vue";
import WorkTypeBadge from "@/features/declarations/components/WorkTypeBadge.vue";
</script>
