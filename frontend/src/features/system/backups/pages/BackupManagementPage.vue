<template>
  <div class="min-h-screen bg-slate-50 p-4 md:p-6">
    <div class="mx-auto w-full space-y-4">
      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 class="text-lg font-semibold text-slate-900 md:text-xl">Sao lưu và khôi phục dữ liệu</h1>
            <p class="mt-1 text-sm text-slate-600">
              Hệ thống sao lưu tự động an toàn. Bạn chỉ cần kiểm tra kết quả và tải export khi cần.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="selectedSnapshotIds.length === 0 || deletingSelection || isForgetting"
              @click="deleteSelectedBackups"
            >
              Xóa đã chọn ({{ selectedSnapshotIds.length }})
            </button>

            <button
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
              :disabled="isLoading"
              @click="refreshList"
            >
              Làm mới danh sách
            </button>

            <button
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
              :disabled="!exportsRootPath"
              @click="openExportsFolder"
            >
              Mở thư mục exports
            </button>

            <button
              class="rounded-xl border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-60"
              :disabled="isPruning || isRunningPrune || isRestoring || isRunningBackup"
              @click="triggerPrune"
            >
              Dọn bản sao lưu cũ
            </button>

            <button
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
              :disabled="isTriggeringBackup || isRunningBackup || isRestoring"
              @click="triggerBackupNow"
            >
              Sao lưu ngay
            </button>
          </div>
        </div>
      </section>

      <section class="grid gap-3 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-slate-500">Lần sao lưu thành công gần nhất</p>
          <p class="mt-2 text-sm font-semibold text-slate-800">
            {{ formatDateTime(lastSuccessfulBackupAt) }}
          </p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-slate-500">Lịch tự động</p>
          <p class="mt-2 text-sm font-semibold text-slate-800">{{ scheduleLabel }}</p>
          <p v-if="nextScheduleRunLabel" class="mt-1 text-xs text-slate-600">{{ nextScheduleRunLabel }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ lastScheduleRunLabel }}</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs uppercase tracking-wide text-slate-500">Vị trí exports trên Drive</p>
          <p class="mt-2 break-all text-sm font-semibold text-slate-800">{{ exportsRootPath || "Chưa cấu hình" }}</p>
        </article>
      </section>

      <section
        v-if="activeRunState && showActiveRunBanner"
        class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 shadow-sm"
      >
        <p class="font-semibold">{{ mapOperationLabel(activeRunState.operation) }} • {{ mapStatusLabel(activeRunState.status) }}</p>
        <p class="mt-1 text-xs text-indigo-700">{{ activeRunState.user_message || activeRunState.message || "Đang xử lý..." }}</p>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
        <div
          v-if="errorMessage"
          class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
        >
          {{ errorMessage }}
        </div>
        <div
          v-if="cacheRefreshLabel"
          class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span>{{ cacheRefreshLabel }}</span>
            <button
              v-if="showRetrySyncButton"
              class="rounded-lg border border-amber-300 bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-900 hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isRetryingSync"
              @click="retrySnapshotSync"
            >
              {{ isRetryingSync ? "Đang đồng bộ..." : "Thử đồng bộ lại" }}
            </button>
            <button
              v-if="showUnlockStaleLockButton"
              class="rounded-lg border border-amber-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-amber-900 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isUnlockingStaleLock"
              @click="unlockStaleRepositoryLock"
            >
              {{ isUnlockingStaleLock ? "Đang gỡ khóa..." : "Gỡ khóa treo" }}
            </button>
          </div>
        </div>

        <div v-if="isLoading && snapshots.length === 0" class="space-y-2 py-2">
          <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded-xl bg-slate-100" />
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                <th class="px-2 py-2">
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300"
                    :checked="isAllCurrentPageSelected"
                    :disabled="snapshots.length === 0"
                    @change="toggleSelectAll"
                  />
                </th>
                <th class="px-2 py-2">Tên bản sao lưu</th>
                <th class="px-2 py-2">Thời gian tạo</th>
                <th class="px-2 py-2">Nội dung</th>
                <th class="px-2 py-2">Kích thước</th>
                <th class="px-2 py-2">Trạng thái</th>
                <th class="px-2 py-2 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="snapshot in snapshots"
                :key="resolveSnapshotId(snapshot) || snapshot.snapshot_id"
                class="border-b border-slate-100"
              >
                <td class="px-2 py-3">
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300"
                    :checked="isSelected(snapshot)"
                    @change="toggleSnapshotSelection(snapshot)"
                  />
                </td>
                <td class="px-2 py-3 align-top">
                  <p class="font-semibold text-slate-800">{{ snapshot.backup_name || `Backup ${snapshot.short_id}` }}</p>
                  <p class="text-xs text-slate-500">{{ mapBackupType(snapshot.backup_type) }}</p>
                </td>
                <td class="px-2 py-3 text-slate-700">{{ formatDateTime(snapshot.created_at) }}</td>
                <td class="px-2 py-3 text-slate-700">{{ backupSummary(snapshot) }}</td>
                <td class="px-2 py-3 text-slate-700">{{ formatSize(snapshot.size_bytes) }}</td>
                <td class="px-2 py-3">
                  <span
                    class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                    :class="statusBadgeClass(snapshot.status || snapshot.run_state)"
                  >
                    {{ mapStatusLabel(snapshot.status || snapshot.run_state) }}
                  </span>
                </td>
                <td class="px-2 py-3">
                  <div class="flex flex-wrap justify-end gap-1.5">
                    <button
                      class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50"
                      @click="openDetails(snapshot)"
                    >
                      Xem chi tiết
                    </button>
                    <button
                      class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                      :disabled="!snapshot.export_available"
                      @click="downloadExportFile(resolveSnapshotId(snapshot))"
                    >
                      Tải export
                    </button>
                    <button
                      class="rounded-lg border border-rose-200 px-2.5 py-1 text-xs text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                      :disabled="isForgetting"
                      @click="deleteSingleBackup(snapshot)"
                    >
                      Xóa
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="shouldShowSyncingEmptyState">
                <td colspan="7" class="px-2 py-8 text-center text-sm text-slate-500">
                  Danh sách đang được đồng bộ nền, dữ liệu sẽ tự cập nhật.
                </td>
              </tr>

              <tr v-else-if="shouldShowNoBackupState">
                <td colspan="7" class="px-2 py-8 text-center text-sm text-slate-500">
                  Chưa có bản sao lưu nào.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
          <p>Trang {{ pagination.page }} / {{ pagination.last_page }}</p>
          <div class="flex gap-2">
            <button
              class="rounded border border-slate-300 px-2 py-1 disabled:opacity-50"
              :disabled="pagination.page <= 1"
              @click="changePage(pagination.page - 1)"
            >
              Trước
            </button>
            <button
              class="rounded border border-slate-300 px-2 py-1 disabled:opacity-50"
              :disabled="pagination.page >= pagination.last_page"
              @click="changePage(pagination.page + 1)"
            >
              Sau
            </button>
          </div>
        </div>
      </section>
    </div>

    <Teleport to="body">
      <div v-if="detailOpen" class="fixed inset-0 z-[85] bg-slate-950/45" @click="closeDetails" />

      <aside
        v-if="detailOpen && detailSnapshot"
        class="fixed right-0 top-0 z-[90] h-full w-full max-w-xl overflow-y-auto border-l border-slate-200 bg-white p-4 shadow-xl"
      >
        <div class="flex items-center justify-between">
          <h3 class="text-base font-semibold text-slate-900">Chi tiết bản sao lưu</h3>
          <button class="rounded border border-slate-300 px-2 py-1 text-xs" @click="closeDetails">Đóng</button>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <button
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            :disabled="!detailResponse?.export.available"
            @click="downloadExportFile(resolveSnapshotId(detailSnapshot))"
          >
            Tải export
          </button>
          <button
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50"
            @click="restoreFromDetail"
          >
            Khôi phục thử (staging)
          </button>
          <button
            class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-50"
            @click="deleteSingleBackup(detailSnapshot)"
          >
            Xóa bản sao lưu
          </button>
        </div>

        <div v-if="detailLoading" class="mt-4 space-y-2">
          <div class="h-10 animate-pulse rounded-xl bg-slate-100" />
          <div class="h-10 animate-pulse rounded-xl bg-slate-100" />
          <div class="h-10 animate-pulse rounded-xl bg-slate-100" />
        </div>

        <div v-else class="mt-3 space-y-3 text-sm">
          <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-xs text-slate-500">Tổng quan</p>
            <ul class="mt-1 space-y-1 text-xs text-slate-700">
              <li>Thời điểm: {{ formatDateTime(detailSnapshot.created_at) }}</li>
              <li>
                Trạng thái:
                <span class="font-semibold" :class="statusTextClass(detailSnapshot.status || detailSnapshot.run_state)">
                  {{ mapStatusLabel(detailSnapshot.status || detailSnapshot.run_state) }}
                </span>
              </li>
              <li>Kích thước: {{ formatSize(detailSnapshot.size_bytes) }}</li>
              <li>Nội dung: {{ backupSummary(detailSnapshot) }}</li>
            </ul>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-xs text-slate-500">Export trên Google Drive</p>
            <p class="mt-1 break-all text-xs text-slate-800">
              {{ detailResponse?.export.drive_path || detailResponse?.export.export_path || "-" }}
            </p>
            <p class="mt-1 text-xs text-slate-600">
              Thư mục: {{ detailResponse?.export.folder_name || "-" }}
            </p>
            <p class="mt-1 text-xs text-slate-600">
              Trạng thái:
              <span class="font-medium" :class="detailResponse?.export.available ? 'text-emerald-700' : 'text-slate-600'">
                {{ detailResponse?.export.available ? "Đã sẵn sàng" : "Chưa có export" }}
              </span>
            </p>
            <p v-if="detailResponse?.export.artifacts?.length" class="mt-1 text-xs text-slate-600">
              Tệp: {{ detailResponse.export.artifacts.join(", ") }}
            </p>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-xs text-slate-500">Thành phần sao lưu</p>
            <ul class="mt-1 space-y-1 text-xs text-slate-700">
              <li>{{ detailResponse?.includes.database_dump ? "✓" : "✕" }} Cơ sở dữ liệu</li>
              <li>{{ detailResponse?.includes.evidence_files ? "✓" : "✕" }} Tệp minh chứng</li>
              <li>{{ detailResponse?.includes.summary ? "✓" : "✕" }} Báo cáo tóm tắt</li>
              <li>{{ detailResponse?.includes.metadata ? "✓" : "✕" }} Thông tin mô tả</li>
            </ul>
          </div>

          <details class="rounded-xl border border-slate-200 bg-white px-3 py-2">
            <summary class="cursor-pointer text-xs font-medium text-slate-700">Thông tin kỹ thuật</summary>
            <div class="mt-2 space-y-2 text-xs text-slate-600">
              <div>
                <p class="text-[11px] uppercase tracking-wide text-slate-500">Snapshot ID</p>
                <p class="break-all font-mono text-slate-800">{{ resolveSnapshotId(detailSnapshot) || detailSnapshot.snapshot_id }}</p>
              </div>
              <div>
                <p class="text-[11px] uppercase tracking-wide text-slate-500">Run ID</p>
                <p class="break-all font-mono text-slate-800">{{ detailSnapshot.run_id || "-" }}</p>
              </div>
              <div v-if="detailResponse?.export.technical_artifacts?.length">
                <p class="text-[11px] uppercase tracking-wide text-slate-500">Tệp kỹ thuật</p>
                <p class="break-all text-slate-800">{{ detailResponse.export.technical_artifacts.join(", ") }}</p>
              </div>
            </div>
          </details>
        </div>
      </aside>
    </Teleport>
    <ConfirmActionModal
      v-model:open="confirmModal.open"
      :title="confirmModal.title"
      :message="confirmModal.message"
      :confirm-text="confirmModal.confirmText"
      :cancel-text="confirmModal.cancelText"
      :loading-text="confirmModal.loadingText"
      :variant="confirmModal.variant"
      :loading="confirmModal.loading"
      @confirm="handleConfirmModalConfirm"
      @cancel="handleConfirmModalCancel"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import {
  downloadExport,
  forgetSnapshots,
  getBackupDetail,
  getExportsInfo,
  getRunStatus,
  listBackups,
  pruneSnapshots,
  refreshBackups,
  restoreSnapshot,
  runBackup,
  unlockStaleLock,
  type BackupCacheMeta,
  type BackupDetailResponse,
  type BackupExportOverview,
  type BackupListItem,
  type BackupRunState,
  type BackupScheduleMeta,
  type BackupScheduleRuntimeMeta,
  type ExportsInfoResponse,
} from "../api/backups.api";
import ConfirmActionModal from "@/shared/components/modals/ConfirmActionModal.vue";
import { resolveApiErrorMessage, useActionFeedback } from "@/shared/composables/useActionFeedback";

