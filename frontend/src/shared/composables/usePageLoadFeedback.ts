import { useActionFeedback } from "@/shared/composables/useActionFeedback";

type PageLoadFeedbackOptions = {
  loading?: {
    title?: string;
    message?: string;
    delayMs?: number;
    minShowMs?: number;
  };
  onError?: (error: unknown) => void;
  rethrow?: boolean;
};

const DEFAULT_TITLE = "Đang tải dữ liệu";
const DEFAULT_MESSAGE = "Vui lòng đợi trong giây lát...";

export function usePageLoadFeedback() {
  const { runWithFeedback } = useActionFeedback();

  async function runPageLoad<TResult>(
    action: () => Promise<TResult>,
    options: PageLoadFeedbackOptions = {},
  ): Promise<TResult | undefined> {
    try {
      return await runWithFeedback(action, {
        loading: {
          title: options.loading?.title ?? DEFAULT_TITLE,
          message: options.loading?.message ?? DEFAULT_MESSAGE,
          delayMs: options.loading?.delayMs ?? 550,
          minShowMs: options.loading?.minShowMs ?? 250,
        },
        success: { enabled: false },
        error: { enabled: false },
        rethrow: true,
      });
    } catch (error) {
      options.onError?.(error);
      if (options.rethrow) {
        throw error;
      }
      return undefined;
    }
  }

  return {
    runPageLoad,
  };
}
