<script setup lang="ts">
import { reactive, ref } from "vue";
import { useRouter } from "vue-router";

import AuthLayout from "@/layouts/AuthLayout.vue";
import LoginCard from "@/features/auth/components/LoginCard.vue";
import ForgotCard from "@/features/auth/components/ForgotCard.vue";
import UniversityLogo from "@/features/auth/components/UniversityLogo.vue";

import { useUserStore, type UserRole } from "../../../app/stores/userStore";

const router = useRouter();
const userStore = useUserStore();

const email = ref("");
const password = ref("");
const role = ref<UserRole>("LECTURER"); // 👈 role chọn trên form
const isForgot = ref(false);

const errors = reactive({
  email: null as string | null,
  password: null as string | null,
  role: null as string | null,
});

const submitting = ref(false);

const validate = () => {
  let ok = true;
  errors.email = errors.password = errors.role = null;

  if (!email.value) {
    errors.email = "Vui lòng nhập tên đăng nhập";
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
};

const handleSubmit = async () => {
  if (!validate()) return;

  submitting.value = true;

  try {
    // 1. Đăng nhập theo username/password
    userStore.login({
      username: email.value,
      password: password.value,
    });

    // 2. Gán role đang dùng theo dropdown
    try {
      userStore.setRole(role.value);
    } catch (err: any) {
      if (err.message === "ROLE_KHONG_HOP_LE") {
        errors.role =
          "Tài khoản này không có quyền với vai trò đã chọn. Vui lòng chọn lại.";
        return;
      }
      throw err;
    }

    // 3. OK → chuyển vào app
    await router.push("/");
  } catch (err: any) {
    if (err.message === "SAI_TAI_KHOAN") {
      errors.password = "Tên đăng nhập hoặc mật khẩu không chính xác";
    } else if (!errors.role) {
      errors.password = "Đăng nhập không thành công. Vui lòng thử lại.";
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
