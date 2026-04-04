import { useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { useActionFeedback } from "@/shared/composables/useActionFeedback";
import { resolveLogoutErrorMessage } from "@/features/auth/utils/authFeedback";

export function useLogoutFeedback(redirectPath = "/") {
  const router = useRouter();
  const userStore = useUserStore();
  const { runWithFeedback } = useActionFeedback();

  async function logoutWithFeedback(): Promise<void> {
    try {
      await runWithFeedback(
        async () => {
          const result = await userStore.logout();
          await router.replace(redirectPath);

          if (!result.success) {
            throw result.error ?? new Error("LOGOUT_ENDPOINT_FAILED");
          }
        },
        {
          loading: {
            title: "Đang đăng xuất",
            message: "Đang đăng xuất khỏi hệ thống. Vui lòng đợi trong giây lát...",
          },
          success: {
            title: "Thành công",
            message: "Bạn đã đăng xuất thành công.",
          },
          error: {
            title: "Không thể đăng xuất",
            message: (error) => resolveLogoutErrorMessage(error),
          },
        },
      );
    } catch {
      // Modal feedback da duoc hien thi trong runWithFeedback.
    }
  }

  return {
    logoutWithFeedback,
  };
}
