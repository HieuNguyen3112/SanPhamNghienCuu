<!-- src/features/profile/components/WorkHistoryForm.vue -->
<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <!-- Dòng 1: thời gian -->
    <div class="grid gap-4 md:grid-cols-3">
      <div>
        <label
          class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Từ ngày <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.fromDate"
          type="date"
          required
          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        />
      </div>

      <div>
        <label
          class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Đến ngày
        </label>
        <input
          v-model="form.toDate"
          type="date"
          :disabled="form.isCurrent"
          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm disabled:bg-slate-100 disabled:text-slate-400 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        />
        <div class="mt-2 flex items-center gap-2">
          <input
            id="isCurrent"
            v-model="form.isCurrent"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
          />
          <label for="isCurrent" class="text-xs text-slate-600">
            Hiện đang công tác tại đây
          </label>
        </div>
      </div>

      <div>
        <label
          class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Hình thức công tác
        </label>
        <select
          v-model="form.workType"
          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        >
          <option value="">-- Chọn --</option>
          <option value="BIEN_CHE">Biên chế</option>
          <option value="HOP_DONG">Hợp đồng</option>
          <option value="KIEM_NHIEM">Kiêm nhiệm</option>
          <option value="THINH_GIANG">Thỉnh giảng</option>
          <option value="KHAC">Khác</option>
        </select>
      </div>
    </div>

    <!-- Dòng 2: đơn vị / phòng ban -->
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label
          class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Cơ quan / đơn vị công tác <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.organization"
          type="text"
          required
          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="Ví dụ: Trường ĐH Sư phạm TP.HCM"
        />
      </div>

      <div>
        <label
          class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Khoa / phòng / bộ môn
        </label>
        <input
          v-model="form.department"
          type="text"
          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="Ví dụ: Khoa Công nghệ Thông tin"
        />
      </div>
    </div>

    <!-- Dòng 3: chức vụ -->
    <div class="grid gap-4 md:grid-cols-2">
      <div>
        <label
          class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Chức vụ / chức danh <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.position"
          type="text"
          required
          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="Ví dụ: Giảng viên, Trưởng bộ môn..."
        />
      </div>

      <div>
        <label
          class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Nơi làm việc chi tiết
        </label>
        <input
          v-model="form.workplace"
          type="text"
          class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="Ví dụ: Cơ sở chính, Cơ sở 2..."
        />
      </div>
    </div>

    <!-- Ghi chú -->
    <div>
      <label
        class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
      >
        Ghi chú
      </label>
      <textarea
        v-model="form.note"
        rows="2"
        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        placeholder="Ví dụ: Quyết định bổ nhiệm, kiêm nhiệm, điều động..."
      />
    </div>

    <!-- Nút hành động -->
    <div class="flex justify-end gap-2 pt-2">
      <button
        type="button"
        class="rounded-md border border-slate-300 bg-white px-4 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="$emit('cancel')"
      >
        Hủy
      </button>
      <button
        type="submit"
        class="rounded-md bg-sky-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-sky-700"
      >
        Lưu
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { reactive, watch } from "vue";

export interface WorkHistoryFormModel {
  fromDate: string;
  toDate: string;
  isCurrent: boolean;
  organization: string;
  department: string;
  position: string;
  workplace: string;
  workType: string;
  note: string;
}

interface Props {
  modelValue?: WorkHistoryFormModel | null; // dùng cho edit sau này
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "submit", payload: WorkHistoryFormModel): void;
  (e: "cancel"): void;
}>();

const form = reactive<WorkHistoryFormModel>({
  fromDate: "",
  toDate: "",
  isCurrent: false,
  organization: "",
  department: "",
  position: "",
  workplace: "",
  workType: "",
  note: "",
});

// nếu sau này truyền dữ liệu để edit thì map vào form
watch(
  () => props.modelValue,
  (val) => {
    if (val) {
      Object.assign(form, val);
    }
  },
  { immediate: true }
);

function onSubmit() {
  if (!form.fromDate || !form.organization || !form.position) {
    // có thể thêm validate chi tiết hơn
    return;
  }
  emit("submit", { ...form });
}
</script>
