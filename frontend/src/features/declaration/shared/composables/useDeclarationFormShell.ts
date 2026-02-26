import { computed, ref } from "vue";
import type { DeclarationStatusUi } from "../contracts/declarationSharedContract";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

type UseDeclarationShellOptions = {
  initial_status: DeclarationStatusUi;
  on_save_draft: () => Promise<void>;
  on_submit: () => Promise<DeclarationStatusUi | void>;
};

export function useDeclarationFormShell(opts: UseDeclarationShellOptions) {
  const status = ref<DeclarationStatusUi>(opts.initial_status);
  const is_saving = ref(false);
  const is_submitting = ref(false);
  const error_message = ref<string | null>(null);

  // Giữ biến legacy để không phá vỡ API hiện tại của các form.
  const success_message = ref<string | null>(null);
  const success_visible = ref(false);

  const { showSuccessModal, closeActionResultModal } = useActionResultModal();

  const pending = computed(() => is_saving.value || is_submitting.value);
  const is_read_only = computed(
    () => !["DRAFT", "MEMBER_REJECTED", "REJECTED"].includes(status.value)
  );

  async function close_success_modal() {
    success_visible.value = false;
    success_message.value = null;
    await closeActionResultModal();
  }

  function open_success_modal(message: string) {
    success_message.value = message;
    success_visible.value = true;
    showSuccessModal(message);
  }

  async function save_draft(options?: { silent_success?: boolean }) {
    error_message.value = null;
    is_saving.value = true;
    try {
      await opts.on_save_draft();
      status.value = "DRAFT";
      if (!options?.silent_success) {
        open_success_modal("Đã lưu bản nháp thành công.");
      }
    } catch (e) {
      error_message.value = e instanceof Error ? e.message : "Không thể lưu bản nháp.";
    } finally {
      is_saving.value = false;
    }
  }

  async function submit_for_approval() {
    error_message.value = null;
    is_submitting.value = true;
    try {
      const nextStatus = await opts.on_submit();
      status.value = nextStatus ?? "PENDING_FACULTY_REVIEW";
      open_success_modal("Đã gửi duyệt thành công.");
    } catch (e) {
      error_message.value = e instanceof Error ? e.message : "Không thể gửi duyệt.";
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
