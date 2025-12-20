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
        v-if="isOpen"
        class="fixed inset-0 z-40 bg-slate-900/20"
        aria-hidden="true"
        @click="emitClose"
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
        v-if="isOpen"
        class="fixed right-0 top-0 z-50 h-full w-full max-w-[720px] overflow-hidden bg-white shadow-2xl ring-1 ring-slate-200"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 p-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-base font-semibold text-slate-900">
                  {{ lecturerOverview?.lecturerFullName ?? "Giảng viên" }}
                </div>
                <div class="mt-1 text-sm text-slate-600">
                  {{ lecturerOverview?.facultyName ?? "—" }}
                  <span class="text-slate-300">•</span>
                  <span>{{
                    lecturerOverview?.degreeName ?? "Chưa cập nhật học vị"
                  }}</span>
                </div>
              </div>
              <button
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                @click="emitClose"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Tổng</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                  {{ lecturerOverview?.totalCount ?? 0 }}
                </div>
              </div>
              <div class="rounded-xl bg-emerald-50 p-3">
                <div class="text-xs text-emerald-700">Approved</div>
                <div class="mt-1 text-lg font-semibold text-emerald-900">
                  {{ lecturerOverview?.approvedCount ?? 0 }}
                </div>
              </div>
              <div class="rounded-xl bg-amber-50 p-3">
                <div class="text-xs text-amber-700">Pending</div>
                <div class="mt-1 text-lg font-semibold text-amber-900">
                  {{ lecturerOverview?.pendingCount ?? 0 }}
                </div>
              </div>
              <div class="rounded-xl bg-rose-50 p-3">
                <div class="text-xs text-rose-700">Rejected</div>
                <div class="mt-1 text-lg font-semibold text-rose-900">
                  {{ lecturerOverview?.rejectedCount ?? 0 }}
                </div>
              </div>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-5">
            <div class="mb-3 text-sm font-semibold text-slate-900">
              Danh sách công trình đã được phê duyệt
            </div>

            <div v-if="isLoading" class="text-sm text-slate-600">
              Đang tải danh sách...
            </div>

            <div
              v-else-if="errorMessage"
              class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
              {{ errorMessage }}
            </div>

            <div
              v-else-if="approvedItems.length === 0"
              class="text-sm text-slate-600"
            >
              {{ emptyMessage }}
            </div>

            <ApprovedResearchWorkList
              v-else
              :approved-items="approvedItems"
              @open-detail="emitOpenDetail"
            />
          </div>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import ApprovedResearchWorkList from "./ApprovedResearchWorkList.vue";
import type {
  ApprovedSummary,
  OverviewItem,
} from "../lecturerResearchWork.contracts";
import { X } from "lucide-vue-next";

interface LecturerDrawerProps {
  isOpen: boolean;
  lecturerOverview: OverviewItem | null;
  approvedItems: ApprovedSummary[];
  isLoading: boolean;
  errorMessage: string | null;
}
const approvedWorkEmptyStateMessage =
  "Giảng viên chưa có công trình đã được phê duyệt.";

interface LecturerDrawerEmits {
  (e: "close"): void;
  (e: "open-detail", activityId: number): void;
}

defineProps<LecturerDrawerProps>();
const emit = defineEmits<LecturerDrawerEmits>();

const emptyMessage = approvedWorkEmptyStateMessage;

function emitClose() {
  emit("close");
}

function emitOpenDetail(activityId: number) {
  emit("open-detail", activityId);
}
</script>
