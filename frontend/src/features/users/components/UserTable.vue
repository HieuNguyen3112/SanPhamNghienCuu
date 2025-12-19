<script setup lang="ts">
import { ref } from "vue";
import type { LecturerUser } from "@/features/users/types";

interface UserTableProps {
  users: LecturerUser[];
  loading: boolean;

  /** Cho phép set chiều cao bảng từ ngoài (vd: "max-h-[70vh]" hoặc "max-h-[520px]") */
  maxHeightClass?: string;
}

interface UserTableEmits {
  (e: "toggle-active", id: number): void;
  (e: "delete", id: number): void;
  (e: "edit", user: LecturerUser): void;
  (e: "manage-roles", user: LecturerUser): void;
}

const props = withDefaults(defineProps<UserTableProps>(), {
  maxHeightClass: "max-h-[70vh]",
});

const emit = defineEmits<UserTableEmits>();

const openMenuUser = ref<LecturerUser | null>(null);
const menuTop = ref(0);
const menuLeft = ref(0);

const openMenu = (user: LecturerUser, event: MouseEvent) => {
  if (openMenuUser.value && openMenuUser.value.id === user.id) {
    closeMenu();
    return;
  }

  const target = event.currentTarget as HTMLElement | null;
  if (!target) return;

  const rect = target.getBoundingClientRect();
  const menuWidth = 256; // ~ w-56
  const gap = 8;

  let left = rect.right - menuWidth;
  if (left < 8) left = 8;

  menuTop.value = rect.bottom + gap;
  menuLeft.value = left;
  openMenuUser.value = user;
};

const closeMenu = () => {
  openMenuUser.value = null;
};

const onToggleActive = (user: LecturerUser) => {
  emit("toggle-active", user.id);
  closeMenu();
};

const onDelete = (user: LecturerUser) => {
  emit("delete", user.id);
  closeMenu();
};

const onEdit = (user: LecturerUser) => {
  emit("edit", user);
  closeMenu();
};

const onManageRoles = (user: LecturerUser) => {
  emit("manage-roles", user);
  closeMenu();
};
</script>

<template>
  <div class="w-full">
    <!-- ✅ Fixed height scroll container -->
    <div class="overflow-auto" :class="props.maxHeightClass">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead
          class="sticky top-0 z-10 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          <tr>
            <th class="px-3 py-2 text-left">Giảng viên</th>
            <th class="px-3 py-2 text-left">Email</th>
            <th class="px-3 py-2 text-left">Tài khoản</th>
            <th class="px-3 py-2 text-left">Đơn vị</th>
            <th class="px-3 py-2 text-left">Vai trò</th>
            <th class="px-3 py-2 text-left">Trạng thái</th>
            <th class="px-3 py-2 text-center">Thao tác</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 bg-white">
          <!-- Loading -->
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-6 text-center text-slate-500">
              Đang tải dữ liệu...
            </td>
          </tr>

          <!-- Empty -->
          <tr v-else-if="!users.length">
            <td colspan="7" class="px-4 py-6 text-center text-slate-500">
              Chưa có tài khoản nào phù hợp với bộ lọc.
            </td>
          </tr>

          <!-- Rows -->
          <tr
            v-else
            v-for="user in users"
            :key="user.id"
            class="align-top hover:bg-slate-50"
          >
            <td class="px-3 py-3">
              <p class="text-sm font-medium text-slate-800">
                {{ user.fullName }}
              </p>
              <p v-if="user.staffCode" class="text-xs text-slate-400">
                Mã CB: {{ user.staffCode }}
              </p>
            </td>

            <td class="px-3 py-3 text-sm text-slate-700">
              {{ user.email }}
            </td>

            <td class="px-3 py-3 text-sm text-slate-700">
              {{ user.username }}
            </td>

            <td class="px-3 py-3 text-sm text-slate-700">
              {{ user.department }}
            </td>

            <td class="px-3 py-3 text-xs text-slate-700">
              <span
                v-for="(role, idx) in user.roles"
                :key="role + idx"
                class="mr-1 inline-flex items-center rounded-full border border-slate-200 px-2 py-0.5 text-[11px]"
              >
                {{ role }}
              </span>
            </td>

            <td class="px-3 py-3 text-sm">
              <span
                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                :class="
                  user.isActive
                    ? 'border-emerald-100 bg-emerald-50 text-emerald-700'
                    : 'border-slate-200 bg-slate-50 text-slate-500'
                "
              >
                {{ user.isActive ? "Đang hoạt động" : "Đã khóa" }}
              </span>
            </td>

            <td class="px-3 py-3 text-center text-xs">
              <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50"
                @click.stop="openMenu(user, $event)"
              >
                <span class="text-base leading-none">⋮</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Menu teleport ra body để không bị cắt bởi scroll -->
    <Teleport to="body">
      <transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-1"
      >
        <div
          v-if="openMenuUser"
          class="fixed z-50"
          :style="{ top: `${menuTop}px`, left: `${menuLeft}px` }"
          @click.stop
        >
          <div
            class="w-56 origin-top-right rounded-xl border border-slate-200 bg-white py-1 text-xs shadow-xl"
          >
            <button
              type="button"
              class="flex w-full items-center justify-between px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50"
              @click="onToggleActive(openMenuUser)"
            >
              <span>
                {{
                  openMenuUser.isActive
                    ? "Vô hiệu hóa tài khoản"
                    : "Kích hoạt tài khoản"
                }}
              </span>
            </button>

            <button
              type="button"
              class="flex w-full items-center justify-between px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50"
              @click="onManageRoles(openMenuUser)"
            >
              <span>Phân quyền</span>
            </button>

            <button
              type="button"
              class="flex w-full items-center justify-between px-3 py-1.5 text-left text-slate-700 hover:bg-slate-50"
              @click="onEdit(openMenuUser)"
            >
              <span>Chỉnh sửa thông tin</span>
            </button>

            <div class="my-1 border-t border-slate-100" />

            <button
              type="button"
              class="flex w-full items-center justify-between px-3 py-1.5 text-left text-rose-600 hover:bg-rose-50"
              @click="onDelete(openMenuUser)"
            >
              <span>Xóa tài khoản</span>
            </button>
          </div>
        </div>
      </transition>
    </Teleport>
  </div>
</template>
