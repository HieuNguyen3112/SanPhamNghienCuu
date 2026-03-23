<template>
  <div class="min-h-screen overflow-x-hidden bg-white">
    <PublicHomeTopHeader />

    <PublicHomeNavBar
      :is-authenticated="isAuthenticated"
      :lecturer-name="lecturerName"
      :lecturer-code="lecturerCode"
      :initials="userInitials"
      @logout="handleLogout"
    />

    <PublicHomeHero
      :filter-state="filterState"
      :faculty-options="facultyOptions"
      :academic-year-options="academicYearOptions"
      :category="category"
      :overview-stats="overviewStats"
      @update-filter="updateFilterState"
      @update:category="category = $event"
      @search="onHomeSearch"
      @reset="onHomeReset"
      @open-list="goToCategoryPage"
    />

    <section v-if="isAuthenticated && countsByKind" class="bg-slate-50">
      <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <div class="text-xl font-extrabold text-slate-900">
                Công trình khoa học của tôi
              </div>
              <div class="mt-1 text-sm text-slate-600">
                Chỉ tính công trình đã duyệt
              </div>
            </div>

            <div class="text-sm font-bold text-slate-500">
              Tổng:
              <span class="text-slate-900">{{ myCounts.total }}</span>
            </div>
          </div>

          <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.paper }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Bài báo</div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.project }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Đề tài</div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.book }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Sách</div>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.textbook }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">
                Giáo trình
              </div>
            </div>

            <div class="col-span-2 rounded-2xl bg-slate-50 p-4 lg:col-span-1">
              <div class="text-3xl font-extrabold text-[#e11d48]">
                {{ myCounts.conference }}
              </div>
              <div class="mt-1 text-sm font-bold text-slate-800">Hội thảo</div>
            </div>
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
import { useLogoutFeedback } from "@/features/auth/composables/useLogoutFeedback";
import { usePublicResearch } from "@/features/public-research/composables/usePublicResearch";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";
import PublicHomeHero from "@/features/search/components/PublicHomeHero.vue";

type CategoryKey = "lecturer" | "article" | "project" | "book" | "conference";

const router = useRouter();
const userStore = useUserStore();
const { logoutWithFeedback } = useLogoutFeedback("/");

onMounted(async () => {
  console.log("[HomePage] mounted");

  if (!userStore.isInitialized) {
    await userStore.bootstrapAuth();
  }

  console.log("[HomePage] loadOverviewStats start");
  await loadOverviewStats();
  console.log("[HomePage] loadOverviewStats done", overviewStats.value);
});

const isAuthenticated = computed(() => userStore.isAuthenticated);
const lecturerName = computed(() => userStore.currentUser?.name ?? "");
const lecturerCode = computed(() => userStore.currentUser?.code ?? "");

const userInitials = computed(() => {
  const name = lecturerName.value.trim();
  if (!name) return "U";
  const parts = name.split(/\s+/).filter(Boolean);
  const first = parts[0]?.[0] ?? "U";
  const last = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? "") : "";
  return (first + last).toUpperCase();
});

async function handleLogout() {
  await logoutWithFeedback();
}

const researchWorks = computed(
  () => (userStore.currentUser as any)?.research_works ?? null,
);
const countsByKind = computed(
  () => researchWorks.value?.counts_by_kind ?? null,
);

const myCounts = computed(() => {
  const c = countsByKind.value || {};
  return {
    paper: Number(c.paper ?? 0),
    project: Number(c.project ?? 0),
    conference: Number(c.conference ?? 0),
    book: Number(c.book_only ?? 0),
    textbook: Number(c.textbook ?? 0),
    total: Number(c.total ?? 0),
  };
});

const {
  filterState,
  facultyOptions,
  academicYearOptions,
  updateFilterState,
  resetFilterState,
  overviewStats,
  loadOverviewStats,
} = usePublicResearch();

const category = ref<CategoryKey>("lecturer");

const categoryToPath: Record<CategoryKey, string> = {
  lecturer: "/lecturers",
  article: "/research-articles",
  project: "/research-projects",
  book: "/textbooks",
  conference: "/research-conferences",
};

async function onHomeSearch() {
  const path = categoryToPath[category.value];
  const q: Record<string, string> = {};

  if (filterState.lecturerQuery?.trim())
    q.lecturer = filterState.lecturerQuery.trim();
  if (filterState.facultyId != null)
    q.facultyId = String(filterState.facultyId);
  if (filterState.academicYearId != null)
    q.academicYearId = String(filterState.academicYearId);

  await router.push({ path, query: q });
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function onHomeReset() {
  resetFilterState();
}

async function goToCategoryPage(nextCategory: Exclude<CategoryKey, "lecturer">) {
  const path = categoryToPath[nextCategory];
  const q: Record<string, string> = {};

  if (filterState.lecturerQuery?.trim()) {
    q.lecturer = filterState.lecturerQuery.trim();
  }
  if (filterState.facultyId != null) {
    q.facultyId = String(filterState.facultyId);
  }
  if (filterState.academicYearId != null) {
    q.academicYearId = String(filterState.academicYearId);
  }

  await router.push({ path, query: q });
}
</script>