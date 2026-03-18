<template>
  <section
    class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl backdrop-blur-xl sm:p-8 lg:px-10 lg:py-8"
    aria-label="Forgot password form"
  >
    <form class="space-y-6" novalidate @submit.prevent="onSubmit">
      <div class="space-y-2 text-center">
        <h2 class="text-2xl font-bold uppercase text-red-700">
          Quên mật khẩu
        </h2>
        <p class="text-sm text-slate-600">
          Nhập email đăng nhập để nhận liên kết đặt lại mật khẩu.
        </p>
      </div>

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

      <div class="pt-2">
        <BaseButton
          type="submit"
          variant="primary"
          :full-width="true"
          :loading="submitting"
          aria-label="Gửi liên kết đặt lại mật khẩu"
          :style="{ backgroundColor: '#14365B' }"
          class="rounded shadow hover:bg-blue-800"
        >
          Gửi liên kết đặt lại mật khẩu
        </BaseButton>
      </div>

      <p class="pt-4 text-center text-sm text-gray-600">
        Đã nhớ mật khẩu?
        <button
          type="button"
          class="font-semibold text-blue-700 hover:underline"
          @click="emit('back-to-login')"
        >
          Đăng nhập
        </button>
      </p>
    </form>
  </section>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { BaseButton, BaseInput } from "../../../shared/form/index";
import { forgotPassword } from "@/features/auth/api";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";

const emit = defineEmits<{
  (e: "back-to-login"): void;
}>();

type AuthHttpError = {
  message?: string;
  response?: {
    status?: number;
    data?: {
      code?: string;
      message?: string;
    };
  };
};

const { runWithFeedback } = useActionFeedback();

const email = ref("");
const emailError = ref<string | null>(null);
const submitting = ref(false);

function resolveForgotPasswordErrorMessage(error: unknown): string {
  const err = error as AuthHttpError;
  const status = err?.response?.status ?? null;
  const code = err?.response?.data?.code ?? "";
  const message = err?.message ?? "";

  if (/Network Error|ECONN|timeout|Failed to fetch|CSRF_FAILED/i.test(message)) {
    return "Không thể kết nối hệ thống lúc này. Vui lòng kiểm tra mạng và thử lại.";
  }

  if (status === 422) {
    return "Vui lòng nhập đúng địa chỉ email để nhận liên kết đặt lại mật khẩu.";
  }

  if (status !== null && status >= 500) {
    if (code === "PASSWORD_RESET_EMAIL_DISPATCH_FAILED") {
      return "Hệ thống chưa thể gửi email đặt lại mật khẩu lúc này. Vui lòng thử lại sau.";
    }

    return "Không thể gửi yêu cầu đặt lại mật khẩu lúc này. Vui lòng thử lại sau.";
  }

  return "Không thể gửi yêu cầu đặt lại mật khẩu lúc này. Vui lòng thử lại sau.";
}

function validate(): boolean {
  emailError.value = null;

  const normalizedEmail = email.value.trim();
  if (!normalizedEmail) {
    emailError.value = "Địa chỉ email là bắt buộc.";
    return false;
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailPattern.test(normalizedEmail)) {
    emailError.value = "Vui lòng nhập đúng định dạng email.";
    return false;
  }

  return true;
}

async function onSubmit(): Promise<void> {
  if (submitting.value) return;
  if (!validate()) return;

  submitting.value = true;

  try {
    const normalizedEmail = email.value.trim().toLowerCase();

    await runWithFeedback(
      () => forgotPassword({ email: normalizedEmail }),
      {
        loading: {
          enabled: true,
          title: "Đang gửi liên kết",
          message: "Hệ thống đang xử lý yêu cầu đặt lại mật khẩu...",
          delayMs: 200,
          minShowMs: 250,
        },
        success: {
          enabled: true,
          title: "Kiểm tra email",
          message:
            "Nếu thông tin hợp lệ, hướng dẫn đặt lại mật khẩu đã được gửi tới email của bạn.",
        },
        error: {
          enabled: true,
          title: "Gửi yêu cầu thất bại",
          message: (error) => resolveForgotPasswordErrorMessage(error),
        },
      },
    );
  } catch {
    // Modal feedback đã được hiển thị bởi runWithFeedback.
  } finally {
    submitting.value = false;
  }
}
</script>
