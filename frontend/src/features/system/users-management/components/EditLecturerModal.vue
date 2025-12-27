<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

    <div
      class="absolute left-1/2 top-1/2 w-[94vw] max-w-[720px] -translate-x-1/2 -translate-y-1/2"
    >
      <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
      >
        <div class="border-b border-slate-200 px-4 py-4 md:px-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold text-slate-900">
                Chỉnh sửa thông tin giảng viên
              </div>
              <div class="mt-1 text-xs text-slate-500">
                Không thể sửa mã giảng viên và tài khoản đăng nhập.
              </div>
            </div>
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
              @click="emit('close')"
              aria-label="Đóng"
              title="Đóng"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div class="p-4 md:p-5">
          <div v-if="!account" class="text-sm text-slate-700">
            Không có dữ liệu.
          </div>

          <form
            v-else
            class="grid grid-cols-1 gap-3 md:grid-cols-2"
            @submit.prevent="onSubmit"
          >
            <!-- readonly -->
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700"
                >Mã giảng viên</label
              >
              <input
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
                :value="account.lecturerCode"
                disabled
              />
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700"
                >Tài khoản</label
              >
              <input
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700"
                :value="account.username"
                disabled
              />
            </div>

            <!-- editable -->
            <div class="md:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-700"
                >Họ tên</label
              >
              <input
                v-model="form.fullName"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
                placeholder="Nhập họ tên..."
              />
            </div>

            <div class="md:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-700"
                >Email</label
              >
              <input
                v-model="form.email"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
                placeholder="Email công vụ..."
              />
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700"
                >Đơn vị</label
              >
              <select
                v-model.number="form.unitId"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
              >
                <option v-for="u in unitOptions" :key="u.id" :value="u.id">
                  {{ u.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-700"
                >Chức danh</label
              >
              <input
                v-model="form.positionTitle"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
                placeholder="Giảng viên / GVC / GVCC..."
              />
            </div>

            <div v-if="localError || error" class="md:col-span-2">
              <div
                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
              >
                {{ localError ?? error }}
              </div>
            </div>

            <div
              class="md:col-span-2 mt-1 flex flex-col gap-2 sm:flex-row sm:justify-end"
            >
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
                :disabled="saving"
              >
                Hủy
              </button>

              <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
                :disabled="saving"
              >
                <Save class="h-4 w-4" />
                Lưu thay đổi
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, watch, ref } from "vue";
import { Save, X } from "lucide-vue-next";
import type {
  LecturerAccount,
  UnitOptionDTO,
  UpdateLecturerAccountPayload,
} from "../contracts/lecturerAccountManagement.contract";

const props = defineProps<{
  open: boolean;
  account: LecturerAccount | null;
  unitOptions: UnitOptionDTO[];
  saving: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "save", payload: UpdateLecturerAccountPayload): void;
}>();

const form = reactive({
  fullName: "",
  email: "",
  unitId: 1,
  positionTitle: "" as string,
});

const localError = ref<string | null>(null);

watch(
  () => props.account,
  (acc) => {
    localError.value = null;
    if (!acc) return;
    form.fullName = acc.fullName;
    form.email = acc.email;
    form.unitId = acc.unitId;
    form.positionTitle = acc.positionTitle ?? "";
  },
  { immediate: true }
);

function isValidEmail(value: string) {
  const v = value.trim();
  if (!v) return false;
  // simple email check
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

function onSubmit() {
  localError.value = null;
  if (!props.account) return;

  const fullName = form.fullName.trim();
  if (!fullName) {
    localError.value = "Họ tên không được để trống.";
    return;
  }
  if (!isValidEmail(form.email)) {
    localError.value = "Email không hợp lệ.";
    return;
  }

  emit("save", {
    id: props.account.id,
    full_name: fullName,
    email: form.email.trim(),
    unit_id: form.unitId,
    position_title: form.positionTitle.trim()
      ? form.positionTitle.trim()
      : null,
  });
}
</script>
