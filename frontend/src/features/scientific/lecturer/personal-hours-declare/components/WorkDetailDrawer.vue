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
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200 sm:w-[480px] lg:w-[560px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900">
                  Chi tiết công trình
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  Chỉ xem, không chỉnh sửa
                </div>
              </div>

              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
                title="Đóng"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div class="flex-1 overflow-auto px-4 py-4">
            <div v-if="loading" class="text-sm text-slate-700">
              Đang tải chi tiết...
            </div>

            <div
              v-else-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4"
            >
              <div class="text-sm font-medium text-rose-700">
                Không tải được chi tiết
              </div>
              <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
                {{ error }}
              </div>
            </div>

            <div v-else-if="!detail" class="text-sm text-slate-700">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-4">
              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  {{ detail.title }}
                </div>
                <div class="mt-2 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div class="flex items-center gap-2 text-slate-700">
                    <BookOpen class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Loại:</span>
                    <span class="font-medium">{{ detail.kindName }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-slate-700">
                    <Calendar class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Năm học:</span>
                    <span class="font-medium">{{
                      detail.academicYearCode
                    }}</span>
                  </div>

                  <div
                    class="flex items-center gap-2 text-slate-700 md:col-span-2"
                  >
                    <Building2 class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500"
                      >Đơn vị / nơi công bố:</span
                    >
                    <span class="font-medium">{{
                      detail.publicationOrUnit
                    }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-slate-700">
                    <User class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Vai trò:</span>
                    <span class="font-medium">{{ detail.memberRoleName }}</span>
                  </div>

                  <div class="flex items-center gap-2 text-slate-700">
                    <Percent class="h-4 w-4 text-slate-500" />
                    <span class="text-xs text-slate-500">Tỷ lệ:</span>
                    <span class="font-medium">
                      {{
                        detail.contributionShare == null
                          ? "—"
                          : `${Math.round(detail.contributionShare * 100)}%`
                      }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Quy tắc quy đổi giờ
                </div>
                <div class="mt-2 text-sm text-slate-700">
                  {{ detail.ruleSummary }}
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Giờ NCKH được tính cho bạn
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900">
                  {{ formatHours(detail.hoursForLecturer) }} giờ
                </div>

                <div class="mt-3">
                  <span
                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                    :class="hoursPillClass(detail)"
                  >
                    <Check
                      v-if="detail.hoursRequestState === 'hours_approved'"
                      class="mr-1 h-4 w-4"
                    />
                    {{ hoursStatusLabel(detail) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="border-t border-slate-200 px-4 py-3">
            <button
              type="button"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
              @click="emit('close')"
            >
              Đóng
            </button>
          </div>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import {
  BookOpen,
  Building2,
  Calendar,
  Check,
  Percent,
  User,
  X,
} from "lucide-vue-next";
import type { WorkDetail } from "../contracts/selectHoursRequest.contract";
import { formatHours } from "../contracts/selectHoursRequest.contract";

defineProps<{
  open: boolean;
  detail: WorkDetail | null;
  loading: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
}>();

function hoursStatusLabel(detail: WorkDetail) {
  if (detail.hoursRequestState === "hours_approved") return "Đã duyệt giờ";
  if (detail.hoursRequestState === "submitted") return "Chờ duyệt giờ";
  if (detail.hoursRequestState === "rejected") return "Bị từ chối";
  return "Chưa duyệt giờ";
}

function hoursPillClass(detail: WorkDetail) {
  if (detail.hoursRequestState === "hours_approved")
    return "bg-emerald-50 text-emerald-700 ring-emerald-200";
  if (detail.hoursRequestState === "submitted")
    return "bg-amber-50 text-amber-700 ring-amber-200";
  if (detail.hoursRequestState === "rejected")
    return "bg-rose-50 text-rose-700 ring-rose-200";
  return "bg-slate-50 text-slate-700 ring-slate-200";
}
</script>
