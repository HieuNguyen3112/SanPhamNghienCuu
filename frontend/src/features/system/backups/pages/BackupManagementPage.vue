<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h1 class="text-lg font-semibold text-slate-900 md:text-xl">
              Sao lưu và khôi phục dữ liệu
            </h1>
            <p class="mt-1 text-sm text-slate-600">
              Chỉ vai trò Trường được phép quản lý backup. Dữ liệu được mã hóa phía máy chủ trước khi đẩy lên cloud.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isRefreshing || isTriggeringBackup"
              @click="refreshData"
            >
              Làm mới
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isTriggeringBackup || activeRunProcessing"
              @click="triggerBackupNow"
            >
              Sao lưu ngay
            </button>
          </div>
        </div>
      </section>

      <section class="grid gap-3 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Lần sao lưu thành công gần nhất
          </p>
          <p class="mt-2 text-sm font-semibold text-slate-800">
            {{ formatDateTime(lastSuccessfulBackupAt) }}
          </p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Lịch tự động
          </p>
          <p class="mt-2 text-sm font-semibold text-slate-800">
            {{ scheduleLabel }}
          </p>
          <p class="mt-1 text-xs text-slate-500">
            Lần chạy kế tiếp: {{ formatDateTime(schedule?.next_run_at ?? null) }}
          </p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Chính sách lưu trữ
          </p>
          <p class="mt-2 text-sm font-semibold text-slate-800">
            Giữ {{ retention?.keep_last ?? 0 }} snapshot gần nhất
          </p>
          <p class="mt-1 text-xs text-slate-500">
            Weekly {{ retention?.keep_weekly ?? 0 }} • Monthly {{ retention?.keep_monthly ?? 0 }}
          </p>
        </article>
      </section>

      <section
        v-if="activeRunState"
        class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm"
      >
        <div class="flex flex-col gap-1 text-sm text-indigo-900 md:flex-row md:items-center md:justify-between">
          <p>
            <span class="font-semibold">Run hiện tại:</span>
            {{ activeRunState.run_id }}
            <span class="mx-1">•</span>
            {{ activeRunStatusLabel }}
          </p>
          <p class="text-xs text-indigo-700">
            {{ activeRunState.message || "Đang đồng bộ trạng thái backup..." }}
          </p>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
          <h2 class="text-base font-semibold text-slate-900">
            Danh sách snapshot
          </h2>
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isPruning"
            @click="triggerPrune"
          >
            Dọn snapshot cũ
          </button>
        </div>

        <div
          v-if="errorMessage"
          class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
        >
          {{ errorMessage }}
        </div>

        <div v-if="isRefreshing && snapshots.length === 0" class="py-6 text-sm text-slate-600">
          Đang tải dữ liệu backup...
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                <th class="px-2 py-2">Snapshot</th>
                <th class="px-2 py-2">Thời gian</th>
                <th class="px-2 py-2">Run ID</th>
                <th class="px-2 py-2">Kích thước</th>
                <th class="px-2 py-2">Trạng thái</th>
                <th class="px-2 py-2 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="snapshot in snapshots"
                :key="snapshot.snapshot_id"
                class="border-b border-slate-100"
              >
                <td class="px-2 py-3">
                  <p class="font-semibold text-slate-800">
                    {{ snapshot.short_id }}
                  </p>
                  <p class="text-xs text-slate-500">{{ snapshot.snapshot_id }}</p>
                </td>
                <td class="px-2 py-3 text-slate-700">
                  {{ formatDateTime(snapshot.created_at) }}
                </td>
                <td class="px-2 py-3 text-xs text-slate-600">
                  {{ snapshot.run_id || "-" }}
                </td>
                <td class="px-2 py-3 text-slate-700">
                  {{ formatBytes(snapshot.size_bytes) }}
                </td>
                <td class="px-2 py-3">
                  <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                    {{ mapStatusLabel(snapshot.run_state) }}
                  </span>
                </td>
                <td class="px-2 py-3">
                  <div class="flex flex-wrap justify-end gap-1.5">
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                      @click="downloadManifest(snapshot.snapshot_id)"
                    >
                      Manifest
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                      @click="downloadDbDump(snapshot.snapshot_id)"
                    >
                      DB dump
                    </button>
                    <button
                      type="button"
                      class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                      @click="openRestoreDialog(snapshot)"
                    >
                      Khôi phục
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="snapshots.length === 0">
                <td colspan="6" class="px-2 py-8 text-center text-sm text-slate-500">
                  Chưa có snapshot backup nào.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-150"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="restoreModalOpen"
          class="fixed inset-0 z-[85] bg-slate-950/45"
          @click="closeRestoreDialog"
        />
      </Transition>

      <Transition
        enter-active-class="transition duration-150"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
      >
        <div
          v-if="restoreModalOpen"
          class="fixed inset-0 z-[90] flex items-center justify-center p-4"
        >
          <div
            class="w-full max-w-[560px] overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-xl"
            @click.stop
          >
            <header class="border-b border-slate-200 px-5 py-4">
              <h3 class="text-base font-semibold text-slate-900">
                Xác nhận khôi phục backup
              </h3>
              <p class="mt-1 text-sm text-slate-600">
                Snapshot: {{ restoreSnapshot?.short_id || "-" }}
              </p>
            </header>

            <div class="max-h-[60vh] space-y-4 overflow-y-auto px-5 py-4">
              <label class="block space-y-1">
                <span class="text-sm font-medium text-slate-700">Phạm vi khôi phục</span>
                <select
                  v-model="restoreScope"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-500"
                >
                  <option value="full">Toàn bộ (DB + tệp)</option>
                  <option value="db_only">Chỉ database</option>
                  <option value="files_only">Chỉ tệp minh chứng/upload</option>
                </select>
              </label>

              <label class="block space-y-1">
                <span class="text-sm font-medium text-slate-700">Đích khôi phục</span>
                <select
                  v-model="restoreTarget"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-500"
                >
                  <option value="staging">Staging (an toàn)</option>
                  <option value="current">Môi trường hiện tại (rủi ro cao)</option>
                </select>
              </label>

              <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Hành động khôi phục có thể ghi đè dữ liệu hiện tại. Vui lòng kiểm tra kỹ trước khi thực hiện.
              </div>

              <label class="block space-y-1">
                <span class="text-sm font-medium text-slate-700">
                  Nhập cụm xác nhận: <code class="rounded bg-slate-100 px-1 py-0.5">KHOI_PHUC_DU_LIEU</code>
                </span>
                <input
                  v-model.trim="restoreConfirmPhrase"
                  type="text"
                  class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-slate-500"
                  placeholder="KHOI_PHUC_DU_LIEU"
                />
              </label>
            </div>

            <footer class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
              <button
                type="button"
                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                @click="closeRestoreDialog"
              >
                Hủy
              </button>
              <button
                type="button"
                class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-500 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="!canRestore"
                @click="confirmRestore"
              >
                Khôi phục
              </button>
            </footer>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import {
  downloadBackupDatabaseDump,
  downloadBackupManifest,
  getBackupRunStatus,
  listBackups,
  pruneBackups,
  restoreBackup,
  runBackupNow,
  type BackupListResponse,
  type BackupRunState,
  type BackupSnapshot,
} from "../api/backups.api";
import {
  resolveApiErrorMessage,
  useActionFeedback,
} from "@/shared/composables/useActionFeedback";

