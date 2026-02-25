<template>
  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="flex items-start gap-3">
        <div class="mt-0.5 rounded-xl bg-white p-2 ring-1 ring-slate-200">
          <Lightbulb class="h-5 w-5 text-slate-700" />
        </div>

        <div class="min-w-0">
          <div class="text-sm font-semibold text-slate-900">Hướng dẫn duyệt giờ NCKH</div>
          <div class="mt-0.5 text-xs text-slate-600">
            Hệ thống tự tính giờ theo quy tắc. Chỉ công trình đã được khoa duyệt nội dung mới đủ điều kiện gửi duyệt giờ.
          </div>
        </div>
      </div>

      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
        @click="expanded = !expanded"
      >
        <span>{{ expanded ? "Thu gọn" : "Mở rộng" }}</span>
        <ChevronUp v-if="expanded" class="h-4 w-4" />
        <ChevronDown v-else class="h-4 w-4" />
      </button>
    </div>

    <div v-if="expanded" class="mt-4 rounded-xl bg-white p-4 ring-1 ring-slate-200">
      <ul class="space-y-2 text-sm text-slate-700">
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span><b>Bước 1:</b> Mở chi tiết công trình để kiểm tra giờ quy đổi tự động.</span>
        </li>
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span><b>Bước 2:</b> Kiểm tra mục <b>Công thức tính giờ</b> để biết tổng giờ công trình và giờ của bạn.</span>
        </li>
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span><b>Bước 3:</b> Tải tối thiểu 1 minh chứng PDF cho từng công trình.</span>
        </li>
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span><b>Bước 4:</b> Chọn công trình hợp lệ và bấm <b>Gửi duyệt giờ</b>.</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { ChevronDown, ChevronUp, Info, Lightbulb } from "lucide-vue-next";

const expanded = ref(true);
</script>
