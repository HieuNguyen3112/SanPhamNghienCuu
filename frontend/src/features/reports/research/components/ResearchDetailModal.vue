<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/30" @click="$emit('close')"></div>

    <div
      class="absolute left-1/2 top-1/2 w-[min(720px,92vw)] -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white shadow-xl"
    >
      <div
        class="flex items-start justify-between border-b border-slate-100 px-5 py-4"
      >
        <div>
          <h3 class="text-sm font-semibold text-slate-900">
            Chi tiết công trình
          </h3>
          <p class="mt-1 text-xs text-slate-500">
            Modal placeholder — bạn có thể thay bằng UI chi tiết thật
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg px-2 py-1 text-sm text-slate-500 hover:bg-slate-100"
          @click="$emit('close')"
        >
          ✕
        </button>
      </div>

      <div class="px-5 py-4" v-if="work">
        <div class="space-y-3 text-sm">
          <div>
            <p class="text-xs font-medium text-slate-500">Title</p>
            <p class="mt-1 font-medium text-slate-900">{{ work.title }}</p>
          </div>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <p class="text-xs font-medium text-slate-500">Type</p>
              <p class="mt-1 text-slate-800">{{ typeLabel(work.type) }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Year</p>
              <p class="mt-1 text-slate-800">{{ work.year }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Department</p>
              <p class="mt-1 text-slate-800">
                {{ deptNameById.get(work.departmentId) }}
              </p>
            </div>
            <div>
              <p class="text-xs font-medium text-slate-500">Score</p>
              <p class="mt-1 font-medium text-slate-900">
                {{ work.score.toFixed(1) }}
              </p>
            </div>
          </div>

          <div>
            <p class="text-xs font-medium text-slate-500">
              Journal / Conference
            </p>
            <p class="mt-1 text-slate-800">{{ work.venue }}</p>
          </div>

          <div>
            <p class="text-xs font-medium text-slate-500">Lecturer(s)</p>
            <p class="mt-1 text-slate-800">
              {{ lecturerNames(work.lecturerIds) }}
            </p>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 border-t border-slate-100 px-5 py-4">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
          @click="$emit('close')"
        >
          Đóng
        </button>
        <button
          type="button"
          class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
          @click="$emit('close')"
        >
          OK
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
  Department,
  Lecturer,
  ResearchType,
  ResearchWork,
} from "../useResearchMockData";

const props = defineProps<{
  open: boolean;
  work: ResearchWork | null;
  departments: Department[];
  lecturers: Lecturer[];
}>();

defineEmits<{ (event: "close"): void }>();

const deptNameById = computed(
  () => new Map(props.departments.map((d) => [d.id, d.name]))
);
const lecturerNameById = computed(
  () => new Map(props.lecturers.map((l) => [l.id, l.name]))
);

function typeLabel(type: ResearchType) {
  switch (type) {
    case "ISI":
      return "ISI";
    case "SCOPUS":
      return "Scopus";
    case "CONFERENCE":
      return "Hội nghị";
    case "PROJECT":
      return "Đề tài";
    case "BOOK":
      return "Sách / Giáo trình";
  }
}

function lecturerNames(lecturerIds: string[]) {
  return lecturerIds
    .map((id) => lecturerNameById.value.get(id) ?? id)
    .join(", ");
}
</script>
