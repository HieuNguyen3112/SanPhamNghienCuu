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
      <div class="mt-1 text-sm text-slate-600">
        Tra cứu giảng viên theo tên/mã, khoa, năm học .
      </div>

      <div class="mt-5 space-y-4">
        <!-- Filter UI (giữ style như bạn đang dùng) -->
        <section
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
        >
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
              <label class="text-xs font-medium text-slate-600"
                >Tên giảng viên (tên/mã)</label
              >
              <input
                v-model="state.q"
                type="text"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
                placeholder="VD: Nguyễn Văn An / GV001"
                @keyup.enter="onSearch"
              />
            </div>

            <div>
              <label class="text-xs font-medium text-slate-600">Khoa</label>
              <select
                v-model="state.departmentId"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
              >
                <option :value="null">Tất cả khoa</option>
                <option
                  v-for="opt in facultyOptionsLocal"
                  :key="String(opt.value)"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </option>
              </select>
            </div>

            <div>
              <label class="text-xs font-medium text-slate-600">Năm học</label>
              <select
                v-model="state.academicYearId"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
              >
                <option :value="null">Tất cả năm học</option>
                <option
                  v-for="opt in academicYearOptionsLocal"
                  :key="String(opt.value)"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </option>
              </select>
            </div>

            <div class="flex items-end justify-end gap-2">
              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-[#e11d48] px-4 py-2 text-sm font-extrabold text-white shadow-sm hover:brightness-110 active:scale-[0.99]"
                @click="onSearch"
              >
                🔎 Tìm kiếm
              </button>

              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.99]"
                @click="onReset"
              >
                ↩ Đặt lại
              </button>
            </div>
          </div>
        </section>

        <!-- Loading / Error -->
        <div
          v-if="loading"
          class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600"
        >
          Đang tải dữ liệu...
        </div>

        <div
          v-else-if="error"
          class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800"
        >
          {{ error }}
        </div>

        <!-- List -->
        <div v-else class="space-y-3">
          <div class="text-sm text-slate-600">
            Hiển thị <b>{{ items.length }}</b> /
            <b>{{ pagination.total }}</b> giảng viên
          </div>

          <PublicLecturerGrid
            :lecturers="lecturerCards"
            @jump-work="jumpToWorkType"
            @open-detail="openDetail"
          />

          <!-- Pagination -->
          <div
            v-if="pagination.last_page > 1"
            class="flex items-center justify-center gap-2 pt-2"
          >
            <button
              class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
              :disabled="state.page <= 1"
              @click="goPage(state.page - 1)"
            >
              Trước
            </button>

            <div class="text-sm text-slate-600">
              Trang <b>{{ pagination.page }}</b> /
              <b>{{ pagination.last_page }}</b>
            </div>

            <button
              class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
              :disabled="state.page >= pagination.last_page"
              @click="goPage(state.page + 1)"
            >
              Sau
            </button>
          </div>
        </div>
      </div>
    </main>

    <PublicHomeFooter />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";
import { fetchPublicResearchLookupsApi } from "@/features/public-research/api/publicResearchLookupsApi";

import PublicLecturerGrid, {
  type LecturerCard,
} from "@/features/search/components/PublicLecturerGrid.vue";

// ✅ API call
type PublicLecturerItem = {
  id: number;
  code: string;
  full_name: string;
  department_name: string | null;
  degree_name: string | null;
  academic_rank_name: string | null;
  profile: {
    current_unit: string | null;
    current_position: string | null;
    teaching_specialization: string | null;
    research_area: string | null;
  } | null;
  research_works: {
    counts_by_kind: {
      paper: number;
      project: number;
      conference: number;
      book_only: number;
      textbook: number;
      total: number;
    };
  };
};

