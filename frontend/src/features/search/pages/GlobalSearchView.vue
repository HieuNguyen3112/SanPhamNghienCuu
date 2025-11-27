<!-- src/features/search/pages/GlobalSearchView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Tra cứu công trình khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Tìm kiếm công trình theo tên công trình, tên giảng viên, loại công
        trình, năm công bố,...
      </p>
    </div>

    <!-- Search + bộ lọc -->
    <div
      class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
    >
      <SearchBar
        v-model="query"
        :loading="loading"
        autofocus
        @submit="onSearch"
      />

      <div class="flex flex-wrap gap-3 text-xs text-slate-600">
        <div class="w-full sm:w-40">
          <label class="block text-[11px] font-medium uppercase tracking-wide">
            Loại công trình
          </label>
          <select
            v-model="workTypeFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="ARTICLE">Bài báo</option>
            <option value="PROJECT">Đề tài</option>
            <option value="BOOK">Sách / Giáo trình</option>
            <option value="OTHER">Khác</option>
          </select>
        </div>

        <div class="w-full sm:w-40">
          <label class="block text-[11px] font-medium uppercase tracking-wide">
            Năm công bố
          </label>
          <select
            v-model="yearFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option v-for="year in availableYears" :key="year" :value="year">
              {{ year }}
            </option>
          </select>
        </div>

        <div class="w-full sm:w-56">
          <label class="block text-[11px] font-medium uppercase tracking-wide">
            Đơn vị / Khoa
          </label>
          <select
            v-model="departmentFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả đơn vị</option>
            <option value="CNTT">Khoa Công nghệ thông tin</option>
            <option value="TOAN">Khoa Toán</option>
            <option value="VL">Khoa Vật lý</option>
          </select>
        </div>

        <div class="flex items-end gap-2">
          <button
            type="button"
            class="mt-4 rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
            @click="resetFilters"
          >
            Xóa bộ lọc
          </button>
          <button
            type="button"
            class="mt-4 rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
            @click="onSearch"
          >
            Áp dụng
          </button>
        </div>
      </div>
    </div>

    <!-- Tóm tắt kết quả -->
    <div v-if="!loading" class="text-xs text-slate-500">
      <span v-if="results.length">
        Tìm thấy
        <span class="font-semibold text-slate-800">
          {{ results.length }}
        </span>
        công trình phù hợp.
      </span>
      <span v-else>
        Nhập từ khóa và bấm <span class="font-medium">Tra cứu</span> để bắt đầu.
      </span>
    </div>

    <!-- Danh sách kết quả -->
    <div class="space-y-3">
      <div
        v-if="loading"
        class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500 shadow-sm"
      >
        Đang tìm kiếm công trình...
      </div>

      <div
        v-else-if="!results.length && hasSearched"
        class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"
      >
        Không tìm thấy công trình nào phù hợp với từ khóa và bộ lọc hiện tại.
        <br />
        Vui lòng thử lại với từ khóa khác hoặc nới lỏng điều kiện lọc.
      </div>

      <article
        v-for="item in results"
        v-else
        :key="item.id"
        class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-700 shadow-sm hover:border-sky-200 hover:bg-sky-50/40"
      >
        <header class="flex flex-wrap items-start justify-between gap-2">
          <div>
            <h2 class="text-sm font-semibold text-slate-900">
              {{ item.title }}
            </h2>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ item.lecturerName }} · {{ item.departmentName }}
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <WorkTypeBadge :type="item.workType" />
            <span
              class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"
            >
              Năm {{ item.year }}
            </span>
          </div>
        </header>

        <p v-if="item.abstract" class="text-xs leading-relaxed text-slate-600">
          {{ item.abstract }}
        </p>

        <div
          class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500"
        >
          <div class="flex flex-wrap gap-1">
            <span
              v-for="tag in item.tags"
              :key="tag"
              class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px]"
            >
              #{{ tag }}
            </span>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
              :class="statusClass(item.status)"
            >
              {{ statusLabel(item.status) }}
            </span>
            <span class="text-[11px] text-slate-400">
              Mã công trình: {{ item.id }}
            </span>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import SearchBar from "@/features/search/components/SearchBar.vue";
import WorkTypeBadge from "@/features/declarations/components/WorkTypeBadge.vue";
import type { WorkType } from "@/features/declarations/types";

type WorkStatus = "PUBLISHED" | "IN_REVIEW" | "DRAFT";

