<!-- src/features/hours/pages/HoursConferencesView.vue (ví dụ) -->
<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Tính giờ tham gia Hội nghị / Hội thảo khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Hệ thống áp dụng: báo cáo × 40 giờ, tham dự 4 giờ/lần (tối đa 40 lần).
      </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <ConferenceHoursForm @submit="handleSubmit" />
    </div>
  </div>
</template>

<script setup lang="ts">
import ConferenceHoursForm from "@/features/declarations/components/form/ConferenceHoursForm.vue";

function handleSubmit(payload: any) {
  console.log("CONFERENCE HOURS PAYLOAD", payload);
  // TODO: gọi API lưu / gắn với hồ sơ tính giờ NCKH
}
</script>
