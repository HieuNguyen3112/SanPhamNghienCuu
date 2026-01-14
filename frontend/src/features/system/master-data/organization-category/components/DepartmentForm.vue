<!-- src/features/organization-category/components/DepartmentForm.vue -->
<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-50">
      <div class="absolute inset-0 bg-slate-900/40" @click="onClose" />

      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div
          class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-xl"
        >
          <div
            class="flex items-start justify-between gap-4 border-b border-slate-200 p-4 md:p-6"
          >
            <div class="min-w-0">
              <div class="text-base font-semibold text-slate-900">
                {{ editing ? "Sửa đơn vị" : "Thêm đơn vị" }}
              </div>
              <div class="mt-1 text-xs text-slate-500">
                Quản lý đơn vị trực thuộc theo khoa. Không hỗ trợ xóa.
              </div>
            </div>

            <button
              class="rounded-xl p-2 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
              @click="onClose"
            >
              <X class="h-5 w-5" />
            </button>
          </div>

          <form class="space-y-4 p-4 md:p-6" @submit.prevent="onSubmit">
            <div class="grid gap-4 md:grid-cols-2">
              <div class="md:col-span-2">
                <label class="text-xs font-semibold text-slate-600"
                  >Khoa quản lý *</label
                >
                <select
                  v-model="form.facultyId"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
                >
                  <option :value="0" disabled>Chọn khoa...</option>
                  <option v-for="f in facultyOptions" :key="f.id" :value="f.id">
                    {{ f.name }}
                  </option>
                </select>
                <p v-if="errors.facultyId" class="mt-1 text-xs text-rose-600">
                  {{ errors.facultyId }}
                </p>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-600"
                  >Mã đơn vị *</label
                >
                <input
                  v-model="form.code"
                  type="text"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:bg-slate-50"
                  placeholder="VD: BM-KTPM"
                  :disabled="isCodeLocked"
                />
                <p v-if="errors.code" class="mt-1 text-xs text-rose-600">
                  {{ errors.code }}
                </p>
                <p v-else-if="isCodeLocked" class="mt-1 text-xs text-amber-600">
                  Không thể sửa mã khi đã có giảng viên.
                </p>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-600"
                  >Tên đơn vị *</label
                >
                <input
                  v-model="form.name"
                  type="text"
                  class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
                  placeholder="VD: Bộ môn Kỹ thuật Phần mềm"
                />
                <p v-if="errors.name" class="mt-1 text-xs text-rose-600">
                  {{ errors.name }}
                </p>
              </div>

              <!-- Không có trong schema -->
              <div class="md:col-span-1">
                <label class="text-xs font-semibold text-slate-600"
                  >Loại đơn vị</label
                >
                <select
                  disabled
                  class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"
                >
                  <option>Chưa hỗ trợ trong DB</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">
                  Trường này chưa có trong DB.
                </p>
              </div>

              <!-- Không có trong schema -->
              <div class="md:col-span-1">
                <label class="text-xs font-semibold text-slate-600"
                  >Trạng thái</label
                >
                <div
                  class="mt-1 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"
                >
                  <span
                    class="inline-flex h-5 w-9 items-center rounded-full bg-slate-200 px-1"
                  >
                    <span class="h-4 w-4 rounded-full bg-white" />
                  </span>
                  <span>Chưa hỗ trợ trong DB</span>
                </div>
              </div>

              <!-- Không có trong schema -->
              <div class="md:col-span-2">
                <label class="text-xs font-semibold text-slate-600"
                  >Ghi chú</label
                >
                <textarea
                  disabled
                  rows="3"
                  class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"
                  placeholder="Chưa hỗ trợ trong DB"
                />
              </div>
            </div>

            <div
              class="flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-end"
            >
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                @click="onClose"
              >
                Hủy
              </button>

              <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="loading"
              >
                <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
                Lưu
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from "vue";
import { Loader2, X } from "lucide-vue-next";
import type {
  Department,
  FacultyOption,
} from "../contracts/organizationCategory.contract";

const props = defineProps<{
  open: boolean;
  loading: boolean;
  editing: Department | null;
  facultyOptions: FacultyOption[];
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (
    e: "submit",
    payload: { facultyId: number; code: string; name: string }
  ): void;
}>();

const form = reactive({
  facultyId: 0,
  code: "",
  name: "",
});

const errors = reactive<{ facultyId?: string; code?: string; name?: string }>(
  {}
);

const isCodeLocked = computed(
  () => (props.editing?.lecturersCount ?? 0) > 0
);

watch(
  () => props.open,
  (v) => {
    if (!v) return;
    errors.facultyId = undefined;
    errors.code = undefined;
    errors.name = undefined;

    if (props.editing) {
      form.facultyId = props.editing.facultyId;
      form.code = props.editing.code;
      form.name = props.editing.name;
    } else {
      form.facultyId = props.facultyOptions[0]?.id ?? 0;
      form.code = "";
      form.name = "";
    }
  },
  { immediate: true }
);

function validate(): boolean {
  errors.facultyId = undefined;
  errors.code = undefined;
  errors.name = undefined;

  if (!form.facultyId || form.facultyId <= 0)
    errors.facultyId = "Vui lòng chọn khoa quản lý.";

  const code = form.code.trim();
  const name = form.name.trim();

  if (!code) errors.code = "Mã đơn vị là bắt buộc.";
  else if (code.length > 50) errors.code = "Mã đơn vị tối đa 50 ký tự.";

  if (!name) errors.name = "Tên đơn vị là bắt buộc.";
  else if (name.length > 255) errors.name = "Tên đơn vị tối đa 255 ký tự.";

  return !errors.facultyId && !errors.code && !errors.name;
}

function onClose() {
  emit("close");
}

function onSubmit() {
  if (!validate()) return;
  emit("submit", {
    facultyId: form.facultyId,
    code: form.code.trim(),
    name: form.name.trim(),
  });
}
</script>
