import { computed, ref } from "vue";
import type { DeclarationStatusUi } from "../contracts/declarationSharedContract";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

export type SubmitForApprovalPayload = {
  minorChange: boolean;
};

type UseDeclarationShellOptions = {
  initial_status: DeclarationStatusUi;
  on_save_draft: () => Promise<void>;
  on_submit: (
    payload: SubmitForApprovalPayload,
  ) => Promise<DeclarationStatusUi | void>;
};

export function useDeclarationFormShell(opts: UseDeclarationShellOptions) {
  const status = ref<DeclarationStatusUi>(opts.initial_status);
  const is_saving = ref(false);
  const is_submitting = ref(false);
  const error_message = ref<string | null>(null);

  // Giữ tương thích ngược với các form đang dùng state này.
  const success_message = ref<string | null>(null);
  const success_visible = ref(false);

  const { closeActionResultModal } = useActionResultModal();
  const { runWithFeedback } = useActionFeedback();

  const pending = computed(() => is_saving.value || is_submitting.value);
  const is_read_only = computed(
    () => !["DRAFT", "MEMBER_REJECTED", "NEED_REVISION"].includes(status.value),
  );

  async function close_success_modal() {
    success_visible.value = false;
    success_message.value = null;
    await closeActionResultModal();
  }

  async function save_draft(options?: { silent_success?: boolean }) {
    error_message.value = null;
    is_saving.value = true;

    try {
      await runWithFeedback(
        async () => {
          await opts.on_save_draft();
          status.value = "DRAFT";
        },
        {
          loading: {
            title: "Đang lưu bản nháp",
            message: "Vui lòng đợi trong giây lát...",
          },
          success: {
            enabled: !options?.silent_success,
            title: "Thành công",
            message: "Đã lưu bản nháp thành công.",
          },
          error: {
            title: "Lưu bản nháp thất bại",
            fallbackMessage: "Không thể lưu bản nháp. Vui lòng thử lại.",
          },
        },
      );
    } catch (error) {
      error_message.value =
        error instanceof Error
          ? error.message
          : "Không thể lưu bản nháp. Vui lòng thử lại.";
    } finally {
      is_saving.value = false;
    }
  }

  async function submit_for_approval(payload?: SubmitForApprovalPayload) {
    error_message.value = null;
    is_submitting.value = true;

    try {
      await runWithFeedback(
        async () => {
          const nextStatus = await opts.on_submit(
            payload ?? { minorChange: false },
          );
          status.value = nextStatus ?? "PENDING_FACULTY_REVIEW";
        },
        {
          loading: {
            title: "Đang gửi duyệt",
            message: "Hệ thống đang xử lý hồ sơ...",
          },
          success: {
            title: "Thành công",
            message: "Đã gửi duyệt thành công.",
          },
          error: {
            title: "Gửi duyệt thất bại",
            fallbackMessage: "Không thể gửi duyệt. Vui lòng thử lại.",
          },
        },
      );
    } catch (error) {
      error_message.value =
        error instanceof Error
          ? error.message
          : "Không thể gửi duyệt. Vui lòng thử lại.";
    } finally {
      is_submitting.value = false;
    }
  }

  return {
    status,
    is_read_only,
    is_saving,
    is_submitting,
    pending,
    error_message,
    success_message,
    success_visible,
    save_draft,
    submit_for_approval,
    close_success_modal,
  };
}
