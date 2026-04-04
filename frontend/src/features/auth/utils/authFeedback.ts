type AuthHttpError = {
  message?: string;
  response?: {
    status?: number;
    data?: {
      code?: string;
      message?: string;
    };
  };
};

function readBackendCode(error: AuthHttpError): string | null {
  const code = error.response?.data?.code;
  return typeof code === "string" && code.trim() ? code.trim() : null;
}

function readBackendMessage(error: AuthHttpError): string {
  const message = error.response?.data?.message;
  return typeof message === "string" ? message.trim() : "";
}

function isNetworkLikeError(message: string): boolean {
  return /Network Error|ECONN|timeout|Failed to fetch|CSRF_FAILED/i.test(message);
}

export function resolveLoginValidationMessage(payload: {
  email: string;
  password: string;
  role: string | null | undefined;
}): string | null {
  const missingFields: string[] = [];

  if (!payload.email.trim()) missingFields.push("tên đăng nhập");
  if (!payload.password.trim()) missingFields.push("mật khẩu");
  if (!payload.role) missingFields.push("vai trò");

  if (missingFields.length === 0) return null;

  if (missingFields.length === 1) {
    return `Vui lòng nhập ${missingFields[0]} trước khi đăng nhập.`;
  }

  if (missingFields.length === 2) {
    return `Vui lòng nhập ${missingFields[0]} và ${missingFields[1]} trước khi đăng nhập.`;
  }

  return "Vui lòng nhập đầy đủ tên đăng nhập, mật khẩu và chọn vai trò trước khi đăng nhập.";
}

export function resolveLoginErrorMessage(error: unknown): string {
  const err = error as AuthHttpError;
  const rawMessage =
    typeof err?.message === "string" ? err.message.trim() : "";
  const backendCode = readBackendCode(err);
  const backendMessage = readBackendMessage(err).toLowerCase();
  const status = err?.response?.status ?? null;

  if (isNetworkLikeError(rawMessage)) {
    return "Không thể kết nối hệ thống lúc này. Vui lòng kiểm tra mạng và thử lại.";
  }

  if (
    rawMessage === "ROLE_KHONG_HOP_LE" ||
    backendCode === "FORBIDDEN_MISSING_ROLE" ||
    backendMessage.includes("right roles")
  ) {
    return "Tài khoản này không có quyền truy cập với vai trò đã chọn.";
  }

  if (
    backendCode === "UNVERIFIED_EMAIL" ||
    backendMessage.includes("not verified")
  ) {
    return "Tài khoản này chưa xác minh email. Vui lòng kiểm tra lại trước khi đăng nhập.";
  }

  if (status === 401 || status === 422) {
    return "Thông tin đăng nhập không chính xác. Vui lòng thử lại.";
  }

  if (status === 403) {
    return "Tài khoản này không có quyền truy cập với vai trò đã chọn.";
  }

  if (status !== null && status >= 500) {
    return "Không thể đăng nhập vào lúc này. Vui lòng thử lại sau.";
  }

  return "Không thể đăng nhập vào lúc này. Vui lòng thử lại sau.";
}

export function resolveLogoutErrorMessage(error: unknown): string {
  const err = error as AuthHttpError;
  const rawMessage =
    typeof err?.message === "string" ? err.message.trim() : "";
  const status = err?.response?.status ?? null;
  const backendCode = readBackendCode(err);

  if (rawMessage === "LOGOUT_ENDPOINT_FAILED") {
    return "Không thể hoàn tất đăng xuất lúc này. Vui lòng thử lại.";
  }

  if (status === 419 || backendCode === "CSRF_TOKEN_MISMATCH") {
    return "Phiên làm việc đã hết hạn hoặc không còn đồng bộ. Vui lòng đăng nhập lại.";
  }

  if (status === 401) {
    return "Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.";
  }

  if (isNetworkLikeError(rawMessage)) {
    return "Không thể kết nối hệ thống để đăng xuất. Vui lòng kiểm tra mạng và thử lại.";
  }

  if (status !== null && status >= 500) {
    return "Không thể đăng xuất lúc này. Vui lòng thử lại sau.";
  }

  return "Không thể đăng xuất lúc này. Vui lòng thử lại.";
}
