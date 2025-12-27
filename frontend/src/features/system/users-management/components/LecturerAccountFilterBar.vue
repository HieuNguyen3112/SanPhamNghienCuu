<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="grid gap-3 md:items-end" :class="controlGridClassName">
      <!-- keyword -->
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
            @click="update('keyword', '')"
            title="Xóa từ khóa"
          >
            <X class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- unit -->
      <div v-if="showUnitFilter">
        <label class="text-xs font-medium text-slate-600">Đơn vị</label>
        <div class="relative mt-1">
          <Building2
            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <select
            class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-8 text-sm focus:border-slate-300 focus:outline-none"
            :value="String(filter.unitId)"
            @change="onUnitChange"
          >
            <option value="ALL">Tất cả</option>
            <option v-for="u in unitOptions" :key="u.id" :value="String(u.id)">
              {{ u.name }}
            </option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
        </div>
      </div>

      <!-- status -->
      <div>
        <label class="text-xs font-medium text-slate-600">Trạng thái</label>
        <div class="relative mt-1">
          <CircleDot
            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <select
            class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-8 text-sm focus:border-slate-300 focus:outline-none"
            :value="filter.status"
            @change="
              update(
                'status',
                ($event.target as HTMLSelectElement)
                  .value as LecturerAccountFilterState['status']
              )
            "
          >
            <option value="all">Tất cả</option>
            <option value="active">Hoạt động</option>
            <option value="inactive">Vô hiệu</option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
        </div>
      </div>

      <!-- roles -->
      <div ref="roleMenuWrapRef">
        <label class="text-xs font-medium text-slate-600">Vai trò</label>

        <div class="relative mt-1">
          <button
            type="button"
            class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 hover:bg-slate-50 disabled:opacity-60"
            :disabled="loading"
            @click="toggleRoleMenu"
          >
            <div class="flex min-w-0 items-center gap-2">
              <Shield class="h-4 w-4 shrink-0 text-slate-500" />
              <span class="truncate text-slate-700">{{ roleSummaryText }}</span>
            </div>

            <div class="flex items-center gap-2">
              <button
                v-if="filter.roleKeys.length > 0"
                type="button"
                class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                :disabled="loading"
                title="Xóa chọn"
                @click.stop="clearRoles"
              >
                <X class="h-4 w-4" />
              </button>

              <ChevronDown class="h-4 w-4 text-slate-400" />
            </div>
          </button>

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
                v-for="r in filteredRoleOptions"
                :key="r.key"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm hover:bg-slate-50"
                @click="toggleRole(r.key)"
              >
                <span class="text-slate-800">{{ r.label }}</span>

                <span
                  class="inline-flex h-5 w-5 items-center justify-center rounded-md border"
                  :class="
                    selectedRoleSet.has(r.key)
                      ? 'border-slate-900 bg-slate-900 text-white'
                      : 'border-slate-200 bg-white text-transparent'
                  "
                  aria-hidden="true"
                >
                  ✓
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
                @click="closeRoleMenu"
              >
                Xong
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- actions (auto width, NEVER wrap) -->
      <div class="flex items-end justify-end gap-2">
        <!-- <div
          class="hidden text-xs text-slate-600 md:block whitespace-nowrap"
          :class="loading ? 'opacity-60' : ''"
        >
          {{ resultCountText }}
        </div> -->

        <button
          type="button"
          class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="emit('reset')"
        >
          <RotateCcw class="h-4 w-4" />
          Reset
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
  ChevronDown,
  CircleDot,
  Filter,
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

const showUnitFilter = computed(() => props.unitOptions.length > 1);
const selectedRoleSet = computed(() => new Set(props.filter.roleKeys));

const resultCountText = computed(
  () => `Kết quả: ${props.resultCount} giảng viên`
);

function update<K extends keyof LecturerAccountFilterState>(
  key: K,
  value: LecturerAccountFilterState[K]
) {
  emit("update:filter", { ...props.filter, [key]: value });
}
const controlGridClassName = computed(() => {
  // mobile: 1 cột (tự xuống dòng)
  // desktop: 1 dòng với cột cuối auto
  return showUnitFilter.value
    ? "grid-cols-1 md:grid-cols-[2.6fr_1.6fr_1.1fr_1.4fr_auto]"
    : "grid-cols-1 md:grid-cols-[3.2fr_1.2fr_1.6fr_auto]";
});

function onUnitChange(e: Event) {
  const v = (e.target as HTMLSelectElement).value;
  update("unitId", v === "ALL" ? "ALL" : Number(v));
}

/** Roles dropdown */
const isRoleMenuOpen = ref(false);
const roleSearch = ref("");
const roleMenuWrapRef = ref<HTMLElement | null>(null);

const roleSummaryText = computed(() => {
  if (props.filter.roleKeys.length === 0) return "Tất cả vai trò";
  if (props.filter.roleKeys.length === 1)
    return roleLabel(props.filter.roleKeys[0]!);
  return `Đã chọn ${props.filter.roleKeys.length} vai trò`;
});

const filteredRoleOptions = computed<RoleOption[]>(() => {
  const q = roleSearch.value.trim().toLowerCase();
  if (!q) return props.roleOptions;
  return props.roleOptions.filter((x) => x.label.toLowerCase().includes(q));
});

function toggleRoleMenu() {
  isRoleMenuOpen.value = !isRoleMenuOpen.value;
  if (isRoleMenuOpen.value) roleSearch.value = "";
}

function closeRoleMenu() {
  isRoleMenuOpen.value = false;
  roleSearch.value = "";
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

function removeRole(key: RoleKey) {
  const next = props.filter.roleKeys.filter((k) => k !== key);
  update("roleKeys", next);
}

function onDocClick(e: MouseEvent) {
  if (!isRoleMenuOpen.value) return;
  const target = e.target as Node | null;
  if (!target) return;
  if (roleMenuWrapRef.value && !roleMenuWrapRef.value.contains(target)) {
    closeRoleMenu();
  }
}

function onEsc(e: KeyboardEvent) {
  if (e.key === "Escape") closeRoleMenu();
}

onMounted(() => {
  document.addEventListener("click", onDocClick);
  window.addEventListener("keydown", onEsc);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", onDocClick);
  window.removeEventListener("keydown", onEsc);
});

function roleLabel(key: RoleKey) {
  if (key === "LECTURER") return "Giảng viên";
  if (key === "DEPARTMENT_BOARD") return "BCN Khoa";
  return "QLKH / Admin";
}
</script>
