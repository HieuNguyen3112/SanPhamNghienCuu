import { reactive } from "vue";

export type ActionResultType = "success" | "error" | "warning" | "info";

type ActionResultCallback = (() => void | Promise<void>) | null;

export interface ActionResultModalState {
  open: boolean;
  type: ActionResultType;
  title: string;
  message: string;
  details: unknown;
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
  closeLabel: "\u0110\u00f3ng",
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
    onClose?: ActionResultCallback;
    onSecondary?: ActionResultCallback;
  }) {
    actionResultModal.type = payload.type;
    actionResultModal.title = payload.title;
    actionResultModal.message = payload.message;
    actionResultModal.details = payload.details ?? null;
    actionResultModal.closeLabel =
      payload.closeLabel?.trim() || "\u0110\u00f3ng";
    actionResultModal.secondaryLabel = payload.secondaryLabel ?? null;
    actionResultModal.onClose = payload.onClose ?? null;
    actionResultModal.onSecondary = payload.onSecondary ?? null;
    actionResultModal.open = true;
  }

  function showSuccessModal(
    message: string,
    title = "Th\u00e0nh c\u00f4ng",
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
    title = "C\u00f3 l\u1ed7i x\u1ea3y ra",
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
      secondaryLabel: options?.secondaryLabel ?? null,
      onClose: options?.onClose ?? null,
      onSecondary: options?.onSecondary ?? null,
    });
  }

  async function closeActionResultModal() {
    const closeCallback = actionResultModal.onClose;
    actionResultModal.open = false;
    actionResultModal.details = null;
    actionResultModal.closeLabel = "\u0110\u00f3ng";
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
    closeActionResultModal,
    runActionResultSecondary,
  };
}
