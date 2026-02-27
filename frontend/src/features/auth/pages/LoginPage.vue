<script setup lang="ts">
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";

import AuthLayout from "@/layouts/AuthLayout.vue";
import LoginCard from "@/features/auth/components/LoginCard.vue";
import ForgotCard from "@/features/auth/components/ForgotCard.vue";
import UniversityLogo from "@/features/auth/components/UniversityLogo.vue";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import { useUserStore, type UserRole } from "@/app/stores/userStore";

const router = useRouter();
const userStore = useUserStore();
const { runWithFeedback } = useActionFeedback();

const email = ref("");
const password = ref("");
const role = ref<UserRole>("LECTURER");
const isForgot = ref(false);

const errors = reactive({
  email: null as string | null,
  password: null as string | null,
  role: null as string | null,
});

const submitting = ref(false);

function validate() {
  let ok = true;
  errors.email = errors.password = errors.role = null;

  if (!email.value) {
    errors.email = "Vui lòng nhập tài khoản";
    ok = false;
  }

  if (!password.value) {
    errors.password = "Vui lòng nhập mật khẩu";
    ok = false;
  }

  if (!role.value) {
    errors.role = "Vui lòng chọn vai trò";
    ok = false;
  }

  return ok;
}

function resolveLoginErrorMessage(error: unknown) {
  const err = error as {
    message?: string;
    response?: { status?: number; data?: { message?: string } };
  };

  if (err?.message === "CSRF_FAILED") {
    return "Không thể kết nối máy chủ. Vui lòng thử lại.";
  }
  if (err?.message === "ROLE_KHONG_HOP_LE") {
    return "Tài khoản này không có quyền với vai trò đã chọn. Vui lòng chọn lại.";
  }

  const backendMessage = err?.response?.data?.message;
  if (typeof backendMessage === "string" && backendMessage.trim()) {
    return backendMessage.trim();
  }

  const status = err?.response?.status;
  if (status === 422) {
    return "Dữ liệu đăng nhập không hợp lệ hoặc tài khoản/mật khẩu sai.";
  }
  if (status === 401) {
    return "Tài khoản hoặc mật khẩu không đúng. Vui lòng thử lại.";
  }
  if (status === 403) {
    return "Tài khoản chưa xác minh email hoặc không đủ quyền.";
  }

  return "Đăng nhập thất bại. Vui lòng kiểm tra lại tài khoản/mật khẩu.";
}

function resolveRedirectTarget() {
  const redirectParam = router.currentRoute.value.query.redirect;
  const redirect = typeof redirectParam === "string" ? redirectParam : "";
  const isSafeRedirect =
    redirect.startsWith("/") &&
    !redirect.startsWith("//") &&
    !redirect.includes("://");
  return isSafeRedirect && redirect && redirect !== "/login"
    ? redirect
    : "/profile";
}

const handleSubmit = async () => {
  if (submitting.value) return;
  if (!validate()) return;

  submitting.value = true;
  errors.password = null;

  try {
    await runWithFeedback(
      async () => {
        await userStore.login({
          email: email.value,
          password: password.value,
          role: role.value,
        });

        userStore.setRole(role.value);
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
      }
    );
  } catch (error) {
    const message = resolveLoginErrorMessage(error);
    if (!errors.role) {
      errors.password = message;
    }
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
      :email-error="errors.email"
      :password-error="errors.password"
      :role-error="errors.role"
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
