import { computed, reactive, watch, type Ref } from "vue";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import type { MenuItem } from "@/app/config/menu.types";

export type MenuGroup = { header: MenuItem; children: MenuItem[] };

export function useSidebarGroups(
  menuItems: Ref<MenuItem[]>,
  route: RouteLocationNormalizedLoaded,
) {
  const groupOpen = reactive<Record<string, boolean>>({});

  const menuGroups = computed<MenuGroup[]>(() => {
    const groups: MenuGroup[] = [];
    let current: MenuGroup | null = null;

    for (const item of menuItems.value) {
      if (!item.routeName) {
        current = { header: item, children: [] };
        groups.push(current);
        if (groupOpen[item.id] === undefined) {
          groupOpen[item.id] = true;
        }
        continue;
      }

      if (!current) {
        const fallback: MenuItem = { id: "misc", label: "Khác" };
        if (groupOpen[fallback.id] === undefined) {
          groupOpen[fallback.id] = true;
        }
        current = { header: fallback, children: [] };
        groups.push(current);
      }

      current.children.push(item);
    }

    return groups;
  });

  const toggleGroup = (groupId: string) => {
    groupOpen[groupId] = !groupOpen[groupId];
  };

  // Tự mở group chứa route đang active, các group khác giữ trạng thái hiện tại.
  watch(
    () => route.name,
    () => {
      for (const group of menuGroups.value) {
        const isActive = group.children.some((child) => child.routeName === route.name);
        if (isActive) {
          groupOpen[group.header.id] = true;
        }
      }
    },
    { immediate: true },
  );

  return { menuGroups, groupOpen, toggleGroup };
}