const snapshots = ref<BackupListItem[]>([]);
const pagination = ref({ page: 1, per_page: 20, total: 0, last_page: 1 });
const scheduleLabel = ref("-");
const scheduleMeta = ref<BackupScheduleMeta | null>(null);
const scheduleRuntime = ref<BackupScheduleRuntimeMeta | null>(null);
const lastSuccessfulBackupAt = ref<string | null>(null);
const exportOverview = ref<BackupExportOverview | null>(null);
const exportsInfo = ref<ExportsInfoResponse | null>(null);
const cacheMeta = ref<BackupCacheMeta | null>(null);
const listSyncing = ref(false);

const errorMessage = ref("");
const isLoading = ref(false);
const isTriggeringBackup = ref(false);
const isPruning = ref(false);
const deletingSelection = ref(false);
const selectedSnapshotIds = ref<string[]>([]);

const currentRunState = ref<BackupRunState | null>(null);
const isRetryingSync = ref(false);
const isUnlockingStaleLock = ref(false);
let pollingTimer: ReturnType<typeof window.setInterval> | null = null;
let pollingErrorCount = 0;

const detailOpen = ref(false);
const detailLoading = ref(false);
const detailSnapshot = ref<BackupListItem | null>(null);
const detailResponse = ref<BackupDetailResponse | null>(null);
type ConfirmVariant = "danger" | "warning" | "primary" | "info";
type ConfirmAction = () => Promise<void>;

