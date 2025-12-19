import { computed, reactive, watch, type Ref } from "vue";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import type { MenuItem } from "@/app/config/menu.types";

export type MenuGroup = { header: MenuItem; children: MenuItem[] };

export function useSidebarGroups(
  menuItems: Ref<MenuItem[]>,
  route: RouteLocationNormalizedLoaded
) {
  const groupOpen = reactive<Record<string, boolean>>({});

  const menuGroups = computed<MenuGroup[]>(() => {
    const groups: MenuGroup[] = [];
    let current: MenuGroup | null = null;

    for (const it of menuItems.value) {
      if (!it.routeName) {
        current = { header: it, children: [] };
        groups.push(current);
        if (groupOpen[it.id] === undefined) groupOpen[it.id] = true;
      } else {
        if (!current) {
          const fallback: MenuItem = { id: "misc", label: "Khác" };
          if (groupOpen[fallback.id] === undefined)
            groupOpen[fallback.id] = true;
          current = { header: fallback, children: [] };
          groups.push(current);
        }
        current.children.push(it);
      }
    }
    return groups;
  });

  const toggleGroup = (groupId: string) => {
    groupOpen[groupId] = !groupOpen[groupId];
  };

  // Auto-open: chỉ mở group có route active, các group khác giữ nguyên hoặc đóng (tuỳ bạn)
  watch(
    () => route.name,
    () => {
      for (const g of menuGroups.value) {
        const isActive = g.children.some((c) => c.routeName === route.name);
        if (isActive) groupOpen[g.header.id] = true;
      }
    },
    { immediate: true }
  );

  return { menuGroups, groupOpen, toggleGroup };
}
