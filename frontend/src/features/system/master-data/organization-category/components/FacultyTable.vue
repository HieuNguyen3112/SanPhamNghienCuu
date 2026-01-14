<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div
      class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm font-semibold text-slate-900">Danh sách khoa</div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative">
          <Search
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            :value="search"
            @input="
              $emit('update:search', ($event.target as HTMLInputElement).value)
            "
            type="text"
            placeholder="Tìm theo mã/tên khoa..."
            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-300 focus:outline-none sm:w-[280px]"
          />
        </div>

        <button
          v-if="allowCreate"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
          @click="$emit('create')"
        >
          <Plus class="h-4 w-4" />
          Thêm khoa
        </button>
      </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
      <div class="max-h-[560px] overflow-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="sticky top-0 z-10 bg-slate-50">
            <tr class="text-xs font-semibold text-slate-600">
              <th class="w-[72px] px-4 py-3">STT</th>
              <th class="px-4 py-3">M? khoa</th>
              <th class="px-4 py-3">Tên khoa</th>
              <th class="px-4 py-3">Tên viết tắt</th>
              <th class="px-4 py-3">Trạng thái</th>
              <th class="px-4 py-3">Ngày cập nhật</th>
              <th class="w-[120px] px-4 py-3 text-right">Thao tác</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="px-4 py-8 text-center text-slate-600">
                Đang tải dữ liệu...
              </td>
            </tr>

            <tr v-else-if="items.length === 0">
              <td colspan="7" class="px-4 py-10 text-center">
                <div class="text-sm font-semibold text-slate-900">
                  Không có dữ liệu
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  Thử đổi bộ lọc hoặc từ khóa tìm kiếm.
                </div>
              </td>
            </tr>

            <tr
              v-for="(row, idx) in items"
              :key="row.id"
              class="border-t border-slate-200 hover:bg-slate-50"
            >
              <td class="px-4 py-3 text-slate-700">
                {{ (currentPageNumber - 1) * pageSize + idx + 1 }}
              </td>
              <td class="px-4 py-3 font-medium text-slate-900">
                {{ row.code }}
              </td>
              <td class="px-4 py-3 text-slate-800">
                {{ row.name }}
              </td>

              <td class="px-4 py-3 text-slate-500" title="Chưa có cột trong DB">
                -
              </td>

              <td class="px-4 py-3 text-slate-500" title="Chưa có cột trong DB">
                -
              </td>

              <td class="px-4 py-3 text-slate-700">
                {{ formatDateTime(row.updatedAt) }}
              </td>

              <td class="px-4 py-3 text-right">
                <button
                  v-if="allowEdit"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                  @click="$emit('edit', row)"
                >
                  <Pencil class="h-4 w-4" />
                  Sửa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="border-t border-slate-200 p-3">
        <SharedPaginationControls
          display-mode="FULL"
          :total-item-count="totalItemCount"
          :current-page-number="currentPageNumber"
          :page-size="pageSize"
          record-summary-unit-label="bản ghi"
          @update:currentPageNumber="$emit('update:currentPageNumber', $event)"
          @update:pageSize="$emit('update:pageSize', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Search, Plus, Pencil } from "lucide-vue-next";
import type { Faculty } from "../contracts/organizationCategory.contract";
import { formatDateTime } from "../contracts/organizationCategory.contract";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";

withDefaults(
  defineProps<{
    loading: boolean;
    items: Faculty[];

    totalItemCount: number;
    currentPageNumber: number; // 1-based
    pageSize: number;

    search: string;
    allowCreate?: boolean;
    allowEdit?: boolean;
  }>(),
  {
    allowCreate: true,
    allowEdit: true,
  }
);

defineEmits<{
  (e: "update:search", value: string): void;
  (e: "update:currentPageNumber", value: number): void;
  (e: "update:pageSize", value: number): void;

  (e: "create"): void;
  (e: "edit", item: Faculty): void;
}>();
</script>
