<template>
  <div>
    <!-- Controls -->

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200">
      <table class="w-full table-fixed">
        <thead class="bg-slate-50">
          <tr class="text-left text-xs font-semibold text-slate-600">
            <th class="w-[34%] px-4 py-3">
              <SortHeader
                label="Tiêu đề"
                :sort-key="'title'"
                :sort="sort"
                @change="setSort"
              />
            </th>

            <th class="w-[10%] px-4 py-3">
              <SortHeader
                label="Loại"
                :sort-key="'type'"
                :sort="sort"
                @change="setSort"
              />
            </th>

            <th class="w-[20%] px-4 py-3">Tạp chí / Hội nghị</th>

            <th class="w-[18%] px-4 py-3">Giảng viên</th>

            <th class="w-[12%] px-4 py-3">
              <SortHeader
                label="Khoa / Đơn vị"
                :sort-key="'departmentId'"
                :sort="sort"
                @change="setSort"
              />
            </th>

            <th class="w-[6%] px-4 py-3">
              <SortHeader
                label="Năm"
                :sort-key="'year'"
                :sort="sort"
                @change="setSort"
              />
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="work in pagedRows"
            :key="work.id"
            class="cursor-pointer border-t border-slate-100 text-sm text-slate-800 hover:bg-slate-50"
            @click="$emit('row-click', work)"
          >
            <td class="px-4 py-3">
              <div class="line-clamp-2 font-medium text-slate-900">
                {{ work.title }}
              </div>
              <div class="mt-1 text-xs text-slate-500">#{{ work.id }}</div>
            </td>

            <td class="px-4 py-3 text-slate-700">
              {{ typeLabel(work.type) }}
            </td>

            <td class="px-4 py-3">
              <div class="line-clamp-2 text-slate-700">{{ work.venue }}</div>
            </td>

            <td class="px-4 py-3">
              <div class="line-clamp-2 text-slate-700">
                {{ lecturerNames(work.lecturerIds) }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">
              {{ deptNameById.get(work.departmentId) }}
            </td>

            <td class="px-4 py-3 text-slate-700">
              {{ work.year }}
            </td>
          </tr>

          <tr v-if="pagedRows.length === 0" class="border-t border-slate-100">
            <td
              class="px-4 py-10 text-center text-sm text-slate-500"
              colspan="6"
            >
              Không có dữ liệu theo bộ lọc hiện tại
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="border-t border-slate-200 px-4 py-3">
      <SharedPaginationControls
        :total-item-count="sortedRows.length"
        :current-page-number="page"
        :page-size="pageSize"
        display-mode="FULL"
        :show-record-summary="true"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="công trình"
        @update:currentPageNumber="(v) => (page = v)"
        @update:pageSize="(v) => (pageSize = v)"
      />
    </div>
    <!-- Pagination -->
    <!-- Pagination (FULL: có summary + page buttons + page size) -->
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, ref, watch, type PropType } from "vue";
import type {
  Department,
  Lecturer,
  ResearchType,
  ResearchWork,
} from "../useResearchMockData";
import SharedPaginationControls from "@/shared/components/SharedPaginationControls.vue";

type SortKey = keyof Pick<
  ResearchWork,
  "title" | "type" | "departmentId" | "year"
>;
type SortState = { key: SortKey; direction: "asc" | "desc" };

const props = defineProps<{
  rows: ResearchWork[];
  departments: Department[];
  lecturers: Lecturer[];
}>();

defineEmits<{
  (event: "row-click", work: ResearchWork): void;
}>();

const deptNameById = computed(
  () => new Map(props.departments.map((dept) => [dept.id, dept.name]))
);
const lecturerNameById = computed(
  () => new Map(props.lecturers.map((lec) => [lec.id, lec.name]))
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
      return "Sách/GT";
  }
}
const page = ref(1);
const pageSize = ref(12);

function lecturerNames(lecturerIds: string[]) {
  return lecturerIds
    .map((id) => lecturerNameById.value.get(id) ?? id)
    .join(", ");
}

/** Sorting */
const sort = ref<SortState>({ key: "year", direction: "desc" });

function setSort(nextKey: SortKey) {
  if (sort.value.key === nextKey) {
    sort.value.direction = sort.value.direction === "asc" ? "desc" : "asc";
    return;
  }
  sort.value.key = nextKey;
  sort.value.direction = "asc";
}

const sortedRows = computed(() => {
  const directionFactor = sort.value.direction === "asc" ? 1 : -1;
  const key = sort.value.key;

  return [...props.rows].sort((left, right) => {
    const a = left[key];
    const b = right[key];

    if (typeof a === "number" && typeof b === "number")
      return (a - b) * directionFactor;
    return String(a).localeCompare(String(b), "vi") * directionFactor;
  });
});

/** Pagination */

const totalPages = computed(() =>
  Math.max(1, Math.ceil(sortedRows.value.length / pageSize.value))
);

watch([() => props.rows, pageSize], () => {
  page.value = 1;
});

watch(totalPages, () => {
  if (page.value > totalPages.value) page.value = totalPages.value;
});

const pagedRows = computed(() => {
  const start = (page.value - 1) * pageSize.value;
  const end = start + pageSize.value;
  return sortedRows.value.slice(start, end);
});

/** ✅ Sort header (no JSX, render bằng h()) */
const SortHeader = defineComponent({
  name: "SortHeader",
  props: {
    label: { type: String, required: true },
    sortKey: { type: String as PropType<SortKey>, required: true },
    sort: { type: Object as PropType<SortState>, required: true },
  },
  emits: {
    change: (_key: SortKey) => true,
  },
  setup(componentProps, { emit }) {
    const isActive = computed(
      () => componentProps.sort.key === componentProps.sortKey
    );
    const icon = computed(() => {
      if (!isActive.value) return "↕";
      return componentProps.sort.direction === "asc" ? "↑" : "↓";
    });

    return () =>
      h(
        "button",
        {
          type: "button",
          class:
            "inline-flex items-center gap-2 rounded-lg px-1 py-0.5 text-left hover:text-slate-900",
          onClick: () => emit("change", componentProps.sortKey),
        },
        [
          h("span", { class: "truncate" }, componentProps.label),
          h(
            "span",
            { class: "text-[10px] font-semibold text-slate-400" },
            icon.value
          ),
        ]
      );
  },
});
</script>
