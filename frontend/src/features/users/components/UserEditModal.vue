<script setup lang="ts">
import { reactive } from "vue";
import type {
  LecturerUser,
  UpdateLecturerUserPayload,
} from "@/features/users/types";

interface UserEditModalProps {
  user: LecturerUser;
}

interface UserEditModalEmits {
  (e: "close"): void;
  (e: "save", payload: UpdateLecturerUserPayload): void;
}

const props = defineProps<UserEditModalProps>();
const emit = defineEmits<UserEditModalEmits>();

interface EditFormState {
  fullName: string;
  email: string;
  username: string;
  department: string;
}

interface EditFormErrors {
  fullName?: string;
  email?: string;
  username?: string;
  department?: string;
}

const state = reactive<EditFormState>({
  fullName: props.user.fullName,
  email: props.user.email,
  username: props.user.username,
  department: props.user.department,
});

const errors = reactive<EditFormErrors>({});

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

  return (
    !errors.fullName && !errors.email && !errors.username && !errors.department
  );
};

const handleSave = () => {
  if (!validate()) return;

  const payload: UpdateLecturerUserPayload = {
    id: props.user.id,
    fullName: state.fullName.trim(),
    email: state.email.trim(),
    username: state.username.trim(),
    department: state.department.trim(),
    roles: [...props.user.roles],
    isActive: props.user.isActive,
  };

  emit("save", payload);
};

const handleClose = () => {
  emit("close");
};
</script>

<template>
  <!-- overlay modal -->
  <div
    class="fixed inset-0 z-30 flex items-center justify-center bg-slate-900/40 px-4"
  >
    <div
      class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl ring-1 ring-slate-200"
    >
      <div class="mb-4 flex items-start justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            Chỉnh sửa thông tin giảng viên
          </h2>
          <p class="mt-1 text-xs text-slate-500">
            Cập nhật thông tin cơ bản của tài khoản giảng viên.
          </p>
        </div>
        <button
          type="button"
          class="ml-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"
          @click="handleClose"
        >
          ✕
        </button>
      </div>

      <form class="space-y-3" @submit.prevent="handleSave">
        <div>
          <label class="block text-xs font-medium text-slate-600">
            Họ và tên <span class="text-red-500">*</span>
          </label>
          <input
            v-model="state.fullName"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          />
          <p v-if="errors.fullName" class="mt-1 text-xs text-red-500">
            {{ errors.fullName }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-slate-600">
              Email <span class="text-red-500">*</span>
            </label>
            <input
              v-model="state.email"
              type="email"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            />
            <p v-if="errors.email" class="mt-1 text-xs text-red-500">
              {{ errors.email }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">
              Tài khoản đăng nhập <span class="text-red-500">*</span>
            </label>
            <input
              v-model="state.username"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            />
            <p v-if="errors.username" class="mt-1 text-xs text-red-500">
              {{ errors.username }}
            </p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">
            Đơn vị công tác <span class="text-red-500">*</span>
          </label>
          <input
            v-model="state.department"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          />
          <p v-if="errors.department" class="mt-1 text-xs text-red-500">
            {{ errors.department }}
          </p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-4 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
            @click="handleClose"
          >
            Hủy
          </button>
          <button
            type="submit"
            class="rounded-lg bg-sky-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-sky-700"
          >
            Lưu thay đổi
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
