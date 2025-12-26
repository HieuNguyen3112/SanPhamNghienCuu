<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm font-semibold text-slate-900">
          Bản nháp đang lưu
        </div>
        <div class="mt-1 text-xs text-slate-500">
          Tiếp tục kê khai các mục bạn đang làm dở.
        </div>
      </div>
    </div>

    <div
      v-if="drafts.length === 0"
      class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4"
    >
      <div class="text-sm font-medium text-slate-900">
        Bạn chưa có bản nháp nào
      </div>
      <div class="mt-1 text-xs text-slate-600">
        Hãy bắt đầu kê khai từ các loại công trình phía trên.
      </div>
    </div>

    <div v-else class="mt-4 overflow-hidden rounded-xl border border-slate-200">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs text-slate-600">
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th>Tên công trình</th>
            <th>Loại</th>
            <th>Cập nhật</th>
            <th class="text-right">Thao tác</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr v-for="d in drafts" :key="d.id" class="hover:bg-slate-50">
            <td class="px-3 py-2">
              <div class="font-medium text-slate-900">{{ d.title }}</div>
              <div class="mt-0.5 text-xs text-slate-500">#{{ d.id }}</div>
            </td>

            <td class="px-3 py-2 text-slate-700">{{ d.typeLabel }}</td>

            <td class="px-3 py-2 text-slate-700">
              {{ formatDateVietnamese(d.updatedAt) }}
            </td>

            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
                @click="emit('continue', d.to)"
              >
                <Pencil class="h-4 w-4" />
                Tiếp tục
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Pencil } from "lucide-vue-next";
import type { DraftDeclarationItem } from "../contracts/ResearchDeclarationGateway.contract";
import { formatDateVietnamese } from "../contracts/ResearchDeclarationGateway.contract";

defineProps<{
  drafts: DraftDeclarationItem[];
}>();

const emit = defineEmits<{
  (e: "continue", to: string): void;
}>();
</script>