const REQUIRED_CONFIRM_PHRASE = "KHOI_PHUC_DU_LIEU";

const snapshots = ref<BackupSnapshot[]>([]);
const schedule = ref<BackupListResponse["schedule"] | null>(null);
const retention = ref<BackupListResponse["retention"] | null>(null);
const lastSuccessfulBackupAt = ref<string | null>(null);

const errorMessage = ref("");
const isRefreshing = ref(false);
const isTriggeringBackup = ref(false);
const isPruning = ref(false);

const currentRunState = ref<BackupRunState | null>(null);
let pollingTimer: ReturnType<typeof window.setInterval> | null = null;

const restoreModalOpen = ref(false);
const restoreSnapshot = ref<BackupSnapshot | null>(null);
const restoreScope = ref<"db_only" | "files_only" | "full">("full");
const restoreTarget = ref<"staging" | "current">("staging");
const restoreConfirmPhrase = ref("");

const { runWithFeedback } = useActionFeedback();

const activeRunProcessing = computed(() => {
  const status = (currentRunState.value?.status || "").toLowerCase();
  return status === "queued" || status === "running";
});

const activeRunState = computed(() => currentRunState.value);

const scheduleLabel = computed(() => {
  if (!schedule.value) return "-";
  const dayMap: Record<number, string> = {
    0: "CN",
    1: "Thứ 2",
    2: "Thứ 3",
    3: "Thứ 4",
    4: "Thứ 5",
    5: "Thứ 6",
    6: "Thứ 7",
  };
  const dayLabels = (schedule.value.days || [])
    .map((day) => dayMap[day] || `Thứ ${day}`)
    .join(", ");
  return `${dayLabels} lúc ${schedule.value.time} (${schedule.value.timezone})`;
});

