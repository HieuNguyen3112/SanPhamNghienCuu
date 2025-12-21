// File: src/features/lecturer-hours-overview/components/BatchDetailDrawer.vue
<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-slate-900/30"
        aria-hidden="true"
        @click="emit('close')"
      />
    </Transition>

    <Transition
      enter-active-class="transition-transform duration-250 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[620px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900">
                  {{ detail ? detail.batchName : "Chi tiết đợt xét duyệt" }}
                </div>

                <div v-if="detail" class="mt-1 flex items-center gap-2">
                  <span
                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                    :class="statusBadgeClass(detail.status)"
                  >
                    {{ statusLabel(detail.status) }}
                  </span>
                  <span class="text-xs text-slate-500">
                    {{ detail.academicYearCode }}
                  </span>
                </div>
              </div>

              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                ✕
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-4">
            <div
              v-if="loading"
              class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
            >
              Đang tải chi tiết…
            </div>

            <div
              v-else-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
            >
              {{ error }}
            </div>

            <div v-else-if="!detail" class="text-sm text-slate-600">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-4">
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Thông tin chung
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <InfoRow label="Đợt" :value="detail.batchName" />
                  <InfoRow label="Năm học" :value="detail.academicYearCode" />
                  <InfoRow
                    label="Ngày gửi"
                    :value="formatDate(detail.submittedAt)"
                  />
                  <InfoRow
                    label="Ngày duyệt"
                    :value="
                      detail.decidedAt ? formatDate(detail.decidedAt) : '–'
                    "
                  />
                  <InfoRow
                    label="Trạng thái"
                    :value="statusLabel(detail.status)"
                  />
                  <InfoRow
                    label="Tổng giờ"
                    :value="String(detail.totalHours)"
                  />
                </div>

                <div class="mt-3 text-xs text-slate-500">
                  TODO(BE): “đợt xét duyệt” hiện là mock/DTO derived — schema
                  chưa có entity batch.
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Công trình trong đợt
                </div>

                <div
                  v-if="detail.items.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Không có công trình nào.
                </div>

                <div
                  v-else
                  class="mt-3 overflow-hidden rounded-lg border border-slate-200"
                >
                  <table class="w-full text-left text-sm">
                    <thead
                      class="bg-slate-50 text-xs font-semibold text-slate-600"
                    >
                      <tr>
                        <th class="px-3 py-2">Công trình</th>
                        <th class="px-3 py-2">Loại</th>
                        <th class="px-3 py-2 text-right">Giờ quy đổi</th>
                        <th class="px-3 py-2">Trạng thái</th>
                      </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                      <tr v-for="item in detail.items" :key="item.activityId">
                        <td class="px-3 py-2 font-medium text-slate-900">
                          {{ item.title }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ item.kindName }}
                        </td>
                        <td
                          class="px-3 py-2 text-right font-semibold tabular-nums text-slate-900"
                        >
                          {{ item.lecturerHours }}
                        </td>
                        <td class="px-3 py-2">
                          <span
                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                            :class="statusBadgeClass(item.status)"
                          >
                            {{ statusLabel(item.status) }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
            </div>
          </div>

          <div class="border-t border-slate-200 px-4 py-3">
            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              @click="emit('close')"
            >
              ← Quay lại
            </button>
          </div>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import type {
  HoursApprovalBatchDetail,
  HoursBatchStatus,
} from "../contracts/HoursOverviewContracts";
import InfoRow from "./InfoRow.vue";

defineProps<{
  open: boolean;
  loading: boolean;
  error: string | null;
  detail: HoursApprovalBatchDetail | null;
}>();

const emit = defineEmits<{ (e: "close"): void }>();

function formatDate(value: string): string {
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleDateString();
}

function statusLabel(status: HoursBatchStatus): string {
  if (status === "approved") return "Đã duyệt";
  if (status === "pending") return "Chờ duyệt";
  return "Từ chối";
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
