<template>
  <section class="relative overflow-hidden bg-[#0b2f54]">
    <div
      class="absolute inset-0 bg-gradient-to-br from-[#0b2f54] via-[#123e66] to-[#234a74]"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-2xl"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-rose-500/20 blur-2xl"
      aria-hidden="true"
    />

    <div class="relative mx-auto max-w-6xl px-4 py-10 md:px-6 md:py-14">
      <div class="grid gap-8 lg:grid-cols-12 lg:items-center">
        <!-- LEFT -->
        <div class="lg:col-span-7">
          <div
            class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white/90"
          >
            <span class="inline-flex h-2 w-2 rounded-full bg-rose-400" />
            Hệ thống tra cứu công trình NCKH (Public)
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

          <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="flex items-start gap-3 rounded-2xl bg-white/10 p-4">
              <span
                class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-500/20 text-white"
                >📚</span
              >
              <div>
                <div class="text-sm font-extrabold text-white">
                  Dữ liệu đã phê duyệt
                </div>
                <div class="mt-1 text-xs text-white/75">
                  Chỉ hiển thị công trình hợp lệ.
                </div>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-2xl bg-white/10 p-4">
              <span
                class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white"
                >🔎</span
              >
              <div>
                <div class="text-sm font-extrabold text-white">
                  Tìm kiếm theo bộ lọc
                </div>
                <div class="mt-1 text-xs text-white/75">
                  Giảng viên, khoa, năm học.
                </div>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-2xl bg-white/10 p-4">
              <span
                class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 text-white"
                >🧾</span
              >
              <div>
                <div class="text-sm font-extrabold text-white">
                  Xem chi tiết/PDF
                </div>
                <div class="mt-1 text-xs text-white/75">
                  Mở drawer để xem thông tin.
                </div>
              </div>
            </div>
          </div>

          <div class="mt-7">
            <div class="text-xs font-semibold text-white/80">
              Nhóm dữ liệu (chọn để tìm)
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="it in categoryItems"
                :key="it.key"
                type="button"
                class="rounded-full border px-3 py-1.5 text-xs font-extrabold uppercase tracking-wide"
                :class="
                  it.key === category
                    ? 'border-white/30 bg-white/20 text-white'
                    : 'border-white/20 bg-white/10 text-white hover:bg-white/15'
                "
                @click="$emit('update:category', it.key)"
              >
                {{ it.label }}
              </button>
            </div>
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
                <div class="mt-1 text-xs text-slate-500">
                  Chọn nhóm dữ liệu và bấm tìm kiếm.
                </div>
              </div>
              <span
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#234a74]/10 text-[#234a74]"
                >🔍</span
              >
            </div>

            <form class="mt-4 space-y-3" @submit.prevent="$emit('search')">
              <div>
                <label class="text-xs font-semibold text-slate-600"
                  >Giảng viên (tên/mã)</label
                >
                <input
                  :value="filterState.lecturerQuery"
                  @input="onLecturerQueryInput"
                  type="text"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
                  placeholder="VD: Nguyễn Văn An / GV001"
                />
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
                <label class="text-xs font-semibold text-slate-600"
                  >Năm học</label
                >
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
                @click="$emit('reset')"
              >
                Đặt lại bộ lọc
              </button>
            </form>

            <div
              class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800"
            >
              ⚠️ Chỉ hiển thị công trình đã được khoa và trường phê duyệt.
            </div>
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

const props = defineProps<{
  filterState: PublicResearchFilterState;
  facultyOptions: SelectOption<number | null>[];
  academicYearOptions: SelectOption<number | null>[];
  category: CategoryKey;
}>();

const emit = defineEmits<{
  (e: "update-filter", next: Partial<PublicResearchFilterState>): void;
  (e: "update:category", next: CategoryKey): void;
  (e: "search"): void;
  (e: "reset"): void;
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
  { key: "article", label: "Bài báo" },
  { key: "project", label: "Đề tài" },
  { key: "book", label: "Sách" },
  { key: "conference", label: "Hội thảo" },
];

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
</script>
