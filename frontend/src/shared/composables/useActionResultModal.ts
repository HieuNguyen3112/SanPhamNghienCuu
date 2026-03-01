import { reactive } from "vue";

export type ActionResultType = "success" | "error" | "warning" | "info";

type ActionResultCallback = (() => void | Promise<void>) | null;

export interface ActionResultModalState {
  open: boolean;
  type: ActionResultType;
  title: string;
  message: string;
  details: unknown;
  loading: boolean;
  disableClose: boolean;
  closeLabel: string;
  secondaryLabel: string | null;
  onClose: ActionResultCallback;
  onSecondary: ActionResultCallback;
}

const actionResultModal = reactive<ActionResultModalState>({
  open: false,
  type: "info",
  title: "",
  message: "",
  details: null,
  loading: false,
  disableClose: false,
  closeLabel: "Đóng",
  secondaryLabel: null,
  onClose: null,
  onSecondary: null,
});

function resetCallbacks() {
  actionResultModal.onClose = null;
  actionResultModal.onSecondary = null;
}

export function useActionResultModal() {
  function openActionResultModal(payload: {
    type: ActionResultType;
    title: string;
    message: string;
    details?: unknown;
    closeLabel?: string;
    secondaryLabel?: string | null;
    loading?: boolean;
    disableClose?: boolean;
    onClose?: ActionResultCallback;
    onSecondary?: ActionResultCallback;
  }) {
    actionResultModal.type = payload.type;
    actionResultModal.title = payload.title;
    actionResultModal.message = payload.message;
    actionResultModal.details = payload.details ?? null;
    actionResultModal.loading = payload.loading ?? false;
    actionResultModal.disableClose = payload.disableClose ?? false;
    actionResultModal.closeLabel = payload.closeLabel?.trim() || "Đóng";
    actionResultModal.secondaryLabel = payload.secondaryLabel ?? null;
    actionResultModal.onClose = payload.onClose ?? null;
    actionResultModal.onSecondary = payload.onSecondary ?? null;
    actionResultModal.open = true;
  }

  function showSuccessModal(
    message: string,
    title = "Thành công",
    details?: unknown,
    options?: {
      secondaryLabel?: string | null;
      onClose?: ActionResultCallback;
      onSecondary?: ActionResultCallback;
    }
  ) {
    openActionResultModal({
      type: "success",
      title,
      message,
      details,
      secondaryLabel: options?.secondaryLabel ?? null,
      onClose: options?.onClose ?? null,
      onSecondary: options?.onSecondary ?? null,
    });
  }

  function showErrorModal(
    message: string,
    title = "Có lỗi xảy ra",
    details?: unknown,
    options?: {
      secondaryLabel?: string | null;
      onClose?: ActionResultCallback;
      onSecondary?: ActionResultCallback;
    }
  ) {
    openActionResultModal({
      type: "error",
      title,
      message,
      details,
      loading: false,
      disableClose: false,
      secondaryLabel: options?.secondaryLabel ?? null,
      onClose: options?.onClose ?? null,
      onSecondary: options?.onSecondary ?? null,
    });
  }

  function showLoadingModal(message: string, title = "Đang xử lý") {
    openActionResultModal({
      type: "info",
      title,
      message,
      closeLabel: "Đang xử lý...",
      secondaryLabel: null,
      loading: true,
      disableClose: true,
      onClose: null,
      onSecondary: null,
    });
  }

  async function closeActionResultModal() {
    const closeCallback = actionResultModal.onClose;
    actionResultModal.open = false;
    actionResultModal.details = null;
    actionResultModal.loading = false;
    actionResultModal.disableClose = false;
    actionResultModal.closeLabel = "Đóng";
    actionResultModal.secondaryLabel = null;
    resetCallbacks();

    if (closeCallback) {
      await closeCallback();
    }
  }

  async function runActionResultSecondary() {
    const secondaryCallback = actionResultModal.onSecondary;
    if (secondaryCallback) {
      await secondaryCallback();
    }

    await closeActionResultModal();
  }

  return {
    actionResultModal,
    openActionResultModal,
    showSuccessModal,
    showErrorModal,
    showLoadingModal,
    closeActionResultModal,
    runActionResultSecondary,
  };
}