const confirmModal = reactive({
  open: false,
  title: "",
  message: "",
  confirmText: "Đồng ý",
  cancelText: "Huỷ",
  loadingText: "Đang xử lý...",
  variant: "primary" as ConfirmVariant,
  loading: false,
});
let pendingConfirmAction: ConfirmAction | null = null;

const { runWithFeedback } = useActionFeedback();

const activeRunState = computed(() => currentRunState.value);
const activeRunStatus = computed(() => (activeRunState.value?.status || "").toLowerCase());
const activeRunOperation = computed(() => (activeRunState.value?.operation || "").toLowerCase());
const activeRunTrigger = computed(() => (activeRunState.value?.trigger || "").toLowerCase());
const activeRunErrorCode = computed(() => (activeRunState.value?.error_code || "").toUpperCase());
const activeRunProcessing = computed(
  () => activeRunTrigger.value !== "schedule" && ["queued", "running"].includes(activeRunStatus.value)
);
const isSyncingList = computed(() => activeRunProcessing.value && activeRunOperation.value === "snapshot_refresh");
const isRunningBackup = computed(() => activeRunProcessing.value && activeRunOperation.value === "backup");
const isRunningPrune = computed(() => activeRunProcessing.value && activeRunOperation.value === "prune");
const isRestoring = computed(() => activeRunProcessing.value && activeRunOperation.value === "restore");
const isForgetting = computed(() => activeRunProcessing.value && activeRunOperation.value === "forget");
const cacheRefreshOperation = computed(() => (cacheMeta.value?.refresh_operation || "").toLowerCase());
const cacheRefreshStatus = computed(() => (cacheMeta.value?.refresh_status || "").toLowerCase());
const cacheLastErrorCode = computed(() => (cacheMeta.value?.last_error_code || "").toUpperCase());
const isCacheSnapshotRefreshRunning = computed(
  () =>
    (Boolean(cacheMeta.value?.refreshing) || Boolean(cacheMeta.value?.refresh_queued))
    && cacheRefreshOperation.value === "snapshot_refresh"
    && ["queued", "running"].includes(cacheRefreshStatus.value)
);
const effectiveListSyncing = computed(() =>
  listSyncing.value
  || isSyncingList.value
  || isCacheSnapshotRefreshRunning.value
);
const showActiveRunBanner = computed(() => activeRunProcessing.value);
const nextScheduleRunLabel = computed(() => {
  const nextRunAt = scheduleMeta.value?.next_run_at || null;
  if (!nextRunAt) return "";
  const timezone = (scheduleMeta.value?.timezone || "").trim();
  const timezoneLabel = timezone ? ` (${timezone})` : "";
  return `Lần chạy kế tiếp: ${formatDateTime(nextRunAt)}${timezoneLabel}`;
});
const lastScheduleRunLabel = computed(() => {
  const lastRunAt = scheduleRuntime.value?.last_run_at || null;
  if (!lastRunAt) {
    return "Chưa ghi nhận lần chạy lịch gần đây.";
  }

  const statusLabel = mapStatusLabel(scheduleRuntime.value?.last_run_status);
  return `Lần chạy lịch gần nhất: ${formatDateTime(lastRunAt)} • ${statusLabel}`;
});

