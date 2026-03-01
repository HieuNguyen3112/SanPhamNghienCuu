<template>
  <div class="min-h-screen bg-slate-100">
    <PublicHomeTopHeader />

    <PublicHomeNavBar
      :is-authenticated="isAuthenticated"
      :lecturer-name="meName"
      :lecturer-code="meCode"
      :initials="meInitials"
      @logout="handleLogout"
    />

    <main class="mx-auto max-w-6xl px-4 py-8 md:px-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-6 md:grid-cols-12 md:items-start">
          <div class="md:col-span-3">
            <div class="aspect-square w-full overflow-hidden rounded-2xl bg-slate-100" />
            <div class="mt-3 text-center text-sm font-extrabold text-slate-900">
              {{ lecturerDisplayName }}
            </div>
            <div class="mt-1 text-center text-xs text-slate-500">{{ lecturerCodeParam }}</div>
          </div>

          <div class="md:col-span-9">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <div class="text-xl font-extrabold text-slate-900">Thông tin</div>
                <div class="mt-1 text-sm text-slate-600">
                  Khoa: <b>{{ facultyName }}</b>
                </div>
              </div>
              <button
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"
                @click="$router.push('/giang-vien')"
              >
                Quay lại
              </button>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-4">
              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
                @click="jump('ARTICLE')"
              >
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.ARTICLE }}</div>
                <div class="mt-1 text-xs font-bold text-slate-600">Bài báo</div>
              </button>

              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
                @click="jump('BOOK')"
              >
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.BOOK }}</div>
                <div class="mt-1 text-xs font-bold text-slate-600">Sách</div>
              </button>

              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
                @click="jump('PROJECT')"
              >
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.PROJECT }}</div>
                <div class="mt-1 text-xs font-bold text-slate-600">Đề tài</div>
              </button>

              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
                @click="jump('CONFERENCE')"
              >
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.CONFERENCE }}</div>
                <div class="mt-1 text-xs font-bold text-slate-600">Hội thảo</div>
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <PublicHomeFooter />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { usePublicResearch } from "@/features/public-research/composables/usePublicResearch";
import type { PublicResearchWorkType } from "@/features/public-research/models/publicResearchModels";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";

const router = useRouter();
const route = useRoute();
const lecturerCodeParam = String(route.params.lecturerCode ?? "");

const userStore = useUserStore();
onMounted(async () => {
  if (!userStore.isInitialized) await userStore.ensureAuthInitialized();
});

const isAuthenticated = computed(() => userStore.isAuthenticated);
const meName = computed(() => userStore.currentUser?.name ?? "");
const meCode = computed(() => userStore.currentUser?.code ?? "");
const meInitials = computed(() => {
  const name = meName.value.trim();
  if (!name) return "U";
  const parts = name.split(/\s+/).filter(Boolean);
  return ((parts[0]?.[0] ?? "U") + (parts.length > 1 ? parts[parts.length - 1]?.[0] ?? "" : "")).toUpperCase();
});

async function handleLogout() {
  await userStore.logout();
  await router.replace("/");
}

const { publicResearchItems, filterState, loadPublicResearchItems, updateFilterState } = usePublicResearch();

const lecturerDisplayName = computed(() => publicResearchItems.value[0]?.lecturerName ?? "Giảng viên");
const facultyName = computed(() => publicResearchItems.value[0]?.facultyName ?? "—");

const counts = ref<Record<PublicResearchWorkType, number>>({
  ARTICLE: 0,
  BOOK: 0,
  PROJECT: 0,
  CONFERENCE: 0,
  OTHER: 0,
});

function computeCounts() {
  const c: Record<PublicResearchWorkType, number> = { ARTICLE: 0, BOOK: 0, PROJECT: 0, CONFERENCE: 0, OTHER: 0 };
  for (const it of publicResearchItems.value) c[it.workType] = (c[it.workType] ?? 0) + 1;
  counts.value = c;
}

onMounted(async () => {
  updateFilterState({
    lecturerQuery: lecturerCodeParam, // lọc theo mã
    workType: null,
    page: 1,
    pageSize: 100,
  });
  await loadPublicResearchItems();
  computeCounts();
});

function jump(type: PublicResearchWorkType) {
  const q = `${lecturerDisplayName.value} / ${lecturerCodeParam}`;
  const map: Record<PublicResearchWorkType, string> = {
    ARTICLE: "/bai-bao-khoa-hoc",
    BOOK: "/sach-giao-trinh",
    PROJECT: "/de-tai-nghien-cuu",
    CONFERENCE: "/hoi-thao-bao-cao-khoa-hoc",
    OTHER: "/bai-bao-khoa-hoc",
  };
  router.push({ path: map[type], query: { lecturer: q } });
}
</script>