const activeRunStatusLabel = computed(() => {
  return mapStatusLabel(activeRunState.value?.status || "");
});

const canRestore = computed(() => {
  return (
    !!restoreSnapshot.value &&
    restoreConfirmPhrase.value.trim() === REQUIRED_CONFIRM_PHRASE
  );
});

function formatDateTime(value: string | null | undefined) {
  if (!value) return "-";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "-";
  return new Intl.DateTimeFormat("vi-VN", {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  }).format(date);
}

function formatBytes(size: number | null | undefined) {
  if (size === null || size === undefined || Number.isNaN(size)) return "-";
  const value = Math.max(0, size);
  if (value < 1024) return `${value} B`;
  const kb = value / 1024;
  if (kb < 1024) return `${kb.toFixed(2)} KB`;
  const mb = kb / 1024;
  if (mb < 1024) return `${mb.toFixed(2)} MB`;
  const gb = mb / 1024;
  return `${gb.toFixed(2)} GB`;
}

function mapStatusLabel(status: string | null | undefined) {
  const normalized = (status || "").toLowerCase();
  if (normalized === "queued") return "Đang chờ";
  if (normalized === "running") return "Đang chạy";
  if (normalized === "success") return "Thành công";
  if (normalized === "failed") return "Thất bại";
  return status || "Không rõ";
}

function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = filename;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  window.URL.revokeObjectURL(url);
}

async function loadBackups() {
  isRefreshing.value = true;
  errorMessage.value = "";
  try {
    const response = await listBackups(50);
    snapshots.value = response.snapshots || [];
    schedule.value = response.schedule || null;
    retention.value = response.retention || null;
    lastSuccessfulBackupAt.value = response.last_successful_backup_at || null;
    const repositoryError = response.repository_error?.trim();
    if (repositoryError) {
      errorMessage.value = `Kho backup chưa sẵn sàng: ${repositoryError}`;
    }
  } catch (error) {
    errorMessage.value = resolveApiErrorMessage(
      error,
      "Không thể tải danh sách backup."
    );
  } finally {
    isRefreshing.value = false;
  }
}

async function refreshData() {
  await loadBackups();
}

function stopPollingRunStatus() {
  if (pollingTimer) {
    window.clearInterval(pollingTimer);
    pollingTimer = null;
  }
}

async function syncRunStatus(runId: string) {
  try {
    const state = await getBackupRunStatus(runId);
    currentRunState.value = state;

    const status = (state.status || "").toLowerCase();
    if (status === "success" || status === "failed") {
      stopPollingRunStatus();
      await loadBackups();
    }
  } catch {
    // polling lỗi tạm thời: giữ im lặng để tránh spam UI
  }
}

function startPollingRunStatus(runId: string) {
  stopPollingRunStatus();
  void syncRunStatus(runId);
  pollingTimer = window.setInterval(() => {
    void syncRunStatus(runId);
  }, 5000);
}