function hasRepositoryLockHint(value: string | null | undefined) {
  const normalized = (value || "").toLowerCase();
  if (!normalized) return false;
  return (
    normalized.includes("unable to create lock")
    || normalized.includes("repository is already locked")
    || normalized.includes("already locked by pid")
    || normalized.includes("the `unlock` command")
    || normalized.includes("đang bị khóa")
    || normalized.includes("bi khoa")
  );
}

function isTechnicalMessage(value: string | null | undefined) {
  const normalized = (value || "").trim().toLowerCase();
  if (!normalized) return false;
  return (
    normalized.includes("restic")
    || normalized.includes("repository")
    || normalized.includes("rclone")
    || normalized.includes("pid")
    || normalized.includes("stdout")
    || normalized.includes("stderr")
    || normalized.includes("unable to create lock")
    || normalized.includes("run timed out after")
  );
}

function toFriendlySyncMessage(rawMessage: string | null | undefined) {
  const normalized = (rawMessage || "").trim();
  const normalizedLower = normalized.toLowerCase();
  if (
    normalizedLower.includes("timed out")
    || normalizedLower.includes("timeout")
    || normalizedLower.includes("quá thời gian")
    || normalizedLower.includes("exceeded the timeout")
  ) {
    return "Đồng bộ danh sách bị quá thời gian. Vui lòng thử lại.";
  }

  if (normalized !== "" && !isTechnicalMessage(normalized)) {
    return normalized;
  }

  return "Đồng bộ danh sách thất bại. Bạn có thể thử lại.";
}
function toFriendlyBackgroundRunFailureMessage(
  userMessage: string | null | undefined,
  errorCode: string | null | undefined
) {
  const code = (errorCode || "").trim().toUpperCase();
  const primary = (userMessage || "").trim();
  if (code === "BACKUP_LOCKED") {
    return "Bản sao lưu đang bị khóa bởi tiến trình khác. Nếu không còn tác vụ nào chạy, bạn có thể dùng nút “Gỡ khóa treo”.";
  }
  if (code === "RUN_TIMEOUT") {
    return "Tiến trình nền bị quá thời gian. Bạn có thể thử lại.";
  }

  if (primary !== "" && !isTechnicalMessage(primary)) {
    return primary;
  }

  return "Không thể xử lý tác vụ sao lưu. Vui lòng thử lại.";
}
const syncFailedMessage = computed(() => {
  if (activeRunOperation.value === "snapshot_refresh" && activeRunStatus.value === "failed") {
    return toFriendlySyncMessage(activeRunState.value?.user_message || activeRunState.value?.message);
  }

  const lastError = cacheMeta.value?.last_error?.trim();
  if (lastError) {
    return toFriendlySyncMessage(lastError);
  }

  return "";
});
const showRetrySyncButton = computed(
  () => !effectiveListSyncing.value && (syncFailedMessage.value !== "" || Boolean(cacheMeta.value?.stale))
);
const showUnlockStaleLockButton = computed(() => {
  if (isRunningBackup.value || isRunningPrune.value || isForgetting.value || isRestoring.value) {
    return false;
  }

  if (activeRunErrorCode.value === "BACKUP_LOCKED" || cacheLastErrorCode.value === "BACKUP_LOCKED") {
    return true;
  }

  return hasRepositoryLockHint(
    `${syncFailedMessage.value} ${errorMessage.value}`.trim()
  );
});
const shouldShowSyncingEmptyState = computed(() => snapshots.value.length === 0 && effectiveListSyncing.value && !isLoading.value);
const shouldShowNoBackupState = computed(() => snapshots.value.length === 0 && !effectiveListSyncing.value && !isLoading.value);
const cacheRefreshLabel = computed(() => {
  if (syncFailedMessage.value) {
    return syncFailedMessage.value;
  }

  if (effectiveListSyncing.value) {
    return "Danh sách snapshot đang được đồng bộ nền, dữ liệu sẽ tự cập nhật.";
  }
  if (!cacheMeta.value) {
    return "";
  }
  if (cacheMeta.value.stale) {
    return "Danh sách snapshot đã cũ. Bạn có thể thử đồng bộ lại để cập nhật ngay.";
  }
  return "";
});

const exportsRootPath = computed(() => {
  const byInfo = exportsInfo.value?.exports_root_path?.trim();
  if (byInfo) return byInfo;
  const byOverview = exportOverview.value?.export_root?.trim();
  return byOverview || "";
});

const isAllCurrentPageSelected = computed(() => {
  const ids = snapshots.value
    .map((item) => resolveSnapshotId(item))
    .filter((id): id is string => Boolean(id));
  if (ids.length === 0) return false;
  return ids.every((id) => selectedSnapshotIds.value.includes(id));
});

const SCHEDULE_DAY_LABELS: Record<number, string> = {
  0: "Chủ nhật",
  1: "Thứ 2",
  2: "Thứ 3",
  3: "Thứ 4",
  4: "Thứ 5",
  5: "Thứ 6",
  6: "Thứ 7",
};

function toTwoDigits(value: number) {
  return String(value).padStart(2, "0");
}

