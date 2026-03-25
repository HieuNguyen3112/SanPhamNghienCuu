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
              :disabled="selectedSnapshotIds.length === 0 || deletingSelection || isDeleteBlocked"
              :title="buildDeleteSelectionTitle()"
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
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
              :disabled="isTriggeringBackup || isRunningBackup || isRestoring"
              @click="triggerBackupNow"
            >
              Sao lưu ngay
            </button>
          </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">
          Xóa snapshot sẽ gỡ bản sao lưu khỏi danh sách sau khi tiến trình hoàn tất. Việc dọn dung lượng được hệ thống xử lý riêng theo bảo trì.
        </p>
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
        <p class="mt-1 text-xs text-indigo-700">{{ activeRunDescription }}</p>
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
                    :disabled="currentPageSelectableSnapshotIds.length === 0"
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
                    :disabled="!isSnapshotBulkSelectable(snapshot)"
                    @change="toggleSnapshotSelection(snapshot)"
                  />
                </td>
                <td class="px-2 py-3 align-top">
                  <p class="font-semibold text-slate-800">{{ snapshot.backup_name || `Backup ${snapshot.short_id}` }}</p>
                  <p class="text-xs text-slate-500">{{ mapBackupType(snapshot.backup_type) }}</p>
                  <p v-if="isSnapshotDeleting(snapshot)" class="mt-1 text-[11px] text-amber-700">
                    Snapshot đang được xóa ở nền. Hàng này sẽ biến mất sau khi tiến trình hoàn tất.
                  </p>
                  <p
                    v-else-if="shouldShowExportStateHint(snapshot)"
                    class="mt-1 text-[11px]"
                    :class="exportStateTextClass(snapshot.export_state)"
                  >
                    {{ resolveExportStateMessage(snapshot) }}
                  </p>
                </td>
                <td class="px-2 py-3 text-slate-700">{{ formatDateTime(snapshot.created_at) }}</td>
                <td class="px-2 py-3 text-slate-700">{{ backupSummary(snapshot) }}</td>
                <td class="px-2 py-3 text-slate-700">{{ formatSize(snapshot.size_bytes) }}</td>
                <td class="px-2 py-3">
                  <div class="space-y-1">
                    <span
                      class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                      :class="isSnapshotDeleting(snapshot) ? 'bg-amber-100 text-amber-700' : statusBadgeClass(snapshot.status || snapshot.run_state)"
                    >
                      {{ isSnapshotDeleting(snapshot) ? "Đang xóa..." : mapStatusLabel(snapshot.status || snapshot.run_state) }}
                    </span>
                    <p
                      v-if="isSnapshotDeleting(snapshot)"
                      class="text-[11px] text-amber-700"
                    >
                      Các thao tác trên snapshot này đã được tạm khóa.
                    </p>
                    <p
                      v-else-if="shouldShowExportStateHint(snapshot)"
                      class="text-[11px]"
                      :class="exportStateTextClass(snapshot.export_state)"
                    >
                      Export: {{ mapExportStateLabel(snapshot.export_state) }}
                    </p>
                  </div>
                </td>
                <td class="px-2 py-3">
                  <div class="flex flex-wrap justify-end gap-1.5">
                    <button
                      class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                      :disabled="isSnapshotActionLocked(snapshot)"
                      :title="buildDetailActionTitle(snapshot)"
                      @click="openDetails(snapshot)"
                    >
                      Xem chi tiết
                    </button>
                    <button
                      class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                      :disabled="!snapshot.export_available || isSnapshotActionLocked(snapshot)"
                      :title="buildExportActionTitle(snapshot)"
                      @click="downloadExportFile(resolveSnapshotId(snapshot))"
                    >
                      {{ buildExportActionLabel(snapshot) }}
                    </button>
                    <button
                      class="rounded-lg border border-rose-200 px-2.5 py-1 text-xs text-rose-700 hover:bg-rose-50 disabled:opacity-50"
                      :disabled="isSnapshotDeleteBlocked(snapshot)"
                      :title="buildDeleteActionTitle(snapshot)"
                      @click="deleteSingleBackup(snapshot)"
                    >
                      {{ buildDeleteActionLabel(snapshot) }}
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
            :disabled="!detailResponse?.export.available || isSnapshotActionLocked(detailSnapshot)"
            :title="buildDetailExportActionTitle()"
            @click="downloadExportFile(resolveSnapshotId(detailSnapshot))"
          >
            {{ buildDetailExportActionLabel() }}
          </button>
          <button
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            :disabled="isSnapshotActionLocked(detailSnapshot)"
            :title="buildRestoreActionTitle(detailSnapshot)"
            @click="restoreFromDetail"
          >
            Khôi phục thử (staging)
          </button>
          <button
            class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-50"
            :disabled="isSnapshotDeleteBlocked(detailSnapshot)"
            :title="buildDeleteActionTitle(detailSnapshot)"
            @click="deleteSingleBackup(detailSnapshot)"
          >
            {{ isSnapshotDeleting(detailSnapshot) ? "Đang xóa..." : "Xóa bản sao lưu" }}
          </button>
        </div>
        <p v-if="isSnapshotDeleting(detailSnapshot)" class="mt-2 text-xs text-amber-700">
          Snapshot đang được xóa ở nền. Các thao tác khác đã được tạm khóa cho đến khi tiến trình hoàn tất.
        </p>

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
              <span
                class="font-medium"
                :class="exportStateTextClass(detailResponse?.export.state)"
              >
                {{ mapExportStateLabel(detailResponse?.export.state) }}
              </span>
            </p>
            <p v-if="detailExportMessage" class="mt-1 text-xs" :class="exportStateTextClass(detailResponse?.export.state)">
              {{ detailExportMessage }}
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
const deletingSelection = ref(false);
const selectedSnapshotIds = ref<string[]>([]);
const deletingSnapshotIds = ref<string[]>([]);
const activeDeleteRunId = ref<string | null>(null);

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
const isRunningBackupPostprocess = computed(
  () => activeRunProcessing.value && activeRunOperation.value === "backup_postprocess"
);
const isRunningPrune = computed(() => activeRunProcessing.value && activeRunOperation.value === "prune");
const isRestoring = computed(() => activeRunProcessing.value && activeRunOperation.value === "restore");
const isForgetting = computed(() => activeRunProcessing.value && activeRunOperation.value === "forget");
const hasActiveBackupPostprocess = computed(() =>
  isRunningBackupPostprocess.value
  || snapshots.value.some((snapshot) => isRunActive(snapshot.export_run, "backup_postprocess"))
  || isRunActive(detailResponse.value?.export.run || null, "backup_postprocess")
);
const activeRunDeleteSnapshotIds = computed(() => {
  if (!isForgetting.value) return [];
  const ids = activeRunState.value?.snapshot_ids;
  return Array.isArray(ids) ? normalizeSnapshotIds(ids) : [];
});
const effectiveDeletingSnapshotIds = computed(() =>
  normalizeSnapshotIds([...deletingSnapshotIds.value, ...activeRunDeleteSnapshotIds.value])
);
const hasDeletingSnapshots = computed(() => effectiveDeletingSnapshotIds.value.length > 0);
const isDeleteBlocked = computed(
  () => isForgetting.value || hasActiveBackupPostprocess.value || hasDeletingSnapshots.value
);
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

