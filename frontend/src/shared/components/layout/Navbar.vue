<template>
  <header
    class="flex h-14 w-full items-center justify-between gap-3 bg-[#234a74] px-3 text-slate-100 shadow-sm sm:px-4 lg:h-16 lg:px-8"
  >
    <div class="flex min-w-0 items-center gap-3">
      <button
        type="button"
        class="flex h-9 w-9 items-center justify-center rounded-md border border-white/20 bg-white/10 text-slate-100 shadow-sm transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/60 lg:hidden"
        aria-controls="app-sidebar"
        :aria-expanded="isDesktop ? undefined : isSidebarDrawerOpen"
        :aria-label="sidebarToggleLabel"
        @click="emit('toggle-sidebar')"
      >
        <span class="sr-only">{{ sidebarToggleLabel }}</span>
        <span class="space-y-1">
          <span class="block h-0.5 w-4 bg-current"></span>
          <span class="block h-0.5 w-4 bg-current"></span>
          <span class="block h-0.5 w-4 bg-current"></span>
        </span>
      </button>

      <div class="flex min-w-0 items-center gap-3">
        <span
          class="block min-w-0 truncate text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-100 sm:text-xs lg:text-sm lg:tracking-[0.18em]"
        >
          {{ title }}
        </span>
      </div>
    </div>

    <div class="flex shrink-0 items-center gap-3 sm:gap-4 lg:gap-6">
      <div ref="notificationRootRef" class="relative">
        <button
          type="button"
          class="relative flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-slate-100 shadow-sm transition hover:bg-white/20"
          :aria-expanded="isNotificationOpen"
          aria-haspopup="menu"
          aria-label="Mở danh sách thông báo"
          @click.stop="toggleNotificationMenu"
        >
          <Bell class="h-5 w-5" />

          <span
            v-if="displayNotificationCount > 0"
            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white"
          >
            {{ displayNotificationCount }}
          </span>
        </button>

        <transition
          enter-active-class="transition duration-150 ease-out"
          enter-from-class="translate-y-1 opacity-0"
          enter-to-class="translate-y-0 opacity-100"
          leave-active-class="transition duration-100 ease-in"
          leave-from-class="translate-y-0 opacity-100"
          leave-to-class="translate-y-1 opacity-0"
        >
          <div
            v-if="isNotificationOpen"
            class="absolute right-0 top-11 z-[70] w-[380px] max-w-[calc(100vw-1rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-800 shadow-xl"
            role="menu"
            aria-label="Danh sách thông báo"
          >
            <div
              class="flex items-center justify-between border-b border-slate-200 px-4 py-3"
            >
              <div class="text-sm font-semibold text-slate-900">Thông báo</div>
              <div class="flex items-center gap-3">
                <button
                  type="button"
                  class="text-xs font-medium text-slate-600 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="
                    !canUseNotifications ||
                    markingAllRead ||
                    displayNotificationCount === 0
                  "
                  @click="markAllAsRead"
                >
                  Đánh dấu tất cả đã đọc
                </button>
                <button
                  type="button"
                  class="text-xs font-medium text-slate-500 hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="
                    !canUseNotifications ||
                    deletingRead ||
                    readNotificationCount === 0
                  "
                  @click="deleteReadNotifications"
                >
                  Xóa đã đọc
                </button>
              </div>
            </div>

            <div class="max-h-[60vh] overflow-y-auto">
              <div
                v-if="!canUseNotifications"
                class="px-4 py-6 text-center text-sm text-slate-500"
              >
                Vai trò hiện tại chưa hỗ trợ thông báo nhanh.
              </div>

              <div
                v-else-if="loadingNotifications"
                class="px-4 py-6 text-center text-sm text-slate-500"
              >
                Đang tải thông báo...
              </div>

              <div
                v-else-if="notifications.length === 0"
                class="px-4 py-6 text-center text-sm text-slate-500"
              >
                Chưa có thông báo mới.
              </div>

              <ul v-else class="divide-y divide-slate-100">
                <li
                  v-for="entry in notificationsWithVisual"
                  :key="`${entry.item.source_role}:${entry.item.id}`"
                  class="px-3 py-2"
                >
                  <div
                    class="rounded-xl border px-3 py-2 transition"
                    :class="
                      entry.item.is_unread
                        ? 'border-blue-200 bg-blue-50/40'
                        : 'border-slate-200/60 bg-white'
                    "
                  >
                    <div class="flex items-start gap-3">
                      <div
                        class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-1"
                        :class="entry.visual.wrapClass"
                      >
                        <component
                          :is="entry.visual.icon"
                          class="h-4 w-4"
                          :class="entry.visual.iconClass"
                        />
                      </div>

                      <button
                        type="button"
                        class="flex-1 text-left"
                        @click="openNotification(entry.item)"
                      >
                        <p
                          class="line-clamp-1 text-sm"
                          :class="
                            entry.item.is_unread
                              ? 'font-semibold text-slate-900'
                              : 'font-medium text-slate-800'
                          "
                        >
                          {{ entry.item.title }}
                        </p>
                        <p class="mt-0.5 line-clamp-2 text-xs text-slate-600">
                          {{ entry.item.message }}
                        </p>
                        <p class="mt-1 text-[11px] text-slate-400">
                          {{ formatRelativeTime(entry.item.created_at) }}
                        </p>
                      </button>

                      <button
                        v-if="entry.item.is_unread"
                        type="button"
                        class="mt-0.5 rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="markingReadId === entry.item.id"
                        @click.stop="markAsReadOnly(entry.item)"
                      >
                        Đã đọc
                      </button>

                      <div v-else class="flex items-center gap-2">
                        <span
                          class="mt-0.5 inline-flex items-center gap-1 text-[11px] font-medium text-slate-400"
                        >
                          <span
                            class="inline-block h-1.5 w-1.5 rounded-full bg-slate-300"
                          ></span>
                          Đã đọc
                        </span>
                        <button
                          type="button"
                          class="mt-0.5 rounded-md border border-slate-200 p-1 text-slate-500 hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                          :disabled="deletingId === entry.item.id"
                          @click.stop="deleteNotification(entry.item)"
                        >
                          <span class="sr-only">Xóa thông báo</span>
                          <Trash2 class="h-3.5 w-3.5" />
                        </button>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </transition>
      </div>

      <div>
        <button
          ref="avatarBtnRef"
          type="button"
          class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-300 text-slate-700 shadow-sm transition hover:bg-slate-200"
          @click="toggleUserMenu"
        >
          <span class="sr-only">Tài khoản</span>
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path
              d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5A1.5 1.5 0 0 0 5.5 20h13A1.5 1.5 0 0 0 20 18.5C20 16 16.42 14 12 14Z"
            />
          </svg>
        </button>

        <UserDropdown
          :open="isUserMenuOpen"
          :anchor-el="avatarBtnRef"
          :user-name="userName"
          :user-code="userCode"
          @close="closeUserMenu"
          @open-profile="handleOpenProfile"
          @change-password="handleChangePassword"
          @logout="handleLogout"
        />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, toRefs, watch } from "vue";
