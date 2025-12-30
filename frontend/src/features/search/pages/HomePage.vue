<template>
  <div class="min-h-screen bg-slate-100">
    <!-- TOP BRAND HEADER (white) -->
    <PublicHomeTopHeader />

    <!-- NAV BAR (blue) + AUTH AREA -->
    <PublicHomeNavBar
      :is-authenticated="isAuthenticated"
      :lecturer-name="lecturerName"
      :lecturer-code="lecturerCode"
      :initials="userInitials"
      @go-home="scrollToTop"
      @go-search="scrollToSearch"
      @logout="handleLogout"
    />

    <!-- QUICK LINKS -->
    <div class="mx-auto max-w-6xl px-4 pt-6 md:px-6">
      <PublicHomeQuickLinks @jump="handleQuickJump" />
    </div>

    <!-- MAIN CONTENT (2 columns like mẫu) -->
    <main class="mx-auto max-w-6xl px-4 py-6 md:px-6 md:py-8">
      <div class="grid gap-4 md:grid-cols-12 md:gap-6">
        <!-- LEFT: news + quick suggestions -->
        <aside class="space-y-4 md:col-span-4">
          <PublicHomeLeftPanel
            :faculty-options="facultyOptions"
            :work-type-options="workTypeOptions"
            :academic-year-options="academicYearOptions"
            @apply-filter="applyFilterAndSearch"
            @scroll-to-search="scrollToSearch"
          />
        </aside>

        <!-- RIGHT: SEARCH (core feature unchanged) -->
        <section class="space-y-4 md:col-span-8">
          <div
            id="public-research-search"
            ref="searchSectionRef"
            class="scroll-mt-24 space-y-4"
          >
            <PublicResearchFilterPanel
              :filter-state="filterState"
              :faculty-options="facultyOptions"
              :work-type-options="workTypeOptions"
              :academic-year-options="academicYearOptions"
              @update-filter="updateFilterState"
              @search="onSearch"
              @reset="onReset"
            />

            <PublicResearchTable
              :items="publicResearchItems"
              :total-items="totalItems"
              :page="filterState.page"
              :page-size="filterState.pageSize"
              :loading-state="loadingState"
              :error-state="errorState"
              @row-click="openResearchDrawer"
              @update-page="onUpdatePage"
              @update-page-size="onUpdatePageSize"
            />
          </div>

          <!-- FOOTER NOTE (business rule) -->
          <div
            class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm"
          >
            ⚠️ Chỉ hiển thị các công trình đã được khoa và trường phê duyệt.
            Không hiển thị giờ NCKH hoặc dữ liệu quản lý nội bộ.
          </div>
        </section>
      </div>
    </main>

    <!-- BIG FOOTER -->
    <PublicHomeFooter />

    <!-- DRAWER (unchanged) -->
    <PublicResearchDrawer
      :open="isResearchDrawerOpen"
      :item="selectedResearchItem"
      @close="closeResearchDrawer"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";

import { useUserStore } from "@/app/stores/userStore";
import { usePublicResearch } from "@/features/public-research/composables/usePublicResearch";
import type { PublicResearchFilterState } from "@/features/public-research/models/publicResearchModels";

import PublicResearchFilterPanel from "@/features/public-research/components/PublicResearchFilterPanel.vue";
import PublicResearchTable from "@/features/public-research/components/PublicResearchTable.vue";
import PublicResearchDrawer from "@/features/public-research/components/PublicResearchDrawer.vue";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeQuickLinks from "@/features/search/components/PublicHomeQuickLinks.vue";
import PublicHomeLeftPanel from "@/features/search/components/PublicHomeLeftPanel.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";

const router = useRouter();
const userStore = useUserStore();

const searchSectionRef = ref<HTMLElement | null>(null);

// Auth affects ONLY header/nav
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
  // logout xong vẫn ở public home
  await router.replace("/");
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function scrollToSearch() {
  const el = searchSectionRef.value ?? document.getElementById("public-research-search");
  el?.scrollIntoView({ behavior: "smooth", block: "start" });
}

function handleQuickJump(key: "search" | "lecturer" | "faculty" | "year" | "guide") {
  if (key === "search") {
    scrollToSearch();
    return;
  }
  if (key === "guide") {
    // scroll tới footer note / cuối trang
    window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
    return;
  }
  // còn lại: đưa user tới khu tra cứu (bạn có thể nâng cấp focus input sau)
  scrollToSearch();
}

// Public research (content does NOT depend on auth)
const {
  publicResearchItems,
  filterState,
  isResearchDrawerOpen,
  loadingState,
  errorState,

  totalItems,
  facultyOptions,
  workTypeOptions,
  academicYearOptions,
  selectedResearchItem,

  loadPublicResearchItems,
  updateFilterState,
  resetFilterState,
  openResearchDrawer,
  closeResearchDrawer,
} = usePublicResearch();

onMounted(() => {
  loadPublicResearchItems();
});

// Keep pagination reactive (page/pageSize changes load new data)
watch(
  () => [filterState.page, filterState.pageSize],
  () => {
    loadPublicResearchItems();
  }
);

function onSearch() {
  updateFilterState({ page: 1 });
  loadPublicResearchItems();
}

function onReset() {
  resetFilterState();
  loadPublicResearchItems();
}

function onUpdatePage(page: number) {
  updateFilterState({ page });
}

function onUpdatePageSize(pageSize: number) {
  updateFilterState({ pageSize, page: 1 });
}

function applyFilterAndSearch(next: Partial<PublicResearchFilterState>) {
  updateFilterState({ ...next, page: 1 });
  loadPublicResearchItems();
  scrollToSearch();
}
</script>
