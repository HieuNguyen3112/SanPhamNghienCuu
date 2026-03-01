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
      <div class="text-2xl font-extrabold text-slate-900">Giảng viên</div>
      <div class="mt-1 text-sm text-slate-600">Tra cứu giảng viên theo tên/mã, khoa, năm học.</div>

      <div class="mt-5 space-y-4">
        <PublicResearchFilterPanel
          :filter-state="filterState"
          :faculty-options="facultyOptions"
          :work-type-options="workTypeOptions"
          :academic-year-options="academicYearOptions"
          :hide-work-type="true"
          :auto-focus-lecturer="true"
          @update-filter="updateFilterState"
          @search="onSearch"
          @reset="onReset"
        />

        <div v-if="loadingState === 'loading'" class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
          Đang tải dữ liệu...
        </div>

        <div v-else-if="errorState" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800">
          {{ errorState }}
        </div>

        <PublicLecturerGrid
          v-else
          :lecturers="lecturerCards"
          @jump-work="jumpToWorkType"
        />
      </div>
    </main>

    <PublicHomeFooter />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";

import { usePublicResearch } from "@/features/public-research/composables/usePublicResearch";
import type { PublicResearchItem, PublicResearchWorkType } from "@/features/public-research/models/publicResearchModels";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";

import PublicResearchFilterPanel from "@/features/public-research/components/PublicResearchFilterPanel.vue";
import PublicLecturerGrid, { type LecturerCard } from "@/features/search/components/PublicLecturerGrid.vue";

const router = useRouter();
const route = useRoute();
const userStore = useUserStore();

onMounted(async () => {
  if (!userStore.isInitialized) await userStore.ensureAuthInitialized();
});

const isAuthenticated = computed(() => userStore.isAuthenticated);
const lecturerName = computed(() => userStore.currentUser?.name ?? "");
const lecturerCode = computed(() => userStore.currentUser?.code ?? "");
const userInitials = computed(() => {
  const name = lecturerName.value.trim();
  if (!name) return "U";
  const parts = name.split(/\s+/).filter(Boolean);
  return ((parts[0]?.[0] ?? "U") + (parts.length > 1 ? parts[parts.length - 1]?.[0] ?? "" : "")).toUpperCase();
});

async function handleLogout() {
  await userStore.logout();
  await router.replace("/");
}

const {
  publicResearchItems,
  filterState,
  loadingState,
  errorState,
  facultyOptions,
  workTypeOptions,
  academicYearOptions,
  loadPublicResearchItems,
  updateFilterState,
  resetFilterState,
} = usePublicResearch();

function initFromQuery() {
  const lecturer = typeof route.query.lecturer === "string" ? route.query.lecturer : "";
  const facultyId = typeof route.query.facultyId === "string" ? Number(route.query.facultyId) : null;
  const academicYearId = typeof route.query.academicYearId === "string" ? Number(route.query.academicYearId) : null;

  updateFilterState({
    lecturerQuery: lecturer,
    facultyId: Number.isFinite(facultyId as any) ? facultyId : null,
    academicYearId: Number.isFinite(academicYearId as any) ? academicYearId : null,
    workType: null,
    page: 1,
    pageSize: 50, // tăng để có nhiều giảng viên hơn
  });
}

onMounted(async () => {
  initFromQuery();
  await loadPublicResearchItems();
});

function onSearch() {
  updateFilterState({ page: 1, workType: null });
  loadPublicResearchItems();
}
function onReset() {
  resetFilterState();
  updateFilterState({ workType: null, page: 1, pageSize: 50 });
  loadPublicResearchItems();
}

function getInitials(name: string) {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return "U";
  return ((parts[0]?.[0] ?? "U") + (parts.length > 1 ? parts[parts.length - 1]?.[0] ?? "" : "")).toUpperCase();
}

function aggregate(items: PublicResearchItem[]): LecturerCard[] {
  const map = new Map<number, LecturerCard>();

  for (const it of items) {
    const key = it.lecturerId;
    if (!map.has(key)) {
      map.set(key, {
        lecturerId: it.lecturerId,
        lecturerCode: it.lecturerCode,
        lecturerName: it.lecturerName,
        facultyName: it.facultyName,
        initials: getInitials(it.lecturerName),
        lecturerQueryPretty: `${it.lecturerName} / ${it.lecturerCode}`,
        counts: { ARTICLE: 0, BOOK: 0, PROJECT: 0, CONFERENCE: 0, OTHER: 0 },
      });
    }
    const row = map.get(key)!;
    row.counts[it.workType] = (row.counts[it.workType] ?? 0) + 1;
  }

  return Array.from(map.values()).sort((a, b) => a.lecturerName.localeCompare(b.lecturerName));
}

const lecturerCards = computed(() => aggregate(publicResearchItems.value));

function jumpToWorkType(payload: { type: PublicResearchWorkType; lecturerQuery: string }) {
  const routeMap: Record<PublicResearchWorkType, string> = {
    ARTICLE: "/bai-bao-khoa-hoc",
    BOOK: "/sach-giao-trinh",
    PROJECT: "/de-tai-nghien-cuu",
    CONFERENCE: "/hoi-thao-bao-cao-khoa-hoc",
    OTHER: "/bai-bao-khoa-hoc", // nếu bạn muốn OTHER đi route khác thì sửa ở đây
  };

  router.push({
    path: routeMap[payload.type],
    query: { lecturer: payload.lecturerQuery },
  });
}
</script>