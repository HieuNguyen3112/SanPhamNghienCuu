export function canManageModalFocus(): boolean {
  if (typeof document === "undefined") {
    return false;
  }

  if (document.visibilityState && document.visibilityState !== "visible") {
    return false;
  }

  return document.hasFocus();
}

export function focusModalElement(
  element: HTMLElement | null | undefined,
  options?: FocusOptions,
): boolean {
  if (!element || !element.isConnected || !canManageModalFocus()) {
    return false;
  }

  element.focus(options);
  return true;
}

export function restoreModalFocus(
  element: HTMLElement | null | undefined,
  options?: FocusOptions,
): boolean {
  if (!element || !element.isConnected) {
    return false;
  }

  return focusModalElement(element, options);
}
