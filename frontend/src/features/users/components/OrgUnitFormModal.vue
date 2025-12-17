<script setup lang="ts">
import { computed, reactive, watch } from "vue";
import type {
  OrganizationUnit,
  OrgUnitType,
  CreateOrganizationUnitPayload,
  UpdateOrganizationUnitPayload,
} from "@/features/users/types";

interface OrgUnitFormModalProps {
  unit?: OrganizationUnit | null;
  parentOptions: OrganizationUnit[];
  initialParentId?: number | null;
  initialCampus?: string;
}

interface OrgUnitFormModalEmits {
  (e: "close"): void;
  (e: "create", payload: CreateOrganizationUnitPayload): void;
  (e: "update", payload: UpdateOrganizationUnitPayload): void;
}

const props = defineProps<OrgUnitFormModalProps>();
const emit = defineEmits<OrgUnitFormModalEmits>();

interface FormState {
  name: string;
  code: string;
  type: OrgUnitType;
  campus: string;
  parentId: number | null;
  order: number;
  isActive: boolean;
  managerName: string;
  managerTitle: string;
}

interface FormErrors {
  name?: string;
  code?: string;
  campus?: string;
  type?: string;
}

const isEditMode = computed(() => !!props.unit);

const state = reactive<FormState>({
  name: "",
  code: "",
  type: "FACULTY",
  campus: props.initialCampus || "TP.HCM",
  parentId: props.initialParentId ?? null,
  order: 1,
  isActive: true,
  managerName: "",
  managerTitle: "",
});

const errors = reactive<FormErrors>({});

watch(
  () => props.unit,
  (u) => {
    if (u) {
      state.name = u.name;
      state.code = u.code;
      state.type = u.type;
      state.campus = u.campus;
      state.parentId = u.parentId;
      state.order = u.order;
      state.isActive = u.isActive;
      state.managerName = u.managerName || "";
      state.managerTitle = u.managerTitle || "";
    } else {
      state.name = "";
      state.code = "";
      state.type = "FACULTY";
      state.campus = props.initialCampus || "TP.HCM";
      state.parentId = props.initialParentId ?? null;
      state.order = 1;
      state.isActive = true;
      state.managerName = "";
      state.managerTitle = "";
    }
  },
  { immediate: true }
);

const parentOptionsFiltered = computed(() =>
  props.parentOptions.filter((u) => u.id !== (props.unit?.id ?? -1))
);

const validate = (): boolean => {
  errors.name = !state.name.trim() ? "Tên đơn vị là bắt buộc." : "";
  errors.code = !state.code.trim() ? "Mã đơn vị là bắt buộc." : "";
  errors.campus = !state.campus.trim() ? "Cơ sở là bắt buộc." : "";
  errors.type = !state.type ? "Loại đơn vị là bắt buộc." : "";

  return !errors.name && !errors.code && !errors.campus && !errors.type;
};

const handleSave = () => {
  if (!validate()) return;

  if (isEditMode.value && props.unit) {
    const payload: UpdateOrganizationUnitPayload = {
      id: props.unit.id,
      name: state.name.trim(),
      code: state.code.trim(),
      type: state.type,
      campus: state.campus.trim(),
      parentId: state.parentId,
      order: state.order,
      isActive: state.isActive,
      managerName: state.managerName.trim() || undefined,
      managerTitle: state.managerTitle.trim() || undefined,
    };
    emit("update", payload);
  } else {
    const payload: CreateOrganizationUnitPayload = {
      name: state.name.trim(),
      code: state.code.trim(),
      type: state.type,
      campus: state.campus.trim(),
      parentId: state.parentId,
      order: state.order,
      isActive: state.isActive,
      managerName: state.managerName.trim() || undefined,
      managerTitle: state.managerTitle.trim() || undefined,
    };
    emit("create", payload);
  }
};

const handleClose = () => {
  emit("close");
};
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center px-4">
    <!-- overlay -->
    <div class="absolute inset-0 bg-slate-900/40" @click="handleClose"></div>

    <div
      class="relative w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl ring-1 ring-slate-200"
    >
      <div class="mb-4 flex items-start justify-between">
        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            {{ isEditMode ? "Chỉnh sửa đơn vị" : "Thêm đơn vị mới" }}
          </h2>
          <p class="mt-1 text-xs text-slate-500">
            {{
              isEditMode
                ? "Cập nhật thông tin đơn vị trong cơ cấu tổ chức."
                : "Khai báo một đơn vị mới trong cơ cấu tổ chức."
            }}
          </p>
        </div>
        <button
          type="button"
          class="ml-3 inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200"
          @click="handleClose"
        >
          ✕
        </button>
      </div>

      <form class="space-y-3" @submit.prevent="handleSave">
        <div>
          <label class="block text-xs font-medium text-slate-600">
            Tên đơn vị <span class="text-red-500">*</span>
          </label>
          <input
            v-model="state.name"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          />
          <p v-if="errors.name" class="mt-1 text-xs text-red-500">
            {{ errors.name }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-slate-600">
              Mã đơn vị <span class="text-red-500">*</span>
            </label>
            <input
              v-model="state.code"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              placeholder="VD: K-CNTT, BM-HTTT"
            />
            <p v-if="errors.code" class="mt-1 text-xs text-red-500">
              {{ errors.code }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">
              Loại đơn vị <span class="text-red-500">*</span>
            </label>
            <select
              v-model="state.type"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            >
              <option value="CAMPUS">Cơ sở</option>
              <option value="FACULTY">Khoa / Viện</option>
              <option value="CENTER">Trung tâm / Đơn vị</option>
            </select>

            <p v-if="errors.type" class="mt-1 text-xs text-red-500">
              {{ errors.type }}
            </p>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-slate-600">
              Người quản lý
            </label>
            <input
              v-model="state.managerName"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              placeholder="VD: Nguyễn Văn A"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">
              Chức danh
            </label>
            <input
              v-model="state.managerTitle"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              placeholder="VD: Hiệu trưởng, Trưởng khoa"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-slate-600">
              Cơ sở <span class="text-red-500">*</span>
            </label>
            <input
              v-model="state.campus"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
              placeholder="VD: TP.HCM, Long An"
            />
            <p v-if="errors.campus" class="mt-1 text-xs text-red-500">
              {{ errors.campus }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600">
              Thứ tự hiển thị
            </label>
            <input
              v-model.number="state.order"
              type="number"
              min="1"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-600">
            Thuộc đơn vị
          </label>
          <select
            v-model="state.parentId"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option :value="null">— Không (đơn vị gốc)</option>
            <option
              v-for="p in parentOptionsFiltered"
              :key="p.id"
              :value="p.id"
            >
              {{ p.name }} ({{ p.code }})
            </option>
          </select>
        </div>

        <div class="flex items-center justify-between pt-2">
          <label class="inline-flex items-center gap-2 text-xs text-slate-700">
            <input
              v-model="state.isActive"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
            />
            <span>Đơn vị đang hoạt động</span>
          </label>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-4 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              @click="handleClose"
            >
              Hủy
            </button>
            <button
              type="submit"
              class="rounded-lg bg-sky-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-sky-700"
            >
              {{ isEditMode ? "Lưu thay đổi" : "Thêm đơn vị" }}
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>
