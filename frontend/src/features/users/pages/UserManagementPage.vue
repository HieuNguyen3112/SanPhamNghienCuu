<script setup lang="ts">
import { onMounted, ref, computed, watch } from "vue";
import UserTable from "@/features/users/components/UserTable.vue";
import UserForm from "@/features/users/components/UserForm.vue";
import UserEditModal from "@/features/users/components/UserEditModal.vue";
import UserRolesModal from "@/features/users/components/UserRolesModal.vue";
import SharedPaginationControls from "@/shared/components/SharedPaginationControls.vue";

import type {
  LecturerUser,
  CreateLecturerUserPayload,
  UpdateLecturerUserPayload,
  UpdateLecturerUserRolesPayload,
} from "@/features/users/types";

import {
  fetchLecturerUsers,
  createLecturerUser,
  deleteLecturerUser,
  toggleLecturerUserActive,
  updateLecturerUser,
  updateLecturerUserRoles,
} from "@/features/users/api";

type UserStatusFilter = "ALL" | "ACTIVE" | "INACTIVE";
type UserRoleFilter = "ALL" | "LECTURER" | "ADMIN";

const users = ref<LecturerUser[]>([]);
const isLoading = ref<boolean>(false);
const isSubmitting = ref<boolean>(false);

const search = ref<string>("");
const statusFilter = ref<UserStatusFilter>("ALL");
const roleFilter = ref<UserRoleFilter>("ALL");

// Modal tạo mới
const showCreateForm = ref<boolean>(false);

// Modal edit & phân quyền
const editingUser = ref<LecturerUser | null>(null);
const rolesUser = ref<LecturerUser | null>(null);

/** Filtered list */
const filteredUsers = computed<LecturerUser[]>(() => {
  let data = [...users.value];

  if (statusFilter.value === "ACTIVE") data = data.filter((u) => u.isActive);
  else if (statusFilter.value === "INACTIVE")
    data = data.filter((u) => !u.isActive);

  if (roleFilter.value === "LECTURER") {
    data = data.filter((u) => u.roles.includes("Giảng viên"));
  } else if (roleFilter.value === "ADMIN") {
    data = data.filter((u) => u.roles.includes("Quản trị viên"));
  }

  const q = search.value.trim().toLowerCase();
  if (q.length) {
    data = data.filter(
      (u) =>
        u.fullName.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q) ||
        u.username.toLowerCase().includes(q) ||
        u.department.toLowerCase().includes(q)
    );
  }

  return data;
});

/** ✅ Pagination (SharedPaginationControls) */
const pageSize = ref<number>(8);
const currentPageNumber = ref<number>(1);

const totalItemCount = computed<number>(() => filteredUsers.value.length);

const totalPageCount = computed<number>(() => {
  return Math.max(1, Math.ceil(totalItemCount.value / pageSize.value));
});

const paginatedUsers = computed<LecturerUser[]>(() => {
  const startIndex = (currentPageNumber.value - 1) * pageSize.value;
  return filteredUsers.value.slice(startIndex, startIndex + pageSize.value);
});

watch([totalItemCount, pageSize], () => {
  if (currentPageNumber.value > totalPageCount.value) {
    currentPageNumber.value = totalPageCount.value;
  }
  if (currentPageNumber.value < 1) currentPageNumber.value = 1;
});

watch(filteredUsers, () => {
  // đổi filter/search -> về trang 1
  currentPageNumber.value = 1;
});

/** API */
const loadUsers = async () => {
  try {
    isLoading.value = true;
    const res = await fetchLecturerUsers();
    users.value = res.data;
  } finally {
    isLoading.value = false;
  }
};

const handleReload = () => {
  currentPageNumber.value = 1;
};

const handleCreateUser = async (payload: CreateLecturerUserPayload) => {
  try {
    isSubmitting.value = true;
    await createLecturerUser(payload);
    await loadUsers();
    showCreateForm.value = false;
    currentPageNumber.value = 1;
  } finally {
    isSubmitting.value = false;
  }
};

const handleToggleActive = async (id: number) => {
  await toggleLecturerUserActive(id);
  await loadUsers();
};