function formatDateTime(value: string | null | undefined) {
  if (!value) return "-";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "-";
  return `${toTwoDigits(d.getHours())}:${toTwoDigits(d.getMinutes())}:${toTwoDigits(d.getSeconds())} ${toTwoDigits(d.getDate())}/${toTwoDigits(d.getMonth() + 1)}/${d.getFullYear()}`;
}

function formatSize(bytes: number | null | undefined) {
  if (!bytes || bytes <= 0) return "-";
  const units = ["B", "KB", "MB", "GB", "TB"];
  let value = bytes;
  let unit = 0;
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024;
    unit += 1;
  }
  return `${value.toFixed(value >= 10 || unit === 0 ? 0 : 1)} ${units[unit]}`;
}

function formatScheduleTime(value: string | null | undefined) {
  if (!value) return null;
  const [hourRaw, minuteRaw] = value.split(":");
  const hour = Number(hourRaw);
  const minute = Number(minuteRaw);
  if (!Number.isFinite(hour) || !Number.isFinite(minute)) return null;
  if (hour < 0 || hour > 23 || minute < 0 || minute > 59) return null;
  return `${toTwoDigits(hour)}:${toTwoDigits(minute)}`;
}

function formatScheduleLabel(schedule: BackupScheduleMeta | null | undefined) {
  if (!schedule) return "Chưa cấu hình lịch tự động";

  const explicit = (schedule.description_vi || "").trim();
  if (explicit) {
    return explicit;
  }

  if ((schedule.mode || "").toLowerCase() === "test") {
    const intervalMinutes = Math.max(1, Number(schedule.interval_minutes || 1));
    return `Kiểm thử lịch tự động: mỗi ${intervalMinutes} phút (chỉ dev/staging).`;
  }

  const intervalWeeks = Math.max(1, Number(schedule.interval_weeks || 1));
  const weekday =
    Number.isFinite(schedule.weekday) && schedule.weekday !== undefined
      ? SCHEDULE_DAY_LABELS[schedule.weekday]
      : undefined;
  const timePart = formatScheduleTime(schedule.time);

  if (weekday) {
    if (intervalWeeks >= 2) {
      return timePart
        ? `Tự động: ${intervalWeeks} tuần/lần vào ${weekday} lúc ${timePart}`
        : `Tự động: ${intervalWeeks} tuần/lần vào ${weekday}`;
    }

    return timePart
      ? `Tự động: hàng tuần vào ${weekday} lúc ${timePart}`
      : `Tự động: hàng tuần vào ${weekday}`;
  }

  const labels = Array.from(
    new Set((schedule.days || []).map((day) => SCHEDULE_DAY_LABELS[day]).filter((label): label is string => Boolean(label)))
  );
  if (labels.length === 0) return "Chưa cấu hình lịch tự động";

  const dayPart = labels.length === 1 ? labels[0] : `${labels.slice(0, -1).join(", ")} & ${labels[labels.length - 1]}`;
  return timePart ? `Tự động: ${dayPart} lúc ${timePart}` : `Tự động: ${dayPart}`;
}

function resolveSnapshotId(snapshot: BackupListItem | null | undefined) {
  if (!snapshot) return null;
  const full = (snapshot.snapshot_id_full || "").trim();
  if (full !== "") return full;
  const fallback = (snapshot.snapshot_id || "").trim();
  return fallback !== "" ? fallback : null;
}

function mapStatusLabel(status: string | null | undefined) {
  const s = (status || "").toLowerCase();
  if (s === "queued") return "Đang chờ";
  if (s === "running") return "Đang chạy";
  if (s === "success") return "Thành công";
  if (s === "failed") return "Thất bại";
  return "Không rõ";
}

function statusBadgeClass(status: string | null | undefined) {
  const s = (status || "").toLowerCase();
  if (s === "success") return "bg-emerald-100 text-emerald-700";
  if (s === "running") return "bg-amber-100 text-amber-700";
  if (s === "queued") return "bg-sky-100 text-sky-700";
  if (s === "failed") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-700";
}

function statusTextClass(status: string | null | undefined) {
  const s = (status || "").toLowerCase();
  if (s === "success") return "text-emerald-700";
  if (s === "running") return "text-amber-700";
  if (s === "queued") return "text-sky-700";
  if (s === "failed") return "text-rose-700";
  return "text-slate-700";
}

function mapOperationLabel(operation: string | undefined) {
  const s = (operation || "").toLowerCase();
  if (s === "backup") return "Tiến trình sao lưu";
  if (s === "prune") return "Tiến trình dọn snapshot";
  if (s === "restore") return "Tiến trình khôi phục";
  if (s === "forget") return "Xóa snapshot";
  if (s === "snapshot_refresh") return "Làm mới danh sách";
  return "Tiến trình hệ thống";
}

function mapBackupType(type: string | undefined) {
  const s = (type || "").toLowerCase();
  if (s === "full") return "Đầy đủ (DB + tệp)";
  if (s === "db_only") return "Chỉ database";
  if (s === "files_only") return "Chỉ tệp";
  return "Không rõ";
}

function backupSummary(snapshot: BackupListItem) {
  const db = snapshot.contains_db_dump ? "Có DB" : "Không DB";
  const files = snapshot.contains_files ? "Có minh chứng" : "Không minh chứng";
  return `${db} • ${files}`;
}

function openConfirmModal(
  options: {
    title: string;
    message: string;
    confirmText: string;
    cancelText?: string;
    loadingText?: string;
    variant?: ConfirmVariant;
  },
  onConfirm: ConfirmAction
) {
  confirmModal.open = true;
  confirmModal.title = options.title;
  confirmModal.message = options.message;
  confirmModal.confirmText = options.confirmText;
  confirmModal.cancelText = options.cancelText || "Huỷ";
  confirmModal.loadingText = options.loadingText || "Đang xử lý...";
  confirmModal.variant = options.variant || "primary";
  confirmModal.loading = false;
  pendingConfirmAction = onConfirm;
}

