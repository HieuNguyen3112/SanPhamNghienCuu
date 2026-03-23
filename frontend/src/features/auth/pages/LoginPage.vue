<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";

import AuthLayout from "@/layouts/AuthLayout.vue";
import LoginCard from "@/features/auth/components/LoginCard.vue";
import ForgotCard from "@/features/auth/components/ForgotCard.vue";
import UniversityLogo from "@/features/auth/components/UniversityLogo.vue";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";
import { useUserStore, type UserRole } from "@/app/stores/userStore";
import {
  resolveLoginErrorMessage,
  resolveLoginValidationMessage,
} from "@/features/auth/utils/authFeedback";

const router = useRouter();
const userStore = useUserStore();
const { runWithFeedback } = useActionFeedback();
const { showErrorModal } = useActionResultModal();

const email = ref("");
const password = ref("");
const role = ref<UserRole>("LECTURER");
const isForgot = ref(false);
const submitting = ref(false);

function resolveRedirectTarget() {
  const redirectParam = router.currentRoute.value.query.redirect;
  const redirect = typeof redirectParam === "string" ? redirectParam : "";
  const isSafeRedirect =
    redirect.startsWith("/") &&
    !redirect.startsWith("//") &&
    !redirect.includes("://");

  if (isSafeRedirect && redirect && redirect !== "/login") {
    return redirect;
  }

  const nextRole = userStore.role ?? role.value;
  if (nextRole === "LECTURER") {
    return "/declarations/gateway";
  }

  if (nextRole === "DEPARTMENT_BOARD") {
    return "/works/facapprovals";
  }

  if (nextRole === "SCIENCE_OFFICE") {
    return "/works/uniapprovals";
  }

  return "/";
}

const handleSubmit = async () => {
  if (submitting.value) return;

  const validationMessage = resolveLoginValidationMessage({
    email: email.value,
    password: password.value,
    role: role.value,
  });
  if (validationMessage) {
    showErrorModal(validationMessage, "Thông tin đăng nhập chưa đầy đủ");
    return;
  }

  submitting.value = true;

  try {
    await runWithFeedback(
      async () => {
        await userStore.login({
          email: email.value,
          password: password.value,
          role: role.value,
        });
        await router.replace(resolveRedirectTarget());
      },
      {
        loading: {
          title: "Đang xác thực",
          message: "Đang đăng nhập vào hệ thống...",
        },
        success: {
          title: "Thành công",
          message: "Đăng nhập thành công.",
        },
        error: {
          title: "Đăng nhập thất bại",
          message: (error) => resolveLoginErrorMessage(error),
        },
      },
    );
  } catch {
    // Modal feedback da duoc hien thi trong runWithFeedback.
  } finally {
    submitting.value = false;
  }
};

const handleForgotPassword = () => {
  isForgot.value = true;
};

const handleBackToLogin = () => {
  isForgot.value = false;
};

const updateRoleFromChild = (value: string) => {
  role.value = value as UserRole;
};
</script>

<template>
  <AuthLayout>
    <template #hero>
      <UniversityLogo />
    </template>

    <LoginCard
      v-if="!isForgot"
      :email="email"
      :password="password"
      :role="role"
      :submitting="submitting"
      @update:email="(v) => (email = v)"
      @update:password="(v) => (password = v)"
      @update:role="updateRoleFromChild"
      @submit="handleSubmit"
      @forgot-password="handleForgotPassword"
    />

    <ForgotCard v-else @back-to-login="handleBackToLogin" />
  </AuthLayout>
</template>
