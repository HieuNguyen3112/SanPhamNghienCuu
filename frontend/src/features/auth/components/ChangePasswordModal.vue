<!-- src/features/auth/components/ChangePasswordModal.vue -->
<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div
        class="w-full max-w-xl rounded-md bg-white shadow-xl"
        role="dialog"
        aria-modal="true"
      >
        <!-- HEADER -->
        <div class="border-b border-slate-200 px-8 py-4">
          <h2
            class="text-center text-lg font-semibold uppercase tracking-wide text-[#234a74]"
          >
            Đổi mật khẩu
          </h2>
        </div>

        <!-- BODY -->
        <form class="px-8 py-6 space-y-5" @submit.prevent="handleSubmit">
          <!-- Mật khẩu cũ -->
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
              Mật khẩu cũ
            </label>
            <div
              class="relative"
              :class="errors.oldPassword ? 'text-red-600' : 'text-slate-700'"
            >
              <input
                :type="showOldPassword ? 'text' : 'password'"
                v-model="form.oldPassword"
                class="block w-full rounded border px-3 py-2 text-sm outline-none transition focus:ring-1"
                :class="
                  errors.oldPassword
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                    : 'border-slate-300 focus:border-[#234a74] focus:ring-[#234a74]'
                "
              />
              <button
                type="button"
                class="absolute inset-y-0 right-2 flex items-center text-slate-500 hover:text-slate-700"
                @click="showOldPassword = !showOldPassword"
              >
                <!-- icon con mắt đơn giản -->
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
            <p v-if="errors.oldPassword" class="mt-1 text-xs text-red-600">
              {{ errors.oldPassword }}
            </p>
          </div>

          <!-- Mật khẩu mới -->
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
              Mật khẩu mới
            </label>
            <div
              class="relative"
              :class="errors.newPassword ? 'text-red-600' : 'text-slate-700'"
            >
              <input
                :type="showNewPassword ? 'text' : 'password'"
                v-model="form.newPassword"
                class="block w-full rounded border px-3 py-2 text-sm outline-none transition focus:ring-1"
                :class="
                  errors.newPassword
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                    : 'border-slate-300 focus:border-[#234a74] focus:ring-[#234a74]'
                "
              />
              <button
                type="button"
                class="absolute inset-y-0 right-2 flex items-center text-slate-500 hover:text-slate-700"
                @click="showNewPassword = !showNewPassword"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
            <p v-if="errors.newPassword" class="mt-1 text-xs text-red-600">
              {{ errors.newPassword }}
            </p>
          </div>

          <!-- Nhập lại mật khẩu mới -->
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
              Nhập lại mật khẩu mới
            </label>
            <div
              class="relative text-slate-700"
              :class="
                errors.confirmPassword ? 'text-red-600' : 'text-slate-700'
              "
            >
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                v-model="form.confirmPassword"
                class="block w-full rounded border px-3 py-2 text-sm outline-none transition focus:ring-1"
                :class="
                  errors.confirmPassword
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                    : 'border-[#234a74] focus:border-[#234a74] focus:ring-[#234a74]'
                "
              />
              <button
                type="button"
                class="absolute inset-y-0 right-2 flex items-center text-slate-500 hover:text-slate-700"
                @click="showConfirmPassword = !showConfirmPassword"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
            <p v-if="errors.confirmPassword" class="mt-1 text-xs text-red-600">
              {{ errors.confirmPassword }}
            </p>
          </div>
        </form>

        <!-- FOOTER -->
        <div class="flex justify-end border-t border-slate-200 px-8 py-4">
          <button
            type="button"
            class="mr-3 rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50"
            @click="close"
          >
            Hủy
          </button>
          <button
            type="button"
            class="rounded bg-[#234a74] px-6 py-2 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
            @click="handleSubmit"
          >
            Đổi mật khẩu
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { reactive, ref, computed, watch } from "vue";

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
  (e: "submit", payload: { oldPassword: string; newPassword: string }): void;
}>();

const visible = computed({
  get: () => props.modelValue,
  set: (val: boolean) => emit("update:modelValue", val),
});

const form = reactive({
  oldPassword: "",
  newPassword: "",
  confirmPassword: "",
});

const errors = reactive({
  oldPassword: "",
  newPassword: "",
  confirmPassword: "",
});

const showOldPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const resetForm = () => {
  form.oldPassword = "";
  form.newPassword = "";
  form.confirmPassword = "";
  errors.oldPassword = "";
  errors.newPassword = "";
  errors.confirmPassword = "";
};

watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      resetForm();
    }
  }
);

const validate = () => {
  let ok = true;
  errors.oldPassword = "";
  errors.newPassword = "";
  errors.confirmPassword = "";

  if (!form.oldPassword) {
    errors.oldPassword = "Mật khẩu cũ là bắt buộc";
    ok = false;
  }
  if (!form.newPassword) {
    errors.newPassword = "Mật khẩu mới là bắt buộc";
    ok = false;
  } else if (form.newPassword.length < 6) {
    errors.newPassword = "Mật khẩu mới phải từ 6 ký tự trở lên";
    ok = false;
  }
  if (!form.confirmPassword) {
    errors.confirmPassword = "Vui lòng nhập lại mật khẩu mới";
    ok = false;
  } else if (form.confirmPassword !== form.newPassword) {
    errors.confirmPassword = "Mật khẩu nhập lại không khớp";
    ok = false;
  }

  return ok;
};

const handleSubmit = () => {
  if (!validate()) return;

  emit("submit", {
    oldPassword: form.oldPassword,
    newPassword: form.newPassword,
  });
};

const close = () => {
  visible.value = false;
};
</script>
