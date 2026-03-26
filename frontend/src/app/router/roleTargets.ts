import type { UserRole } from "@/app/stores/userStore";

export function resolvePostLoginLandingPath(
  role: UserRole | null | undefined,
): string {
  if (role === "LECTURER") {
    return "/declarations/gateway";
  }

  if (role === "DEPARTMENT_BOARD") {
    return "/works/facapprovals";
  }

  if (role === "SCIENCE_OFFICE") {
    return "/works/unimanagement";
  }

  return "/";
}

export function resolvePublicAccountTargetPath(
  role: UserRole | null | undefined,
): string {
  if (role === "LECTURER") {
    return "/profile";
  }

  return resolvePostLoginLandingPath(role);
}
