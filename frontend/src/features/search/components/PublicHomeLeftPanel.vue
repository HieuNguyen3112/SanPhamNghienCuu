<template>
  <div class="space-y-4">
    <!-- NEWS -->
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="flex items-center gap-2 bg-[#234a74] px-4 py-3 text-sm font-bold text-white">
        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-white/15">🌐</span>
        Tin tức
      </div>

      <div class="p-4">
        <ul class="space-y-2 text-sm text-slate-700">
          <li class="hover:text-slate-900">Thông báo chung</li>
          <li class="hover:text-slate-900">Thông báo từ phòng KHTC</li>
          <li class="hover:text-slate-900">Kế hoạch xét duyệt công trình</li>
          <li class="hover:text-slate-900">Hướng dẫn kê khai & minh chứng</li>
          <li class="hover:text-slate-900">Lịch họp khoa / trường</li>
          <li class="hover:text-slate-900">Thông báo nổi bật</li>
        </ul>
      </div>
    </section>

    <!-- QUICK SUGGESTIONS -->
    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="text-sm font-bold text-slate-900">Gợi ý tra cứu nhanh</div>
      <div class="mt-1 text-xs text-slate-500">
        Bấm một chip để áp dụng bộ lọc và xem kết quả.
      </div>

      <div class="mt-4 space-y-4">
        <!-- Faculty chips -->
        <div>
          <div class="text-xs font-semibold text-slate-600">Theo khoa</div>
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="opt in facultyChips"
              :key="String(opt.value)"
              type="button"
              class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
              @click="applyFaculty(opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <!-- Work type chips -->
        <div>
          <div class="text-xs font-semibold text-slate-600">Loại công trình</div>
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="opt in workTypeChips"
              :key="String(opt.value)"
              type="button"
              class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
              @click="applyWorkType(opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <!-- Academic year chips -->
        <div>
          <div class="text-xs font-semibold text-slate-600">Năm học</div>
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="opt in yearChips"
              :key="String(opt.value)"
              type="button"
              class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
              @click="applyYear(opt.value)"
            >
              {{ opt.label }}
            </button>
          </div>
        </div>

        <button
          type="button"
          class="w-full rounded-lg bg-[#234a74] px-4 py-2 text-sm font-bold text-white hover:brightness-110"
          @click="$emit('scroll-to-search')"
        >
          Mở khu tra cứu
        </button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { SelectOption, PublicResearchFilterState } from "@/features/public-research/models/publicResearchModels";

const props = defineProps<{
  facultyOptions: SelectOption<number | null>[];
    workTypeOptions: SelectOption<PublicResearchFilterState["workType"]>[];
  academicYearOptions: SelectOption<number | null>[];
}>();

const emit = defineEmits<{
  (e: "apply-filter", next: Partial<PublicResearchFilterState>): void;
  (e: "scroll-to-search"): void;
}>();

const facultyChips = computed(() =>
  props.facultyOptions.filter((x) => x.value !== null).slice(0, 4)
);

const workTypeChips = computed(() =>
  props.workTypeOptions.filter((x) => x.value !== null).slice(0, 4)
);

const yearChips = computed(() =>
  props.academicYearOptions.filter((x) => x.value !== null).slice(0, 4)
);

function applyFaculty(value: number | null) {
  emit("apply-filter", { facultyId: value, page: 1 });
}

function applyWorkType(value: PublicResearchFilterState["workType"]) {
  emit("apply-filter", { workType: value, page: 1 });
}


function applyYear(value: number | null) {
  emit("apply-filter", { academicYearId: value, page: 1 });
}
</script>