type ListRes = {
  success: boolean;
  data: {
    items: PublicLecturerItem[];
    pagination: {
      page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  };
};

async function fetchPublicLecturers(params: {
  q?: string;
  department_id?: number | null;
  academic_year_id?: number | null;
  page?: number;
  per_page?: number;
}): Promise<ListRes> {
  const sp = new URLSearchParams();
  if (params.q) sp.set("q", params.q);
  if (params.department_id != null)
    sp.set("department_id", String(params.department_id));
  if (params.academic_year_id != null)
    sp.set("academic_year_id", String(params.academic_year_id));
  sp.set("page", String(params.page ?? 1));
  sp.set("per_page", String(params.per_page ?? 12));

  const res = await fetch(`/api/public/lecturers?${sp.toString()}`, {
    method: "GET",
    headers: { Accept: "application/json" },
  });

  if (!res.ok) {
    const text = await res.text().catch(() => "");
    throw new Error(text || `HTTP ${res.status}`);
  }
  return res.json();
}

const facultyOptionsLocal = ref<Array<{ value: number; label: string }>>([]);
const academicYearOptionsLocal = ref<Array<{ value: number; label: string }>>([]);

async function loadLookups() {
  const lookups = await fetchPublicResearchLookupsApi();
  facultyOptionsLocal.value = lookups.faculties.map((f) => ({ value: f.id, label: f.name }));
  academicYearOptionsLocal.value = lookups.academic_years.map((y) => ({ value: y.id, label: y.code }));
}

/** layout */
const router = useRouter();
const route = useRoute();
const userStore = useUserStore();

onMounted(async () => {
  if (!userStore.isInitialized) await userStore.ensureAuthInitialized();
});
const EMPTY_COUNTS: PublicLecturerItem["research_works"]["counts_by_kind"] = {
  paper: 0,
  project: 0,
  conference: 0,
  book_only: 0,
  textbook: 0,
  total: 0,
};
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

/** state + data */
const state = reactive({
  q: "",
  departmentId: null as number | null,
  academicYearId: null as number | null,
  page: 1,
  perPage: 12,
});

const items = ref<PublicLecturerItem[]>([]);
const pagination = ref({ page: 1, per_page: 12, total: 0, last_page: 1 });
const loading = ref(false);
const error = ref<string | null>(null);



/** init from query */
function initFromQuery() {
  const q = typeof route.query.q === "string" ? route.query.q : "";
  const departmentId =
    typeof route.query.department_id === "string"
      ? Number(route.query.department_id)
      : null;
  const academicYearId =
    typeof route.query.academic_year_id === "string"
      ? Number(route.query.academic_year_id)
      : null;
  const page =
    typeof route.query.page === "string" ? Number(route.query.page) : 1;

  state.q = q;
  state.departmentId = Number.isFinite(departmentId as any)
    ? departmentId
    : null;
  state.academicYearId = Number.isFinite(academicYearId as any)
    ? academicYearId
    : null;
  state.page = Number.isFinite(page as any) && page > 0 ? page : 1;
}

async function load() {
  loading.value = true;
  error.value = null;

  try {
    const res = await fetchPublicLecturers({
      q: state.q.trim() || undefined,
      department_id: state.departmentId,
      academic_year_id: state.academicYearId,
      page: state.page,
      per_page: state.perPage,
    });

    items.value = res.data.items;
    pagination.value = res.data.pagination;
  } catch (e: any) {
    error.value = e?.message || "Không tải được danh sách giảng viên";
    items.value = [];
    pagination.value = {
      page: 1,
      per_page: state.perPage,
      total: 0,
      last_page: 1,
    };
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  initFromQuery();
  await loadLookups();  
  await load();
});

function syncQuery() {
  const q: Record<string, any> = {};
  if (state.q.trim()) q.q = state.q.trim();
  if (state.departmentId != null) q.department_id = state.departmentId;
  if (state.academicYearId != null) q.academic_year_id = state.academicYearId;
  if (state.page !== 1) q.page = state.page;

  router.replace({ query: q });
}

/** actions */
async function onSearch() {
  state.page = 1;
  syncQuery();
  await load();
}

async function onReset() {
  state.q = "";
  state.departmentId = null;
  state.academicYearId = null;
  state.page = 1;
  syncQuery();
  await load();
}

async function goPage(page: number) {
  state.page = page;
  syncQuery();
  await load();
}

/** map to grid cards */
const lecturerCards = computed<LecturerCard[]>(() => {
  return items.value.map((it) => {
  const c = it.research_works?.counts_by_kind ?? EMPTY_COUNTS;
  const bookTotal = c.book_only + c.textbook;

  return {
    lecturerId: it.id,
    lecturerCode: it.code,
    lecturerName: it.full_name,
    facultyName: it.department_name ?? "",
    lecturerQueryPretty: it.full_name,
    counts: {
      ARTICLE: c.paper,
      BOOK: bookTotal,
      PROJECT: c.project,
      CONFERENCE: c.conference,
      OTHER: 0,
    } as any,
  } as any;
});
});

/** navigation */
function jumpToWorkType(payload: { type: any; lecturerQuery: string }) {
  // Nếu bạn đã chuẩn hoá type: ARTICLE/PROJECT/BOOK/CONFERENCE thì map như sau:
  const routeMap: Record<string, string> = {
    ARTICLE: "/bai-bao-khoa-hoc",
    PROJECT: "/de-tai-nghien-cuu",
    BOOK: "/sach-giao-trinh",
    CONFERENCE: "/hoi-thao-bao-cao-khoa-hoc",
    TEXTBOOK: "/sach-giao-trinh",
    OTHER: "/sach-giao-trinh",
  };

  const path = routeMap[payload.type] ?? "/bai-bao-khoa-hoc";

  router.push({
    path,
    query: { lecturer: payload.lecturerQuery },
  });
}

function openDetail(lecturerCode: string) {
  router.push({ path: `/giang-vien/${encodeURIComponent(lecturerCode)}` });
}
</script>