function looksLikeMojibake(value: string | null | undefined) {
  const normalized = (value || "").trim();
  if (!normalized) return false;
  return [
    "\u00C3",
    "\u00C2",
    "\u00C6",
    "\u00E2\u20AC",
    "\uFFFD",
  ].some((marker) => normalized.includes(marker));
}

function readableBackupMessage(value: string | null | undefined, fallback = "") {
  const normalized = (value || "").trim();
  if (!normalized) return fallback;
  return looksLikeMojibake(normalized) ? fallback : normalized;
}

function fallbackExportStateMessage(state: string | null | undefined) {
  const normalized = (state || "").toLowerCase();
  if (normalized === "ready") return "Readable export \u0111\u00e3 s\u1eb5n s\u00e0ng \u0111\u1ec3 t\u1ea3i.";
  if (normalized === "running") return "Readable export \u0111ang \u0111\u01b0\u1ee3c t\u1ea1o v\u00e0 \u0111\u1ed3ng b\u1ed9 l\u00ean Drive.";
  if (normalized === "queued" || normalized === "pending") return "Readable export \u0111ang ch\u1edd x\u1eed l\u00fd \u1edf n\u1ec1n.";
  if (normalized === "finalizing") return "Readable export \u0111\u00e3 x\u1eed l\u00fd xong, \u0111ang ch\u1edd c\u00f4ng b\u1ed1 ho\u00e0n t\u1ea5t.";
  if (normalized === "failed") return "Snapshot an to\u00e0n \u0111\u00e3 xong nh\u01b0ng readable export ch\u01b0a ho\u00e0n thi\u1ec7n.";
  if (normalized === "disabled") return "Readable export \u0111ang t\u1eaft theo c\u1ea5u h\u00ecnh.";
  return "Readable export ch\u01b0a s\u1eb5n s\u00e0ng.";
}