import { useRouter } from "vue-router";
import { Bell, Trash2 } from "lucide-vue-next";
import { useUserStore } from "@/app/stores/userStore";
import UserDropdown from "@/shared/components/layout/UserDropdown.vue";
import { getNotificationVisual } from "@/shared/notifications/notificationVisual";
import {
  deleteNotificationByRole,
  deleteReadNotificationsByRole,
  listNotificationsByRole,
  markAllNotificationsAsReadByRole,
  markNotificationAsReadByRole,
  type AppNotificationItem,
  type NotificationRole,
} from "@/shared/services/lecturerNotifications.service";

const emit = defineEmits<{
  (e: "toggle-sidebar"): void;
  (e: "open-profile"): void;
  (e: "change-password"): void;
  (e: "logout"): void;
  (e: "go-home"): void;
}>();

const props = withDefaults(
  defineProps<{
    title?: string;
    notificationCount?: number;
    userName?: string;
    userCode?: string;
    isDesktop?: boolean;
    isSidebarDrawerOpen?: boolean;
  }>(),
  {
    title: "TRƯỜNG ĐẠI HỌC SƯ PHẠM THÀNH PHỐ HỒ CHÍ MINH",
    notificationCount: 0,
    userName: "",
    userCode: "",
    isDesktop: false,
    isSidebarDrawerOpen: false,
  },
);

