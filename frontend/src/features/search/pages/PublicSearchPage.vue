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
            Tra cứu theo mục (kết quả load ngay khi vào trang).
          </div>
        </div>

        <RouterLink
          to="/"
          class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50"
        >
          Về trang chủ
        </RouterLink>
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

        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm">
          ⚠️ Chỉ hiển thị công trình đã được khoa và trường phê duyệt. Không hiển thị dữ liệu quản lý nội bộ.
        </div>
      </div>
    </main>

    <PublicHomeFooter />

    <PublicResearchDrawer :open="isResearchDrawerOpen" :item="selectedResearchItem" @close="closeResearchDrawer" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";

import { useUserStore } from "@/app/stores/userStore";
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
  // 1) preset workType
  updateFilterState({ workType: fixedWorkType.value, page: 1 });

  // 2) query from home: ?lecturer=&facultyId=&academicYearId=
  const lecturer = typeof route.query.lecturer === "string" ? route.query.lecturer : "";
  const facultyId = typeof route.query.facultyId === "string" ? Number(route.query.facultyId) : null;
  const academicYearId = typeof route.query.academicYearId === "string" ? Number(route.query.academicYearId) : null;

  updateFilterState({
    lecturerQuery: lecturer,
    facultyId: Number.isFinite(facultyId as any) ? facultyId : null,
    academicYearId: Number.isFinite(academicYearId as any) ? academicYearId : null,
    page: 1,
  });

  loadPublicResearchItems();
}

onMounted(() => {
  applyPresetAndQueryThenSearch();
});

// pagination: giữ behavior như file HomePage cũ
watch(
  () => [filterState.page, filterState.pageSize],
  () => {
    loadPublicResearchItems();
  }
);

function onSearch() {
  // giữ fixed workType với các tab không phải giảng viên
  updateFilterState({ page: 1, workType: fixedWorkType.value });
  loadPublicResearchItems();
}

function onReset() {
  resetFilterState();
  // reset xong vẫn phải giữ preset workType
  updateFilterState({ workType: fixedWorkType.value, page: 1 });
  loadPublicResearchItems();
}

function onUpdatePage(page: number) {
  updateFilterState({ page });
}

function onUpdatePageSize(pageSize: number) {
  updateFilterState({ pageSize, page: 1 });
}
</script>