function toFriendlySyncMessage(rawMessage: string | null | undefined) {
  const normalized = (rawMessage || "").trim();
  const normalizedLower = normalized.toLowerCase();
  if (
    normalizedLower.includes("timed out")
    || normalizedLower.includes("timeout")
    || normalizedLower.includes("qu\u00e1 th\u1eddi gian")
    || normalizedLower.includes("exceeded the timeout")
  ) {
    return "\u0110\u1ed3ng b\u1ed9 danh s\u00e1ch b\u1ecb qu\u00e1 th\u1eddi gian. Vui l\u00f2ng th\u1eed l\u1ea1i.";
  }

  if (normalized !== "" && !looksLikeMojibake(normalized) && !isTechnicalMessage(normalized)) {
    return normalized;
  }

  return "\u0110\u1ed3ng b\u1ed9 danh s\u00e1ch th\u1ea5t b\u1ea1i. B\u1ea1n c\u00f3 th\u1ec3 th\u1eed l\u1ea1i.";
}

function toFriendlyBackgroundRunFailureMessage(
  userMessage: string | null | undefined,
  errorCode: string | null | undefined,
  operation?: string | null | undefined
) {
  const code = (errorCode || "").trim().toUpperCase();
  const normalizedOperation = (operation || "").trim().toLowerCase();
  const primary = (userMessage || "").trim();
  if (code === "BACKUP_LOCKED") {
    if (normalizedOperation === "forget") {
      return "Kh\u00f4ng th\u1ec3 x\u00f3a snapshot l\u00fac n\u00e0y v\u00ec h\u1ec7 th\u1ed1ng sao l\u01b0u \u0111ang b\u1eadn. N\u1ebfu snapshot v\u1eeba sao l\u01b0u xong, h\u00e3y \u0111\u1ee3i readable export ho\u00e0n t\u1ea5t r\u1ed3i th\u1eed l\u1ea1i.";
    }
    return "B\u1ea3n sao l\u01b0u \u0111ang b\u1ecb kh\u00f3a b\u1edfi ti\u1ebfn tr\u00ecnh kh\u00e1c. N\u1ebfu kh\u00f4ng c\u00f2n t\u00e1c v\u1ee5 n\u00e0o ch\u1ea1y, b\u1ea1n c\u00f3 th\u1ec3 d\u00f9ng n\u00fat \u201cG\u1ee1 kh\u00f3a treo\u201d.";
  }
  if (code === "RUN_TIMEOUT") {
    return "Ti\u1ebfn tr\u00ecnh n\u1ec1n b\u1ecb qu\u00e1 th\u1eddi gian. B\u1ea1n c\u00f3 th\u1ec3 th\u1eed l\u1ea1i.";
  }

  if (primary !== "" && !looksLikeMojibake(primary) && !isTechnicalMessage(primary)) {
    return primary;
  }

  return "Kh\u00f4ng th\u1ec3 x\u1eed l\u00fd t\u00e1c v\u1ee5 sao l\u01b0u. Vui l\u00f2ng th\u1eed l\u1ea1i.";
}

const activeRunDescription = computed(() =>
  readableBackupMessage(activeRunState.value?.user_message || activeRunState.value?.message, "\u0110ang x\u1eed l\u00fd...")
);

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

const currentPageSelectableSnapshotIds = computed(() =>
  snapshots.value
    .filter((snapshot) => isSnapshotBulkSelectable(snapshot))
    .map((item) => resolveSnapshotId(item))
    .filter((id): id is string => Boolean(id))
);

