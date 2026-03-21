import { defineStore } from "pinia";

const KEY = "sidebar_collapsed";

function readInitialSidebarCollapsed() {
  if (typeof window === "undefined") {
    return false;
  }

  return window.localStorage.getItem(KEY) === "1";
}

function persistSidebarCollapsed(value: boolean) {
  if (typeof window === "undefined") {
    return;
  }

  window.localStorage.setItem(KEY, value ? "1" : "0");
}

export const useLayoutStore = defineStore("layout", {
  state: () => ({
    isSidebarCollapsed: readInitialSidebarCollapsed(),
  }),
  actions: {
    toggleSidebarCollapse() {
      this.isSidebarCollapsed = !this.isSidebarCollapsed;
      persistSidebarCollapsed(this.isSidebarCollapsed);
    },
    setSidebarCollapse(value: boolean) {
      this.isSidebarCollapsed = value;
      persistSidebarCollapsed(value);
    },
  },
});
