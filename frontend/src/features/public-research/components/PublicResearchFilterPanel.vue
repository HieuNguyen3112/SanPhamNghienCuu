<template>
  <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
    <div class="flex flex-col gap-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div>
          <label class="text-xs font-medium text-slate-600">Tên giảng viên (tên/mã)</label>
          <input
            ref="lecturerInputRef"
            :value="filterState.lecturerQuery"
            @input="onLecturerQueryInput"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none ring-0 placeholder:text-slate-400 focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
            placeholder="VD: Nguyễn Văn An / GV001"
          />
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Khoa</label>
          <select
            :value="filterState.facultyId ?? ''"
            @change="onFacultyChange"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
          >
            <option v-for="opt in facultyOptions" :key="String(opt.value)" :value="opt.value ?? ''">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <!-- ✅ Ẩn "Loại công trình" khi tab đã cố định loại -->
        <div v-if="!hideWorkType">
          <label class="text-xs font-medium text-slate-600">Loại công trình</label>
          <select
            :value="filterState.workType ?? ''"
            @change="onWorkTypeChange"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
          >
            <option v-for="opt in workTypeOptions" :key="String(opt.value)" :value="opt.value ?? ''">
              {{ opt.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Năm học</label>
          <select
            :value="filterState.academicYearId ?? ''"
            @change="onAcademicYearChange"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-300 focus:ring-2 focus:ring-[#234a74]/20"
          >
            <option v-for="opt in academicYearOptions" :key="String(opt.value)" :value="opt.value ?? ''">
              {{ opt.label }}
            </option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl bg-[#e11d48] px-4 py-2 text-sm font-extrabold text-white shadow-sm hover:brightness-110 active:scale-[0.99]"
          @click="$emit('search')"
        >
          <Search class="h-4 w-4" />
          Tìm kiếm
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.99]"
          @click="$emit('reset')"
        >
          <RotateCcw class="h-4 w-4" />
          Đặt lại
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { nextTick, onMounted, ref } from "vue";
import type { PublicResearchFilterState, SelectOption } from "../models/publicResearchModels";
import { RotateCcw, Search } from "lucide-vue-next";

type Props = {
  filterState: PublicResearchFilterState;
  facultyOptions: SelectOption<number | null>[];
  workTypeOptions: SelectOption<string | null>[];
  academicYearOptions: SelectOption<number | null>[];
  hideWorkType?: boolean;
  autoFocusLecturer?: boolean;
};

type Emits = {
  (e: "update-filter", next: Partial<PublicResearchFilterState>): void;
  (e: "search"): void;
  (e: "reset"): void;
};

const props = withDefaults(defineProps<Props>(), {
  hideWorkType: false,
  autoFocusLecturer: false,
});

const emit = defineEmits<Emits>();

const lecturerInputRef = ref<HTMLInputElement | null>(null);

onMounted(async () => {
  if (props.autoFocusLecturer) {
    await nextTick();
    lecturerInputRef.value?.focus();
  }
});

function onLecturerQueryInput(event: Event) {
  emit("update-filter", { lecturerQuery: (event.target as HTMLInputElement).value });
}
function onFacultyChange(event: Event) {
  const raw = (event.target as HTMLSelectElement).value;
  emit("update-filter", { facultyId: raw === "" ? null : Number(raw), page: 1 });
}
function onWorkTypeChange(event: Event) {
  const raw = (event.target as HTMLSelectElement).value;
  emit("update-filter", { workType: raw === "" ? null : (raw as any), page: 1 });
}
function onAcademicYearChange(event: Event) {
  const raw = (event.target as HTMLSelectElement).value;
  emit("update-filter", { academicYearId: raw === "" ? null : Number(raw), page: 1 });
}
</script>