function closeConfirmModal() {
  confirmModal.open = false;
  confirmModal.loading = false;
  pendingConfirmAction = null;
}

async function handleConfirmModalConfirm() {
  if (!pendingConfirmAction || confirmModal.loading) return;

  confirmModal.loading = true;
  try {
    await pendingConfirmAction();
    closeConfirmModal();
  } catch {
    confirmModal.loading = false;
  }
}

function handleConfirmModalCancel() {
  if (confirmModal.loading) return;
  closeConfirmModal();
}

function stopPolling() {
  if (pollingTimer) {
    window.clearInterval(pollingTimer);
    pollingTimer = null;
  }
  pollingErrorCount = 0;
}

async function syncRunStatus(runId: string) {
  try {
    const state = await getRunStatus(runId);
    pollingErrorCount = 0;
    const stateTrigger = (state.trigger || "").toLowerCase();
    if (stateTrigger === "schedule") {
      stopPolling();
      currentRunState.value = null;
      await loadBackups({ silent: true });
      return;
    }

    currentRunState.value = state;
    const stateStatus = (state.status || "").toLowerCase();
    const stateOperation = (state.operation || "").toLowerCase();
    if (!["queued", "running"].includes(stateStatus)) {
      stopPolling();
      if (stateStatus === "failed" && stateOperation !== "snapshot_refresh") {
        errorMessage.value = toFriendlyBackgroundRunFailureMessage(
          state.user_message || state.message,
          state.error_code
        );
      }
      await loadBackups({ silent: true });
    }
  } catch (error) {
    pollingErrorCount += 1;
    if (pollingErrorCount >= 3) {
      stopPolling();
      await loadBackups({ silent: true });
      if (activeRunState.value && activeRunStatus.value === "running" && activeRunOperation.value === "snapshot_refresh") {
        currentRunState.value = {
          ...activeRunState.value,
          status: "failed",
          message: resolveApiErrorMessage(error, "Đồng bộ danh sách thất bại. Bạn có thể thử lại."),
        };
      }
    }
  }
}

function startPolling(runId: string, intervalMs = 3000) {
  stopPolling();
  pollingErrorCount = 0;
  void syncRunStatus(runId);
  pollingTimer = window.setInterval(() => void syncRunStatus(runId), intervalMs);
}

function syncSelectionWithCurrentPage() {
  const available = new Set(
    snapshots.value
      .map((item) => resolveSnapshotId(item))
      .filter((id): id is string => Boolean(id))
  );
  selectedSnapshotIds.value = selectedSnapshotIds.value.filter((id) => available.has(id));
}

async function loadExportsInfo() {
  try {
    exportsInfo.value = await getExportsInfo();
  } catch {
    // Không chặn luồng chính nếu endpoint thông tin exports lỗi.
  }
}

async function loadBackups(options: { silent?: boolean } = {}) {
  const silent = options.silent ?? false;
  if (!silent) {
    isLoading.value = true;
    errorMessage.value = "";
  }

  try {
    const response = await listBackups({
      page: pagination.value.page,
      per_page: pagination.value.per_page,
    });

    snapshots.value = (response.items || response.snapshots || []).map((item) => ({
      ...item,
      snapshot_id_full: item.snapshot_id_full || item.snapshot_id,
    }));
    pagination.value = response.pagination || pagination.value;
    lastSuccessfulBackupAt.value = response.last_successful_backup_at || null;
    scheduleMeta.value = response.schedule || null;
    scheduleRuntime.value = response.schedule_runtime || null;
    scheduleLabel.value = formatScheduleLabel(scheduleMeta.value);
    exportOverview.value = response.export_overview || null;
    cacheMeta.value = response.cache || null;
    const responseCacheRefreshOperation = (response.cache?.refresh_operation || "").toLowerCase();
    const responseCacheRefreshStatus = (response.cache?.refresh_status || "").toLowerCase();
    const responseCacheSnapshotRefreshActive =
      responseCacheRefreshOperation === "snapshot_refresh"
      && ["queued", "running"].includes(responseCacheRefreshStatus);
    listSyncing.value = Boolean(response.is_syncing ?? responseCacheSnapshotRefreshActive);

    syncSelectionWithCurrentPage();

    const running =
      response.active_run ||
      (response.runs || []).find((run) => ["queued", "running"].includes((run.status || "").toLowerCase()));

    if (running?.run_id) {
      currentRunState.value = running;
      startPolling(running.run_id);
    } else if (
      currentRunState.value &&
      ["success", "failed"].includes((currentRunState.value.status || "").toLowerCase())
    ) {
      currentRunState.value = null;
    }

    const cacheRefreshRunId = response.cache?.refresh_run_id || null;
    const shouldPollCacheRefresh =
      Boolean(cacheRefreshRunId)
      && responseCacheRefreshOperation === "snapshot_refresh"
      && ["queued", "running"].includes(responseCacheRefreshStatus);
    if (shouldPollCacheRefresh && cacheRefreshRunId) {
      startPolling(cacheRefreshRunId);
    }
  } catch (error) {
    if (!silent) {
      errorMessage.value = resolveApiErrorMessage(error, "Không thể tải danh sách backup.");
    }
  } finally {
    if (!silent) {
      isLoading.value = false;
    }
  }
}

function changePage(page: number) {
  pagination.value.page = page;
  void loadBackups();
}

