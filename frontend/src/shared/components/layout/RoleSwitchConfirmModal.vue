<template>
  <Teleport to="body">
    <transition
      enter-active-class="transition-opacity duration-150 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-120 flex items-center justify-center bg-slate-950/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="role-switch-title"
      >
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
          <h2
            id="role-switch-title"
            class="text-lg font-semibold text-slate-900"
          >
            Xác nhận chuyển vai trò
          </h2>

          <p class="mt-2 text-sm text-slate-600">
            Bạn sắp chuyển chế độ làm việc từ
            <span class="font-medium text-slate-900">{{
              currentRoleLabel
            }}</span>
            sang
            <span class="font-medium text-slate-900">{{ nextRoleLabel }}</span
            >.
          </p>

          <p class="mt-2 text-xs text-slate-500">
            Sau khi chuyển, menu và quyền thao tác sẽ được áp dụng theo vai trò
            mới.
          </p>

          <div class="mt-5 flex justify-end gap-2">
            <button
              type="button"
              class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              :disabled="loading"
              @click="$emit('cancel')"
            >
              Hủy
            </button>
            <button
              type="button"
              class="rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading"
              @click="$emit('confirm')"
            >
              {{ loading ? "Đang chuyển..." : "Xác nhận" }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { UserRole } from "@/app/stores/userStore";

const props = withDefaults(
  defineProps<{
    open: boolean;
    loading?: boolean;
    currentRole?: UserRole | null;
    nextRole?: UserRole | null;
  }>(),
  {
    loading: false,
    currentRole: null,
    nextRole: null,
  },
);

defineEmits<{
  (e: "confirm"): void;
  (e: "cancel"): void;
}>();

const roleLabelMap: Record<UserRole, string> = {
  LECTURER: "Giảng viên",
  DEPARTMENT_BOARD: "Ban chủ nhiệm khoa",
  SCIENCE_OFFICE: "Phòng quản lý khoa học",
};

const currentRoleLabel = computed(() => {
  if (!props.currentRole) return "chưa xác định";
  return roleLabelMap[props.currentRole] ?? props.currentRole;
});

const nextRoleLabel = computed(() => {
  if (!props.nextRole) return "chưa xác định";
  return roleLabelMap[props.nextRole] ?? props.nextRole;
});
</script>
