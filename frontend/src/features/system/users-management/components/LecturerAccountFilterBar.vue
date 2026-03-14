<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="grid gap-3 md:items-end" :class="controlGridClassName">
      <div>
        <label class="text-xs font-medium text-slate-600">Từ khóa</label>
        <div class="relative mt-1">
          <Search
            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-9 text-sm focus:border-slate-300 focus:outline-none"
            :value="filter.keyword"
            placeholder="Tên / Email / Mã GV..."
            @input="
              update('keyword', ($event.target as HTMLInputElement).value)
            "
            @keydown.enter.prevent="emit('search')"
          />

          <button
            v-if="filter.keyword.trim().length > 0"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 hover:bg-slate-50 hover:text-slate-600"
            :disabled="loading"
            title="Xóa từ khóa"
            @click="update('keyword', '')"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div v-if="showUnitFilter" ref="unitMenuWrapRef">
        <label class="text-xs font-medium text-slate-600">Đơn vị</label>
        <div class="relative mt-1">
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 pr-28 text-sm text-slate-800 hover:bg-slate-50 disabled:opacity-60"
            :disabled="loading"
            @click="toggleMenu('unit')"
          >
            <div class="flex min-w-0 items-center gap-2">
              <Building2 class="h-4 w-4 shrink-0 text-slate-500" />
              <span class="truncate text-left text-slate-700">
                {{ unitSummaryText }}
              </span>
            </div>
          </button>

          <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-1">
            <span
              v-if="hasUnitSelection"
              class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-slate-900 bg-slate-900 text-white"
              aria-hidden="true"
            >
              <Check class="h-3.5 w-3.5" />
            </span>

            <button
              v-if="hasUnitSelection"
              type="button"
              class="pointer-events-auto rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
              :disabled="loading"
              title="Xóa chọn"
              @click.stop="clearUnit"
            >
              <X class="h-4 w-4" />
            </button>

            <ChevronDown class="h-4 w-4 shrink-0 text-slate-400" />
          </div>

          <div
            v-if="isUnitMenuOpen"
            class="absolute left-0 right-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
          >
            <div v-if="enableUnitSearch" class="border-b border-slate-100 p-2">
              <input
                v-model="unitSearch"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
                placeholder="Tìm đơn vị..."
              />
            </div>

            <div class="max-h-56 overflow-auto p-2">
              <button
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="selectUnit('ALL')"
              >
                <span class="text-slate-800">Tất cả</span>
                <span
                  class="inline-flex h-5 w-5 items-center justify-center rounded-md border"
                  :class="selectionBoxClass(filter.unitId === 'ALL')"
                  aria-hidden="true"
                >
                  <Check class="h-3.5 w-3.5" />
                </span>
              </button>

              <button
                v-for="unit in filteredUnitOptions"
                :key="unit.id"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="selectUnit(unit.id)"
              >
                <span class="pr-3 text-slate-800">{{ unit.name }}</span>
                <span
                  class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border"
                  :class="selectionBoxClass(filter.unitId === unit.id)"
                  aria-hidden="true"
                >
                  <Check class="h-3.5 w-3.5" />
                </span>
              </button>

              <div
                v-if="filteredUnitOptions.length === 0"
                class="px-3 py-6 text-center text-xs text-slate-500"
              >
                Không có đơn vị phù hợp.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div ref="statusMenuWrapRef">
        <label class="text-xs font-medium text-slate-600">Trạng thái</label>
        <div class="relative mt-1">
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 pr-28 text-sm text-slate-800 hover:bg-slate-50 disabled:opacity-60"
            :disabled="loading"
            @click="toggleMenu('status')"
          >
            <div class="flex min-w-0 items-center gap-2">
              <CircleDot class="h-4 w-4 shrink-0 text-slate-500" />
              <span class="truncate text-left text-slate-700">
                {{ statusSummaryText }}
              </span>
            </div>
          </button>

          <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-1">
            <span
              v-if="hasStatusSelection"
              class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-slate-900 bg-slate-900 text-white"
              aria-hidden="true"
            >
              <Check class="h-3.5 w-3.5" />
            </span>

            <button
              v-if="hasStatusSelection"
              type="button"
              class="pointer-events-auto rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
              :disabled="loading"
              title="Xóa chọn"
              @click.stop="clearStatus"
            >
              <X class="h-4 w-4" />
            </button>

            <ChevronDown class="h-4 w-4 shrink-0 text-slate-400" />
          </div>

          <div
            v-if="isStatusMenuOpen"
            class="absolute left-0 right-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
          >
            <div class="max-h-56 overflow-auto p-2">
              <button
                v-for="statusOption in statusOptions"
                :key="statusOption.value"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="selectStatus(statusOption.value)"
              >
                <span class="text-slate-800">{{ statusOption.label }}</span>
                <span
                  class="inline-flex h-5 w-5 items-center justify-center rounded-md border"
                  :class="selectionBoxClass(filter.status === statusOption.value)"
                  aria-hidden="true"
                >
                  <Check class="h-3.5 w-3.5" />
                </span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div ref="roleMenuWrapRef">
        <label class="text-xs font-medium text-slate-600">Vai trò</label>

        <div class="relative mt-1">
          <button
            type="button"
            class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 pr-28 text-sm text-slate-800 hover:bg-slate-50 disabled:opacity-60"
            :disabled="loading"
            @click="toggleMenu('role')"
          >
            <div class="flex min-w-0 items-center gap-2">
              <Shield class="h-4 w-4 shrink-0 text-slate-500" />
              <span class="truncate text-slate-700">{{ roleSummaryText }}</span>
            </div>
          </button>

          <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-1">
            <span
              v-if="hasRoleSelection"
              class="inline-flex h-5 w-5 items-center justify-center rounded-md border border-slate-900 bg-slate-900 text-white"
              aria-hidden="true"
            >
              <Check class="h-3.5 w-3.5" />
            </span>

            <button
              v-if="hasRoleSelection"
              type="button"
              class="pointer-events-auto rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
              :disabled="loading"
              title="Xóa chọn"
              @click.stop="clearRoles"
            >
              <X class="h-4 w-4" />
            </button>

            <ChevronDown class="h-4 w-4 shrink-0 text-slate-400" />
          </div>

          <div
            v-if="isRoleMenuOpen"
            class="absolute left-0 right-0 z-30 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
          >
            <div class="border-b border-slate-100 p-2">
              <input
                v-model="roleSearch"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
                placeholder="Tìm vai trò..."
              />
            </div>

            <div class="max-h-56 overflow-auto p-2">
              <button
                v-for="role in filteredRoleOptions"
                :key="role.key"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50"
                @click="toggleRole(role.key)"
              >
                <span class="text-slate-800">{{ role.label }}</span>

                <span
                  class="inline-flex h-5 w-5 items-center justify-center rounded-md border"
                  :class="selectionBoxClass(selectedRoleSet.has(role.key))"
                  aria-hidden="true"
                >
                  <Check class="h-3.5 w-3.5" />
                </span>
              </button>

              <div
                v-if="filteredRoleOptions.length === 0"
                class="px-3 py-6 text-center text-xs text-slate-500"
              >
                Không có vai trò phù hợp.
              </div>
            </div>

            <div
              class="flex items-center justify-between gap-2 border-t border-slate-100 p-2"
            >
              <button
                type="button"
                class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                :disabled="loading"
                @click="clearRoles"
              >
                Xóa chọn
              </button>

              <button
                type="button"
                class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                @click="closeMenu"
              >
                Xong
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-end justify-end gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="emit('reset')"
        >
          <RotateCcw class="h-4 w-4" />
          Xóa lọc
        </button>

        <button
          type="button"
          class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60"
          :disabled="loading"
          @click="emit('search')"
        >
          <Search class="h-4 w-4" />
          Lọc
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import {
  Building2,
  Check,
  ChevronDown,
  CircleDot,
  RotateCcw,
  Search,
  Shield,
  X,
} from "lucide-vue-next";
import type {
  LecturerAccountFilterState,
  RoleKey,
  RoleOption,
  UnitOption,
} from "../contracts/lecturerAccountManagement.contract";

