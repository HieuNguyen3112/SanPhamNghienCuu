import { defineStore } from "pinia";

const KEY = "sidebar_collapsed";

export const useLayoutStore = defineStore("layout", {
  state: () => ({
    isSidebarCollapsed: localStorage.getItem(KEY) === "1",
  }),
  actions: {
    toggleSidebarCollapse() {
      this.isSidebarCollapsed = !this.isSidebarCollapsed;
      localStorage.setItem(KEY, this.isSidebarCollapsed ? "1" : "0");
    },
    setSidebarCollapse(v: boolean) {
      this.isSidebarCollapsed = v;
      localStorage.setItem(KEY, v ? "1" : "0");
    },
  },
});
