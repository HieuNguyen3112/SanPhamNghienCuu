import { computed, ref } from "vue";
import type { DeclarationStatusUi } from "../contracts/declarationSharedContract";

type UseDeclarationShellOptions = {
  initial_status: DeclarationStatusUi;
  on_save_draft: () => Promise<void>;
  on_submit: () => Promise<void>;
};

export function useDeclarationFormShell(opts: UseDeclarationShellOptions) {
  const status = ref<DeclarationStatusUi>(opts.initial_status);
  const is_saving = ref(false);
  const is_submitting = ref(false);
  const error_message = ref<string | null>(null);

  const pending = computed(() => is_saving.value || is_submitting.value);
  const is_read_only = computed(() => status.value !== "DRAFT");

  async function save_draft() {
    error_message.value = null;
    is_saving.value = true;
    try {
      await opts.on_save_draft();
      status.value = "DRAFT";
    } catch (e) {
      error_message.value =
        e instanceof Error ? e.message : "Save draft failed";
    } finally {
      is_saving.value = false;
    }
  }

  async function submit_for_approval() {
    error_message.value = null;
    is_submitting.value = true;
    try {
      await opts.on_submit();
      status.value = "SUBMITTED";
    } catch (e) {
      error_message.value = e instanceof Error ? e.message : "Submit failed";
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
    save_draft,
    submit_for_approval,
  };
}
