<template>
  <div :class="rootContainerClassName">
    <!-- PAGE SIZE ONLY -->
    <div
      v-if="componentProperties.displayMode === 'PAGE_SIZE_ONLY'"
      class="flex items-center gap-2"
    >
      <label class="text-xs font-medium text-slate-600">
        {{ componentProperties.pageSizeLabel }}
      </label>

      <select
        class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-800 shadow-sm focus:border-slate-400 focus:ring-0"
        :value="componentProperties.pageSize"
        @change="updatePageSize(($event.target as HTMLSelectElement).value)"
      >
        <option
          v-for="pageSizeOption in componentProperties.pageSizeOptionList"
          :key="pageSizeOption"
          :value="pageSizeOption"
        >
          {{ pageSizeOption }}
        </option>
      </select>
    </div>

    <!-- PAGINATION ONLY / FULL -->
    <div v-else :class="paginationContainerClassName">
      <!-- ✅ Record summary (tách riêng, nằm bên trái) -->
      <div
        v-if="componentProperties.showRecordSummary"
        class="text-xs text-slate-600"
      >
        {{ recordSummaryText }}
      </div>

      <!-- Pagination buttons -->
      <div class="flex items-center justify-between gap-2 md:justify-end">
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            :disabled="isFirstPage"
            @click="updateCurrentPageNumber(1)"
            aria-label="Trang đầu"
            title="Trang đầu"
          >
            <ChevronsLeft class="h-4 w-4" />
          </button>

          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            :disabled="isFirstPage"
            @click="
              updateCurrentPageNumber(componentProperties.currentPageNumber - 1)
            "
            aria-label="Trang trước"
            title="Trang trước"
          >
            <ChevronLeft class="h-4 w-4" />
          </button>

          <div class="hidden items-center gap-1 md:flex">
            <template
              v-for="pageNumber in pageNumberDisplayList"
              :key="String(pageNumber)"
            >
              <span
                v-if="pageNumber === 'ELLIPSIS'"
                class="px-2 text-sm text-slate-400"
              >
                …
              </span>

              <button
                v-else
                type="button"
                class="min-w-[38px] rounded-xl border px-3 py-1.5 text-sm font-semibold"
                :class="
                  pageNumber === componentProperties.currentPageNumber
                    ? 'border-slate-300 bg-slate-900 text-white'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                "
                @click="updateCurrentPageNumber(pageNumber)"
              >
                {{ pageNumber }}
              </button>
            </template>
          </div>

          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            :disabled="isLastPage"
            @click="
              updateCurrentPageNumber(componentProperties.currentPageNumber + 1)
            "
            aria-label="Trang sau"
            title="Trang sau"
          >
            <ChevronRight class="h-4 w-4" />
          </button>

          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            :disabled="isLastPage"
            @click="updateCurrentPageNumber(totalPageCount)"
            aria-label="Trang cuối"
            title="Trang cuối"
          >
            <ChevronsRight class="h-4 w-4" />
          </button>
        </div>

        <!-- FULL: show page size dropdown (đặt cạnh pagination) -->
        <div
          v-if="componentProperties.displayMode === 'FULL'"
          class="ml-2 hidden items-center gap-2 md:flex"
        >
          <label class="text-xs font-medium text-slate-600">
            {{ componentProperties.pageSizeLabel }}
          </label>
          <select
            class="rounded-xl border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-800 shadow-sm focus:border-slate-400 focus:ring-0"
            :value="componentProperties.pageSize"
            @change="updatePageSize(($event.target as HTMLSelectElement).value)"
          >
            <option
              v-for="pageSizeOption in componentProperties.pageSizeOptionList"
              :key="pageSizeOption"
              :value="pageSizeOption"
            >
              {{ pageSizeOption }}
            </option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from "lucide-vue-next";

type DisplayMode = "PAGE_SIZE_ONLY" | "PAGINATION_ONLY" | "FULL";
type RecordSummaryMode = "RANGE" | "PAGE_COUNT";

const componentProperties = withDefaults(
  defineProps<{
    totalItemCount: number;
    currentPageNumber: number; // 1-based
    pageSize: number;

    displayMode: DisplayMode;

    pageSizeOptionList?: number[];
    pageSizeLabel?: string;

    showRecordSummary?: boolean;
    recordSummaryMode?: RecordSummaryMode;
    recordSummaryUnitLabel?: string;

    /** Optional: cho bạn “nắn” layout theo từng page */
    containerClassName?: string;
  }>(),
  {
    pageSizeOptionList: () => [8, 12, 20, 50],
    pageSizeLabel: "Dòng / trang",

    showRecordSummary: true,
    recordSummaryMode: "RANGE",
    recordSummaryUnitLabel: "bản ghi",

    containerClassName: "",
  }
);

const componentEvents = defineEmits<{
  (eventName: "update:currentPageNumber", currentPageNumber: number): void;
  (eventName: "update:pageSize", pageSize: number): void;
}>();

