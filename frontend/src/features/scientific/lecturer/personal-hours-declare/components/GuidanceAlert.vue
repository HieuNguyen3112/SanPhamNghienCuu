<template>
  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex items-start justify-between gap-3">
      <div class="flex items-start gap-3">
        <div class="mt-0.5 rounded-xl bg-white p-2 ring-1 ring-slate-200">
          <Lightbulb class="h-5 w-5 text-slate-700" />
        </div>

        <div class="min-w-0">
          <div class="text-sm font-semibold text-slate-900">
            Hướng dẫn kê khai & gửi xét duyệt giờ NCKH
          </div>
          <div class="mt-0.5 text-xs text-slate-600">
            Chỉ công trình đã được duyệt nội dung mới được tính giờ.
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

    <div
      v-if="expanded"
      class="mt-4 rounded-xl bg-white p-4 ring-1 ring-slate-200"
    >
      <ul class="space-y-2 text-sm text-slate-700">
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span
            ><b>Bước 1:</b> Chọn các công trình đã được duyệt hợp lệ</span
          >
        </li>
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span
            ><b>Bước 2:</b> Kiểm tra số giờ NCKH tương ứng của từng công
            trình</span
          >
        </li>
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span
            ><b>Bước 3:</b> Gửi yêu cầu xét duyệt giờ NCKH lên Khoa</span
          >
        </li>
        <li class="flex gap-2">
          <Info class="mt-0.5 h-4 w-4 text-slate-600" />
          <span
            ><b>Lưu ý:</b> Chỉ công trình đã duyệt nội dung mới được tính giờ
            NCKH</span
          >
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
