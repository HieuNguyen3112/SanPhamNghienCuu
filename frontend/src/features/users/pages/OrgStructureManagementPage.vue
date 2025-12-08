<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import OrgUnitFormModal from "../../users/components/OrgUnitFormModal.vue";
import type {
  OrganizationUnit,
  OrgUnitType,
  CreateOrganizationUnitPayload,
  UpdateOrganizationUnitPayload,
} from "@/features/users/types";
import {
  fetchOrganizationUnits,
  createOrganizationUnit,
  updateOrganizationUnit,
  deleteOrganizationUnit,
  toggleOrganizationUnitActive,
} from "@/features/users/api";
import OrgUnitRowActionsMenu from "@/features/users/components/OrgUnitRowActionsMenu.vue";

type CampusFilter = "ALL" | string;
type OrgTypeFilter = "ALL" | OrgUnitType;

const orgUnits = ref<OrganizationUnit[]>([]);
const loading = ref<boolean>(false);

const campusFilter = ref<CampusFilter>("ALL");
const typeFilter = ref<OrgTypeFilter>("ALL");
const search = ref<string>("");

// Modal state
const showOrgModal = ref<boolean>(false);
const editingOrgUnit = ref<OrganizationUnit | null>(null);
const modalParentId = ref<number | null>(null);
const modalCampus = ref<string | null>(null);

const campusOptions = computed<string[]>(() => {
  const set = new Set<string>();
  orgUnits.value.forEach((u) => {
    if (u.campus) set.add(u.campus);
  });
  return Array.from(set);
});

const orgTypeLabel = (type: OrgUnitType): string => {
  switch (type) {
    case "CAMPUS":
      return "Cơ sở";
    case "FACULTY":
      return "Khoa / Viện";
    case "DEPARTMENT":
      return "Bộ môn / Phòng ban";
    case "CENTER":
      return "Trung tâm / Đơn vị";
    default:
      return type;
  }
};

const filteredOrgUnits = computed<OrganizationUnit[]>(() => {
  let data = [...orgUnits.value];

  if (campusFilter.value !== "ALL") {
    data = data.filter((u) => u.campus === campusFilter.value);
  }

  if (typeFilter.value !== "ALL") {
    data = data.filter((u) => u.type === typeFilter.value);
  }

  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase();
    data = data.filter(
      (u) =>
        u.name.toLowerCase().includes(q) ||
        u.code.toLowerCase().includes(q) ||
        (u.parentName && u.parentName.toLowerCase().includes(q))
    );
  }

  return data.sort((a, b) => a.order - b.order || a.name.localeCompare(b.name));
});

const totalUnits = computed(() => orgUnits.value.length);
const activeUnits = computed(
  () => orgUnits.value.filter((u) => u.isActive).length
);
const inactiveUnits = computed(
  () => orgUnits.value.filter((u) => !u.isActive).length
);

const loadOrgUnits = async () => {
  try {
    loading.value = true;
    const res = await fetchOrganizationUnits();
    orgUnits.value = res;
  } finally {
    loading.value = false;
  }
};

const openCreateModal = (parent?: OrganizationUnit) => {
  editingOrgUnit.value = null;
  modalParentId.value = parent ? parent.id : null;
  modalCampus.value =
    campusFilter.value !== "ALL"
      ? campusFilter.value
      : parent
      ? parent.campus
      : campusOptions.value[0] ?? "TP.HCM";
  showOrgModal.value = true;
};

const openEditModal = (unit: OrganizationUnit) => {
  editingOrgUnit.value = { ...unit };
  modalParentId.value = unit.parentId;
  modalCampus.value = unit.campus;
  showOrgModal.value = true;
};

const closeOrgModal = () => {
  showOrgModal.value = false;
  editingOrgUnit.value = null;
  modalParentId.value = null;
  modalCampus.value = null;
};

const handleCreateOrgUnit = async (payload: CreateOrganizationUnitPayload) => {
  await createOrganizationUnit(payload);
  await loadOrgUnits();
  closeOrgModal();
};

const handleUpdateOrgUnit = async (payload: UpdateOrganizationUnitPayload) => {
  await updateOrganizationUnit(payload);
  await loadOrgUnits();
  closeOrgModal();
};

const handleToggleActive = async (unit: OrganizationUnit) => {
  await toggleOrganizationUnitActive(unit.id);
  await loadOrgUnits();
};

const handleDeleteUnit = async (unit: OrganizationUnit) => {
  const ok = window.confirm(
    `Bạn có chắc muốn xóa đơn vị "${unit.name}"? Các đơn vị con (nếu có) sẽ bị ảnh hưởng.`
  );
  if (!ok) return;
  await deleteOrganizationUnit(unit.id);
  await loadOrgUnits();
};

