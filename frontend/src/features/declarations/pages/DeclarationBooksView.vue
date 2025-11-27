// src/features/declarations/pages/DeclarationBooksView.vue
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Kê khai sách / giáo trình
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Nhập thông tin sách, giáo trình, nhà xuất bản, số ISBN, số trang, vai
        trò biên soạn,...
      </p>
    </div>

    <!-- Badge loại công trình -->
    <WorkTypeBadge label="Sách / Giáo trình" type="BOOK" />

    <!-- Nội dung chính -->
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Form -->
      <div class="lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Thông tin sách / giáo trình
          </h2>

          <WorkForm mode="create" work-type="BOOK" />
        </div>
      </div>

      <!-- Tài liệu minh chứng -->
      <div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="mb-4 text-base font-medium text-slate-700">
            Tài liệu minh chứng
          </h2>

          <WorkEvidenceUpload work-type="BOOK" />
        </div>

        <p class="mt-3 text-xs text-slate-400">
          ⚠️ Ưu tiên bản scan bìa sách, trang thông tin xuất bản, mục lục và các
          trang có tên tác giả.
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
