<template>
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-4 py-3 md:px-6">
      <div class="flex items-center justify-between gap-3">
        <div class="text-sm font-semibold text-slate-900">Bảng nhật ký</div>
        <div class="text-sm text-slate-500">{{ totalItems }} bản ghi</div>
      </div>
    </div>

    <div v-if="isLoading" class="p-6 text-sm text-slate-500">
      Đang tải dữ liệu...
    </div>

    <div v-else-if="error" class="p-6">
      <div
        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        {{ error }}
      </div>
    </div>

    <div v-else-if="entries.length === 0" class="p-6 text-sm text-slate-500">
      Không có nhật ký phù hợp.
    </div>

    <div v-else class="max-h-[560px] overflow-auto">
      <table class="w-full text-left text-sm">
        <thead
          class="sticky top-0 z-10 bg-slate-50 text-xs font-semibold text-slate-600"
        >
          <tr>
            <th class="w-12 px-4 py-3 md:px-6"></th>
            <th class="px-4 py-3 md:px-6">Thời gian</th>
            <th class="px-4 py-3 md:px-6">Người thực hiện</th>
            <th class="px-4 py-3 md:px-6">Hành động</th>
            <th class="px-4 py-3 md:px-6">Đối tượng</th>
            <th class="px-4 py-3 md:px-6">Kết quả</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="row in entries"
            :key="row.id"
            class="cursor-pointer hover:bg-slate-50"
            @click="emit('select', row)"
          >
            <td class="px-4 py-3 md:px-6">
              <div
                class="flex h-8 w-8 items-center justify-center rounded-full ring-1 ring-slate-200"
                :class="iconWrapClass(row.actionGroup)"
              >
                <component
                  :is="iconComponent(row.actionGroup)"
                  class="h-4 w-4"
                />
              </div>
            </td>

            <td class="whitespace-nowrap px-4 py-3 md:px-6 text-slate-700">
              {{ formatDateTimeVi(row.occurredAt) }}
            </td>

            <td class="px-4 py-3 md:px-6">
              <div class="font-medium text-slate-900">
                {{ row.actor.name ?? "Không xác định" }}
              </div>
              <div class="text-xs text-slate-500">
                {{ row.actor.email ?? "" }}
              </div>
            </td>

            <td class="px-4 py-3 md:px-6">
              <div class="font-medium text-slate-900">
                {{ row.actionLabel }}
              </div>
              <div class="text-xs text-slate-500">{{ row.actionCode }}</div>
            </td>

            <td class="px-4 py-3 md:px-6 text-slate-700">
              {{ row.target.display ?? "-" }}
            </td>

            <td class="px-4 py-3 md:px-6">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ring-1"
                :class="resultBadgeClass(row.result.status)"
              >
                {{
                  row.result.status === "success" ? "Thành công" : "Thất bại"
                }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination (MUST use SharedPaginationControls) -->
    <div class="border-t border-slate-200 px-4 py-3 md:px-6">
      <SharedPaginationControls
        :total-item-count="totalItems"
        :current-page-number="page"
        :page-size="pageSize"
        display-mode="FULL"
        :show-record-summary="true"
        record-summary-mode="RANGE"
        @update:currentPageNumber="emit('change-page', $event)"
        @update:pageSize="emit('change-page-size', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  AuditActionGroup,
  AuditLogEntry,
  AuditResultStatus,
} from "../contracts/audit-log.contract";
import { formatDateTimeVi } from "../contracts/audit-log.contract";
import {
  FileText,
  ShieldAlert,
  Settings,
  User,
  CheckCircle2,
  ClipboardCheck,
} from "lucide-vue-next";

// ✅ dùng component bạn đưa (đổi đúng path theo dự án)
import SharedPaginationControls from "@/shared/components/SharedPaginationControls.vue";

const props = defineProps<{
  entries: AuditLogEntry[];
  isLoading: boolean;
  error: string | null;

  page: number;
  pageSize: number;
  totalItems: number;
}>();

const emit = defineEmits<{
  (e: "select", row: AuditLogEntry): void;
  (e: "change-page", page: number): void;
  (e: "change-page-size", pageSize: number): void;
}>();

function iconComponent(group: AuditActionGroup) {
  const map: Record<AuditActionGroup, any> = {
    research: FileText,
    approval: ClipboardCheck,
    lecturer: User,
    auth: CheckCircle2,
    config: Settings,
    security: ShieldAlert,
  };
  return map[group];
}

function iconWrapClass(group: AuditActionGroup) {
  const map: Record<AuditActionGroup, string> = {
    research: "text-sky-700 bg-sky-50",
    approval: "text-sky-700 bg-sky-50",
    lecturer: "text-amber-700 bg-amber-50",
    auth: "text-amber-700 bg-amber-50",
    config: "text-violet-700 bg-violet-50",
    security: "text-rose-700 bg-rose-50",
  };
  return map[group];
}

function resultBadgeClass(status: AuditResultStatus) {
  return status === "success"
    ? "bg-emerald-50 text-emerald-700 ring-emerald-200"
    : "bg-rose-50 text-rose-700 ring-rose-200";
}
</script>
