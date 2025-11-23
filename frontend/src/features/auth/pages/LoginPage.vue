<template>
  <AuthLayout>
    <template #hero>
      <UniversityLogo />
    </template>

    <LoginCard
      v-if="!isForgot"
      :email="email"
      :password="password"
      :email-error="errors.email"
      :password-error="errors.password"
      :submitting="submitting"
      @update:email="(value) => (email = value)"
      @update:password="(value) => (password = value)"
      @submit="handleSubmit"
      @google-sign-in="handleGoogleSignIn"
      @forgot-password="handleForgotPassword"
      @create-account="handleCreateAccount"
    />

    <ForgotCard v-else @back-to-login="handleBackToLogin" />
  </AuthLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from "vue";
import AuthLayout from "../../../layouts/AuthLayout.vue";
import UniversityLogo from "../components/UniversityLogo.vue";
import LoginCard from "../components/LoginCard.vue";
import ForgotCard from "../components/ForgotCard.vue";

const email = ref<string>("");
const password = ref<string>("");
const isForgot = ref(false);

interface LoginErrors {
  email: string | null;
  password: string | null;
}

const errors = reactive<LoginErrors>({
  email: null,
  password: null,
});

const submitting = ref(false);

const handleSubmit = async (): Promise<void> => {
  // TODO: validate + gọi API đăng nhập
};

const handleGoogleSignIn = (): void => {
  console.log("Google sign in");
};

const handleForgotPassword = (): void => {
  isForgot.value = true;
};

const handleBackToLogin = (): void => {
  isForgot.value = false;
};

const handleCreateAccount = (): void => {
  console.log("Create account clicked");
};
</script>