function toggleSnapshotSelection(snapshot: BackupListItem) {
  const snapshotId = resolveSnapshotId(snapshot);
  if (!snapshotId) return;

  if (selectedSnapshotIds.value.includes(snapshotId)) {
    selectedSnapshotIds.value = selectedSnapshotIds.value.filter((id) => id !== snapshotId);
    return;
  }

  selectedSnapshotIds.value = [...selectedSnapshotIds.value, snapshotId];
}

function isSelected(snapshot: BackupListItem) {
  const snapshotId = resolveSnapshotId(snapshot);
  if (!snapshotId) return false;
  return selectedSnapshotIds.value.includes(snapshotId);
}

function toggleSelectAll() {
  if (isAllCurrentPageSelected.value) {
    selectedSnapshotIds.value = [];
    return;
  }

  selectedSnapshotIds.value = snapshots.value
    .map((item) => resolveSnapshotId(item))
    .filter((id): id is string => Boolean(id));
}

function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  window.URL.revokeObjectURL(url);
}

async function triggerBackupNow() {
  if (isTriggeringBackup.value || isRunningBackup.value || isRestoring.value) return;
  isTriggeringBackup.value = true;

  try {
    const result = await runWithFeedback(() => runBackup(), {
      loading: {
        title: "Đang khởi tạo backup",
        message: "Hệ thống đang tạo tiến trình sao lưu...",
      },
      success: {
        title: "Thành công",
        message: "Hệ thống đã sao lưu an toàn.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) => resolveApiErrorMessage(error, "Không thể kích hoạt backup."),
      },
    });

    if (result?.run_id) startPolling(result.run_id);
  } finally {
    isTriggeringBackup.value = false;
  }
}

async function triggerPrune() {
  if (isPruning.value || isRunningPrune.value || isRestoring.value || isRunningBackup.value) return;

  openConfirmModal(
    {
      title: "Xác nhận dọn snapshot",
      message: "Bạn có chắc muốn dọn bản sao lưu cũ theo chính sách retention hiện tại không?",
      confirmText: "Dọn",
      cancelText: "Huỷ",
      variant: "warning",
    },
    async () => {
      isPruning.value = true;
      try {
        const result = await runWithFeedback(() => pruneSnapshots(), {
          loading: { enabled: false },
          success: {
            title: "Đã kích hoạt",
            message: "Đã bắt đầu dọn bản sao lưu cũ.",
          },
          error: {
            title: "Có lỗi xảy ra",
            message: (error) => resolveApiErrorMessage(error, "Không thể dọn snapshot cũ."),
          },
        });

        if (result?.run_id) startPolling(result.run_id);
      } finally {
        isPruning.value = false;
      }
    }
  );
}
async function refreshList() {
  await refreshListInternal();
}

async function retrySnapshotSync() {
  if (isRetryingSync.value) return;
  isRetryingSync.value = true;

  try {
    const result = await runWithFeedback(() => refreshBackups(), {
      loading: { enabled: false },
      success: {
        title: "Đã kích hoạt",
        message: "Đã bắt đầu đồng bộ lại danh sách snapshot.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) => resolveApiErrorMessage(error, "Không thể đồng bộ lại danh sách backup."),
      },
    });

    if (result?.run_id) {
      currentRunState.value = {
        run_id: result.run_id,
        operation: result.operation || "snapshot_refresh",
        status: result.status || "queued",
        message: result.user_message || "Đã tiếp nhận yêu cầu làm mới danh sách.",
        user_message: result.user_message || "Đã tiếp nhận yêu cầu làm mới danh sách.",
        error_code: result.error_code || null,
      };
      startPolling(result.run_id);
    }

    await loadBackups({ silent: true });
  } finally {
    isRetryingSync.value = false;
  }
}

async function unlockStaleRepositoryLock() {
  if (isUnlockingStaleLock.value) return;
  isUnlockingStaleLock.value = true;

  try {
    await runWithFeedback(() => unlockStaleLock(), {
      loading: { enabled: false },
      success: {
        title: "Đã gỡ khóa",
        message: "Đã gửi yêu cầu gỡ khóa sao lưu.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) => resolveApiErrorMessage(error, "Không thể gỡ khóa sao lưu lúc này."),
      },
    });

    currentRunState.value = null;
    await loadBackups({ silent: true });
  } finally {
    isUnlockingStaleLock.value = false;
  }
}

async function refreshListInternal(options: { quiet?: boolean; silentError?: boolean } = {}) {
  const quiet = options.quiet ?? false;
  const silentError = options.silentError ?? false;
  try {
    const result = await refreshBackups();
    if (result?.run_id) {
      startPolling(result.run_id);
    }
    await loadBackups({ silent: quiet });
  } catch (error) {
    if (!silentError) {
      errorMessage.value = resolveApiErrorMessage(error, "Không thể làm mới danh sách backup.");
    }
  }
}

async function openExportsFolder() {
  const openUrl = exportsInfo.value?.open_url?.trim();
  if (openUrl) {
    window.open(openUrl, "_blank", "noopener,noreferrer");
    return;
  }

  const path = exportsRootPath.value;
  if (!path) {
    errorMessage.value = "Chưa có đường dẫn exports để mở.";
    return;
  }

  try {
    await navigator.clipboard.writeText(path);
    await runWithFeedback(
      async () => path,
      {
        loading: { enabled: false },
        success: {
          title: "Đã sao chép",
          message: "Đã sao chép đường dẫn exports vào clipboard.",
        },
        error: { enabled: false },
      }
    );
  } catch {
    errorMessage.value = `Đường dẫn exports: ${path}`;
  }
}