const router = useRouter();
const userStore = useUserStore();

const notificationRoles = computed<NotificationRole[]>(() => {
  const roles = userStore.currentUser?.roles ?? [];
  const supportedRoles: NotificationRole[] = [];
  if (roles.includes("LECTURER")) supportedRoles.push("LECTURER");
  if (roles.includes("DEPARTMENT_BOARD"))
    supportedRoles.push("DEPARTMENT_BOARD");
  return supportedRoles;
});
const canUseNotifications = computed(() => notificationRoles.value.length > 0);

const isUserMenuOpen = ref(false);
const avatarBtnRef = ref<HTMLElement | null>(null);

const isNotificationOpen = ref(false);
const notificationRootRef = ref<HTMLElement | null>(null);
type NotificationListEntry = AppNotificationItem & {
  source_role: NotificationRole;
};

const notifications = ref<NotificationListEntry[]>([]);
const unreadCount = ref(0);
const loadingNotifications = ref(false);
const markingReadId = ref<string | null>(null);
const markingAllRead = ref(false);
const deletingId = ref<string | null>(null);
const deletingRead = ref(false);

const displayNotificationCount = computed(() => {
  const fallbackCount = Number(props.notificationCount ?? 0);
  return Math.max(unreadCount.value, fallbackCount);
});

const sidebarToggleLabel = computed(() =>
  props.isSidebarDrawerOpen ? "Đóng menu điều hướng" : "Mở menu điều hướng",
);

const notificationsWithVisual = computed(() =>
  notifications.value.map((item) => ({
    item,
    visual: getNotificationVisual(item),
  })),
);

const readNotificationCount = computed(
  () => notifications.value.filter((item) => !item.is_unread).length,
);

const defaultTargetByRole: Record<NotificationRole, string> = {
  LECTURER: "/declarations/gateway",
  DEPARTMENT_BOARD: "/works/facapprovals",
};

function toggleUserMenu() {
  isUserMenuOpen.value = !isUserMenuOpen.value;
}

function closeUserMenu() {
  isUserMenuOpen.value = false;
}

function handleOpenProfile() {
  emit("open-profile");
  closeUserMenu();
}

function handleChangePassword() {
  emit("change-password");
  closeUserMenu();
}

function handleLogout() {
  emit("logout");
  closeUserMenu();
}

