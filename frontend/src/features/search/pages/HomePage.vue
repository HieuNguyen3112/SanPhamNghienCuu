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

    <!-- (Bạn có thể giữ/đưa thêm overview/news ở đây nếu muốn) -->

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
  const last = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? "") : "";
  return (first + last).toUpperCase();
});

async function handleLogout() {
  await userStore.logout();
  await router.replace("/");
}

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
</script>