onMounted(() => {
  loadOrgUnits();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Quản lý cơ cấu tổ chức
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Quản lý hệ thống khoa, bộ môn, phòng ban theo từng cơ sở (TP.HCM, Long
        An, ...).
      </p>
    </div>

    <!-- Bộ lọc -->
    <div
      class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-end lg:justify-between"
    >
      <div class="flex flex-wrap gap-3">
        <div class="w-44">
          <label class="block text-xs font-medium text-slate-600">
            Cơ sở
          </label>
          <select
            v-model="campusFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option v-for="c in campusOptions" :key="c" :value="c">
              {{ c }}
            </option>
          </select>
        </div>

        <div class="w-48">
          <label class="block text-xs font-medium text-slate-600">
            Loại đơn vị
          </label>
          <select
            v-model="typeFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="CAMPUS">Cơ sở</option>
            <option value="FACULTY">Khoa / Viện</option>
            <option value="DEPARTMENT">Bộ môn / Phòng ban</option>
            <option value="CENTER">Trung tâm / Đơn vị</option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap items-end gap-2">
        <input
          v-model="search"
          type="text"
          placeholder="Tìm theo tên, mã đơn vị, đơn vị cha..."
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 lg:w-80"
        />

        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-sky-700"
          @click="openCreateModal()"
        >
          + Thêm đơn vị
        </button>
      </div>
    </div>

    <!-- Tóm tắt -->
    <!-- <div
      class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="mb-0.5">
            Tổng số đơn vị:
            <span class="font-semibold text-slate-800">
              {{ totalUnits }}
            </span>
          </p>
          <p class="mb-0.5">
            Đang hoạt động:
            <span class="font-semibold text-emerald-700">
              {{ activeUnits }}
            </span>
            &nbsp;·&nbsp; Đã vô hiệu:
            <span class="font-semibold text-rose-600">
              {{ inactiveUnits }}
            </span>
          </p>
          <p class="mt-0.5">
            Đơn vị theo bộ lọc hiện tại:
            <span class="font-semibold text-slate-800">
              {{ filteredOrgUnits.length }}
            </span>
          </p>
        </div>
      </div>
    </div> -->

    <!-- Bảng cơ cấu tổ chức (cao cố định, scroll dọc) -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="max-h-[70vh] overflow-y-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead
            class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-3 py-2 text-left">Đơn vị</th>
              <th class="px-3 py-2 text-left">Mã</th>
              <th class="px-3 py-2 text-left">Loại</th>
              <th class="px-3 py-2 text-left">Cơ sở</th>
              <th class="px-3 py-2 text-left">Thuộc đơn vị</th>
              <th class="px-3 py-2 text-left">Thứ tự</th>
              <th class="px-3 py-2 text-center">Trạng thái</th>
              <th class="px-3 py-2 text-center">Thao tác</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-if="loading">
              <td colspan="8" class="px-4 py-6 text-center text-slate-500">
                Đang tải dữ liệu...
              </td>
            </tr>

            <tr v-else-if="!filteredOrgUnits.length">
              <td colspan="8" class="px-4 py-6 text-center text-slate-500">
                Chưa có đơn vị nào phù hợp với bộ lọc.
              </td>
            </tr>

            <tr
              v-else
              v-for="unit in filteredOrgUnits"
              :key="unit.id"
              class="align-top hover:bg-slate-50"
            >
              <td class="px-3 py-3">
                <p class="text-sm font-medium text-slate-800">
                  {{ unit.name }}
                </p>
              </td>
              <td class="px-3 py-3 text-sm text-slate-700">
                {{ unit.code }}
              </td>
              <td class="px-3 py-3 text-xs text-slate-700">
                {{ orgTypeLabel(unit.type) }}
              </td>
              <td class="px-3 py-3 text-sm text-slate-700">
                {{ unit.campus }}
              </td>
              <td class="px-3 py-3 text-sm text-slate-700">
                {{ unit.parentName || "—" }}
              </td>
              <td class="px-3 py-3 text-sm text-slate-700">
                {{ unit.order }}
              </td>
              <td class="px-3 py-3 text-sm text-center">
                <span
                  class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold"
                  :class="
                    unit.isActive
                      ? 'border-emerald-100 bg-emerald-50 text-emerald-700'
                      : 'border-slate-200 bg-slate-50 text-slate-500'
                  "
                >
                  {{ unit.isActive ? "Đang hoạt động" : "Đã vô hiệu" }}
                </span>
              </td>
              <td class="px-3 py-3 text-center text-xs">
                <OrgUnitRowActionsMenu
                  :is-active="unit.isActive"
                  @add-child="openCreateModal(unit)"
                  @edit="openEditModal(unit)"
                  @toggle-active="handleToggleActive(unit)"
                  @delete="handleDeleteUnit(unit)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal thêm / sửa đơn vị -->
    <OrgUnitFormModal
      v-if="showOrgModal"
      :unit="editingOrgUnit"
      :parent-options="orgUnits"
      :initial-parent-id="modalParentId"
      :initial-campus="modalCampus || undefined"
      @close="closeOrgModal"
      @create="handleCreateOrgUnit"
      @update="handleUpdateOrgUnit"
    />
  </div>
</template>
