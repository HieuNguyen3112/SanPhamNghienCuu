<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

    <div
      class="absolute left-1/2 top-1/2 w-[94vw] max-w-[640px] -translate-x-1/2 -translate-y-1/2"
    >
      <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
      >
        <div class="border-b border-slate-200 px-4 py-4 md:px-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold text-slate-900">
                {{ titleText }}
              </div>
              <div class="mt-1 text-xs text-slate-500">
                Không xóa tài khoản. Chỉ thay đổi trạng thái đăng nhập.
              </div>
            </div>
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
              @click="emit('close')"
              aria-label="Đóng"
              title="Đóng"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div class="p-4 md:p-5">
          <div v-if="!account" class="text-sm text-slate-700">
            Không có dữ liệu.
          </div>

          <div v-else class="space-y-4">
            <div
              class="rounded-2xl border p-4"
              :class="
                account.status === 'ACTIVE'
                  ? 'border-rose-200 bg-rose-50'
                  : 'border-emerald-200 bg-emerald-50'
              "
            >
              <div class="flex items-start gap-2">
                <UserX
                  class="mt-0.5 h-4 w-4"
                  :class="
                    account.status === 'ACTIVE'
                      ? 'text-rose-700'
                      : 'text-emerald-700'
                  "
                />
                <div
                  class="text-sm"
                  :class="
                    account.status === 'ACTIVE'
                      ? 'text-rose-900'
                      : 'text-emerald-900'
                  "
                >
                  <div class="font-semibold">{{ confirmText }}</div>
                  <div class="mt-1 text-xs opacity-90">
                    {{
                      account.status === "ACTIVE"
                        ? "Giảng viên sẽ không thể đăng nhập hệ thống."
                        : "Tài khoản sẽ có thể đăng nhập lại bình thường."
                    }}
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="text-sm font-semibold text-slate-900">
                {{ account.fullName }}
              </div>
              <div class="mt-1 text-xs text-slate-500">
                {{ account.lecturerCode }} • {{ account.email }}
              </div>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700"
                >Lý do (tuỳ chọn)</label
              >
              <textarea
                v-model="reason"
                rows="3"
                class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
                placeholder="Nhập lý do (nếu cần)..."
              />
            </div>

            <div
              v-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
            >
              {{ error }}
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
                :disabled="saving"
              >
                Hủy
              </button>

              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
                :class="
                  account.status === 'ACTIVE'
                    ? 'bg-rose-600 hover:bg-rose-700'
                    : 'bg-emerald-600 hover:bg-emerald-700'
                "
                :disabled="saving"
                @click="onConfirm"
              >
                <UserX class="h-4 w-4" />
                {{ confirmButtonText }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { UserX, X } from "lucide-vue-next";
import type {
  LecturerAccount,
  ToggleAccountStatusPayload,
} from "../contracts/lecturerAccountManagement.contract";

const props = defineProps<{
  open: boolean;
  account: LecturerAccount | null;
  saving: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "confirm", payload: ToggleAccountStatusPayload): void;
}>();

const reason = ref("");

watch(
  () => props.open,
  (v) => {
    if (v) reason.value = "";
  }
);

const titleText = computed(() => {
  if (!props.account) return "Cập nhật trạng thái tài khoản";
  return props.account.status === "ACTIVE"
    ? "Vô hiệu tài khoản"
    : "Kích hoạt lại tài khoản";
});

const confirmText = computed(() => {
  if (!props.account) return "";
  return props.account.status === "ACTIVE"
    ? "Bạn có chắc muốn vô hiệu tài khoản này?"
    : "Bạn có chắc muốn kích hoạt lại tài khoản này?";
});

const confirmButtonText = computed(() => {
  if (!props.account) return "Xác nhận";
  return props.account.status === "ACTIVE"
    ? "Xác nhận vô hiệu"
    : "Kích hoạt lại";
});

function onConfirm() {
  if (!props.account) return;

  const isActive = props.account.status !== "ACTIVE";
  emit("confirm", {
    id: props.account.id,
    is_active: isActive,
    reason: reason.value.trim() ? reason.value.trim() : null,
  });
}
</script>
