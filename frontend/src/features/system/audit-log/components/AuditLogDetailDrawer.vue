<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

    <aside
      class="fixed right-0 top-0 h-full w-full max-w-xl border-l border-slate-200 bg-white shadow-2xl"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex h-full flex-col">
        <div
          class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-4"
        >
          <div class="min-w-0">
            <div class="text-sm font-semibold text-slate-900">
              Chi tiết nhật ký
            </div>
            <div class="mt-1 text-xs text-slate-500">
              {{ entry ? formatDateTimeVi(entry.occurredAt) : "" }}
            </div>
          </div>

          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="emit('close')"
          >
            Đóng
          </button>
        </div>

        <div v-if="loading" class="p-4 text-sm text-slate-500">
          Đang tải chi tiết...
        </div>

        <div v-else-if="error" class="p-4">
          <div
            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
          >
            {{ error }}
          </div>
        </div>

        <div v-else-if="entry" class="flex-1 overflow-y-auto px-4 py-4 space-y-5">
          <section class="space-y-2">
            <h3
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Thông tin chung
            </h3>

            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="text-xs font-medium text-slate-500">Thời gian</div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ formatDateTimeVi(entry.occurredAt) }}
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="text-xs font-medium text-slate-500">
                Người thực hiện
              </div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ actorDisplay(entry) }}
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="text-xs font-medium text-slate-500">
                Vai trò (snapshot)
              </div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ rolesDisplay(entry) }}
              </div>
            </div>

            <div class="grid gap-2 md:grid-cols-2">
              <div
                class="rounded-xl border border-slate-200 bg-white px-3 py-2"
              >
                <div class="text-xs font-medium text-slate-500">IP</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">
                  {{ entry.ip ?? "-" }}
                </div>
              </div>

              <div
                class="rounded-xl border border-slate-200 bg-white px-3 py-2"
              >
                <div class="text-xs font-medium text-slate-500">Thiết bị</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">
                  {{ shortUserAgent(entry.userAgent) }}
                </div>
              </div>
            </div>
          </section>

          <section class="space-y-2">
            <h3
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Hành động
            </h3>

            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="text-xs font-medium text-slate-500">Action code</div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ entry.actionCode }}
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="text-xs font-medium text-slate-500">Mô tả</div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ entry.actionLabel }}
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="text-xs font-medium text-slate-500">Đối tượng</div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ entry.target.display ?? "-" }}
              </div>
            </div>

            <div>
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                :disabled="!entry.target.routeName"
                @click="emit('view-target', entry.target)"
              >
                Xem đối tượng
              </button>
            </div>
          </section>

          <section class="space-y-2">
            <h3
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Kết quả
            </h3>

            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2">
              <div class="text-xs font-medium text-slate-500">Trạng thái</div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ entry.result.status === "success" ? "Thành công" : "Thất bại" }}
              </div>
            </div>

            <div
              v-if="entry.result.status === 'failure'"
              class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2"
            >
              <div class="text-xs font-medium text-rose-700">Lỗi</div>
              <div class="mt-1 text-sm font-semibold text-rose-800">
                {{ entry.result.errorMessage ?? "-" }}
              </div>
            </div>

            <div
              v-if="entry.request"
              class="rounded-xl border border-slate-200 bg-white px-3 py-2"
            >
              <div class="text-xs font-medium text-slate-500">Request</div>
              <div class="mt-1 text-sm font-semibold text-slate-900">
                {{ requestDisplay(entry) }}
              </div>
            </div>
          </section>

          <section
            v-if="entry.changes && entry.changes.length > 0"
            class="space-y-2"
          >
            <h3
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Thay đổi dữ liệu
            </h3>

            <div class="overflow-hidden rounded-xl border border-slate-200">
              <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold text-slate-600">
                  <tr>
                    <th class="px-3 py-2">Trường</th>
                    <th class="px-3 py-2">Trước</th>
                    <th class="px-3 py-2">Sau</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(c, idx) in entry.changes" :key="idx">
                    <td class="px-3 py-2 font-medium text-slate-900">
                      {{ c.field }}
                    </td>
                    <td class="px-3 py-2 text-slate-600">
                      {{ c.before ?? "-" }}
                    </td>
                    <td class="px-3 py-2 text-slate-900">
                      {{ c.after ?? "-" }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section v-if="entry.note" class="space-y-2">
            <h3
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Ghi chú hệ thống
            </h3>
            <div
              class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
            >
              {{ entry.note }}
            </div>
          </section>
        </div>

        <div v-else class="p-4 text-sm text-slate-500">Không có dữ liệu.</div>
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import type {
  AuditLogEntry,
  AuditTarget,
} from "../contracts/audit-log.contract";
import {
  formatDateTimeVi,
  shortUserAgent,
} from "../contracts/audit-log.contract";

defineProps<{
  open: boolean;
  entry: AuditLogEntry | null;
  loading: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "view-target", target: AuditTarget): void;
}>();

function actorDisplay(e: AuditLogEntry) {
  const name = e.actor.name ?? "Không xác định";
  const email = e.actor.email ? ` • ${e.actor.email}` : "";
  return `${name}${email}`;
}

function rolesDisplay(e: AuditLogEntry) {
  const roles = e.actor.backendRolesSnapshot;
  if (!roles || roles.length === 0) return "-";
  return roles.join(", ");
}

function requestDisplay(e: AuditLogEntry) {
  if (!e.request) return "-";
  const m = e.request.method ?? "";
  const p = e.request.path ?? "";
  const s = e.request.httpStatus != null ? ` (${e.request.httpStatus})` : "";
  return `${m} ${p}${s}`.trim();
}
</script>
