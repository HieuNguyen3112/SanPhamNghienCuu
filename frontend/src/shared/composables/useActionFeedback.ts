import { ref } from "vue";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

const DEFAULT_LOADING_DELAY_MS = 450;
const DEFAULT_MIN_SHOW_TIME_MS = 250;
const DEFAULT_GENERIC_ERROR_MESSAGE = "Thao tác thất bại. Vui lòng thử lại.";

let loadingOwner: symbol | null = null;
let loadingShownAt = 0;

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => window.setTimeout(resolve, ms));
}

type UnknownObject = Record<string, unknown>;

function isObject(value: unknown): value is UnknownObject {
  return typeof value === "object" && value !== null;
}

function readErrorMessageFromResponseData(data: unknown): string | null {
  if (!isObject(data)) return null;

  const message = data.message;
  if (typeof message === "string" && message.trim()) {
    return message.trim();
  }

  const errors = data.errors;
  if (!isObject(errors)) return null;

  const firstEntry = Object.values(errors).find((value) => Array.isArray(value));
  if (!Array.isArray(firstEntry) || firstEntry.length === 0) return null;
  const firstMessage = firstEntry[0];
  if (typeof firstMessage !== "string") return null;
  return firstMessage.trim() || null;
}

export function resolveApiErrorMessage(
  error: unknown,
  fallback = DEFAULT_GENERIC_ERROR_MESSAGE
): string {
  if (isObject(error)) {
    const response = error.response;
    if (isObject(response)) {
      const fromResponseData = readErrorMessageFromResponseData(response.data);
      if (fromResponseData) return fromResponseData;
    }
  }

  if (error instanceof Error && error.message.trim()) {
    const normalized = error.message.trim();
    if (/Network Error|ECONN|timeout|Failed to fetch/i.test(normalized)) {
      return "Không thể kết nối máy chủ. Vui lòng thử lại.";
    }
    return normalized;
  }

  if (typeof error === "string" && error.trim()) {
    return error.trim();
  }

  return fallback;
}

export interface ActionFeedbackRunOptions<TResult> {
  loading?: {
    enabled?: boolean;
    title?: string;
    message?: string;
    delayMs?: number;
    minShowMs?: number;
  };
  success?: {
    enabled?: boolean;
    title?: string;
    message?: string | ((result: TResult) => string);
    details?: unknown;
  };
  error?: {
    enabled?: boolean;
    title?: string;
    message?: string | ((error: unknown) => string);
    fallbackMessage?: string;
    details?: unknown;
  };
  rethrow?: boolean;
}

export function useActionFeedback() {
  const running = ref(false);
  const {
    showLoadingModal,
    closeActionResultModal,
    showSuccessModal,
    showErrorModal,
  } = useActionResultModal();

  async function runWithFeedback<TResult>(
    action: () => Promise<TResult>,
    options: ActionFeedbackRunOptions<TResult> = {}
  ): Promise<TResult> {
    const owner = Symbol("action-feedback");
    const shouldShowLoading = options.loading?.enabled ?? true;
    const loadingDelayMs = options.loading?.delayMs ?? DEFAULT_LOADING_DELAY_MS;
    const loadingMinShowMs =
      options.loading?.minShowMs ?? DEFAULT_MIN_SHOW_TIME_MS;
    const shouldShowSuccess = options.success?.enabled ?? true;
    const shouldShowError = options.error?.enabled ?? true;
    const shouldRethrow = options.rethrow ?? true;

    let completed = false;
    let loadingShownByCurrentAction = false;
    let loadingClosed = false;
    let loadingTimer: ReturnType<typeof window.setTimeout> | null = null;

    running.value = true;

    if (shouldShowLoading) {
      loadingTimer = window.setTimeout(() => {
        if (completed || loadingOwner) return;
        loadingOwner = owner;
        loadingShownAt = Date.now();
        loadingShownByCurrentAction = true;
        showLoadingModal(
          options.loading?.message ?? "Đang xử lý dữ liệu...",
          options.loading?.title ?? "Đang xử lý"
        );
      }, Math.max(0, loadingDelayMs));
    }

    async function closeOwnedLoadingIfNeeded() {
      if (loadingClosed) return;
      if (!loadingShownByCurrentAction || loadingOwner !== owner) return;

      const elapsed = Date.now() - loadingShownAt;
      const remaining = Math.max(0, loadingMinShowMs - elapsed);
      if (remaining > 0) {
        await sleep(remaining);
      }
      loadingOwner = null;
      loadingShownAt = 0;
      loadingClosed = true;
      await closeActionResultModal();
    }

    try {
      const result = await action();
      await closeOwnedLoadingIfNeeded();

      if (shouldShowSuccess) {
        const successMessage =
          typeof options.success?.message === "function"
            ? options.success.message(result)
            : options.success?.message;
        if (successMessage) {
          showSuccessModal(
            successMessage,
            options.success?.title ?? "Thành công",
            options.success?.details
          );
        }
      }

      return result;
    } catch (error) {
      await closeOwnedLoadingIfNeeded();
      if (shouldShowError) {
        const resolvedMessage =
          typeof options.error?.message === "function"
            ? options.error.message(error)
            : options.error?.message ||
              resolveApiErrorMessage(
                error,
                options.error?.fallbackMessage ?? DEFAULT_GENERIC_ERROR_MESSAGE
              );
        showErrorModal(
          resolvedMessage,
          options.error?.title ?? "Có lỗi xảy ra",
          options.error?.details ?? error
        );
      }

      if (shouldRethrow) throw error;
      return undefined as TResult;
    } finally {
      completed = true;
      running.value = false;

      if (loadingTimer) {
        window.clearTimeout(loadingTimer);
      }

      await closeOwnedLoadingIfNeeded();
    }
  }

  return {
    running,
    runWithFeedback,
  };
}
