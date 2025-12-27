<script setup lang="ts">
import { reactive } from "vue";
import type { CreateLecturerUserPayload } from "../types";

interface UserFormProps {
  submitting: boolean;
}

interface UserFormEmits {
  (e: "submit", payload: CreateLecturerUserPayload): void;
  (e: "cancel"): void;
}

const props = defineProps<UserFormProps>();
const emit = defineEmits<UserFormEmits>();

interface UserFormState {
  fullName: string;
  email: string;
  username: string;
  department: string;
  password: string;
  roles: string[];
}

interface UserFormErrors {
  fullName?: string;
  email?: string;
  username?: string;
  department?: string;
  password?: string;
}

const state = reactive<UserFormState>({
  fullName: "",
  email: "",
  username: "",
  department: "",
  password: "",
  roles: ["Giảng viên"],
});

const errors = reactive<UserFormErrors>({});

const validate = (): boolean => {
  errors.fullName = !state.fullName.trim() ? "Họ và tên là bắt buộc." : "";
  errors.email = !state.email.trim()
    ? "Email là bắt buộc."
    : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(state.email)
    ? "Email không hợp lệ."
    : "";
  errors.username = !state.username.trim()
    ? "Tài khoản đăng nhập là bắt buộc."
    : "";
  errors.department = !state.department.trim()
    ? "Đơn vị công tác là bắt buộc."
    : "";
  errors.password = !state.password.trim()
    ? "Mật khẩu là bắt buộc."
    : state.password.length < 6
    ? "Mật khẩu phải có ít nhất 6 ký tự."
    : "";

  return (
    !errors.fullName &&
    !errors.email &&
    !errors.username &&
    !errors.department &&
    !errors.password
  );
};

const toggleRole = (role: string) => {
  if (state.roles.includes(role)) {
    state.roles = state.roles.filter((r) => r !== role);
  } else {
    state.roles.push(role);
  }
};

const handleSubmit = () => {
  if (!validate()) return;

  const payload: CreateLecturerUserPayload = {
    fullName: state.fullName.trim(),
    email: state.email.trim(),
    username: state.username.trim(),
    department: state.department.trim(),
    password: state.password,
    roles: state.roles,
  };

  emit("submit", payload);
};

const handleCancel = () => {
  emit("cancel");
};
</script>

<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 md:p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
      Thêm tài khoản giảng viên
    </h2>

    <form
      @submit.prevent="handleSubmit"
      class="grid grid-cols-1 md:grid-cols-2 gap-4"
    >
      <div class="col-span-1 md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Họ và tên <span class="text-red-500">*</span>
        </label>
        <input
          v-model="state.fullName"
          type="text"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          placeholder="VD: Nguyễn Văn A"
        />
        <p v-if="errors.fullName" class="mt-1 text-xs text-red-500">
          {{ errors.fullName }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Email <span class="text-red-500">*</span>
        </label>
        <input
          v-model="state.email"
          type="email"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          placeholder="VD: a.nguyen@university.edu.vn"
        />
        <p v-if="errors.email" class="mt-1 text-xs text-red-500">
          {{ errors.email }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Tài khoản đăng nhập <span class="text-red-500">*</span>
        </label>
        <input
          v-model="state.username"
          type="text"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          placeholder="VD: nguyenvana"
        />
        <p v-if="errors.username" class="mt-1 text-xs text-red-500">
          {{ errors.username }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Đơn vị công tác <span class="text-red-500">*</span>
        </label>
        <input
          v-model="state.department"
          type="text"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          placeholder="VD: Khoa Công nghệ thông tin"
        />
        <p v-if="errors.department" class="mt-1 text-xs text-red-500">
          {{ errors.department }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Mật khẩu mặc định <span class="text-red-500">*</span>
        </label>
        <input
          v-model="state.password"
          type="password"
          class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          placeholder="Tối thiểu 6 ký tự"
        />
        <p v-if="errors.password" class="mt-1 text-xs text-red-500">
          {{ errors.password }}
        </p>
      </div>

      <div class="col-span-1 md:col-span-2">
        <p class="block text-sm font-medium text-gray-700 mb-1">Vai trò</p>
        <div class="flex flex-wrap gap-3 text-sm">
          <label class="inline-flex items-center gap-2">
            <input
              type="checkbox"
              class="rounded border-gray-300"
              :checked="state.roles.includes('Giảng viên')"
              @change="toggleRole('Giảng viên')"
            />
            <span>Giảng viên</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input
              type="checkbox"
              class="rounded border-gray-300"
              :checked="state.roles.includes('Quản trị viên')"
              @change="toggleRole('Quản trị viên')"
            />
            <span>Ban chủ nhiệm </span>
          </label>
        </div>
      </div>

      <div class="col-span-1 md:col-span-2 flex justify-end gap-2 mt-2">
        <button
          type="button"
          class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
          @click="handleCancel"
        >
          Hủy
        </button>
        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:opacity-60"
          :disabled="submitting"
        >
          <span v-if="submitting">Đang lưu...</span>
          <span v-else>Lưu tài khoản</span>
        </button>
      </div>
    </form>
  </div>
</template>