function formatRelativeTime(value: string | null) {
  if (!value) return "Vừa xong";

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "Vừa xong";

  const diffMs = date.getTime() - Date.now();
  const diffMinute = Math.round(diffMs / 60000);
  const absMinute = Math.abs(diffMinute);

  const formatter = new Intl.RelativeTimeFormat("vi", { numeric: "auto" });

  if (absMinute < 1) return "Vừa xong";
  if (absMinute < 60) return formatter.format(diffMinute, "minute");

  const diffHour = Math.round(diffMinute / 60);
  if (Math.abs(diffHour) < 24) return formatter.format(diffHour, "hour");

  const diffDay = Math.round(diffHour / 24);
  if (Math.abs(diffDay) < 7) return formatter.format(diffDay, "day");

  return date.toLocaleString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

async function fetchNotifications(options?: { silent?: boolean }) {
  const roles = notificationRoles.value;
  if (roles.length === 0) {
    notifications.value = [];
    unreadCount.value = 0;
    return;
  }

  const silent = options?.silent ?? false;
  if (!silent) {
    loadingNotifications.value = true;
  }

  try {
    const results = await Promise.allSettled(
      roles.map((role) =>
        listNotificationsByRole(role, {
          page: 1,
          per_page: 12,
        }).then((response) => ({ role, response })),
      ),
    );

    const mergedItems: NotificationListEntry[] = [];
    let mergedUnreadCount = 0;

    for (const result of results) {
      if (result.status !== "fulfilled") continue;
      const { role, response } = result.value;
      mergedUnreadCount += Number(response.unread_count ?? 0);
      mergedItems.push(
        ...response.items.map((item) => ({
          ...item,
          source_role: role,
        })),
      );
    }

    mergedItems.sort((a, b) => {
      const left = a.created_at ? new Date(a.created_at).getTime() : 0;
      const right = b.created_at ? new Date(b.created_at).getTime() : 0;
      return right - left;
    });

    notifications.value = mergedItems.slice(0, 12);
    unreadCount.value = mergedUnreadCount;
  } catch (error) {
    console.error(error);
  } finally {
    if (!silent) {
      loadingNotifications.value = false;
    }
  }
}

async function markAsReadOnly(item: NotificationListEntry) {
  const role = item.source_role;
  if (markingReadId.value) return;

  markingReadId.value = item.id;
  try {
    await markNotificationAsReadByRole(role, item.id);
    notifications.value = notifications.value.map((entry) =>
      entry.id === item.id && entry.source_role === role
        ? {
            ...entry,
            is_unread: false,
            read_at: entry.read_at ?? new Date().toISOString(),
          }
        : entry,
    );
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  } catch (error) {
    console.error(error);
  } finally {
    markingReadId.value = null;
  }
}

async function openNotification(item: NotificationListEntry) {
  const role = item.source_role;

  if (item.is_unread) {
    await markAsReadOnly(item);
  }

  isNotificationOpen.value = false;

  const fallbackTarget = defaultTargetByRole[role];
  const targetPath = item.target_url || fallbackTarget;
  await router.push(targetPath).catch(() => undefined);
}

async function markAllAsRead() {
  const roles = notificationRoles.value;
  if (roles.length === 0) return;
  if (markingAllRead.value || displayNotificationCount.value === 0) return;

  markingAllRead.value = true;
  try {
    await Promise.all(
      roles.map((role) => markAllNotificationsAsReadByRole(role)),
    );
    notifications.value = notifications.value.map((item) => ({
      ...item,
      is_unread: false,
      read_at: item.read_at ?? new Date().toISOString(),
    }));
    unreadCount.value = 0;
  } catch (error) {
    console.error(error);
  } finally {
    markingAllRead.value = false;
  }
}

async function deleteNotification(item: NotificationListEntry) {
  const role = item.source_role;
  if (deletingId.value) return;

  deletingId.value = item.id;
  try {
    await deleteNotificationByRole(role, item.id);
    const removed = notifications.value.find(
      (entry) => entry.id === item.id && entry.source_role === role,
    );
    notifications.value = notifications.value.filter(
      (entry) => !(entry.id === item.id && entry.source_role === role),
    );

    if (removed?.is_unread) {
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
  } catch (error) {
    console.error(error);
  } finally {
    deletingId.value = null;
  }
}

async function deleteReadNotifications() {
  const roles = notificationRoles.value;
  if (
    roles.length === 0 ||
    deletingRead.value ||
    readNotificationCount.value === 0
  )
    return;

  deletingRead.value = true;
  try {
    await Promise.all(roles.map((role) => deleteReadNotificationsByRole(role)));
    notifications.value = notifications.value.filter((item) => item.is_unread);
  } catch (error) {
    console.error(error);
  } finally {
    deletingRead.value = false;
  }
}

async function toggleNotificationMenu() {
  isNotificationOpen.value = !isNotificationOpen.value;
  if (!isNotificationOpen.value) return;

  if (!canUseNotifications.value) return;
  await fetchNotifications();
}

function closeNotificationMenu() {
  isNotificationOpen.value = false;
}

function handleClickOutside(event: MouseEvent) {
  const target = event.target as Node | null;

  if (isNotificationOpen.value) {
    if (
      notificationRootRef.value &&
      !notificationRootRef.value.contains(target)
    ) {
      closeNotificationMenu();
    }
  }

  if (isUserMenuOpen.value) {
    if (avatarBtnRef.value && !avatarBtnRef.value.contains(target)) {
      closeUserMenu();
    }
  }
}

function handleEsc(event: KeyboardEvent) {
  if (event.key !== "Escape") return;
  closeNotificationMenu();
  closeUserMenu();
}

watch(notificationRoles, () => {
  notifications.value = [];
  unreadCount.value = 0;
  void fetchNotifications({ silent: true });
});

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  document.addEventListener("keydown", handleEsc);
  void fetchNotifications({ silent: true });
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  document.removeEventListener("keydown", handleEsc);
});

const { userName, userCode, title, isDesktop, isSidebarDrawerOpen } =
  toRefs(props);
</script>
