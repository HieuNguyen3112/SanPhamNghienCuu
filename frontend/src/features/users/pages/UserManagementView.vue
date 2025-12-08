<script setup lang="ts">
import { onMounted, ref, computed, watch } from "vue";
import UserTable from "@/features/users/components/UserTable.vue";
import UserForm from "@/features/users/components/UserForm.vue";
import UserEditModal from "@/features/users/components/UserEditModal.vue";
import UserRolesModal from "@/features/users/components/UserRolesModal.vue";

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

const totalUsers = computed(() => users.value.length);
const activeUsers = computed(
  () => users.value.filter((u) => u.isActive).length
);
const inactiveUsers = computed(
  () => users.value.filter((u) => !u.isActive).length
);

const filteredUsers = computed<LecturerUser[]>(() => {
  let data = [...users.value];

  if (statusFilter.value === "ACTIVE") {
    data = data.filter((u) => u.isActive);
  } else if (statusFilter.value === "INACTIVE") {
    data = data.filter((u) => !u.isActive);
  }

  if (roleFilter.value === "LECTURER") {
    data = data.filter((u) => u.roles.includes("Giảng viên"));
  } else if (roleFilter.value === "ADMIN") {
    data = data.filter((u) => u.roles.includes("Quản trị viên"));
  }

  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase();
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

/* 🔹 Phân trang client-side */
const PAGE_SIZE = 8;
const currentPage = ref(1);

const paginatedUsers = computed<LecturerUser[]>(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE;
  const end = start + PAGE_SIZE;
  return filteredUsers.value.slice(start, end);
});

const totalPages = computed(() =>
  filteredUsers.value.length
    ? Math.ceil(filteredUsers.value.length / PAGE_SIZE)
    : 1
);

const showingFrom = computed(() =>
  filteredUsers.value.length ? (currentPage.value - 1) * PAGE_SIZE + 1 : 0
);

const showingTo = computed(() =>
  Math.min(filteredUsers.value.length, currentPage.value * PAGE_SIZE)
);

// Khi filteredUsers thay đổi mà currentPage > totalPages -> kéo về trang cuối
watch(filteredUsers, () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = totalPages.value || 1;
  }
});

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
  // hiện tại filter đang làm client-side → chỉ cần reset về trang 1
  currentPage.value = 1;
};

const handleCreateUser = async (payload: CreateLecturerUserPayload) => {
  try {
    isSubmitting.value = true;
    await createLecturerUser(payload);
    await loadUsers();
    showCreateForm.value = false;
    currentPage.value = 1;
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

// mở modal edit
const handleEditUser = (user: LecturerUser) => {
  editingUser.value = { ...user };
};

// submit modal edit
const handleSaveEditUser = async (payload: UpdateLecturerUserPayload) => {
  await updateLecturerUser(payload);
  await loadUsers();
  editingUser.value = null;
};

// mở modal phân quyền
const handleManageRoles = (user: LecturerUser) => {
  rolesUser.value = { ...user };
};

// submit modal phân quyền
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

        <!-- mở modal tạo tài khoản -->
        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-sky-700"
          @click="showCreateForm = true"
        >
          + Thêm tài khoản
        </button>
      </div>
    </div>

    <!-- Tóm tắt -->
    <!-- <div
      class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="mb-0.5">
            Tổng số tài khoản:
            <span class="font-semibold text-slate-800">
              {{ totalUsers }}
            </span>
          </p>
          <p class="mb-0.5">
            Đang hoạt động:
            <span class="font-semibold text-emerald-700">
              {{ activeUsers }}
            </span>
            &nbsp;·&nbsp; Đã khóa:
            <span class="font-semibold text-rose-600">
              {{ inactiveUsers }}
            </span>
          </p>
          <p class="mt-0.5">
            Số tài khoản theo bộ lọc hiện tại:
            <span class="font-semibold text-slate-800">
              {{ filteredUsers.length }}
            </span>
          </p>
        </div>
      </div>
    </div> -->

    <!-- Bảng tài khoản (chiều cao cố định, scroll dọc + phân trang) -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="max-h-[70vh] overflow-y-auto">
        <UserTable
          :users="paginatedUsers"
          :loading="isLoading"
          @toggle-active="handleToggleActive"
          @delete="handleDeleteUser"
          @edit="handleEditUser"
          @manage-roles="handleManageRoles"
        />
      </div>

      <!-- Thanh phân trang -->
      <div
        v-if="filteredUsers.length"
        class="flex items-center justify-between border-t border-slate-100 px-4 py-2 text-xs text-slate-600"
      >
        <p>
          Hiển thị
          <span class="font-semibold text-slate-800">{{ showingFrom }}</span>
          –
          <span class="font-semibold text-slate-800">{{ showingTo }}</span>
          trong tổng số
          <span class="font-semibold text-slate-800">
            {{ filteredUsers.length }}
          </span>
          tài khoản
        </p>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="currentPage === 1"
            @click="currentPage--"
          >
            Trước
          </button>
          <span class="text-[11px]">
            Trang
            <span class="font-semibold text-slate-800">{{ currentPage }}</span>
            / {{ totalPages }}
          </span>
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="currentPage === totalPages"
            @click="currentPage++"
          >
            Sau
          </button>
        </div>
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
        <!-- Overlay -->
        <div
          class="absolute inset-0 bg-slate-900/40"
          @click="showCreateForm = false"
        ></div>

        <!-- Hộp modal -->
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

    <!-- Modal chỉnh sửa thông tin -->
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
