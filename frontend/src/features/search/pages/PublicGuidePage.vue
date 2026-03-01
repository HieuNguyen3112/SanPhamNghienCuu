<template>
  <div class="min-h-screen bg-slate-100">
    <PublicHomeTopHeader />

    <PublicHomeNavBar
      :is-authenticated="isAuthenticated"
      :lecturer-name="lecturerName"
      :lecturer-code="lecturerCode"
      :initials="userInitials"
      @logout="handleLogout"
    />

    <main class="mx-auto max-w-6xl px-4 py-10 md:px-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="text-2xl font-extrabold text-slate-900">Hướng dẫn sử dụng</div>
        <p class="mt-2 text-sm text-slate-600">
          Trang này là placeholder — bạn có thể điền nội dung hướng dẫn, quy định, FAQ, link biểu mẫu.
        </p>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-sm font-extrabold text-slate-900">1) Tra cứu theo mục</div>
            <div class="mt-1 text-sm text-slate-600">
              Vào menu Giảng viên / Bài báo / Đề tài / Sách / Hội thảo để tra cứu theo đúng loại.
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="text-sm font-extrabold text-slate-900">2) Bộ lọc</div>
            <div class="mt-1 text-sm text-slate-600">
              Dùng các trường: giảng viên, khoa, năm học… rồi bấm Tìm kiếm.
            </div>
          </div>
        </div>
      </div>
    </main>

    <PublicHomeFooter />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";

const router = useRouter();
const userStore = useUserStore();

onMounted(async () => {
  if (!userStore.isInitialized) {
    await userStore.ensureAuthInitialized();
  }
});

const isAuthenticated = computed(() => userStore.isAuthenticated);
const lecturerName = computed(() => userStore.currentUser?.name ?? "");
const lecturerCode = computed(() => userStore.currentUser?.code ?? "");

const userInitials = computed(() => {
  const name = lecturerName.value.trim();
  if (!name) return "U";
  const parts = name.split(/\s+/).filter(Boolean);
  const first = parts[0]?.[0] ?? "U";
  const last = parts.length > 1 ? parts[parts.length - 1]?.[0] ?? "" : "";
  return (first + last).toUpperCase();
});

async function handleLogout() {
  await userStore.logout();
  await router.replace("/");
}
</script>