<template>
  <div class="min-h-screen bg-white">
    <PublicHomeTopHeader />

    <PublicHomeNavBar
      :is-authenticated="isAuthenticated"
      :lecturer-name="lecturerName"
      :lecturer-code="lecturerCode"
      :initials="userInitials"
      @logout="handleLogout"
    />

    <!-- ✅ Trang chủ chỉ có HERO + nội dung landing -->
    <PublicHomeHero
      :filter-state="filterState"
      :faculty-options="facultyOptions"
      :academic-year-options="academicYearOptions"
      :category="category"
      @update-filter="updateFilterState"
      @update:category="category = $event"
      @search="onHomeSearch"
      @reset="onHomeReset"
    />

    <!-- ✅ THỐNG KÊ CÔNG TRÌNH CỦA TÔI (LẤY TỪ BACKEND THẬT) -->
    <section v-if="isAuthenticated && countsByKind" class="bg-slate-50">
      <div class="mx-auto max-w-6xl px-4 py-8 md:px-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-xl font-extrabold text-slate-900">
                Công trình khoa học của tôi
              </div>
              <div class="mt-1 text-sm text-slate-600">
                Chỉ tính công trình đã duyệt (read-only).
              </div>
            </div>

            <div class="text-sm font-bold text-slate-500">
              Tổng:
              <span class="text-slate-900">{{ myCounts.total }}</span>
            </div>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <button
              class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
              @click="goToPapers"
            >
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.paper }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Bài báo</div>
            </button>

            <button
              class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
              @click="goToProjects"
            >
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.project }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Đề tài</div>
            </button>

            <button
              class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
              @click="goToBooks('book')"
            >
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.book }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Sách</div>
            </button>

            <button
              class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
              @click="goToBooks('textbook')"
            >
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.textbook }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Giáo trình</div>
            </button>

            <button
              class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100"
              @click="goToConferences"
            >
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.conference }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Hội thảo</div>
            </button>
          </div>
        </div>
      </div>
    </section>

    <PublicHomeFooter />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import { useUserStore } from "@/app/stores/userStore";
import { usePublicResearch } from "@/features/public-research/composables/usePublicResearch";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";
import PublicHomeHero from "@/features/search/components/PublicHomeHero.vue";

type CategoryKey = "lecturer" | "article" | "project" | "book" | "conference";

const router = useRouter();
const userStore = useUserStore();

onMounted(async () => {
  if (!userStore.isInitialized) {
    await userStore.bootstrapAuth();
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

/** ✅ research_works từ backend thật */
const researchWorks = computed(() => (userStore.currentUser as any)?.research_works ?? null);
const countsByKind = computed(() => researchWorks.value?.counts_by_kind ?? null);

/** ✅ map số lượng */
const myCounts = computed(() => {
  const c = countsByKind.value || {};
  return {
    paper: Number(c.paper ?? 0),
    project: Number(c.project ?? 0),
    conference: Number(c.conference ?? 0),
    book: Number(c.book_only ?? 0),     // ✅ sách
    textbook: Number(c.textbook ?? 0),  // ✅ giáo trình
    total: Number(c.total ?? 0),
  };
});

/** ✅ query để public search tự fill tên/mã */
const lecturerQuery = computed(() => {
  const name = lecturerName.value.trim();
  const code = lecturerCode.value.trim();
  if (!name && !code) return "";
  return `${name}${code ? " / " + code : ""}`;
});

/** ✅ click thống kê -> nhảy sang trang tương ứng */
function goToPapers() {
  router.push({ path: "/bai-bao-khoa-hoc", query: { lecturer: lecturerQuery.value } });
}
function goToProjects() {
  router.push({ path: "/de-tai-nghien-cuu", query: { lecturer: lecturerQuery.value } });
}
function goToConferences() {
  router.push({ path: "/hoi-thao-bao-cao-khoa-hoc", query: { lecturer: lecturerQuery.value } });
}
function goToBooks(kind: "book" | "textbook") {
  router.push({
    path: "/sach-giao-trinh",
    query: { lecturer: lecturerQuery.value, kind },
  });
}

/** ✅ Hero search (public) */
const {
  filterState,
  facultyOptions,
  academicYearOptions,
  updateFilterState,
  resetFilterState,
} = usePublicResearch();

const category = ref<CategoryKey>("lecturer");

const categoryToPath: Record<CategoryKey, string> = {
  lecturer: "/giang-vien",
  article: "/bai-bao-khoa-hoc",
  project: "/de-tai-nghien-cuu",
  book: "/sach-giao-trinh",
  conference: "/hoi-thao-bao-cao-khoa-hoc",
};

async function onHomeSearch() {
  const path = categoryToPath[category.value];
  const q: Record<string, string> = {};

  if (filterState.lecturerQuery?.trim()) q.lecturer = filterState.lecturerQuery.trim();
  if (filterState.facultyId != null) q.facultyId = String(filterState.facultyId);
  if (filterState.academicYearId != null) q.academicYearId = String(filterState.academicYearId);

  await router.push({ path, query: q });
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function onHomeReset() {
  resetFilterState();
}
</script>
