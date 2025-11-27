// src/features/declarations/pages/DeclarationArticlesView.vue
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Kê khai bài báo khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Nhập thông tin chi tiết về bài báo, tạp chí, chỉ số ISSN, thứ hạng,...
      </p>
    </div>

    <!-- Badge loại công trình -->
    <WorkTypeBadge label="Bài báo khoa học" type="ARTICLE" />

    <!-- Nội dung chính -->
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Form -->
      <div class="lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Thông tin bài báo
          </h2>

          <WorkForm mode="create" work-type="ARTICLE" />
        </div>
      </div>

      <!-- Tài liệu minh chứng -->
      <div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Tài liệu minh chứng
          </h2>

          <WorkEvidenceUpload work-type="ARTICLE" />
        </div>

        <p class="mt-3 text-xs text-slate-400">
          ⚠️ Vui lòng đính kèm bản scan toàn văn, trang bìa tạp chí và các minh
          chứng liên quan theo quy định của đơn vị.
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
