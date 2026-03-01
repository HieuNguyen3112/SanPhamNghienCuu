<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-center gap-2">
      <List class="h-4 w-4 text-slate-700" />
      <div class="text-sm font-semibold text-slate-900">
        {{ labels.sectionTitle }}
      </div>
    </div>

    <div
      v-if="loading"
      class="mt-4 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
    >
      {{ labels.loading }}
    </div>

    <div
      v-else-if="error"
      class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div v-else-if="batches.length === 0" class="mt-4 text-sm text-slate-600">
      {{ labels.empty }}
    </div>

    <div v-else class="mt-4 overflow-hidden rounded-xl border border-slate-200">
      <div class="max-h-[520px] overflow-auto">
        <table class="w-full min-w-[980px] text-left text-sm">
          <thead
            class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"
          >
            <tr>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">{{ labels.colBatch }}</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">{{ labels.colYear }}</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">{{ labels.colStatus }}</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">{{ labels.colSubmitted }}</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3">{{ labels.colDecided }}</th>
              <th class="sticky top-0 z-10 bg-slate-50 px-4 py-3 text-right">{{ labels.colTotal }}</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="batch in batches"
              :key="batch.batchId"
              class="cursor-pointer hover:bg-slate-50"
              @click="emit('open-detail', batch.batchId)"
            >
              <td class="px-4 py-3 font-semibold text-slate-900">
                {{ batch.batchName }}
              </td>
              <td class="px-4 py-3 text-slate-700">
                {{ batch.academicYearCode || labels.fallback }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                  :class="statusBadgeClass(batch.status)"
                >
                  {{ statusLabel(batch.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-700">
                {{ formatDate(batch.submittedAt) }}
              </td>
              <td class="px-4 py-3 text-slate-700">
                {{ batch.decidedAt ? formatDate(batch.decidedAt) : labels.fallback }}
              </td>
              <td
                class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900"
              >
                {{ batch.totalHours }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <BatchDetailDrawer
      :open="detailOpen"
      :detail="detail"
      :loading="loadingDetail"
      :error="errorDetail"
      @close="emit('close-detail')"
    />
  </section>
</template>

<script setup lang="ts">
import { List } from "lucide-vue-next";
import BatchDetailDrawer from "./BatchDetailDrawer.vue";
import type {
  HoursApprovalBatchDetail,
  HoursApprovalBatchSummary,
  HoursBatchStatus,
} from "../contracts/HoursOverviewContracts";

const labels = {
  sectionTitle:
    "\u0043\u00e1c \u0111\u1ee3t y\u00eau c\u1ea7u x\u00e9t duy\u1ec7t gi\u1edd NCKH",
  loading: "\u0110ang t\u1ea3i l\u1ecbch s\u1eed...",
  empty: "Ch\u01b0a c\u00f3 \u0111\u1ee3t x\u00e9t duy\u1ec7t n\u00e0o.",
  colBatch: "\u0110\u1ee3t",
  colYear: "N\u0103m h\u1ecdc",
  colStatus: "Tr\u1ea1ng th\u00e1i",
  colSubmitted: "Ng\u00e0y g\u1eedi",
  colDecided: "Ng\u00e0y duy\u1ec7t",
  colTotal: "T\u1ed5ng gi\u1edd",
  fallback: "-",
};

defineProps<{
  batches: HoursApprovalBatchSummary[];
  loading: boolean;
  error: string | null;

  detailOpen: boolean;
  detail: HoursApprovalBatchDetail | null;
  loadingDetail: boolean;
  errorDetail: string | null;
}>();

const emit = defineEmits<{
  (e: "open-detail", batchId: number): void;
  (e: "close-detail"): void;
}>();

function formatDate(value: string): string {
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleDateString();
}

function statusLabel(status: HoursBatchStatus): string {
  if (status === "approved") return "\u0110\u00e3 duy\u1ec7t";
  if (status === "pending") return "Ch\u1edd duy\u1ec7t";
  return "T\u1eeb ch\u1ed1i";
}

function statusBadgeClass(status: HoursBatchStatus): string {
  switch (status) {
    case "approved":
      return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
    case "pending":
      return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    case "rejected":
      return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
    default:
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
  }
}
</script>