<script setup lang="ts">
import { reactive } from "vue";
import type {
  LecturerUser,
  UpdateLecturerUserRolesPayload,
} from "@/features/users/types";

interface UserRolesModalProps {
  user: LecturerUser;
}

interface UserRolesModalEmits {
  (e: "close"): void;
  (e: "save-roles", payload: UpdateLecturerUserRolesPayload): void;
}

const props = defineProps<UserRolesModalProps>();
const emit = defineEmits<UserRolesModalEmits>();

interface RolesState {
  roles: string[];
}

const state = reactive<RolesState>({
  roles: [...props.user.roles],
});

// tùy role system thực tế, tạm thời mock 2 role
const roleOptions: string[] = ["Giảng viên", "Quản trị viên"];

const toggleRole = (role: string) => {
  if (state.roles.includes(role)) {
    state.roles = state.roles.filter((r) => r !== role);
  } else {
    state.roles.push(role);
  }
};

const handleSave = () => {
  const payload: UpdateLecturerUserRolesPayload = {
    id: props.user.id,
    roles: [...state.roles],
  };

  emit("save-roles", payload);
};

const handleClose = () => {
  emit("close");
};
</script>

<template>
  <div
    class="fixed inset-0 z-30 flex items-center justify-center bg-slate-900/40 px-4"
  >
    <div
      class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl ring-1 ring-slate-200"
    >
      <div class="mb-4 flex items-start justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            Phân quyền tài khoản
          </h2>
          <p class="mt-1 text-xs text-slate-500">
            Chọn các vai trò cho tài khoản:
            <span class="font-medium text-slate-700">
              {{ user.fullName }}
            </span>
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

      <div class="space-y-3">
        <div
          class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-xs text-slate-600"
        >
          <p>
            Các vai trò được dùng để xác định quyền truy cập chức năng trong hệ
            thống (ví dụ: quản lý kê khai, duyệt công trình, quản trị người
            dùng, ...).
          </p>
        </div>

        <div class="space-y-2 text-sm">
          <label
            v-for="role in roleOptions"
            :key="role"
            class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50"
          >
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                :checked="state.roles.includes(role)"
                @change="toggleRole(role)"
              />
              <span class="text-sm font-medium">{{ role }}</span>
            </div>
          </label>
        </div>
      </div>

      <div class="mt-4 flex items-center justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-4 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
          @click="handleClose"
        >
          Hủy
        </button>
        <button
          type="button"
          class="rounded-lg bg-sky-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-sky-700"
          @click="handleSave"
        >
          Lưu phân quyền
        </button>
      </div>
    </div>
  </div>
</template>
