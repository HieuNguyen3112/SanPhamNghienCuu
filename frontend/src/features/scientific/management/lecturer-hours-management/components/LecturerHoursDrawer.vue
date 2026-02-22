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
        @click="emit('close')"
      />
    </Transition>

    <Transition
      enter-active-class="transition-transform duration-200 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-150 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200 sm:w-[720px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900">
                  {{ lecturer?.lecturerFullName ?? "—" }}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ lecturer?.facultyName ?? "—" }}
                  <span class="mx-1">·</span>
                  {{ degreeRankLabel }}
                  <span class="mx-1">·</span>
                  Năm học: {{ academicYearCode }}
                </div>
              </div>

              <button
                type="button"
                class="h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-5">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
              <KpiMini
                title="Tổng giờ NCKH"
                :value="formatHours(hoursTotal)"
                :tone="tone"
              />
              <KpiMini
                title="Định mức"
                :value="formatHours(requiredHours)"
                tone="neutral"
              />
              <KpiMini
                title="Chênh lệch"
                :value="formatSignedHours(difference)"
                :tone="tone"
              />
              <KpiMini title="Trạng thái" :value="statusLabel" :tone="tone" />
            </div>

            <div class="mt-5 rounded-2xl border border-slate-200 bg-white">
              <div class="border-b border-slate-200 px-4 py-3">
                <div class="text-sm font-semibold text-slate-900">
                  Danh sách công trình đã duyệt và quy đổi giờ NCKH
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  Chỉ hiển thị công trình đã duyệt (khoa + trường) và giờ quy
                  đổi.
                </div>
              </div>

              <div v-if="loadingDetail" class="p-4 text-sm text-slate-700">
                Đang tải chi tiết...
              </div>

              <div v-else-if="errorDetail" class="p-4">
                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                  <div class="text-sm font-medium text-rose-700">
                    Không tải được chi tiết
                  </div>
                  <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
                    {{ errorDetail }}
                  </div>
                </div>
              </div>

              <div v-else-if="detailRows.length === 0" class="p-6 text-center">
                <div class="text-sm font-medium text-slate-900">
                  Không có công trình đã duyệt
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  Giờ NCKH hiện tại = 0 hoặc chưa có dữ liệu.
                </div>
              </div>

              <div v-else class="overflow-auto">
                <table class="min-w-full text-left text-sm">
                  <thead class="bg-slate-50 text-xs text-slate-600">
                    <tr class="[&>th]:px-3 [&>th]:py-2">
                      <th>Công trình</th>
                      <th>Loại</th>
                      <th>Năm học</th>
                      <th class="text-right">Giờ quy đổi</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200">
                    <tr
                      v-for="r in detailRows"
                      :key="r.activityId"
                      class="hover:bg-slate-50"
                    >
                      <td class="px-3 py-2">
                        <div class="font-medium text-slate-900">
                          {{ r.activityTitle }}
                        </div>
                        <div class="mt-0.5 text-xs text-slate-500">
                          id: {{ r.activityId }}
                        </div>
                      </td>
                      <td class="px-3 py-2 text-slate-700">
                        {{ r.activityKindName }}
                      </td>
                      <td class="px-3 py-2 text-slate-700">
                        {{ r.academicYearCode }}
                      </td>
                      <td
                        class="px-3 py-2 text-right font-semibold text-slate-900"
                      >
                        {{ formatHours(r.hoursConverted) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="mt-5 rounded-2xl border p-4" :class="footerClass">
              <div class="text-sm font-medium" :class="footerTextClass">
                <span v-if="difference >= 0">
                  Giảng viên đã hoàn thành định mức giờ NCKH.
                </span>
                <span v-else>
                  Giảng viên còn thiếu
                  {{ formatHours(Math.abs(difference)) }} giờ NCKH so với định
                  mức.
                </span>
              </div>
            </div>
          </div>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type {
  LecturerHoursOverview,
  LecturerHoursDetailRow,
} from "../lecturerHours.contract";
import KpiMini from "./internal/KpiMini.vue";
import { X } from "lucide-vue-next";

type Tone = "hit" | "near" | "miss" | "neutral";

interface Props {
  open: boolean;
  lecturer: LecturerHoursOverview | null;
  academicYearCode: string;

  detailRows: LecturerHoursDetailRow[];
  loadingDetail: boolean;
  errorDetail: string | null;
}

interface Emits {
  (e: "close"): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const hoursTotal = computed(() => props.lecturer?.hoursTotal ?? 0);
const requiredHours = computed(() => props.lecturer?.requiredHours ?? 0);
const difference = computed(() => hoursTotal.value - requiredHours.value);

const statusLabel = computed(() => (difference.value >= 0 ? "Đạt" : "Thiếu"));

const tone = computed<Tone>(() => {
  if (difference.value >= 0) return "hit";
  if (difference.value >= -50) return "near";
  return "miss";
});

const degreeRankLabel = computed(() => {
  const degree = props.lecturer?.degreeName ?? "—";
  const rank = props.lecturer?.academicRankName ?? "—";
  return `${degree} · ${rank}`;
});

const footerClass = computed(() => {
  if (tone.value === "hit") return "border-emerald-200 bg-emerald-50";
  if (tone.value === "near") return "border-amber-200 bg-amber-50";
  return "border-rose-200 bg-rose-50";
});

const footerTextClass = computed(() => {
  if (tone.value === "hit") return "text-emerald-800";
  if (tone.value === "near") return "text-amber-800";
  return "text-rose-800";
});

function formatHours(value: number) {
  return value.toFixed(0);
}

function formatSignedHours(value: number) {
  const sign = value >= 0 ? "+" : "";
  return `${sign}${value.toFixed(0)}`;
}
</script>
