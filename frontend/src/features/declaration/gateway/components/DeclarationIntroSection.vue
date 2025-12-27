<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-start gap-3">
      <div class="mt-0.5 rounded-xl bg-slate-100 p-2 text-slate-700">
        <Lightbulb class="h-5 w-5" />
      </div>

      <div class="min-w-0">
        <div class="text-base font-semibold text-slate-900 md:text-lg">
          Kê khai công trình nghiên cứu khoa học
        </div>
        <div class="mt-1 text-sm text-slate-600">
          Chọn loại công trình phù hợp để bắt đầu kê khai. Bạn có thể lưu bản
          nháp và hoàn tất sau.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Lightbulb } from "lucide-vue-next";
</script>
