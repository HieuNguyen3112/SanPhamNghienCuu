// src/features/declarations/pages/DeclarationOthersView.vue
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Kê khai công trình khoa học khác
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Kê khai báo cáo hội nghị, hội thảo, sản phẩm NCKH khác theo quy định của
        đơn vị.
      </p>
    </div>

    <!-- Badge loại công trình -->
    <WorkTypeBadge label="Công trình khác" type="OTHER" />

    <!-- Nội dung chính -->
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Form -->
      <div class="lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Thông tin công trình
          </h2>

          <WorkForm mode="create" work-type="OTHER" />
        </div>
      </div>

      <!-- Tài liệu minh chứng -->
      <div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Tài liệu minh chứng
          </h2>

          <WorkEvidenceUpload work-type="OTHER" />
        </div>

        <p class="mt-3 text-xs text-slate-400">
          ⚠️ Đính kèm chương trình hội nghị, giấy mời, chứng nhận tham gia hoặc
          các giấy tờ liên quan.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import WorkForm from "../components/WorkForm.vue";
import WorkEvidenceUpload from "../components/WorkEvidenceUpload.vue";
import WorkTypeBadge from "../components//WorkTypeBadge.vue";
</script>
