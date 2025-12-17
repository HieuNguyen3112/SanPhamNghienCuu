<!-- src/features/declarations/pages/DeclarationArticlesView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Kê khai bài báo khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Nhập thông tin bài báo, danh sách thành viên và các minh chứng liên
        quan.
      </p>
    </div>

    <!-- Card form -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <ArticleDeclarationForm @submit="handleSubmit" />
    </div>
  </div>
</template>

<script setup lang="ts">
import ArticleDeclarationForm from "@/features/declarations/components/form/ArticleDeclarationForm.vue";
import type {
  ArticleFormModel,
  Member,
  WorkEvidenceFile,
} from "@/features/declarations/types";

function handleSubmit(payload: {
  form: ArticleFormModel;
  members: Member[];
  evidences: WorkEvidenceFile[];
  baseHours: number;
  memberHours: number;
}) {
  // TODO: thay bằng gọi API thật (FormData nếu upload file)
  console.log("ARTICLE DECLARATION FULL PAYLOAD", payload);
  alert(
    "(Mock) Đã lưu kê khai bài báo + minh chứng. Xem payload trong console."
  );
}
</script>
