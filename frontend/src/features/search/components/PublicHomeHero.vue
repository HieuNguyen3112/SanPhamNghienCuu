<template>
  <section class="relative overflow-hidden bg-[#0b2f54]">
    <div
      class="absolute inset-0 bg-gradient-to-br from-[#0b2f54] via-[#123e66] to-[#234a74]"
      aria-hidden="true"
    />

    <div class="relative mx-auto max-w-6xl px-4 py-10 md:px-6 md:py-14">
      <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
        <!-- LEFT -->
        <div class="lg:col-span-7">
          <div
            class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/90"
          >
            <span class="inline-flex h-2 w-2 rounded-full bg-rose-400" />
            Hệ thống tra cứu công trình NCKH
          </div>

          <h1
            class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-white md:text-4xl"
          >
            Cơ sở dữ liệu
            <span class="text-rose-300">Khoa học &amp; Công nghệ</span>
          </h1>

          <p
            class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80 md:text-base"
          >
            Tra cứu nhanh các công trình đã được khoa và trường phê duyệt.
          </p>

          <!-- Tổng giảng viên -->
          <div class="mt-6">
            <div
              class="flex items-center justify-between rounded-2xl bg-white/12 px-5 py-4 ring-1 ring-white/10 backdrop-blur-sm"
            >
              <div class="flex items-center gap-3">
                <span
                  class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-500/20 text-xl text-white"
                >
                  👨‍🏫
                </span>
                <div>
                  <div class="text-sm font-semibold text-white/80">
                    Tổng số giảng viên
                  </div>
                  <div class="mt-1 text-2xl font-extrabold text-white">
                    {{ formatStatValue(overviewStats.lecturerCount) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 4 nhóm dữ liệu -->
          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <button
              type="button"
              class="rounded-2xl bg-white/10 p-4 text-left ring-1 ring-white/10 transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/30"
              @click="emit('open-list', 'article')"
            >
              <div class="flex items-start gap-3">
                <span
                  class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/20 text-white"
                >
                  📄
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-extrabold text-white">
                    Bài báo khoa học
                  </div>
                  <div class="mt-1 text-2xl font-extrabold text-rose-200">
                    {{ formatStatValue(overviewStats.articleCount) }}
                  </div>
                  <div class="mt-1 text-xs text-white/70">
                    Công trình dạng bài báo
                  </div>
                </div>
              </div>
            </button>

            <button
              type="button"
              class="rounded-2xl bg-white/10 p-4 text-left ring-1 ring-white/10 transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/30"
              @click="emit('open-list', 'project')"
            >
              <div class="flex items-start gap-3">
                <span
                  class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white"
                >
                  💡
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-extrabold text-white">
                    Đề tài nghiên cứu
                  </div>
                  <div class="mt-1 text-2xl font-extrabold text-rose-200">
                    {{ formatStatValue(overviewStats.projectCount) }}
                  </div>
                  <div class="mt-1 text-xs text-white/70">Đề tài các cấp</div>
                </div>
              </div>
            </button>

            <button
              type="button"
              class="rounded-2xl bg-white/10 p-4 text-left ring-1 ring-white/10 transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/30"
              @click="emit('open-list', 'book')"
            >
              <div class="flex items-start gap-3">
                <span
                  class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white"
                >
                  📚
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-extrabold text-white">
                    Sách - Giáo trình
                  </div>
                  <div class="mt-1 text-2xl font-extrabold text-rose-200">
                    {{ formatStatValue(overviewStats.bookCount) }}
                  </div>
                  <div class="mt-1 text-xs text-white/70">
                    Sách, giáo trình, tài liệu
                  </div>
                </div>
              </div>
            </button>

            <button
              type="button"
              class="rounded-2xl bg-white/10 p-4 text-left ring-1 ring-white/10 transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/30"
              @click="emit('open-list', 'conference')"
            >
              <div class="flex items-start gap-3">
                <span
                  class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white"
                >
                  🎤
                </span>
                <div class="min-w-0">
                  <div class="text-sm font-extrabold text-white">
                    Hội thảo - Báo cáo
                  </div>
                  <div class="mt-1 text-2xl font-extrabold text-rose-200">
                    {{ formatStatValue(overviewStats.conferenceCount) }}
                  </div>
                  <div class="mt-1 text-xs text-white/70">
                    Báo cáo và hội thảo khoa học
                  </div>
                </div>
              </div>
            </button>
          </div>
        </div>

        <!-- RIGHT: search card -->
        <div class="lg:col-span-5">
          <div class="rounded-2xl bg-white p-5 shadow-lg ring-1 ring-black/5">
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="text-sm font-extrabold text-slate-900">
                  Tìm kiếm dữ liệu
                </div>
              </div>
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="emit('search')">
              <div>
                <label class="text-xs font-semibold text-slate-600">
                  Giảng viên (tên/mã)
                </label>
                <input
                  :value="filterState.lecturerQuery"
                  @input="onLecturerQueryInput"
                  type="text"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
                  placeholder="Nguyễn Văn An / GV-001"
                />
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-600">
                  Nhóm dữ liệu
                </label>
                <select
                  :value="category"
                  @change="onCategoryChange"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
                >
                  <option
                    v-for="it in categoryItems"
                    :key="it.key"
                    :value="it.key"
                  >
                    {{ it.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-600">Khoa</label>
                <select
                  :value="selectedFacultyValue"
                  @change="onFacultyChange"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
                >
                  <option
                    v-for="opt in normalizedFacultyOptions"
                    :key="String(opt.value)"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-600">
                  Năm học
                </label>
                <select
                  :value="selectedAcademicYearValue"
                  @change="onAcademicYearChange"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
                >
                  <option
                    v-for="opt in normalizedAcademicYearOptions"
                    :key="String(opt.value)"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </option>
                </select>
              </div>

              <button
                type="submit"
                class="mt-2 inline-flex h-11 w-full items-center justify-center rounded-xl bg-[#e11d48] px-4 text-sm font-extrabold text-white shadow-sm hover:brightness-110 active:scale-[0.99]"
              >
                Tìm kiếm
              </button>

              <button
                type="button"
                class="w-full text-center text-xs font-semibold text-slate-500 hover:text-slate-700"
                @click="emit('reset')"
              >
                Đặt lại bộ lọc
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
  PublicResearchFilterState,
  SelectOption,
} from "@/features/public-research/models/publicResearchModels";

type CategoryKey = "lecturer" | "article" | "project" | "book" | "conference";

type OverviewStats = {
  lecturerCount: number;
  articleCount: number;
  projectCount: number;
  bookCount: number;
  conferenceCount: number;
};

const props = withDefaults(
  defineProps<{
    filterState: PublicResearchFilterState;
    facultyOptions: SelectOption<number | null>[];
    academicYearOptions: SelectOption<number | null>[];
    category: CategoryKey;
    overviewStats?: OverviewStats;
  }>(),
  {
    overviewStats: () => ({
      lecturerCount: 0,
      articleCount: 0,
      projectCount: 0,
      bookCount: 0,
      conferenceCount: 0,
    }),
  },
);

const emit = defineEmits<{
  (e: "update-filter", next: Partial<PublicResearchFilterState>): void;
  (e: "update:category", next: CategoryKey): void;
  (e: "search"): void;
  (e: "reset"): void;
  (e: "open-list", next: Exclude<CategoryKey, "lecturer">): void;
}>();

const ALL_OPTION_VALUE = "ALL";

const normalizedFacultyOptions = computed(() => {
  const hasAll = props.facultyOptions.some((opt) => opt.value === null);

  if (hasAll) {
    return props.facultyOptions.map((opt) => ({
      value: opt.value === null ? ALL_OPTION_VALUE : String(opt.value),
      label: opt.label,
    }));
  }

  return [
    { value: ALL_OPTION_VALUE, label: "Tất cả khoa" },
    ...props.facultyOptions.map((opt) => ({
      value: String(opt.value),
      label: opt.label,
    })),
  ];
});

const normalizedAcademicYearOptions = computed(() => {
  const hasAll = props.academicYearOptions.some((opt) => opt.value === null);

  if (hasAll) {
    return props.academicYearOptions.map((opt) => ({
      value: opt.value === null ? ALL_OPTION_VALUE : String(opt.value),
      label: opt.label,
    }));
  }

  return [
    { value: ALL_OPTION_VALUE, label: "Tất cả năm học" },
    ...props.academicYearOptions.map((opt) => ({
      value: String(opt.value),
      label: opt.label,
    })),
  ];
});

const selectedFacultyValue = computed(() =>
  props.filterState.facultyId == null
    ? ALL_OPTION_VALUE
    : String(props.filterState.facultyId),
);

const selectedAcademicYearValue = computed(() =>
  props.filterState.academicYearId == null
    ? ALL_OPTION_VALUE
    : String(props.filterState.academicYearId),
);

const categoryItems: Array<{ key: CategoryKey; label: string }> = [
  { key: "lecturer", label: "Giảng viên" },
  { key: "article", label: "Bài báo khoa học" },
  { key: "project", label: "Đề tài nghiên cứu" },
  { key: "book", label: "Sách - Giáo trình" },
  { key: "conference", label: "Hội thảo - Báo cáo khoa học" },
];

function formatStatValue(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(value ?? 0);
}

function onLecturerQueryInput(e: Event) {
  emit("update-filter", {
    lecturerQuery: (e.target as HTMLInputElement).value,
  });
}

function onFacultyChange(e: Event) {
  const raw = (e.target as HTMLSelectElement).value;
  emit("update-filter", {
    facultyId: raw === ALL_OPTION_VALUE ? null : Number(raw),
  });
}

function onAcademicYearChange(e: Event) {
  const raw = (e.target as HTMLSelectElement).value;
  emit("update-filter", {
    academicYearId: raw === ALL_OPTION_VALUE ? null : Number(raw),
  });
}

function onCategoryChange(e: Event) {
  const raw = (e.target as HTMLSelectElement).value as CategoryKey;
  emit("update:category", raw);
}
</script>
