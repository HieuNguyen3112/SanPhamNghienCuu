// src/components/auth/LoginCard.vue
<template>
  <section
    class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl backdrop-blur-xl sm:p-8 lg:px-10 lg:py-8"
    aria-label="Sign in form"
  >
    <form class="space-y-6" @submit.prevent="onSubmit" novalidate>
      <!-- Title -->
      <h2 class="mb-6 text-center text-2xl font-bold uppercase text-red-700">
        ĐĂNG NHẬP
      </h2>

      <!-- Username -->
      <BaseInput
        id="username"
        v-model="emailModel"
        label="Tên đăng nhập"
        type="text"
        autocomplete="username"
        placeholder="Nhập tên đăng nhập"
        :error="emailError ?? undefined"
        required
        inputClass="bg-blue-50 border border-gray-300 rounded py-3"
      />

      <!-- Password -->
      <BaseInput
        id="password"
        v-model="passwordModel"
        label="Mật khẩu"
        type="password"
        autocomplete="current-password"
        placeholder="Nhập mật khẩu"
        :error="passwordError ?? undefined"
        required
        inputClass="bg-blue-50 border border-gray-300 rounded py-3"
      />

      <!-- Login button -->
      <div class="pt-2">
        <BaseButton
          type="submit"
          variant="primary"
          :full-width="true"
          :loading="submitting"
          aria-label="Đăng nhập"
          :style="{ backgroundColor: '#14365B' }"
          class="rounded shadow hover:bg-blue-800"
        >
          Đăng nhập
        </BaseButton>
      </div>

      <!-- Divider -->
      <hr class="my-4 border-gray-200" />

      <!-- Forgot password -->
      <button
        type="button"
        class="text-sm text-gray-600 hover:underline"
        aria-label="Quên mật khẩu?"
        @click="onForgotPassword"
      >
        Quên mật khẩu?
      </button>
    </form>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { BaseInput, BaseButton } from "../../../shared/components/form/index";

interface LoginCardProps {
  email: string;
  password: string;
  emailError?: string | null;
  passwordError?: string | null;
  submitting?: boolean;
}

const props = withDefaults(defineProps<LoginCardProps>(), {
  emailError: null,
  passwordError: null,
  submitting: false,
});

const emit = defineEmits<{
  (e: "update:email", value: string): void;
  (e: "update:password", value: string): void;
  (e: "submit"): void;
  (e: "google-sign-in"): void;
  (e: "forgot-password"): void;
  (e: "create-account"): void;
}>();

const emailModel = computed<string>({
  get: () => props.email,
  set: (value: string) => emit("update:email", value),
});

const passwordModel = computed<string>({
  get: () => props.password,
  set: (value: string) => emit("update:password", value),
});

const onSubmit = (): void => {
  emit("submit");
};

const onForgotPassword = (): void => {
  emit("forgot-password");
};
</script>
