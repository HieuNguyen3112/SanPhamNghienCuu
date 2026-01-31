<template>
  <div>
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
                :sort-key="'category'"
                :sort="sort"
                @change="setSort"
              />
            </th>

            <th class="w-[20%] px-4 py-3">Tạp chí / Hội nghị</th>

            <th class="w-[18%] px-4 py-3">
              <SortHeader
                label="Giảng viên"
                :sort-key="'lecturer'"
                :sort="sort"
                @change="setSort"
              />
            </th>

            <th class="w-[12%] px-4 py-3">
              <SortHeader
                label="Khoa / Đơn vị"
                :sort-key="'department'"
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

        <tbody v-if="loading">
          <tr>
            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
              Đang tải dữ liệu...
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr
            v-for="work in rows"
            :key="work.id"
            class="cursor-pointer border-t border-slate-100 text-sm text-slate-800 hover:bg-slate-50"
            @click="emit('row-click', work)"
          >
            <td class="px-4 py-3">
              <div class="line-clamp-2 font-medium text-slate-900">
                {{ work.title }}
              </div>
              <div v-if="work.activityCode" class="mt-1 text-xs text-slate-500">
                #{{ work.activityCode }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">
              {{ work.categoryLabel }}
            </td>

            <td class="px-4 py-3">
              <div class="line-clamp-2 text-slate-700">
                {{ work.venueLabel }}
              </div>
            </td>

            <td class="px-4 py-3">
              <div class="line-clamp-2 text-slate-700">
                {{ work.lecturerNames }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">
              {{ work.departmentName }}
            </td>

            <td class="px-4 py-3 text-slate-700">
              {{ work.year ?? "-" }}
            </td>
          </tr>

          <tr v-if="rows.length === 0" class="border-t border-slate-100">
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
        :total-item-count="pagination.total"
        :current-page-number="pagination.page"
        :page-size="pagination.perPage"
        display-mode="FULL"
        :show-record-summary="true"
        record-summary-mode="PAGE_COUNT"
        record-summary-unit-label="công trình"
        @update:currentPageNumber="(v) => emit('pageChanged', v)"
        @update:pageSize="(v) => emit('pageSizeChanged', v)"
      />
    </div>
  </div>
</template>


<script setup lang="ts">
import { computed, defineComponent, h, type PropType } from "vue";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import type {
  ResearchReportPagination,
  ResearchReportRow,
  ResearchReportSortCondition,
  ResearchReportSortField,
} from "../researchReportTypes";

type SortState = ResearchReportSortCondition;

const props = defineProps<{
  rows: ResearchReportRow[];
  pagination: ResearchReportPagination;
  sort: SortState;
  loading: boolean;
}>();

const emit = defineEmits<{
  (event: "row-click", work: ResearchReportRow): void;
  (event: "sortChanged", sort: SortState): void;
  (event: "pageChanged", page: number): void;
  (event: "pageSizeChanged", pageSize: number): void;
}>();

function setSort(nextKey: ResearchReportSortField) {
  if (props.sort.sortFieldIdentifier === nextKey) {
    const nextDirection = props.sort.sortDirection === "asc" ? "desc" : "asc";
    const nextSort: SortState = {
      sortFieldIdentifier: nextKey,
      sortDirection: nextDirection,
    };
    return emitSort(nextSort);
  }

  emitSort({ sortFieldIdentifier: nextKey, sortDirection: "asc" });
}

function emitSort(nextSort: SortState) {
  emit("sortChanged", nextSort);
}

const SortHeader = defineComponent({
  name: "SortHeader",
  props: {
    label: { type: String, required: true },
    sortKey: { type: String as PropType<ResearchReportSortField>, required: true },
    sort: { type: Object as PropType<SortState>, required: true },
  },
  emits: {
    change: (_key: ResearchReportSortField) => true,
  },
  setup(componentProps, { emit }) {
    const isActive = computed(
      () => componentProps.sort.sortFieldIdentifier === componentProps.sortKey
    );
    const icon = computed(() => {
      if (!isActive.value) return "↕";
      return componentProps.sort.sortDirection === "asc" ? "↑" : "↓";
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