interface SearchResultItem {
  id: string;
  title: string;
  workType: WorkType;
  year: number;
  lecturerName: string;
  departmentCode: "CNTT" | "TOAN" | "VL";
  departmentName: string;
  status: WorkStatus;
  tags: string[];
  abstract?: string;
}

// state
const query = ref("");
const workTypeFilter = ref<WorkType | "ALL">("ALL");
const yearFilter = ref<"ALL" | number>("ALL");
const departmentFilter = ref<"ALL" | "CNTT" | "TOAN" | "VL">("ALL");
const loading = ref(false);
const hasSearched = ref(false);

// mock data
const allItems = ref<SearchResultItem[]>([
  {
    id: "CT-001",
    title: "Ứng dụng trí tuệ nhân tạo trong dạy học trực tuyến",
    workType: "ARTICLE",
    year: 2024,
    lecturerName: "Nguyễn Văn A",
    departmentCode: "CNTT",
    departmentName: "Khoa Công nghệ thông tin",
    status: "PUBLISHED",
    tags: ["AI", "e-learning"],
    abstract:
      "Nghiên cứu ứng dụng các mô hình học sâu trong việc cá nhân hóa quá trình học trực tuyến.",
  },
  {
    id: "CT-002",
    title:
      "Đề tài cấp trường: Phân tích dữ liệu học tập để dự báo nguy cơ bỏ học",
    workType: "PROJECT",
    year: 2023,
    lecturerName: "Trần Thị B",
    departmentCode: "CNTT",
    departmentName: "Khoa Công nghệ thông tin",
    status: "IN_REVIEW",
    tags: ["learning analytics", "predictive"],
  },
  {
    id: "CT-003",
    title: "Giáo trình Xác suất Thống kê cho kỹ sư",
    workType: "BOOK",
    year: 2022,
    lecturerName: "Lê Văn C",
    departmentCode: "TOAN",
    departmentName: "Khoa Toán",
    status: "PUBLISHED",
    tags: ["giáo trình", "xác suất"],
  },
  {
    id: "CT-004",
    title: "Báo cáo khoa học tại Hội nghị Vật lý toàn quốc",
    workType: "OTHER",
    year: 2024,
    lecturerName: "Phạm Thị D",
    departmentCode: "VL",
    departmentName: "Khoa Vật lý",
    status: "DRAFT",
    tags: ["vật lý", "hội nghị"],
  },
]);

const availableYears = computed(() => {
  const years = Array.from(new Set(allItems.value.map((x) => x.year))).sort(
    (a, b) => b - a
  );
  return years;
});

const results = ref<SearchResultItem[]>([]);

function applyFilters() {
  let data = [...allItems.value];

  if (query.value.trim()) {
    const q = query.value.toLowerCase();
    data = data.filter(
      (x) =>
        x.title.toLowerCase().includes(q) ||
        x.lecturerName.toLowerCase().includes(q)
    );
  }

  if (workTypeFilter.value !== "ALL") {
    data = data.filter((x) => x.workType === workTypeFilter.value);
  }

  if (yearFilter.value !== "ALL") {
    data = data.filter((x) => x.year === yearFilter.value);
  }

  if (departmentFilter.value !== "ALL") {
    data = data.filter((x) => x.departmentCode === departmentFilter.value);
  }

  results.value = data;
}

function onSearch() {
  hasSearched.value = true;
  loading.value = true;
  // giả lập call API
  setTimeout(() => {
    applyFilters();
    loading.value = false;
  }, 400);
}

function resetFilters() {
  workTypeFilter.value = "ALL";
  yearFilter.value = "ALL";
  departmentFilter.value = "ALL";
  onSearch();
}

function statusLabel(status: WorkStatus): string {
  switch (status) {
    case "PUBLISHED":
      return "Đã công bố";
    case "IN_REVIEW":
      return "Đang phản biện / duyệt";
    case "DRAFT":
      return "Bản thảo / chưa công bố";
    default:
      return status;
  }
}

function statusClass(status: WorkStatus): string {
  switch (status) {
    case "PUBLISHED":
      return "bg-emerald-50 text-emerald-700 border border-emerald-100";
    case "IN_REVIEW":
      return "bg-amber-50 text-amber-700 border border-amber-100";
    case "DRAFT":
      return "bg-slate-50 text-slate-600 border border-slate-100";
    default:
      return "bg-slate-50 text-slate-600 border border-slate-100";
  }
}
</script>