async function triggerBackupNow() {
  if (isTriggeringBackup.value) return;
  isTriggeringBackup.value = true;

  try {
    const result = await runWithFeedback(
      async () => runBackupNow(),
      {
        loading: {
          title: "Đang khởi tạo backup",
          message: "Hệ thống đang tạo tiến trình sao lưu dữ liệu...",
        },
        success: {
          title: "Thành công",
          message: "Đã kích hoạt sao lưu dữ liệu.",
        },
        error: {
          title: "Có lỗi xảy ra",
          message: (error) =>
            resolveApiErrorMessage(error, "Không thể kích hoạt backup."),
        },
      }
    );

    if (result?.run_id) {
      startPollingRunStatus(result.run_id);
    } else {
      await loadBackups();
    }
  } finally {
    isTriggeringBackup.value = false;
  }
}

async function triggerPrune() {
  if (isPruning.value) return;
  isPruning.value = true;
  try {
    await runWithFeedback(
      async () => pruneBackups(),
      {
        loading: {
          title: "Đang dọn backup cũ",
          message: "Hệ thống đang áp dụng chính sách lưu trữ...",
        },
        success: {
          title: "Thành công",
          message: "Đã dọn snapshot cũ thành công.",
        },
        error: {
          title: "Có lỗi xảy ra",
          message: (error) =>
            resolveApiErrorMessage(error, "Không thể dọn snapshot cũ."),
        },
      }
    );
    await loadBackups();
  } finally {
    isPruning.value = false;
  }
}

async function downloadManifest(snapshotId: string) {
  await runWithFeedback(
    async () => {
      const payload = await downloadBackupManifest(snapshotId);
      downloadBlob(payload.blob, payload.filename);
      return payload;
    },
    {
      loading: {
        title: "Đang tải manifest",
        message: "Hệ thống đang chuẩn bị manifest backup...",
      },
      success: {
        title: "Thành công",
        message: "Tải manifest backup thành công.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) =>
          resolveApiErrorMessage(error, "Không thể tải manifest backup."),
      },
    }
  );
}

async function downloadDbDump(snapshotId: string) {
  await runWithFeedback(
    async () => {
      const payload = await downloadBackupDatabaseDump(snapshotId);
      downloadBlob(payload.blob, payload.filename);
      return payload;
    },
    {
      loading: {
        title: "Đang tải DB dump",
        message: "Hệ thống đang chuẩn bị tệp SQL...",
      },
      success: {
        title: "Thành công",
        message: "Tải DB dump thành công.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) =>
          resolveApiErrorMessage(error, "Không thể tải DB dump backup."),
      },
    }
  );
}

function openRestoreDialog(snapshot: BackupSnapshot) {
  restoreSnapshot.value = snapshot;
  restoreScope.value = "full";
  restoreTarget.value = "staging";
  restoreConfirmPhrase.value = "";
  restoreModalOpen.value = true;
}

function closeRestoreDialog() {
  restoreModalOpen.value = false;
  restoreSnapshot.value = null;
  restoreConfirmPhrase.value = "";
}

async function confirmRestore() {
  if (!restoreSnapshot.value || !canRestore.value) return;

  const snapshotId = restoreSnapshot.value.snapshot_id;
  const payload = {
    scope: restoreScope.value,
    target: restoreTarget.value,
    confirm: true,
    confirm_phrase: restoreConfirmPhrase.value,
  } as const;

  await runWithFeedback(
    async () => restoreBackup(snapshotId, payload),
    {
      loading: {
        title: "Đang khôi phục backup",
        message: "Hệ thống đang xử lý khôi phục dữ liệu, vui lòng chờ...",
      },
      success: {
        title: "Thành công",
        message: "Khôi phục dữ liệu thành công.",
      },
      error: {
        title: "Khôi phục thất bại",
        message: (error) =>
          resolveApiErrorMessage(error, "Không thể khôi phục dữ liệu."),
      },
      rethrow: false,
    }
  );

  closeRestoreDialog();
  await loadBackups();
}

onMounted(async () => {
  await loadBackups();
});

onBeforeUnmount(() => {
  stopPollingRunStatus();
});
</script>
