<!-- File: src/features/lecturer-account-management/components/LecturerAccountTable.vue -->
<template>
  <div
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
  >
    <div v-if="loading" class="p-4 text-sm text-slate-700">
      Đang tải danh sách...
    </div>

    <div v-else-if="error" class="p-4">
      <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
        <div class="text-sm font-medium text-rose-700">
          Không tải được dữ liệu
        </div>
        <div class="mt-1 whitespace-pre-wrap text-xs text-rose-700">
          {{ error }}
        </div>
      </div>
    </div>

    <div v-else-if="rows.length === 0" class="p-6 text-center">
      <div class="text-sm font-medium text-slate-900">Không có dữ liệu</div>
      <div class="mt-1 text-xs text-slate-500">
        Không tìm thấy giảng viên nào phù hợp với bộ lọc.
      </div>
    </div>

    <!-- Table scroll -->
    <div v-else class="max-h-[620px] overflow-auto" @scroll="closeMenu">
      <table class="min-w-full text-left text-sm">
        <thead
          class="sticky top-0 z-10 bg-slate-50 text-xs font-semibold text-slate-600"
        >
          <tr class="[&>th]:px-3 [&>th]:py-2">
            <th class="w-[280px]">Giảng viên</th>
            <th>Email</th>
            <th>Tài khoản</th>
            <th>Đơn vị</th>
            <th>Vai trò</th>
            <th class="text-center">Trạng thái</th>
            <th class="w-12 text-right"></th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50">
            <td class="px-3 py-2">
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-xs font-semibold text-slate-700"
                >
                  {{ initials(row.fullName) }}
                </div>

                <div class="min-w-0">
                  <div class="truncate font-semibold text-slate-900">
                    {{ row.fullName }}
                  </div>
                  <div class="mt-0.5 text-xs text-slate-500">
                    {{ row.lecturerCode }}
                  </div>
                </div>
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">
              <div class="truncate">{{ row.email }}</div>
            </td>

            <td class="px-3 py-2">
              <div class="truncate font-medium text-slate-900">
                {{ row.username }}
              </div>
              <div class="mt-0.5 text-xs text-slate-500">
                Cập nhật: {{ formatDate(row.updatedAt) }}
              </div>
            </td>

            <td class="px-3 py-2 text-slate-700">
              <div class="truncate">{{ row.unitName }}</div>
            </td>

            <td class="px-3 py-2">
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="rk in row.roleKeys"
                  :key="rk"
                  class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700"
                >
                  {{ roleShortLabel(rk) }}
                </span>
              </div>
            </td>

            <td class="px-3 py-2 text-center">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ring-1"
                :class="statusPill(row.status)"
              >
                {{ row.status === "ACTIVE" ? "Hoạt động" : "Vô hiệu" }}
              </span>
            </td>

            <!-- Action trigger -->
            <td class="px-3 py-2 text-right">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                title="Thao tác"
                @click.stop="openMenu(row.id, $event)"
              >
                <MoreVertical class="h-4 w-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination footer -->
    <div
      v-if="!loading && !error && rows.length > 0"
      class="border-t border-slate-200 px-4 py-3"
    >
      <SharedPaginationControls
        :total-item-count="totalItemCount"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        display-mode="FULL"
        :show-record-summary="true"
        record-summary-mode="RANGE"
        record-summary-unit-label="giảng viên"
        container-class-name="w-full"
        @update:currentPageNumber="emit('update:currentPageNumber', $event)"
        @update:pageSize="emit('update:pageSize', $event)"
      />
    </div>

    <!-- Teleport menu => luôn nổi trên table -->
    <Teleport to="body">
      <div v-if="openMenuId !== null && activeRow">
        <div class="fixed inset-0 z-9000" @click="closeMenu" />

        <div
          ref="menuEl"
          class="fixed z-9999 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"
          :style="menuStyle"
          @click.stop
        >
          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            @click="onEdit(activeRow.id)"
          >
            <Pencil class="h-4 w-4" />
            Sửa
          </button>

          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            @click="onRoles(activeRow.id)"
          >
            <ShieldCheck class="h-4 w-4" />
            Role
          </button>

          <div class="h-px bg-slate-200" />

          <button
            type="button"
            class="flex w-full items-center gap-2 px-3 py-2 text-sm font-semibold hover:bg-slate-50"
            :class="
              activeRow.status === 'ACTIVE'
                ? 'text-rose-700'
                : 'text-emerald-700'
            "
            @click="onToggleStatus(activeRow.id)"
          >
            <UserX class="h-4 w-4" />
            {{ activeRow.status === "ACTIVE" ? "Vô hiệu" : "Kích hoạt" }}
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from "vue";
import { MoreVertical, Pencil, ShieldCheck, UserX } from "lucide-vue-next";
import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";
import type {
  LecturerAccount,
  RoleKey,
} from "../contracts/lecturerAccountManagement.contract";