const isAllCurrentPageSelected = computed(() => {
  const ids = currentPageSelectableSnapshotIds.value;
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

function normalizeSnapshotId(value: string | null | undefined) {
  return (value || "").trim().toLowerCase();
}

function normalizeSnapshotIds(values: string[]) {
  return Array.from(
    new Set(values.map((value) => normalizeSnapshotId(value)).filter((value) => value !== ""))
  );
}

function matchesSnapshotTarget(snapshotId: string, targetId: string) {
  return snapshotId === targetId || snapshotId.startsWith(targetId) || targetId.startsWith(snapshotId);
}

function snapshotMatchesAnyDeleteTarget(
  snapshot: BackupListItem | null | undefined,
  targets = effectiveDeletingSnapshotIds.value
) {
  const snapshotId = normalizeSnapshotId(resolveSnapshotId(snapshot));
  if (!snapshotId) return false;
  return targets.some((targetId) => matchesSnapshotTarget(snapshotId, targetId));
}

function isSnapshotDeleting(snapshot: BackupListItem | null | undefined) {
  return snapshotMatchesAnyDeleteTarget(snapshot);
}

function isSnapshotActionLocked(snapshot: BackupListItem | null | undefined) {
  return isSnapshotDeleting(snapshot);
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

function mapExportStateLabel(state: string | null | undefined) {
  const normalized = (state || "").toLowerCase();
  if (normalized === "ready") return "Sẵn sàng";
  if (normalized === "running") return "Đang xử lý";
  if (normalized === "queued") return "Đang chờ";
  if (normalized === "pending") return "Đã tiếp nhận";
  if (normalized === "finalizing") return "Đang hoàn tất";
  if (normalized === "failed") return "Thất bại";
  if (normalized === "disabled") return "Đã tắt";
  return "Chưa sẵn sàng";
}

function exportStateTextClass(state: string | null | undefined) {
  const normalized = (state || "").toLowerCase();
  if (normalized === "ready") return "text-emerald-700";
  if (["running", "queued", "pending", "finalizing"].includes(normalized)) return "text-amber-700";
  if (normalized === "failed") return "text-rose-700";
  if (normalized === "disabled") return "text-slate-500";
  return "text-slate-600";
}

function resolveExportStateMessage(snapshot: BackupListItem) {
  return readableBackupMessage(snapshot.export_message, fallbackExportStateMessage(snapshot.export_state));
}

function shouldShowExportStateHint(snapshot: BackupListItem) {
  return (snapshot.export_state || "").toLowerCase() !== "ready";
}

function buildExportActionLabel(snapshot: BackupListItem) {
  if (isSnapshotDeleting(snapshot)) return "Đang xóa...";
  const normalized = (snapshot.export_state || "").toLowerCase();
  if (snapshot.export_available) return "Tải export";
  if (["running", "queued", "pending", "finalizing"].includes(normalized)) return "Đang tạo export...";
  if (normalized === "failed") return "Export lỗi";
  if (normalized === "disabled") return "Export đã tắt";
  return "Chưa có export";
}

function buildExportActionTitle(snapshot: BackupListItem) {
  if (isSnapshotDeleting(snapshot)) {
    return "Snapshot đang được xóa. Tải export đã tạm khóa cho đến khi tiến trình hoàn tất.";
  }
  if (snapshot.export_available) return "Tải gói export đã hoàn tất.";
  return resolveExportStateMessage(snapshot);
}

function buildDetailExportActionLabel() {
  const snapshot = detailResponse.value?.snapshot || detailSnapshot.value;
  if (!snapshot) return "Tải export";
  return buildExportActionLabel(snapshot);
}

function buildDetailExportActionTitle() {
  const snapshot = detailResponse.value?.snapshot || detailSnapshot.value;
  if (!snapshot) return "T\u1ea3i export";
  return buildExportActionTitle(snapshot);
}

const detailExportMessage = computed(() => {
  const exportPayload = detailResponse.value?.export;
  if (!exportPayload) return "";
  return readableBackupMessage(exportPayload.message, fallbackExportStateMessage(exportPayload.state));
});

function mapOperationLabel(operation: string | undefined) {
  const s = (operation || "").toLowerCase();
  if (s === "backup") return "Tiến trình sao lưu";
  if (s === "backup_postprocess") return "Hoàn thiện readable export";
  if (s === "prune") return "Tiến trình dọn snapshot";
  if (s === "restore") return "Tiến trình khôi phục";
  if (s === "forget") return "Xóa snapshot";
  if (s === "snapshot_refresh") return "Làm mới danh sách";
  return "Tiến trình hệ thống";
}

function isRunActive(run: BackupRunState | null | undefined, operation?: string) {
  if (!run) return false;
  const status = (run.status || "").toLowerCase();
  if (!["queued", "running"].includes(status)) return false;

  if (!operation) return true;

  return (run.operation || "").toLowerCase() === operation.toLowerCase();
}

function resolveDeleteBlockedMessage() {
  if (hasDeletingSnapshots.value || isForgetting.value) {
    return "Đang có tiến trình xóa snapshot. Vui lòng đợi hoàn tất.";
  }
  if (hasActiveBackupPostprocess.value) {
    return "Readable export vẫn đang xử lý ở nền. Vui lòng đợi hoàn tất trước khi xóa snapshot.";
  }

  return "";
}

function isSnapshotDeleteBlocked(snapshot: BackupListItem | null | undefined) {
  if (isDeleteBlocked.value) return true;

  return isRunActive(snapshot?.export_run || null, "backup_postprocess");
}

function isSnapshotBulkSelectable(snapshot: BackupListItem | null | undefined) {
  return !isSnapshotDeleting(snapshot) && !isSnapshotDeleteBlocked(snapshot);
}

function buildDeleteSelectionTitle() {
  const blockedMessage = resolveDeleteBlockedMessage();
  if (blockedMessage) return blockedMessage;

  if (selectedSnapshotIds.value.length === 0) {
    return "Chọn ít nhất một snapshot để xóa.";
  }

  return "Xóa các snapshot đã chọn.";
}

function buildDeleteActionTitle(snapshot: BackupListItem | null | undefined) {
  if (isSnapshotDeleting(snapshot)) {
    return "Snapshot đang được xóa ở nền. Vui lòng đợi tiến trình hoàn tất.";
  }
  if (isSnapshotDeleteBlocked(snapshot)) {
    return resolveDeleteBlockedMessage() || "Snapshot đang tạm thời không thể xóa.";
  }

  return "Xóa snapshot này.";
}

function buildDeleteActionLabel(snapshot: BackupListItem | null | undefined) {
  return isSnapshotDeleting(snapshot) ? "Đang xóa..." : "Xóa";
}

function buildDetailActionTitle(snapshot: BackupListItem | null | undefined) {
  if (isSnapshotActionLocked(snapshot)) {
    return "Snapshot đang được xóa. Tạm thời chưa thể mở thêm thao tác chi tiết.";
  }

  return "Xem chi tiết snapshot này.";
}

function buildRestoreActionTitle(snapshot: BackupListItem | null | undefined) {
  if (isSnapshotActionLocked(snapshot)) {
    return "Snapshot đang được xóa. Tạm thời chưa thể khôi phục.";
  }

  return "Khôi phục thử snapshot này vào staging.";
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

function clearDeleteTracking(runId?: string | null) {
  if (runId && activeDeleteRunId.value && activeDeleteRunId.value !== runId) return;
  deletingSnapshotIds.value = [];
  activeDeleteRunId.value = null;
}

function beginDeleteTracking(snapshotIds: string[], runId?: string | null) {
  deletingSnapshotIds.value = normalizeSnapshotIds(snapshotIds);
  activeDeleteRunId.value = runId || null;
  selectedSnapshotIds.value = selectedSnapshotIds.value.filter(
    (selectedId) =>
      !deletingSnapshotIds.value.some((targetId) => matchesSnapshotTarget(normalizeSnapshotId(selectedId), targetId))
  );
}

function finalizeDeleteSuccess(runId?: string | null) {
  if (runId && activeDeleteRunId.value && activeDeleteRunId.value !== runId) return;
  const targets = [...deletingSnapshotIds.value];
  if (targets.length === 0) {
    clearDeleteTracking(runId);
    return;
  }

  const previousTotal = snapshots.value.length;
  snapshots.value = snapshots.value.filter((snapshot) => !snapshotMatchesAnyDeleteTarget(snapshot, targets));
  const removedCount = previousTotal - snapshots.value.length;

  if (detailSnapshot.value && snapshotMatchesAnyDeleteTarget(detailSnapshot.value, targets)) {
    closeDetails();
  }

  selectedSnapshotIds.value = selectedSnapshotIds.value.filter(
    (selectedId) => !targets.some((targetId) => matchesSnapshotTarget(normalizeSnapshotId(selectedId), targetId))
  );

  if (removedCount > 0) {
    pagination.value.total = Math.max(0, (pagination.value.total || 0) - removedCount);
  }

  clearDeleteTracking(runId);
}

function reconcileDeleteTrackingFromCurrentData() {
  if (deletingSnapshotIds.value.length === 0) return;
  if (isForgetting.value) return;

  const stillExists = snapshots.value.some((snapshot) => snapshotMatchesAnyDeleteTarget(snapshot));
  if (!stillExists) {
    if (detailSnapshot.value && snapshotMatchesAnyDeleteTarget(detailSnapshot.value)) {
      closeDetails();
    }
    clearDeleteTracking();
  }
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
      if (stateOperation === "forget") {
        if (stateStatus === "success") {
          finalizeDeleteSuccess(state.run_id);
        } else {
          clearDeleteTracking(state.run_id);
        }
      }
      stopPolling();
      if (stateStatus === "failed" && stateOperation !== "snapshot_refresh") {
        errorMessage.value = toFriendlyBackgroundRunFailureMessage(
          state.user_message || state.message,
          state.error_code,
          state.operation
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
    reconcileDeleteTrackingFromCurrentData();

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
  if (!snapshotId || !isSnapshotBulkSelectable(snapshot)) return;

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

  selectedSnapshotIds.value = [...currentPageSelectableSnapshotIds.value];
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
        title: "Đã khởi tạo",
        message: "Yêu cầu sao lưu đã được tiếp nhận. Hệ thống sẽ cập nhật kết quả khi tiến trình hoàn tất.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) => resolveApiErrorMessage(error, "Không thể kích hoạt backup."),
      },
    });

    if (result?.run_id) {
      currentRunState.value = {
        run_id: result.run_id,
        operation: result.operation || "backup",
        status: result.status || "queued",
        message: result.user_message || "Đã tiếp nhận yêu cầu sao lưu. Hệ thống đang chạy tác vụ nền.",
        user_message: result.user_message || "Đã tiếp nhận yêu cầu sao lưu. Hệ thống đang chạy tác vụ nền.",
        error_code: result.error_code || null,
      };
      startPolling(result.run_id);
    }
  } finally {
    isTriggeringBackup.value = false;
  }
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
  if (isSnapshotActionLocked(snapshot)) return;
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
  if (isSnapshotActionLocked(detailSnapshot.value)) return;

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

  const normalizedTargets = normalizeSnapshotIds(snapshotIds);

  const result = await runWithFeedback(
    () => forgetSnapshots({ snapshot_ids: snapshotIds }),
    {
      loading: { enabled: false },
      success: {
        title: "Đã tiếp nhận",
        message:
          snapshotIds.length > 1
            ? `Đã tiếp nhận yêu cầu xóa ${snapshotIds.length} bản sao lưu. Các hàng sẽ biến mất khi tiến trình xóa hoàn tất.`
            : "Đã tiếp nhận yêu cầu xóa bản sao lưu. Hàng này sẽ biến mất khi tiến trình xóa hoàn tất.",
      },
      error: {
        title: "Có lỗi xảy ra",
        message: (error) => resolveApiErrorMessage(error, "Không thể xoá bản sao lưu. Vui lòng thử lại."),
      },
    }
  );

  if (result?.run_id) {
    beginDeleteTracking(normalizedTargets, result.run_id);
    currentRunState.value = {
      run_id: result.run_id,
      operation: result.operation || "forget",
      status: result.status || "queued",
      message: result.user_message || "Đã tiếp nhận yêu cầu xóa bản sao lưu.",
      user_message: result.user_message || "Đã tiếp nhận yêu cầu xóa bản sao lưu.",
      error_code: result.error_code || null,
      snapshot_ids: normalizedTargets,
    };
    startPolling(result.run_id);
    return;
  }

  void loadBackups({ silent: true });
}
async function deleteSelectedBackups() {
  if (selectedSnapshotIds.value.length === 0 || deletingSelection.value || isDeleteBlocked.value) return;

  const ids = [...selectedSnapshotIds.value];
  openConfirmModal(
    {
      title: "Xác nhận xoá",
      message: `Bạn có chắc muốn xoá ${ids.length} bản sao lưu đã chọn không? Các snapshot này sẽ được gỡ khỏi danh sách sau khi tiến trình xóa hoàn tất.`,
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
  if (isSnapshotDeleteBlocked(snapshot)) return;
  const snapshotId = resolveSnapshotId(snapshot);
  if (!snapshotId) {
    errorMessage.value = "Không xác định được snapshot để xoá.";
    return;
  }

  openConfirmModal(
    {
      title: "Xác nhận xoá",
      message: "Bạn có chắc muốn xoá bản sao lưu này không? Snapshot sẽ được gỡ khỏi danh sách sau khi tiến trình xóa hoàn tất.",
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


