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

    <main class="mx-auto max-w-6xl px-4 py-8 md:px-6">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <div class="text-2xl font-extrabold text-slate-900">{{ pageTitle }}</div>
          <div class="mt-1 text-sm text-slate-600">
            Tra cứu theo mục
          </div>
        </div>
      </div>

      <div class="mt-5 space-y-4">
        <PublicResearchFilterPanel
          :filter-state="filterState"
          :faculty-options="facultyOptions"
          :work-type-options="workTypeOptions"
          :academic-year-options="academicYearOptions"
          :hide-work-type="hideWorkType"
          :auto-focus-lecturer="preset === 'lecturer'"
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
    </main>

    <PublicHomeFooter />

    <PublicResearchDrawer
      :open="isResearchDrawerOpen"
      :item="selectedResearchItem"
      @close="closeResearchDrawer"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from "vue";
import { useRoute } from "vue-router";

import { useUserStore } from "@/app/stores/userStore";
import { useLogoutFeedback } from "@/features/auth/composables/useLogoutFeedback";
import { usePublicResearch } from "@/features/public-research/composables/usePublicResearch";
import type { PublicResearchWorkType } from "@/features/public-research/models/publicResearchModels";

import PublicResearchFilterPanel from "@/features/public-research/components/PublicResearchFilterPanel.vue";
import PublicResearchTable from "@/features/public-research/components/PublicResearchTable.vue";
import PublicResearchDrawer from "@/features/public-research/components/PublicResearchDrawer.vue";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";

type Preset = "lecturer" | "article" | "project" | "book" | "conference";

const props = defineProps<{ preset: Preset }>();
const route = useRoute();
const { logoutWithFeedback } = useLogoutFeedback("/");

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
  await logoutWithFeedback();
}

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

const fixedWorkType = computed<PublicResearchWorkType | null>(() => {
  switch (props.preset) {
    case "article":
      return "ARTICLE";
    case "project":
      return "PROJECT";
    case "book":
      return "BOOK";
    case "conference":
      return "CONFERENCE";
    case "lecturer":
    default:
      return null;
  }
});

const hideWorkType = computed(() => props.preset !== "lecturer");

const pageTitle = computed(() => {
  switch (props.preset) {
    case "lecturer":
      return "Giảng viên";
    case "article":
      return "Bài báo khoa học";
    case "project":
      return "Đề tài nghiên cứu";
    case "book":
      return "Sách - Giáo trình";
    case "conference":
      return "Hội thảo - Báo cáo khoa học";
    default:
      return "Tra cứu";
  }
});

function applyPresetAndQueryThenSearch() {
  resetFilterState();

  const lecturer =
    typeof route.query.lecturer === "string" ? route.query.lecturer : "";

  const facultyIdRaw =
    typeof route.query.facultyId === "string"
      ? Number(route.query.facultyId)
      : null;

  const academicYearIdRaw =
    typeof route.query.academicYearId === "string"
      ? Number(route.query.academicYearId)
      : null;

  const facultyId =
    facultyIdRaw !== null && Number.isFinite(facultyIdRaw) ? facultyIdRaw : null;

  const academicYearId =
    academicYearIdRaw !== null && Number.isFinite(academicYearIdRaw)
      ? academicYearIdRaw
      : null;

  updateFilterState({
    keyword: "",
    lecturerQuery: lecturer,
    facultyId,
    academicYearId,
    workType: fixedWorkType.value,
    page: 1,
  });

  loadPublicResearchItems();
}

onMounted(() => {
  applyPresetAndQueryThenSearch();
});

watch(
  () => props.preset,
  () => {
    applyPresetAndQueryThenSearch();
  }
);

watch(
  () => [route.query.lecturer, route.query.facultyId, route.query.academicYearId],
  () => {
    applyPresetAndQueryThenSearch();
  }
);

// Chỉ reload khi user thao tác pagination sau khi page đã được set
watch(
  () => [filterState.page, filterState.pageSize],
  ([nextPage, nextPageSize], [prevPage, prevPageSize]) => {
    if (nextPage === prevPage && nextPageSize === prevPageSize) return;
    loadPublicResearchItems();
  }
);

function onSearch() {
  updateFilterState({
    page: 1,
    workType: fixedWorkType.value,
  });
  loadPublicResearchItems();
}

function onReset() {
  resetFilterState();
  updateFilterState({
    workType: fixedWorkType.value,
    page: 1,
  });
  loadPublicResearchItems();
}

function onUpdatePage(page: number) {
  updateFilterState({ page });
}

function onUpdatePageSize(pageSize: number) {
  updateFilterState({ pageSize, page: 1 });
}
</script>