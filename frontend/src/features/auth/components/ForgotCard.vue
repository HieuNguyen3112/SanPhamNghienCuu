<template>
  <section
    class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl backdrop-blur-xl sm:p-8 lg:px-10 lg:py-8"
    aria-label="Forgot password form"
  >
    <form class="space-y-6" @submit.prevent="onSubmit" novalidate>
      <!-- Title -->
      <h2 class="mb-6 text-center text-2xl font-bold uppercase text-red-700">
        QUÊN MẬT KHẨU
      </h2>

      <!-- Username -->
      <BaseInput
        id="forgot-username"
        v-model="username"
        label="Tên đăng nhập"
        type="text"
        autocomplete="username"
        placeholder="Nhập tên đăng nhập"
        :error="usernameError ?? undefined"
        required
        inputClass="bg-blue-50 border border-gray-300 rounded py-3"
      />

      <!-- Email -->
      <BaseInput
        id="forgot-email"
        v-model="email"
        label="Địa chỉ email"
        type="email"
        autocomplete="email"
        placeholder="Nhập địa chỉ email"
        :error="emailError ?? undefined"
        required
        inputClass="bg-blue-50 border border-gray-300 rounded py-3"
      />

      <!-- Submit button -->
      <div class="pt-2">
        <BaseButton
          type="submit"
          variant="primary"
          :full-width="true"
          :loading="submitting"
          aria-label="Quên mật khẩu"
          :style="{ backgroundColor: '#14365B' }"
          class="rounded shadow hover:bg-blue-800"
        >
          Quên mật khẩu
        </BaseButton>
      </div>

      <!-- Back to login -->
      <p class="pt-4 text-center text-sm text-gray-600">
        Nhớ mật khẩu?
        <button
          type="button"
          class="font-semibold text-blue-700 hover:underline"
          @click="onBackToLogin"
        >
          Đăng nhập
        </button>
      </p>
    </form>
  </section>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { BaseInput, BaseButton } from "../../../shared/form/index";

const emit = defineEmits<{
  (e: "back-to-login"): void;
}>();

const username = ref("");
const email = ref("");
const usernameError = ref<string | null>(null);
const emailError = ref<string | null>(null);
const submitting = ref(false);

const validate = (): boolean => {
  usernameError.value = null;
  emailError.value = null;

  if (!username.value.trim()) {
    usernameError.value = "Tên đăng nhập là bắt buộc";
  }

  if (!email.value.trim()) {
    emailError.value = "Địa chỉ email là bắt buộc";
  }

  return !usernameError.value && !emailError.value;
};

const onSubmit = (): void => {
  if (!validate()) return;

  submitting.value = true;

  console.log("Gửi yêu cầu đặt lại mật khẩu", {
    username: username.value,
    email: email.value,
  });

  // TODO: gọi API thực tế ở đây nếu cần

  submitting.value = false;
};

const onBackToLogin = (): void => {
  emit("back-to-login");
};
</script>
