import { ref } from "vue";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";

export type ExportKind = "excel" | "pdf";

export type ExportPayload = {
  blob: Blob;
  filename: string;
};

function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

export function useExportActionFeedback() {
  const exporting = ref<ExportKind | null>(null);
  const { runWithFeedback } = useActionFeedback();

  async function runExport(
    kind: ExportKind,
    exporter: () => Promise<ExportPayload>
  ) {
    if (exporting.value) return;
    exporting.value = kind;

    try {
      await runWithFeedback(
        async () => {
          const result = await exporter();
          downloadBlob(result.blob, result.filename);
          return result;
        },
        {
          loading: {
            title: "Đang xuất dữ liệu",
            message:
              kind === "excel"
                ? "Đang tạo tệp Excel..."
                : "Đang tạo tệp PDF...",
          },
          success: {
            title: "Thành công",
            message:
              kind === "excel"
                ? "Xuất file Excel thành công."
                : "Xuất file PDF thành công.",
          },
          error: {
            title: "Xuất file thất bại",
            fallbackMessage: "Xuất file thất bại. Vui lòng thử lại.",
          },
        }
      );
    } finally {
      exporting.value = null;
    }
  }

  return {
    exporting,
    runExport,
  };
}