const handleDeleteUser = async (id: number) => {
  const ok = window.confirm("Bạn có chắc chắn muốn xóa tài khoản này?");
  if (!ok) return;
  await deleteLecturerUser(id);
  await loadUsers();
};

const handleEditUser = (user: LecturerUser) => {
  editingUser.value = { ...user };
};

const handleSaveEditUser = async (payload: UpdateLecturerUserPayload) => {
  await updateLecturerUser(payload);
  await loadUsers();
  editingUser.value = null;
};

const handleManageRoles = (user: LecturerUser) => {
  rolesUser.value = { ...user };
};

const handleSaveRoles = async (payload: UpdateLecturerUserRolesPayload) => {
  await updateLecturerUserRoles(payload);
  await loadUsers();
  rolesUser.value = null;
};

const closeEditModal = () => {
  editingUser.value = null;
};

const closeRolesModal = () => {
  rolesUser.value = null;
};

onMounted(() => {
  loadUsers();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Quản lý tài khoản giảng viên
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Xem danh sách tài khoản giảng viên, thực hiện tạo mới, vô hiệu hóa, phân
        quyền và chỉnh sửa thông tin.
      </p>
    </div>

    <!-- Bộ lọc -->
    <div
      class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-end lg:justify-between"
    >
      <div class="flex flex-wrap gap-3">
        <div class="w-40">
          <label class="block text-xs font-medium text-slate-600">
            Trạng thái
          </label>
          <select
            v-model="statusFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="ACTIVE">Đang hoạt động</option>
            <option value="INACTIVE">Đã khóa</option>
          </select>
        </div>

        <div class="w-40">
          <label class="block text-xs font-medium text-slate-600">
            Vai trò
          </label>
          <select
            v-model="roleFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="LECTURER">Giảng viên</option>
            <option value="ADMIN">Quản trị viên</option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap items-end gap-2">
        <input
          v-model="search"
          type="text"
          placeholder="Tìm theo tên, email, tài khoản, đơn vị..."
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 lg:w-72"
          @keyup.enter="handleReload"
        />

        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
          @click="handleReload"
        >
          Lọc
        </button>

        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-sky-700"
          @click="showCreateForm = true"
        >
          + Thêm tài khoản
        </button>
      </div>
    </div>

    <!-- Bảng tài khoản -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="h-[75vh]">
        <UserTable
          :users="paginatedUsers"
          :loading="isLoading"
          maxHeightClass="max-h-[75vh]"
          @toggle-active="handleToggleActive"
          @delete="handleDeleteUser"
          @edit="handleEditUser"
          @manage-roles="handleManageRoles"
        />
      </div>

      <!-- Thanh phân trang -->
      <div
        v-if="totalItemCount > 0"
        class="border-t border-slate-100 px-4 py-2"
      >
        <SharedPaginationControls
          displayMode="PAGINATION_ONLY"
          :totalItemCount="totalItemCount"
          v-model:currentPageNumber="currentPageNumber"
          v-model:pageSize="pageSize"
          :showRecordSummary="true"
          recordSummaryMode="RANGE"
          recordSummaryUnitLabel="bản ghi"
          :pageSizeOptionList="[8, 12, 20]"
        />
      </div>
    </div>

    <!-- Modal tạo tài khoản -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div
        v-if="showCreateForm"
        class="fixed inset-0 z-40 flex items-center justify-center p-4"
      >
        <div
          class="absolute inset-0 bg-slate-900/40"
          @click="showCreateForm = false"
        ></div>

        <div
          class="relative w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl"
        >
          <UserForm
            :submitting="isSubmitting"
            @submit="handleCreateUser"
            @cancel="showCreateForm = false"
          />
        </div>
      </div>
    </transition>

    <!-- Modal chỉnh sửa -->
    <UserEditModal
      v-if="editingUser"
      :user="editingUser"
      @close="closeEditModal"
      @save="handleSaveEditUser"
    />

    <!-- Modal phân quyền -->
    <UserRolesModal
      v-if="rolesUser"
      :user="rolesUser"
      @close="closeRolesModal"
      @save-roles="handleSaveRoles"
    />
  </div>
</template>