async function downloadExportFile(snapshotId: string | null) {
  if (!snapshotId) {
    errorMessage.value = "Không xác định được snapshot để tải export.";
    return;
  }

  await runWithFeedback(
    async () => {
      const payload = await downloadExport(snapshotId);
      downloadBlob(payload.blob, payload.filename);
      return payload;
    },
    {
      loading: {
        title: "Đang tải export",
        message: "Hệ thống đang chuẩn bị gói export...",
      },
      success: {
        title: "Thành công",
        message: "Đã tải gói export.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) => resolveApiErrorMessage(error, "Không thể tải gói export."),
      },
    }
  );
}

async function openDetails(snapshot: BackupListItem) {
  const snapshotId = resolveSnapshotId(snapshot);
  if (!snapshotId) {
    errorMessage.value = "Không xác định được snapshot để xem chi tiết.";
    return;
  }

  detailOpen.value = true;
  detailLoading.value = true;
  detailSnapshot.value = snapshot;
  detailResponse.value = null;

  try {
    const response = await getBackupDetail(snapshotId);
    detailResponse.value = response;
    detailSnapshot.value = {
      ...snapshot,
      ...response.snapshot,
    };
  } catch (error) {
    detailOpen.value = false;
    errorMessage.value = resolveApiErrorMessage(error, "Không thể tải chi tiết bản sao lưu.");
  } finally {
    detailLoading.value = false;
  }
}

function closeDetails() {
  detailOpen.value = false;
  detailLoading.value = false;
  detailSnapshot.value = null;
  detailResponse.value = null;
}

async function restoreFromDetail() {
  if (!detailSnapshot.value) return;

  const snapshotId = resolveSnapshotId(detailSnapshot.value);
  if (!snapshotId) {
    errorMessage.value = "Không xác định được snapshot để khôi phục.";
    return;
  }

  openConfirmModal(
    {
      title: "Xác nhận khôi phục",
      message: "Bạn có chắc muốn khôi phục thử vào staging không? Hành động này có thể ghi đè dữ liệu vùng staging.",
      confirmText: "Khôi phục",
      cancelText: "Huỷ",
      variant: "danger",
    },
    async () => {
      const result = await runWithFeedback(
        () =>
          restoreSnapshot(snapshotId, {
            scope: "full",
            target: "staging",
            confirm: true,
            confirm_phrase: "RESTORE",
          }),
        {
          loading: { enabled: false },
          success: {
            title: "Đã kích hoạt",
            message: "Tiến trình khôi phục đã được xếp lịch.",
          },
          error: {
            title: "Có lỗi xảy ra",
            message: (error) => resolveApiErrorMessage(error, "Không thể khởi tạo khôi phục."),
          },
        }
      );

      if (result?.run_id) {
        startPolling(result.run_id);
      }
    }
  );
}
async function deleteSnapshots(snapshotIds: string[]) {
  if (snapshotIds.length === 0) return;

  const result = await runWithFeedback(
    () =>
      forgetSnapshots({
        snapshot_ids: snapshotIds,
        prune_after: false,
      }),
    {
      loading: { enabled: false },
      success: {
        title: "Thành công",
        message:
          snapshotIds.length > 1
            ? `Đã xoá thành công ${snapshotIds.length} bản sao lưu.`
            : "Đã xoá thành công.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) => resolveApiErrorMessage(error, "Không thể xoá bản sao lưu. Vui lòng thử lại."),
      },
    }
  );

  const normalizedTargets = snapshotIds
    .map((id) => id.trim().toLowerCase())
    .filter((id) => id !== "");
  const isDeletedSnapshot = (id: string | null) => {
    const normalizedId = (id || "").trim().toLowerCase();
    if (normalizedId === "") return false;
    return normalizedTargets.some(
      (target) => normalizedId === target || normalizedId.startsWith(target)
    );
  };

  snapshots.value = snapshots.value.filter(
    (snapshot) => !isDeletedSnapshot(resolveSnapshotId(snapshot))
  );

  if (detailSnapshot.value) {
    const detailId = resolveSnapshotId(detailSnapshot.value);
    if (isDeletedSnapshot(detailId)) {
      closeDetails();
    }
  }

  selectedSnapshotIds.value = selectedSnapshotIds.value.filter(
    (id) => !isDeletedSnapshot(id)
  );

  pagination.value.total = Math.max(0, (pagination.value.total || 0) - snapshotIds.length);

  if (result?.run_id) {
    startPolling(result.run_id);
  }

  void loadBackups({ silent: true });
}
async function deleteSelectedBackups() {
  if (selectedSnapshotIds.value.length === 0 || deletingSelection.value || isForgetting.value) return;

  const ids = [...selectedSnapshotIds.value];
  openConfirmModal(
    {
      title: "Xác nhận xoá",
      message: `Bạn có chắc muốn xoá ${ids.length} bản sao lưu đã chọn không?`,
      confirmText: "Xoá",
      cancelText: "Huỷ",
      loadingText: "Đang xoá…",
      variant: "warning",
    },
    async () => {
      deletingSelection.value = true;
      try {
        await deleteSnapshots(ids);
      } finally {
        deletingSelection.value = false;
      }
    }
  );
}
async function deleteSingleBackup(snapshot: BackupListItem) {
  if (isForgetting.value) return;
  const snapshotId = resolveSnapshotId(snapshot);
  if (!snapshotId) {
    errorMessage.value = "Không xác định được snapshot để xoá.";
    return;
  }

  openConfirmModal(
    {
      title: "Xác nhận xoá",
      message: "Bạn có chắc muốn xoá bản sao lưu này không?",
      confirmText: "Xoá",
      cancelText: "Huỷ",
      loadingText: "Đang xoá…",
      variant: "warning",
    },
    async () => {
      await deleteSnapshots([snapshotId]);
    }
  );
}
onMounted(() => {
  void loadBackups();
  void loadExportsInfo();
});

onBeforeUnmount(() => {
  stopPolling();
});
</script>