type FilterMenuKey = "unit" | "status" | "role";

interface StatusOptionItem {
  value: LecturerAccountFilterState["status"];
  label: string;
}

const props = defineProps<{
  filter: LecturerAccountFilterState;
  unitOptions: UnitOption[];
  roleOptions: RoleOption[];
  loading: boolean;
  resultCount: number;
}>();

const emit = defineEmits<{
  (e: "update:filter", v: LecturerAccountFilterState): void;
  (e: "search"): void;
  (e: "reset"): void;
}>();

const statusOptions: StatusOptionItem[] = [
  { value: "all", label: "Tất cả" },
  { value: "active", label: "Hoạt động" },
  { value: "inactive", label: "Vô hiệu" },
];

const openMenu = ref<FilterMenuKey | null>(null);
const unitSearch = ref("");
const roleSearch = ref("");

const unitMenuWrapRef = ref<HTMLElement | null>(null);
const statusMenuWrapRef = ref<HTMLElement | null>(null);
const roleMenuWrapRef = ref<HTMLElement | null>(null);

const showUnitFilter = computed(() => props.unitOptions.length > 1);
const enableUnitSearch = computed(() => props.unitOptions.length > 6);
const isUnitMenuOpen = computed(() => openMenu.value === "unit");
const isStatusMenuOpen = computed(() => openMenu.value === "status");
const isRoleMenuOpen = computed(() => openMenu.value === "role");
const hasUnitSelection = computed(() => props.filter.unitId !== "ALL");
const hasStatusSelection = computed(() => props.filter.status !== "all");
const hasRoleSelection = computed(() => props.filter.roleKeys.length > 0);
const selectedRoleSet = computed(() => new Set(props.filter.roleKeys));

