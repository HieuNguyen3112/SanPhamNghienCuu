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
        class="fixed right-0 top-0 z-50 h-full w-full max-w-[860px] overflow-hidden bg-white shadow-2xl ring-1 ring-slate-200"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <div class="border-b border-slate-200 p-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span
                    class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700"
                  >
                    Approved
                  </span>
                  <div class="text-xs text-slate-500">
                    {{ detail ? detail.academicYearCode : "" }}
                  </div>
                </div>
                <div
                  class="mt-2 truncate text-base font-semibold text-slate-900"
                >
                  {{ detail?.title ?? "Chi tiết công trình" }}
                </div>
                <div class="mt-1 text-sm text-slate-600">
                  {{ detail?.typeName ?? detail?.kindName ?? "—" }}
                  <span class="text-slate-300">•</span>
                  <span
                    >Approved:
                    {{ detail ? formatDate(detail.approvedAt) : "—" }}</span
                  >
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="rounded-lg bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                  @click="emitBack"
                >
                  <ArrowLeftToLine />
                </button>
              </div>
            </div>
          </div>

          <div class="flex-1 overflow-auto p-5">
            <div v-if="isLoading" class="text-sm text-slate-600">
              Đang tải chi tiết...
            </div>

            <div
              v-else-if="errorMessage"
              class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
              {{ errorMessage }}
            </div>

            <div v-else-if="!detail" class="text-sm text-slate-600">
              Không có dữ liệu chi tiết.
            </div>

            <div v-else class="space-y-6">
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">Tóm tắt</div>
                <div class="mt-2 text-sm text-slate-700">
                  <div v-if="detail.abstract" class="whitespace-pre-wrap">
                    {{ detail.abstract }}
                  </div>
                  <div v-else class="text-slate-500">Không có tóm tắt.</div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">Tác giả</div>
                <div
                  v-if="detail.authors.length === 0"
                  class="mt-2 text-sm text-slate-500"
                >
                  Chưa có thông tin tác giả.
                </div>
                <ul v-else class="mt-2 space-y-2">
                  <li
                    v-for="author in detail.authors"
                    :key="author.lecturerId"
                    class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"
                  >
                    <div class="min-w-0">
                      <div class="text-sm font-medium text-slate-900">
                        {{ author.lecturerFullName }}
                      </div>
                      <div class="text-xs text-slate-500">
                        {{ author.memberRoleName }}
                      </div>
                    </div>
                    <div class="text-xs text-slate-600">
                      {{
                        author.contributionShare
                          ? `Tỷ lệ: ${author.contributionShare}`
                          : "—"
                      }}
                    </div>
                  </li>
                </ul>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Minh chứng
                </div>

                <div class="mt-3">
                  <div class="text-xs font-semibold text-slate-600">
                    Liên kết
                  </div>
                  <div class="mt-2 space-y-2">
                    <div
                      v-for="item in linkEvidenceItems"
                      :key="item.evidenceFileId"
                      class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"
                    >
                      <div class="min-w-0">
                        <div
                          class="truncate text-sm font-medium text-slate-900"
                        >
                          {{ item.originalName }}
                        </div>
                        <div class="text-xs text-slate-500">
                          {{ item.fileTypeName }}
                        </div>
                      </div>
                      <a
                        class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        :href="item.path"
                        target="_blank"
                        rel="noreferrer"
                      >
                        Mở link
                      </a>
                    </div>

                    <div
                      v-if="linkEvidenceItems.length === 0"
                      class="text-sm text-slate-500"
                    >
                      Không có liên kết.
                    </div>
                  </div>
                </div>

                <div class="mt-4">
                  <div class="text-xs font-semibold text-slate-600">Tệp</div>
                  <div class="mt-2 space-y-2">
                    <div
                      v-for="item in fileEvidenceItems"
                      :key="item.evidenceFileId"
                      class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2"
                    >
                      <div class="min-w-0">
                        <div
                          class="truncate text-sm font-medium text-slate-900"
                        >
                          {{ item.originalName }}
                        </div>
                        <div class="text-xs text-slate-500">
                          {{ item.fileTypeName }} •
                          {{ formatFileSize(item.sizeBytes) }}
                        </div>
                      </div>
                      <button
                        type="button"
                        class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                        @click="openEvidenceUrl(item)"
                      >
                        Xem
                      </button>
                    </div>

                    <div
                      v-if="fileEvidenceItems.length === 0"
                      class="text-sm text-slate-500"
                    >
                      {{ emptyEvidenceMessage }}
                    </div>
                  </div>
                </div>
              </section>

              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Phê duyệt cuối
                </div>

                <div
                  v-if="!detail.finalApproval"
                  class="mt-2 text-sm text-slate-500"
                >
                  Chưa có dữ liệu phê duyệt cuối.
                </div>

                <div v-else class="mt-2 space-y-1 text-sm text-slate-700">
                  <div>
                    <span class="text-slate-500">Người duyệt:</span>
                    <span class="font-medium text-slate-900">
                      {{ detail.finalApproval.decidedByUserName ?? "—" }}
                    </span>
                  </div>
                  <div>
                    <span class="text-slate-500">Thời gian:</span>
                    <span class="font-medium text-slate-900">
                      {{
                        detail.finalApproval.decidedAt
                          ? formatDate(detail.finalApproval.decidedAt)
                          : "—"
                      }}
                    </span>
                  </div>
                  <div
                    v-if="detail.finalApproval.note"
                    class="pt-2 text-slate-600"
                  >
                    Ghi chú: {{ detail.finalApproval.note }}
                  </div>
                </div>
              </section>
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
  ApprovedDetail,
  Evidence,
} from "../lecturerResearchWork.contracts";
import { ArrowLeftToLine } from "lucide-vue-next";
interface DetailDrawerProps {
  isOpen: boolean;
  detail: ApprovedDetail | null;
  isLoading: boolean;
  errorMessage: string | null;
}

interface DetailDrawerEmits {
  (e: "back"): void;
  (e: "close"): void;
}
const detailEmptyEvidenceMessage = "Chưa có minh chứng cho công trình này.";

const props = defineProps<DetailDrawerProps>();
const emit = defineEmits<DetailDrawerEmits>();

const emptyEvidenceMessage = detailEmptyEvidenceMessage;

const linkEvidenceItems = computed(() => {
  return (props.detail?.evidenceItems ?? []).filter(
    (item) => item.disk === "url"
  );
});

const fileEvidenceItems = computed(() => {
  return (props.detail?.evidenceItems ?? []).filter(
    (item) => item.disk !== "url"
  );
});

function emitBack() {
  emit("back");
}

function emitClose() {
  emit("close");
}

function formatDate(iso: string) {
  const date = new Date(iso);
  return date.toLocaleString("vi-VN");
}

function formatFileSize(sizeBytes: number) {
  if (!Number.isFinite(sizeBytes) || sizeBytes <= 0) return "0 B";
  const units = ["B", "KB", "MB", "GB"];
  let value = sizeBytes;
  let unitIndex = 0;
  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024;
    unitIndex += 1;
  }
  return `${value.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function openEvidenceUrl(item: Evidence) {
  // NOTE: production should build signed URL if disk='s3'
  // Here we demo with a pseudo-public url.
  const evidenceUrl =
    item.disk === "s3" ? `https://example.com/${item.path}` : item.path;
  window.open(evidenceUrl, "_blank", "noreferrer");
}
</script>
