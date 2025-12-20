<template>
  <Teleport to="body">
    <!-- Overlay -->
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

    <!-- Drawer -->
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
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[540px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <!-- Header -->
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900">
                  {{ work ? work.title : "Chi tiết công trình" }}
                </div>

                <div class="mt-1 flex items-center gap-2">
                  <span
                    v-if="work"
                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                    :class="badgeClass(work.statusCode)"
                  >
                    {{ work.statusName }}
                  </span>

                  <span v-if="work" class="text-xs text-slate-500">
                    {{ work.activityCode }}
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

          <!-- Body -->
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

            <div v-else-if="!work" class="text-sm text-slate-600">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-5">
              <!-- General -->
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Thông tin chung
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <InfoRow label="Loại công trình" :value="work.kindName" />
                  <InfoRow label="Phân loại" :value="work.typeName ?? '—'" />
                  <InfoRow label="Vai trò" :value="work.roleName ?? '—'" />
                  <InfoRow
                    label="Năm"
                    :value="work.workYear ? String(work.workYear) : '—'"
                  />
                  <InfoRow
                    wrapper-class="md:col-span-2"
                    label="Nơi công bố/đơn vị"
                    :value="work.venueName ?? '—'"
                  />

                  <InfoRow
                    label="Ngày gửi"
                    :value="formatDateTime(work.submittedAt)"
                  />
                  <InfoRow
                    label="Ngày duyệt"
                    :value="formatDateTime(work.approvedAt)"
                  />

                  <InfoRow
                    v-if="work.statusCode === 'approved'"
                    label="Giờ NCKH của bạn"
                    :value="work.lecturerHours ?? '—'"
                  />
                </div>

                <div
                  v-if="work.statusCode === 'rejected' && work.rejectionNote"
                  class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
                >
                  <div class="text-xs font-semibold">Lý do từ chối</div>
                  <div class="mt-1">{{ work.rejectionNote }}</div>
                </div>
              </section>

              <!-- Authors -->
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Danh sách tác giả
                </div>

                <div
                  v-if="work.authors.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Chưa có tác giả.
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
                        <th class="px-3 py-2">Tác giả</th>
                        <th class="px-3 py-2">Vai trò</th>
                        <th class="px-3 py-2">Đơn vị</th>
                      </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                      <tr
                        v-for="author in work.authors"
                        :key="author.lecturerId"
                      >
                        <td class="px-3 py-2 font-medium text-slate-900">
                          {{ author.lecturerFullName }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ author.memberRoleName }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ author.departmentName ?? "—" }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <!-- Evidence -->
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Minh chứng
                </div>

                <div
                  v-if="work.evidenceItems.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Chưa có minh chứng.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="item in work.evidenceItems"
                    :key="item.evidenceFileId"
                    class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:bg-slate-50"
                  >
                    <div class="min-w-0">
                      <div
                        class="truncate text-sm font-semibold text-slate-900"
                      >
                        {{ item.originalName }}
                      </div>
                      <div class="mt-1 text-xs text-slate-500">
                        {{ item.fileTypeName }}
                        <span class="px-1 text-slate-300">•</span>
                        {{ item.mimeType }}
                        <span class="px-1 text-slate-300">•</span>
                        {{ formatBytes(item.sizeBytes) }}
                      </div>
                      <div class="mt-1 text-xs text-slate-400">
                        Uploaded: {{ formatDateTime(item.uploadedAt) }}
                      </div>
                    </div>

                    <a
                      class="shrink-0 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                      :href="buildEvidenceUrl(item.disk, item.path)"
                      target="_blank"
                      rel="noreferrer"
                      title="TODO(BE): signed url / download_url"
                    >
                      Mở
                    </a>
                  </div>
                </div>
              </section>

              <!-- Timeline -->
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold text-slate-700">
                  Trạng thái & lịch sử
                </div>

                <div class="mt-3 space-y-3">
                  <TimelineItem
                    label="Gửi duyệt"
                    :value="formatDateTime(work.submittedAt)"
                    :note="null"
                    :status="null"
                  />

                  <TimelineItem
                    v-for="approval in work.approvals"
                    :key="approval.stageCode"
                    :label="`Xét duyệt ${approval.stageName}`"
                    :value="
                      approval.decidedAt
                        ? formatDateTime(approval.decidedAt)
                        : '—'
                    "
                    :note="approval.note"
                    :status="approval.status"
                  />

                  <TimelineItem
                    v-if="work.statusCode === 'approved'"
                    label="Phê duyệt"
                    :value="formatDateTime(work.approvedAt)"
                    :note="null"
                    status="approved"
                  />

                  <TimelineItem
                    v-if="work.statusCode === 'rejected'"
                    label="Từ chối"
                    :value="formatDateTime(rejectedActedAt)"
                    :note="work.rejectionNote"
                    status="rejected"
                  />
                </div>
              </section>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t border-slate-200 px-4 py-3">
            <div class="flex items-center justify-between gap-2">
              <button
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                ← Quay lại danh sách
              </button>

              <button
                v-if="work?.statusCode === 'draft'"
                type="button"
                class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800"
                @click="emit('edit-draft', work.activityId)"
              >
                Tiếp tục kê khai
              </button>
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
  PersonalWorkDetail,
  PersonalWorkStatusCode,
} from "../contracts/personalResearchWorksContracts";
import InfoRow from "@/features/scientific/personal-research-works/components/InfoRow.vue";
import TimelineItem from "@/features/scientific/personal-research-works/components/TimelineItem.vue";

const props = defineProps<{
  open: boolean;
  loading: boolean;
  error: string | null;
  work: PersonalWorkDetail | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "edit-draft", workId: number): void;
}>();

const rejectedActedAt = computed<string | null>(() => {
  const currentWork = props.work;
  if (!currentWork) return null;

  const histories = currentWork.statusHistories;
  for (let index = histories.length - 1; index >= 0; index -= 1) {
    const history = histories[index];
    if (!history) continue;
    if (history.toStatusCode === "rejected") return history.actedAt;
  }
  return null;
});

function badgeClass(statusCode: PersonalWorkStatusCode): string {
  switch (statusCode) {
    case "approved":
      return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
    case "submitted":
      return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
    case "rejected":
      return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
    case "draft":
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
    default:
      return "bg-slate-100 text-slate-700 ring-1 ring-slate-200";
  }
}

function formatDateTime(value: string | null): string {
  if (!value) return "—";
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString();
}

function formatBytes(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes <= 0) return "0 B";
  const units = ["B", "KB", "MB", "GB", "TB"] as const;
  let size = bytes;
  let unitIndex = 0;
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024;
    unitIndex += 1;
  }
  return `${size.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

// TODO(BE): evidence_files.path cần signed URL (download_url) thay vì tự ghép
function buildEvidenceUrl(disk: string, path: string): string {
  void disk;
  return path.startsWith("http") ? path : `/${path.replace(/^\/+/, "")}`;
}
</script>