const filteredUnitOptions = computed<UnitOption[]>(() => {
  const query = unitSearch.value.trim().toLowerCase();
  if (!query) return props.unitOptions;
  return props.unitOptions.filter((unit) =>
    unit.name.toLowerCase().includes(query),
  );
});

const filteredRoleOptions = computed<RoleOption[]>(() => {
  const query = roleSearch.value.trim().toLowerCase();
  if (!query) return props.roleOptions;
  return props.roleOptions.filter((role) =>
    role.label.toLowerCase().includes(query),
  );
});

const unitSummaryText = computed(() => {
  if (props.filter.unitId === "ALL") return "Tất cả đơn vị";
  return (
    props.unitOptions.find((unit) => unit.id === props.filter.unitId)?.name ??
    "Tất cả đơn vị"
  );
});

const statusSummaryText = computed(() => {
  return (
    statusOptions.find((status) => status.value === props.filter.status)
      ?.label ?? "Tất cả"
  );
});

const roleSummaryText = computed(() => {
  if (props.filter.roleKeys.length === 0) return "Tất cả vai trò";
  if (props.filter.roleKeys.length === 1) {
    return roleLabel(props.filter.roleKeys[0]!);
  }
  return `Đã chọn ${props.filter.roleKeys.length} vai trò`;
});

const controlGridClassName = computed(() => {
  return showUnitFilter.value
    ? "grid-cols-1 md:grid-cols-[2.6fr_1.6fr_1.1fr_1.4fr_auto]"
    : "grid-cols-1 md:grid-cols-[3.2fr_1.2fr_1.6fr_auto]";
});

function update<K extends keyof LecturerAccountFilterState>(
  key: K,
  value: LecturerAccountFilterState[K],
) {
  emit("update:filter", { ...props.filter, [key]: value });
}

function toggleMenu(menu: FilterMenuKey) {
  openMenu.value = openMenu.value === menu ? null : menu;
  if (openMenu.value === "unit") unitSearch.value = "";
  if (openMenu.value === "role") roleSearch.value = "";
}

function closeMenu() {
  openMenu.value = null;
  unitSearch.value = "";
  roleSearch.value = "";
}

function selectUnit(value: number | "ALL") {
  update("unitId", value);
  closeMenu();
}

function clearUnit() {
  update("unitId", "ALL");
  closeMenu();
}

function selectStatus(value: LecturerAccountFilterState["status"]) {
  update("status", value);
  closeMenu();
}

function clearStatus() {
  update("status", "all");
  closeMenu();
}

function clearRoles() {
  update("roleKeys", []);
}

function toggleRole(key: RoleKey) {
  const next = new Set(props.filter.roleKeys);
  if (next.has(key)) next.delete(key);
  else next.add(key);
  update("roleKeys", [...next]);
}

function selectionBoxClass(selected: boolean) {
  return selected
    ? "border-slate-900 bg-slate-900 text-white"
    : "border-slate-200 bg-white text-transparent";
}

function onDocClick(event: MouseEvent) {
  if (!openMenu.value) return;
  const target = event.target as Node | null;
  if (!target) return;

  const wrapRefMap: Record<FilterMenuKey, HTMLElement | null> = {
    unit: unitMenuWrapRef.value,
    status: statusMenuWrapRef.value,
    role: roleMenuWrapRef.value,
  };

  const activeWrap = wrapRefMap[openMenu.value];
  if (activeWrap && !activeWrap.contains(target)) {
    closeMenu();
  }
}

function onEsc(event: KeyboardEvent) {
  if (event.key === "Escape") {
    closeMenu();
  }
}

function roleLabel(key: RoleKey) {
  if (key === "LECTURER") return "Giảng viên";
  if (key === "DEPARTMENT_BOARD") return "BCN Khoa";
  return "QLKH / Admin";
}

onMounted(() => {
  document.addEventListener("click", onDocClick);
  window.addEventListener("keydown", onEsc);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", onDocClick);
  window.removeEventListener("keydown", onEsc);
});
</script>
