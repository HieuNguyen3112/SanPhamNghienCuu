<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

    <div
      class="absolute left-1/2 top-1/2 w-[94vw] max-w-[760px] -translate-x-1/2 -translate-y-1/2"
    >
      <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
      >
        <div class="border-b border-slate-200 px-4 py-4 md:px-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold text-slate-900">
                Thêm tài khoản giảng viên
              </div>
              <div class="mt-1 text-xs text-slate-500">
                Mật khẩu mặc định là
                <span class="font-semibold">Password!123</span>. Giảng viên có
                thể đổi mật khẩu sau khi đăng nhập, không bắt buộc ở lần đầu.
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

        <form
          class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2 md:p-5"
          @submit.prevent="onSubmit"
        >
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700"
              >Mã giảng viên</label
            >
            <input
              v-model="form.lecturerCode"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm uppercase focus:border-slate-400 focus:ring-0"
              placeholder="VD: GV123"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700"
              >Trạng thái</label
            >
            <select
              v-model="form.status"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            >
              <option value="ACTIVE">Hoạt động</option>
              <option value="INACTIVE">Vô hiệu</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-700"
              >Họ tên</label
            >
            <input
              v-model="form.fullName"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
              placeholder="Nhập họ tên giảng viên"
            />
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-700"
              >Tên đăng nhập</label
            >
            <div class="flex overflow-hidden rounded-xl border border-slate-200">
              <input
                v-model="form.email"
                class="min-w-0 flex-1 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-0"
                placeholder="Nhập phần trước @"
              />
              <span
                class="inline-flex items-center border-l border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-600"
              >
                @local.test
              </span>
            </div>
            <div class="mt-1 text-xs text-slate-500">
              Hệ thống tự gắn hậu tố <span class="font-semibold">@local.test</span>.
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-700">{{
              organizationLabel
            }}</label>
            <select
              v-model="form.unitId"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            >
              <option :value="null" disabled>{{ placeholderText }}</option>
              <option
                v-for="unit in unitOptions"
                :key="unit.id"
                :value="unit.id"
              >
                {{ unit.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700"
              >Số điện thoại</label
            >
            <input
              v-model="form.phoneNumber"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
              placeholder="Tùy chọn"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-700"
              >Chức danh</label
            >
            <input
              v-model="form.academicTitle"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
              placeholder="VD: Giảng viên chính"
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
              <UserPlus class="h-4 w-4" />
              Tạo tài khoản
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { UserPlus, X } from "lucide-vue-next";
import type {
  CreateLecturerAccountPayload,
  UnitOptionDTO,
} from "../contracts/lecturerAccountManagement.contract";

const props = defineProps<{
  open: boolean;
  saving: boolean;
  error: string | null;
  unitOptions: UnitOptionDTO[];
  organizationLabel?: "Đơn vị" | "Khoa";
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "save", payload: CreateLecturerAccountPayload): void;
}>();

const form = reactive({
  lecturerCode: "",
  fullName: "",
  email: "",
  unitId: null as number | null,
  phoneNumber: "",
  academicTitle: "",
  status: "ACTIVE" as "ACTIVE" | "INACTIVE",
});

const localError = ref<string | null>(null);
const organizationLabel = computed(() => props.organizationLabel ?? "Đơn vị");
const placeholderText = computed(() =>
  organizationLabel.value === "Khoa" ? "Chọn khoa" : "Chọn đơn vị",
);
const emailDomain = "@local.test";

watch(
  () => props.open,
  (open) => {
    if (!open) return;
    localError.value = null;
    form.lecturerCode = "";
    form.fullName = "";
    form.email = "";
    form.unitId = props.unitOptions[0]?.id ?? null;
    form.phoneNumber = "";
    form.academicTitle = "";
    form.status = "ACTIVE";
  },
);

function normalizeEmailLocalPart(value: string) {
  const trimmed = value.trim().toLowerCase();
  if (!trimmed) return "";

  if (!trimmed.includes("@")) {
    return trimmed;
  }

  return trimmed.split("@")[0] ?? "";
}

function isValidEmailLocalPart(value: string) {
  if (!value) return false;

  return /^[a-z0-9._-]+$/.test(value);
}

function onSubmit() {
  localError.value = null;

  const lecturerCode = form.lecturerCode.trim().toUpperCase();
  const fullName = form.fullName.trim();
  const emailLocalPart = normalizeEmailLocalPart(form.email);
  const email = `${emailLocalPart}${emailDomain}`;

  if (!lecturerCode) {
    localError.value = "Mã giảng viên không được để trống.";
    return;
  }
  if (!fullName) {
    localError.value = "Họ tên không được để trống.";
    return;
  }
  if (!isValidEmailLocalPart(emailLocalPart)) {
    localError.value =
      "Tên đăng nhập không hợp lệ. Chỉ dùng chữ thường không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.";
    return;
  }
  if (typeof form.unitId !== "number" || Number.isNaN(form.unitId)) {
    localError.value =
      organizationLabel.value === "Khoa"
        ? "Vui lòng chọn khoa."
        : "Vui lòng chọn đơn vị.";
    return;
  }

  const selectedOrganizationId = form.unitId;

  emit("save", {
    lecturer_code: lecturerCode,
    full_name: fullName,
    email,
    unit_id:
      organizationLabel.value === "Đơn vị" ? selectedOrganizationId : null,
    faculty_id:
      organizationLabel.value === "Khoa" ? selectedOrganizationId : null,
    phone_number: form.phoneNumber.trim() || null,
    academic_title: form.academicTitle.trim() || null,
    status: form.status,
  });
}
</script>
