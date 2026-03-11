import { resolveApiErrorMessage, useActionFeedback } from "@/shared/composables/useActionFeedback";

type RunPageLoadOptions = {
  onError: (message: string) => void;
  fallbackMessage?: string;
};

const PAGE_LOAD_TITLE = "Đang tải dữ liệu kê khai";
const PAGE_LOAD_MESSAGE = "Vui lòng đợi trong giây lát...";

export function useDeclarationPageLoadFeedback() {
  const { runWithFeedback } = useActionFeedback();

  async function runPageLoad(
    action: () => Promise<void>,
    options: RunPageLoadOptions,
  ) {
    try {
      await runWithFeedback(action, {
        loading: {
          title: PAGE_LOAD_TITLE,
          message: PAGE_LOAD_MESSAGE,
          delayMs: 550,
          minShowMs: 250,
        },
        success: { enabled: false },
        error: { enabled: false },
        rethrow: true,
      });
    } catch (error) {
      options.onError(
        resolveApiErrorMessage(
          error,
          options.fallbackMessage ?? "Không thể khởi tạo trang kê khai.",
        ),
      );
    }
  }

  return {
    runPageLoad,
  };
}
