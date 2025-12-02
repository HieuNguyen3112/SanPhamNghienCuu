<!-- src/features/declarations/pages/DeclarationBooksView.vue -->
<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Kê khai biên soạn giáo trình / tài liệu tham khảo
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Hệ thống sẽ tính giờ NCKH theo quy định: giáo trình 900 giờ, tài liệu
        tham khảo 600 giờ.
      </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <BookHoursForm @submit="handleSubmit" />
    </div>
  </div>
</template>

<script setup lang="ts">
import BookHoursForm from "@/features/declarations/components/form/BookHoursForm.vue";

function handleSubmit(payload: any) {
  console.log("BOOK HOURS PAYLOAD", payload);
  // TODO: gọi API lưu kê khai + giờ NCKH
}
</script>
