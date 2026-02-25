<template>
  <span
    :class="badgeClass"
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
  >
    <span class="h-1.5 w-1.5 rounded-full bg-current/80" />
    {{ label }}
  </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { DeclarationStatusUi } from "../contracts/declarationSharedContract";

const props = defineProps<{
  status: DeclarationStatusUi;
}>();

const label = computed(() => {
  switch (props.status) {
    case "DRAFT":
      return "Bản nháp";
    case "PENDING_MEMBER_CONFIRM":
      return "Chờ thành viên xác nhận";
    case "MEMBER_REJECTED":
      return "Thành viên từ chối";
    case "PENDING_FACULTY_REVIEW":
      return "Chờ khoa duyệt";
    case "SUBMITTED":
      return "Đã gửi duyệt";
    case "APPROVED":
      return "Được duyệt";
    case "REJECTED":
      return "Bị từ chối";
  }
});

const badgeClass = computed(() => {
  switch (props.status) {
    case "DRAFT":
      return "border border-slate-200 bg-slate-50 text-slate-700";
    case "PENDING_MEMBER_CONFIRM":
      return "border border-amber-200 bg-amber-50 text-amber-700";
    case "MEMBER_REJECTED":
      return "border border-rose-200 bg-rose-50 text-rose-700";
    case "PENDING_FACULTY_REVIEW":
      return "border border-blue-200 bg-blue-50 text-blue-700";
    case "SUBMITTED":
      return "border border-blue-200 bg-blue-50 text-blue-700";
    case "APPROVED":
      return "border border-emerald-200 bg-emerald-50 text-emerald-700";
    case "REJECTED":
      return "border border-rose-200 bg-rose-50 text-rose-700";
  }
});
</script>
