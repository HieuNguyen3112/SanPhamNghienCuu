<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import AuthLayout from "@/layouts/AuthLayout.vue";
import UniversityLogo from "@/features/auth/components/UniversityLogo.vue";
import { BaseButton, BaseInput } from "@/shared/form";
import { resetPassword } from "@/features/auth/api";

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

const route = useRoute();
const router = useRouter();

const password = ref("");
const passwordConfirmation = ref("");
const passwordError = ref<string | null>(null);
const passwordConfirmationError = ref<string | null>(null);
const requestError = ref("");
const successMessage = ref("");
const submitting = ref(false);

const token = computed(() => {
  const raw = route.query.token;
  return typeof raw === "string" ? raw.trim() : "";
});

const email = computed(() => {
  const raw = route.query.email;
  return typeof raw === "string" ? raw.trim() : "";
});

const hasValidLink = computed(() => token.value !== "" && email.value !== "");

function resolveResetPasswordErrorMessage(error: unknown): string {
  const err = error as AuthHttpError;
  const status = err?.response?.status ?? null;
  const code = err?.response?.data?.code ?? "";
  const message = err?.message ?? "";

  if (/Network Error|ECONN|timeout|Failed to fetch|CSRF_FAILED/i.test(message)) {
    return "Không thể kết nối hệ thống lúc này. Vui lòng kiểm tra mạng và thử lại.";
  }

  if (status === 422) {
    if (code === "INVALID_RESET_TOKEN") {
      return "Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.";
    }

    return "Vui lòng kiểm tra lại mật khẩu mới và thử lại.";
  }

  if (status !== null && status >= 500) {
    return "Không thể đặt lại mật khẩu lúc này. Vui lòng thử lại sau.";
  }

  return "Không thể đặt lại mật khẩu lúc này. Vui lòng thử lại sau.";
}

function validate(): boolean {
  passwordError.value = null;
  passwordConfirmationError.value = null;
  requestError.value = "";

  if (!hasValidLink.value) {
    requestError.value =
      "Liên kết đặt lại mật khẩu không hợp lệ. Vui lòng kiểm tra lại email hoặc yêu cầu liên kết mới.";
    return false;
  }

  if (!password.value) {
    passwordError.value = "Mật khẩu mới là bắt buộc.";
  } else if (password.value.length < 8) {
    passwordError.value = "Mật khẩu mới phải có ít nhất 8 ký tự.";
  }

  if (!passwordConfirmation.value) {
    passwordConfirmationError.value = "Vui lòng nhập lại mật khẩu mới.";
  } else if (passwordConfirmation.value !== password.value) {
    passwordConfirmationError.value = "Mật khẩu xác nhận không khớp.";
  }

  return !passwordError.value && !passwordConfirmationError.value;
}

async function onSubmit(): Promise<void> {
  if (submitting.value) return;
  if (!validate()) return;

  submitting.value = true;
  successMessage.value = "";

  try {
    const response = await resetPassword({
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });

    successMessage.value =
      response.data?.message ?? "Đặt lại mật khẩu thành công.";

    window.setTimeout(() => {
      router.replace({
        name: "login",
        query: { reset: "success" },
      });
    }, 1200);
  } catch (error) {
    requestError.value = resolveResetPasswordErrorMessage(error);
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <AuthLayout>
    <template #hero>
      <UniversityLogo />
    </template>

    <section
      class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl backdrop-blur-xl sm:p-8 lg:px-10 lg:py-8"
      aria-label="Reset password form"
    >
      <form class="space-y-6" novalidate @submit.prevent="onSubmit">
        <div class="space-y-2 text-center">
          <h2 class="text-2xl font-bold uppercase text-red-700">
            Đặt lại mật khẩu
          </h2>
          <p class="text-sm text-slate-600">
            Nhập mật khẩu mới cho tài khoản
            <span class="font-semibold text-slate-800">{{ email || "của bạn" }}</span>.
          </p>
        </div>

        <div
          v-if="successMessage"
          class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
        >
          {{ successMessage }} Đang chuyển về trang đăng nhập...
        </div>

        <div
          v-else-if="requestError"
          class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
        >
          {{ requestError }}
        </div>

        <BaseInput
          id="reset-password"
          v-model="password"
          label="Mật khẩu mới"
          type="password"
          autocomplete="new-password"
          placeholder="Nhập mật khẩu mới"
          :error="passwordError ?? undefined"
          required
          inputClass="bg-blue-50 border border-gray-300 rounded py-3"
        />

        <BaseInput
          id="reset-password-confirmation"
          v-model="passwordConfirmation"
          label="Xác nhận mật khẩu mới"
          type="password"
          autocomplete="new-password"
          placeholder="Nhập lại mật khẩu mới"
          :error="passwordConfirmationError ?? undefined"
          required
          inputClass="bg-blue-50 border border-gray-300 rounded py-3"
        />

        <div class="pt-2">
          <BaseButton
            type="submit"
            variant="primary"
            :full-width="true"
            :loading="submitting"
            :disabled="!hasValidLink"
            aria-label="Đặt lại mật khẩu"
            :style="{ backgroundColor: '#14365B' }"
            class="rounded shadow hover:bg-blue-800"
          >
            Đặt lại mật khẩu
          </BaseButton>
        </div>

        <p class="text-center text-sm text-gray-600">
          <RouterLink class="font-semibold text-blue-700 hover:underline" to="/login">
            Quay lại đăng nhập
          </RouterLink>
        </p>
      </form>
    </section>
  </AuthLayout>
</template>
