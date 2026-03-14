<template>
  <section
    class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl backdrop-blur-xl sm:p-8 lg:px-10 lg:py-8"
    aria-label="Sign in form"
  >
    <form class="space-y-6" novalidate @submit.prevent="onSubmit">
      <h2 class="mb-6 text-center text-2xl font-bold uppercase text-red-700">
        ĐĂNG NHẬP
      </h2>

      <BaseInput
        id="username"
        v-model="emailModel"
        label="Tên đăng nhập"
        type="text"
        autocomplete="username"
        placeholder="Nhập tên đăng nhập"
        required
        inputClass="bg-blue-50 border border-gray-300 rounded py-3"
      />

      <BaseInput
        id="password"
        v-model="passwordModel"
        label="Mật khẩu"
        type="password"
        autocomplete="current-password"
        placeholder="Nhập mật khẩu"
        required
        inputClass="bg-blue-50 border border-gray-300 rounded py-3"
      />

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">
          Vai trò
        </label>
        <select
          v-model="roleModel"
          class="w-full rounded border px-3 py-2 text-sm"
        >
          <option value="LECTURER">Giảng viên</option>
          <option value="DEPARTMENT_BOARD">Ban chủ nhiệm khoa</option>
          <option value="SCIENCE_OFFICE">Phòng quản lý khoa học</option>
        </select>
      </div>

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

      <hr class="my-4 border-gray-200" />

      <button
        type="button"
        class="text-sm text-gray-600 hover:underline"
        aria-label="Quên mật khẩu?"
        @click="emit('forgot-password')"
      >
        Quên mật khẩu?
      </button>
    </form>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { BaseButton, BaseInput } from "@/shared/form";

interface LoginCardProps {
  email: string;
  password: string;
  role: string;
  submitting?: boolean;
}

const props = withDefaults(defineProps<LoginCardProps>(), {
  submitting: false,
});

const emit = defineEmits<{
  (e: "update:email", value: string): void;
  (e: "update:password", value: string): void;
  (e: "update:role", value: string): void;
  (e: "submit"): void;
  (e: "forgot-password"): void;
}>();

const emailModel = computed({
  get: () => props.email,
  set: (v) => emit("update:email", v),
});

const passwordModel = computed({
  get: () => props.password,
  set: (v) => emit("update:password", v),
});

const roleModel = computed({
  get: () => props.role,
  set: (v) => emit("update:role", v),
});

const onSubmit = () => {
  emit("submit");
};
</script>
