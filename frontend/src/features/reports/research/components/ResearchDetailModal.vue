<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/30" @click="$emit('close')"></div>

    <div
      class="absolute left-1/2 top-1/2 w-[min(720px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-xl"
    >
      <div
        class="flex items-start justify-between border-b border-slate-100 px-5 py-4"
      >
        <div>
          <h3 class="text-sm font-semibold text-slate-900">
            Chi tiết công trình
          </h3>
          <p class="mt-1 text-xs text-slate-500">
            Thông tin tóm tắt từ báo cáo nghiên cứu.
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg px-2 py-1 text-sm text-slate-500 hover:bg-slate-100"
          @click="$emit('close')"
        >
          Đóng
        </button>
      </div>

      <div class="px-5 py-4" v-if="work">
        <div class="space-y-3 text-sm">
          <div>
            <p class="text-xs font-medium text-slate-500">Tiêu đề</p>
            <p class="mt-1 font-medium text-slate-900">{{ work.title }}</p>
          </div>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <p class="text-xs font-medium text-slate-500">Loại</p>
              <p class="mt-1 text-slate-800">{{ work.categoryLabel }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Năm</p>
              <p class="mt-1 text-slate-800">{{ work.year ?? "-" }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Khoa / Đơn vị</p>
              <p class="mt-1 text-slate-800">{{ work.departmentName }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Mã công trình</p>
              <p class="mt-1 text-slate-800">
                {{ work.activityCode ?? "-" }}
              </p>
            </div>
          </div>

          <div>
            <p class="text-xs font-medium text-slate-500">
              Tạp chí / Hội nghị
            </p>
            <p class="mt-1 text-slate-800">{{ work.venueLabel }}</p>
          </div>

          <div>
            <p class="text-xs font-medium text-slate-500">Giảng viên</p>
            <p class="mt-1 text-slate-800">{{ work.lecturerNames }}</p>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
          @click="$emit('close')"
        >
          Đóng
        </button>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">
import type { ResearchReportRow } from "../researchReportTypes";

defineProps<{
  open: boolean;
  work: ResearchReportRow | null;
}>();

defineEmits<{ (event: "close"): void }>();
</script>