const props = defineProps<{
  rows: LecturerAccount[];
  loading: boolean;
  error: string | null;
  currentPageNumber: number;
  pageSize: number;
  totalItemCount: number;
}>();

const emit = defineEmits<{
  (e: "edit", id: number): void;
  (e: "roles", id: number): void;
  (e: "toggle-status", id: number): void;
  (e: "update:currentPageNumber", value: number): void;
  (e: "update:pageSize", value: number): void;
}>();

watch(
  () => props.rows,
  () => {
    closeMenu();
  },
  { deep: true }
);

/** Teleport menu */
const openMenuId = ref<number | null>(null);
const menuStyle = ref<Record<string, string>>({});
const menuEl = ref<HTMLElement | null>(null);
const anchorRect = ref<DOMRect | null>(null);

const activeRow = computed(() => {
  if (openMenuId.value == null) return null;
  return props.rows.find((r) => r.id === openMenuId.value) ?? null;
});

function clamp(v: number, min: number, max: number) {
  return Math.min(Math.max(v, min), max);
}

async function openMenu(id: number, e: MouseEvent) {
  if (openMenuId.value === id) {
    closeMenu();
    return;
  }

  const btn = e.currentTarget as HTMLElement | null;
  if (!btn) return;

  anchorRect.value = btn.getBoundingClientRect();
  openMenuId.value = id;

  // vị trí tạm
  positionMenu(180);

  await nextTick();
  // đo chiều cao thật và flip nếu cần
  const realH = menuEl.value?.getBoundingClientRect().height ?? 180;
  positionMenu(realH);
}

function positionMenu(menuHeight: number) {
  if (!anchorRect.value) return;

  const rect = anchorRect.value;
  const gap = 8;
  const menuWidth = menuEl.value?.getBoundingClientRect().width ?? 224; // w-56

  const leftPreferred = rect.right - menuWidth;
  const left = clamp(leftPreferred, 8, window.innerWidth - menuWidth - 8);

  const spaceBelow = window.innerHeight - rect.bottom - gap - 8;
  const spaceAbove = rect.top - gap - 8;

  const openUp = spaceBelow < menuHeight && spaceAbove >= menuHeight;
  const top = openUp ? rect.top - menuHeight - gap : rect.bottom + gap;

  menuStyle.value = {
    left: `${left}px`,
    top: `${clamp(top, 8, window.innerHeight - 8)}px`,
  };
}

function closeMenu() {
  openMenuId.value = null;
  anchorRect.value = null;
  menuStyle.value = {};
}

function onEdit(id: number) {
  closeMenu();
  emit("edit", id);
}
function onRoles(id: number) {
  closeMenu();
  emit("roles", id);
}
function onToggleStatus(id: number) {
  closeMenu();
  emit("toggle-status", id);
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === "Escape") closeMenu();
}
function onWindowScrollOrResize() {
  closeMenu();
}

onMounted(() => {
  window.addEventListener("keydown", onKeydown);
  window.addEventListener("resize", onWindowScrollOrResize);
  window.addEventListener("scroll", onWindowScrollOrResize, true);
});

onBeforeUnmount(() => {
  window.removeEventListener("keydown", onKeydown);
  window.removeEventListener("resize", onWindowScrollOrResize);
  window.removeEventListener("scroll", onWindowScrollOrResize, true);
});

/** Local helpers (không phụ thuộc contract) */
function initials(name: string) {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  const a = parts[0]?.[0] ?? "";
  const b = parts.length > 1 ? parts[parts.length - 1]![0] : "";
  return (a + b).toUpperCase() || "GV";
}

function roleShortLabel(key: RoleKey) {
  if (key === "LECTURER") return "GV";
  if (key === "DEPARTMENT_BOARD") return "BCN";
  return "QLKH";
}

function statusPill(status: "ACTIVE" | "INACTIVE") {
  return status === "ACTIVE"
    ? "bg-emerald-50 text-emerald-700 ring-emerald-200"
    : "bg-rose-50 text-rose-700 ring-rose-200";
}

function formatDate(iso: string) {
  const d = new Date(iso);
  return new Intl.DateTimeFormat("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(d);
}
</script>
