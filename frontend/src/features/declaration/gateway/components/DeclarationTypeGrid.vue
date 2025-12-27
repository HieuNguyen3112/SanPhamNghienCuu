<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-center justify-between">
      <div>
        <div class="text-sm font-semibold text-slate-900">
          Chọn loại công trình
        </div>
        <div class="mt-1 text-xs text-slate-500">
          Chạm vào card hoặc nhấn “Kê khai” để đi tới trang tương ứng.
        </div>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="card in types"
        :key="card.typeKey"
        class="group cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 hover:shadow-sm"
        @click="emit('navigate', card.to)"
      >
        <div class="flex items-start gap-3">
          <div class="rounded-xl bg-slate-100 p-2 text-slate-700">
            <component v-if="card.icon" :is="card.icon" class="h-5 w-5" />
          </div>

          <div class="min-w-0">
            <div class="text-sm font-semibold text-slate-900">
              {{ card.title }}
            </div>

            <ul class="mt-2 space-y-1 text-xs text-slate-600">
              <li
                v-for="line in card.descriptionLines"
                :key="line"
                class="flex gap-2"
              >
                <span
                  class="mt-1.5 inline-block h-1.5 w-1.5 rounded-full bg-slate-300"
                />
                <span class="min-w-0">{{ line }}</span>
              </li>
            </ul>

            <button
              type="button"
              class="mt-4 inline-flex items-center justify-center rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-800"
              @click.stop="emit('navigate', card.to)"
            >
              Kê khai
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { DeclarationTypeCard } from "../contracts/ResearchDeclarationGateway.contract";

defineProps<{
  types: DeclarationTypeCard[];
}>();

const emit = defineEmits<{
  (e: "navigate", to: string): void;
}>();
</script>
