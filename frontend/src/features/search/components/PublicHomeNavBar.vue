<template>
  <div class="sticky top-0 z-40 bg-[#234a74] shadow-sm">
    <div class="mx-auto max-w-7xl px-4 py-2 sm:px-6">
      <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
        <nav
          class="-mx-1 flex min-w-0 gap-1 overflow-x-auto px-1 pb-1 text-[12px] font-normal no-scrollbar sm:text-[13px] lg:mx-0 lg:flex-1 lg:px-0"
        >
          <RouterLink :to="{ path: '/' }" :class="navClass(isActiveExact('/'))">
            Trang chủ
          </RouterLink>

          <RouterLink
            :to="{ path: '/lecturers' }"
            :class="navClass(isActivePrefix('/lecturers'))"
          >
            Giảng viên
          </RouterLink>

          <RouterLink
            :to="{ path: '/research-articles' }"
            :class="navClass(isActivePrefix('/research-articles'))"
          >
            Bài báo khoa học
          </RouterLink>

          <RouterLink
            :to="{ path: '/research-projects' }"
            :class="navClass(isActivePrefix('/research-projects'))"
          >
            Đề tài nghiên cứu
          </RouterLink>

          <RouterLink
            :to="{ path: '/textbooks' }"
            :class="navClass(isActivePrefix('/textbooks'))"
          >
            Sách - Giáo trình
          </RouterLink>

          <RouterLink
            :to="{ path: '/research-conferences' }"
            :class="navClass(isActivePrefix('/research-conferences'))"
          >
            <span class="hidden xl:inline">Hội thảo - Báo cáo khoa học</span>
            <span class="xl:hidden">Hội thảo - Báo cáo</span>
          </RouterLink>

          <RouterLink
            :to="{ path: '/user-guide' }"
            :class="navClass(isActivePrefix('/user-guide'))"
          >
            <span class="hidden xl:inline">Hướng dẫn sử dụng</span>
            <span class="xl:hidden">Hướng dẫn</span>
          </RouterLink>
        </nav>

        <div class="flex w-full shrink-0 items-center justify-end gap-2 lg:w-auto">
          <RouterLink
            v-if="!isAuthenticated"
            to="/login"
            class="inline-flex h-10 w-full items-center justify-center rounded-xl bg-[#e11d48] px-4 text-sm font-semibold text-white hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-white/35 sm:w-auto"
          >
            Đăng nhập
          </RouterLink>

          <div v-else class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            <RouterLink
              :to="accountTargetPath"
              class="flex h-10 min-w-0 items-center justify-center gap-2 rounded-full bg-white/10 px-3 text-sm font-normal text-white hover:bg-white/15 focus:outline-none sm:justify-start"
              :title="accountButtonTitle"
            >
              <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-[#234a74]"
                aria-hidden="true"
              >
                {{ initials || "U" }}
              </span>
              <span class="max-w-[150px] truncate md:max-w-[180px]">
                {{ lecturerName || "Tài khoản" }}
              </span>
            </RouterLink>

            <button
              type="button"
              class="h-10 rounded-xl border border-white/20 bg-white/10 px-3 text-sm font-normal text-white hover:bg-white/15 focus:outline-none"
              @click="$emit('logout')"
            >
              Đăng xuất
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { resolvePublicAccountTargetPath } from "@/app/router/roleTargets";

defineProps<{
  isAuthenticated: boolean;
  lecturerName: string;
  lecturerCode: string;
  initials: string;
}>();

defineEmits<{
  (e: "logout"): void;
}>();

const route = useRoute();
const userStore = useUserStore();

const accountTargetPath = computed(() =>
  resolvePublicAccountTargetPath(userStore.role),
);

const accountButtonTitle = computed(() =>
  userStore.role === "LECTURER" ? "Trang cá nhân" : "Khu vực làm việc",
);

function isActiveExact(path: string) {
  return route.path === path;
}
function isActivePrefix(prefix: string) {
  return route.path === prefix || route.path.startsWith(prefix + "/");
}
function navClass(active: boolean) {
  return [
    "inline-flex h-10 shrink-0 items-center whitespace-nowrap rounded-xl px-3 text-white/90 transition hover:bg-white/10 hover:text-white sm:px-4",
    active ? "bg-white/15 text-white" : "",
  ].join(" ");
}
</script>

<style scoped>
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
