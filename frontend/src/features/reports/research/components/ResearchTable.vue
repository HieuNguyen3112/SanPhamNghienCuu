<template>
  <div>
    <!-- Controls -->
    <div
      class="mb-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm text-slate-600">
        Hiển thị
        <span class="font-medium text-slate-900">{{ pagedRows.length }}</span> /
        <span class="font-medium text-slate-900">{{ rows.length }}</span>
        công trình
      </div>

      <div class="flex items-center gap-2">
        <label class="text-xs text-slate-600">Dòng/trang</label>
        <select
          class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
          v-model.number="pageSize"
        >
          <option :value="8">8</option>
          <option :value="12">12</option>
          <option :value="20">20</option>
        </select>
      </div>
    </div>

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

    <!-- Pagination -->
    <div class="mt-4 flex items-center justify-between">
      <div class="text-sm text-slate-600">
        Trang <span class="font-medium text-slate-900">{{ page }}</span> /
        <span class="font-medium text-slate-900">{{ totalPages }}</span>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          :disabled="page === 1"
          @click="page = page - 1"
        >
          Trước
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          :disabled="page === totalPages"
          @click="page = page + 1"
        >
          Sau
        </button>
      </div>
    </div>
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
const page = ref(1);
const pageSize = ref(12);

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