const totalPageCount = computed<number>(() => {
  return Math.max(
    1,
    Math.ceil(componentProperties.totalItemCount / componentProperties.pageSize)
  );
});

const isFirstPage = computed<boolean>(
  () => componentProperties.currentPageNumber <= 1
);
const isLastPage = computed<boolean>(
  () => componentProperties.currentPageNumber >= totalPageCount.value
);

const firstItemNumberInCurrentPage = computed<number>(() => {
  if (componentProperties.totalItemCount === 0) return 0;
  return (
    (componentProperties.currentPageNumber - 1) * componentProperties.pageSize +
    1
  );
});

const lastItemNumberInCurrentPage = computed<number>(() => {
  if (componentProperties.totalItemCount === 0) return 0;
  return Math.min(
    componentProperties.totalItemCount,
    componentProperties.currentPageNumber * componentProperties.pageSize
  );
});

/** ✅ Cái bạn cần (tương đương pagedRecords.length) */
const currentPageItemCount = computed<number>(() => {
  if (componentProperties.totalItemCount === 0) return 0;
  const remaining =
    componentProperties.totalItemCount -
    (componentProperties.currentPageNumber - 1) * componentProperties.pageSize;
  return Math.max(0, Math.min(componentProperties.pageSize, remaining));
});

const recordSummaryText = computed<string>(() => {
  const unit = componentProperties.recordSummaryUnitLabel?.trim()
    ? ` ${componentProperties.recordSummaryUnitLabel.trim()}`
    : "";

  if (componentProperties.recordSummaryMode === "PAGE_COUNT") {
    return `Hiển thị ${currentPageItemCount.value} / ${componentProperties.totalItemCount}${unit}`;
  }

  // RANGE (default)
  if (componentProperties.totalItemCount === 0) return `Hiển thị 0 / 0${unit}`;
  return `Hiển thị ${firstItemNumberInCurrentPage.value}–${lastItemNumberInCurrentPage.value} / ${componentProperties.totalItemCount}${unit}`;
});

const pageNumberDisplayList = computed<Array<number | "ELLIPSIS">>(() => {
  const lastPageNumber = totalPageCount.value;
  const currentPageNumber = componentProperties.currentPageNumber;

  if (lastPageNumber <= 7) {
    return Array.from({ length: lastPageNumber }, (_, index) => index + 1);
  }

  const pageNumberSet = new Set<number>([
    1,
    lastPageNumber,
    currentPageNumber,
    currentPageNumber - 1,
    currentPageNumber + 1,
  ]);

  const normalizedPageNumberList = [...pageNumberSet]
    .filter((pageNumber) => pageNumber >= 1 && pageNumber <= lastPageNumber)
    .sort((a, b) => a - b);

  const displayList: Array<number | "ELLIPSIS"> = [];
  for (let index = 0; index < normalizedPageNumberList.length; index += 1) {
    const pageNumber = normalizedPageNumberList[index]!;
    const previousPageNumber = normalizedPageNumberList[index - 1];
    if (previousPageNumber && pageNumber - previousPageNumber > 1)
      displayList.push("ELLIPSIS");
    displayList.push(pageNumber);
  }

  return displayList;
});

function updateCurrentPageNumber(nextPageNumber: number): void {
  const normalizedNextPageNumber = Math.min(
    Math.max(1, nextPageNumber),
    totalPageCount.value
  );
  componentEvents("update:currentPageNumber", normalizedNextPageNumber);
}

function updatePageSize(nextPageSizeValue: string): void {
  const nextPageSize = Number(nextPageSizeValue);
  if (!Number.isFinite(nextPageSize) || nextPageSize <= 0) return;

  // ✅ clamp theo pageSize mới (tránh dùng totalPageCount cũ)
  const nextTotalPageCount = Math.max(
    1,
    Math.ceil(componentProperties.totalItemCount / nextPageSize)
  );

  const currentFirstItemIndexZeroBased =
    (componentProperties.currentPageNumber - 1) * componentProperties.pageSize;

  const nextPageNumber =
    Math.floor(currentFirstItemIndexZeroBased / nextPageSize) + 1;

  componentEvents("update:pageSize", nextPageSize);
  componentEvents(
    "update:currentPageNumber",
    Math.min(Math.max(1, nextPageNumber), nextTotalPageCount)
  );
}

const rootContainerClassName = computed(() => {
  // Root: cho phép page truyền thêm class để canh theo layout
  return [
    "flex flex-col gap-2 md:flex-row md:items-center",
    componentProperties.containerClassName,
  ].join(" ");
});

const paginationContainerClassName = computed(() => {
  // ✅ điểm mấu chốt: cho summary đứng trái, pagination đứng phải
  return "flex w-full flex-col gap-2 md:flex-row md:items-center md:justify-between";
});
</script>
