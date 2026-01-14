<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 md:p-6">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-2">
          <Lightbulb class="h-5 w-5 text-amber-700" />
          <h3 class="text-sm font-semibold text-slate-900">Gợi ý cho bạn</h3>
        </div>
        <p class="mt-1 text-sm text-slate-600">
          Một vài hành động nhanh giúp xử lý các cảnh báo liên quan đến giờ NCKH.
        </p>
      </div>
    </div>

    <div v-if="loading" class="mt-4 text-sm text-slate-700">
      Đang tải gợi ý...
    </div>

    <div
      v-else-if="error"
      class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4"
    >
      <div class="text-sm font-semibold text-rose-700">
        Không tải được gợi ý
      </div>
      <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
        {{ error }}
      </div>
    </div>

    <div
      v-else-if="suggestions.length === 0"
      class="mt-4 text-sm text-slate-700"
    >
      Chưa có gợi ý nào.
    </div>

    <div v-else class="mt-4 space-y-3">
      <div
        v-for="item in suggestions"
        :key="item.id"
        class="rounded-2xl border border-slate-200 bg-slate-50/40 p-4"
      >
        <div class="text-sm font-semibold text-slate-900">
          {{ item.title }}
        </div>
        <div class="mt-1 text-sm text-slate-700">
          {{ item.description }}
        </div>

        <div class="mt-3 flex items-center gap-2">
          <RouterLink
            v-if="item.ctaTo && item.ctaLabel && !isExternal(item.ctaTo)"
            :to="item.ctaTo"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
          >
            <ArrowRight class="h-4 w-4" />
            {{ item.ctaLabel }}
          </RouterLink>

          <a
            v-else-if="item.ctaTo && item.ctaLabel"
            :href="item.ctaTo"
            target="_blank"
            rel="noreferrer"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
          >
            <ArrowRight class="h-4 w-4" />
            {{ item.ctaLabel }}
          </a>

          <button
            v-else
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-400"
            disabled
          >
            <ArrowRight class="h-4 w-4" />
            Không có hành động
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HoursAlertActionSuggestion } from "../contracts/hoursWarning.contract";
import { ArrowRight, Lightbulb } from "lucide-vue-next";

defineProps<{
  suggestions: HoursAlertActionSuggestion[];
  loading: boolean;
  error: string | null;
}>();

const isExternal = (path: string) => path.startsWith("http");
</script>
