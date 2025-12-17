<!-- src/features/declarations/components/ProjectHoursForm.vue -->
<template>
  <form class="space-y-6" @submit.prevent="onSubmit">
    <!-- 1. Thông tin đề tài -->
    <div class="space-y-4">
      <h2 class="text-sm font-semibold text-slate-700">
        Thông tin đề tài KH&CN
      </h2>

      <div class="grid gap-4 md:grid-cols-2">
        <!-- Loại đề tài -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Loại đề tài <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.level"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            required
          >
            <option value="" disabled>-- Chọn loại đề tài --</option>
            <option value="MINISTRY">Cấp Bộ</option>
            <option value="PROVINCIAL">Cấp Tỉnh / Bộ ngành</option>
            <option value="INSTITUTION">Cấp Cơ sở</option>
            <option value="OTHER">Khác</option>
          </select>
          <p class="mt-1 text-[11px] text-slate-500">
            Giờ chuẩn (mock):
            <span class="font-semibold">{{ baseHours }}</span> giờ
            <!-- TODO: thay theo công thức thực tế trong file Word -->
          </p>
        </div>

        <!-- Chủ nhiệm đề tài -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Chủ nhiệm đề tài <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.principalName"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Họ và tên chủ nhiệm đề tài"
            required
          />
        </div>
      </div>

      <!-- Tên đề tài + mã số -->
      <div class="grid gap-4 md:grid-cols-3">
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-slate-700">
            Tên đề tài <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.title"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Nhập tên đề tài KH&CN"
            required
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700">
            Mã số đề tài
          </label>
          <input
            v-model="form.code"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: B2024-XXX-01"
          />
        </div>
      </div>

      <!-- Thời gian thực hiện -->
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Năm bắt đầu
          </label>
          <input
            v-model="form.startYear"
            type="text"
            inputmode="numeric"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: 2024"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Năm kết thúc
          </label>
          <input
            v-model="form.endYear"
            type="text"
            inputmode="numeric"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: 2026"
          />
        </div>
      </div>

      <!-- Ghi chú -->
      <div>
        <label class="block text-xs font-medium text-slate-700">
          Ghi chú (nếu có)
        </label>
        <textarea
          v-model="form.note"
          rows="2"
          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="Ví dụ: Đề tài đã nghiệm thu loại Xuất sắc..."
        />
      </div>
    </div>

    <!-- 2. Danh sách thành viên -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">
          Danh sách thành viên tham gia
        </h2>
        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200"
          @click="addMember"
        >
          + Thêm thành viên
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs">
          <thead
            class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-2 py-2 text-center">#</th>
              <th class="px-2 py-2">Trong / ngoài trường</th>
              <th class="px-2 py-2">Họ và tên</th>
              <th class="px-2 py-2">Chức danh KH (ngoài trường)</th>
              <th class="px-2 py-2">Học vị</th>
              <th class="px-2 py-2">Đơn vị công tác</th>
              <th class="px-2 py-2">Vai trò</th>
              <th class="px-2 py-2 text-right">Giờ được tính</th>
              <th class="px-2 py-2 text-center">Xóa</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!members.length">
              <td
                colspan="9"
                class="px-3 py-4 text-center text-xs text-slate-500"
              >
                Chưa có thành viên nào. Hãy bấm "Thêm thành viên".
              </td>
            </tr>

            <tr v-for="(m, index) in members" :key="m.id">
              <td class="px-2 py-2 text-center align-top">
                {{ index + 1 }}
              </td>

              <!-- Trong / ngoài trường -->
              <td class="px-2 py-2 align-top">
                <select
                  v-model="m.isExternal"
                  class="w-36 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                >
                  <option :value="false">Trong trường</option>
                  <option :value="true">Ngoài trường</option>
                </select>
              </td>

              <!-- Họ và tên -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="m.fullName"
                  type="text"
                  class="w-40 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  placeholder="Họ và tên"
                />
              </td>

              <!-- Chức danh KH -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="m.academicTitle"
                  type="text"
                  class="w-40 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  :placeholder="
                    m.isExternal
                      ? 'GS, PGS, TS...'
                      : 'Chỉ nhập cho người ngoài trường'
                  "
                  :disabled="!m.isExternal"
                />
              </td>

              <!-- Học vị -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="m.degree"
                  type="text"
                  class="w-32 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  placeholder="ThS, TS..."
                />
              </td>

              <!-- Đơn vị công tác -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="m.organization"
                  type="text"
                  class="w-40 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  placeholder="Đơn vị công tác"
                />
              </td>

              <!-- Vai trò -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="m.role"
                  type="text"
                  class="w-32 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  placeholder="Chủ nhiệm, thư ký, TV..."
                />
              </td>

              <!-- Giờ được tính (chia cho mỗi thành viên) -->
              <td class="px-2 py-2 align-top text-right text-xs">
                <span
                  class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700"
                >
                  {{ memberHoursEach }} giờ
                </span>
              </td>

              <!-- Xóa -->
              <td class="px-2 py-2 align-top text-center">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-[11px] text-red-600 hover:bg-red-50"
                  @click="removeMember(m.id)"
                >
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tóm tắt chia giờ đề tài -->
      <div
        v-if="baseHours && 1 + members.length"
        class="rounded-lg bg-slate-50 p-3 text-[11px] text-slate-600"
      >
        <p class="mb-1">
          Tổng giờ chuẩn cho đề tài:
          <span class="font-semibold text-slate-800">
            {{ baseHours }}
          </span>
          giờ.
        </p>
        <p class="mb-1">
          Tỷ lệ cho chủ nhiệm:
          <span class="font-semibold text-slate-800">
            {{ principalSharePercent }}%
          </span>
          ⇒ giờ chủ nhiệm:
          <span class="font-semibold text-emerald-700">
            {{ principalHours }}
          </span>
          giờ.
        </p>
        <p>
          Số thành viên (không tính chủ nhiệm):
          <span class="font-semibold text-slate-800">
            {{ members.length }}
          </span>
          ⇒ mỗi thành viên được:
          <span class="font-semibold text-emerald-700">
            {{ memberHoursEach }}
          </span>
          giờ (chia đều phần còn lại).
        </p>
      </div>

      <!-- Có thể cho chỉnh tỷ lệ chủ nhiệm -->
      <div class="flex items-center gap-2 text-[11px] text-slate-500">
        <span class="whitespace-nowrap">Tỷ lệ giờ cho chủ nhiệm:</span>
        <input
          v-model.number="principalSharePercent"
          type="number"
          min="0"
          max="100"
          class="w-16 rounded-md border border-slate-300 px-2 py-1 text-xs focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
        />
        <span>% (mặc định 50%)</span>
      </div>
    </div>

    <!-- 3. Minh chứng công trình -->
    <div class="space-y-3">
      <h2 class="text-sm font-semibold text-slate-700">
        Minh chứng công trình
      </h2>

      <WorkEvidenceUpload v-model="evidences" work-type="PROJECT" />

      <p class="mt-1 text-[11px] text-slate-400">
        ⚠️ Đính kèm quyết định giao đề tài, hợp đồng, biên bản nghiệm thu, báo
        cáo tổng kết và các minh chứng liên quan theo quy định.
      </p>
    </div>

    <!-- Nút hành động -->
    <div class="flex justify-end gap-2 pt-2">
      <button
        type="button"
        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
      >
        Hủy
      </button>
      <button
        type="submit"
        class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="!canSubmit"
      >
        Lưu kê khai
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import WorkEvidenceUpload from "@/features/declarations/components/WorkEvidenceUpload.vue";
import type {
  ProjectHoursFormModel,
  Member,
  WorkEvidenceFile,
  ProjectLevel,
} from "@/features/declarations/types";

const emit = defineEmits<{
  (
    e: "submit",
    payload: {
      form: ProjectHoursFormModel;
      members: Member[];
      evidences: WorkEvidenceFile[];
      baseHours: number;
      principalHours: number;
      memberHoursEach: number;
    }
  ): void;
}>();

const form = reactive<ProjectHoursFormModel>({
  level: "",
  title: "",
  code: "",
  principalName: "",
  startYear: "",
  endYear: "",
  note: "",
});

const members = ref<Member[]>([]);
const nextMemberId = ref(1);
const evidences = ref<WorkEvidenceFile[]>([]);

// mock quy đổi giờ theo loại đề tài
const baseHours = computed(() => {
  switch (form.level as ProjectLevel) {
    case "MINISTRY":
      return 300; // ví dụ: đề tài cấp Bộ
    case "PROVINCIAL":
      return 220; // ví dụ: đề tài cấp Tỉnh/Bộ ngành
    case "INSTITUTION":
      return 150; // ví dụ: đề tài cấp cơ sở
    case "OTHER":
      return 100; // các loại khác
    default:
      return 0;
  }
});

// % giờ cho chủ nhiệm (phần còn lại chia đều cho thành viên)
const principalSharePercent = ref(50);

const principalHours = computed(() => {
  if (!baseHours.value) return 0;
  const raw = (baseHours.value * principalSharePercent.value) / 100;
  return Math.round(raw * 100) / 100;
});

const memberHoursEach = computed(() => {
  if (!baseHours.value) return 0;
  const remain = baseHours.value - principalHours.value;
  if (!members.value.length || remain <= 0) return 0;
  const raw = remain / members.value.length;
  return Math.round(raw * 100) / 100;
});

const canSubmit = computed(
  () =>
    !!form.level &&
    !!form.title &&
    !!form.principalName &&
    members.value.length >= 0 // có thể cho phép không có thành viên, chỉ có chủ nhiệm
);

function addMember() {
  members.value.push({
    id: nextMemberId.value++,
    isExternal: false,
    fullName: "",
    academicTitle: "",
    degree: "",
    organization: "",
    role: "",
  });
}

function removeMember(id: number) {
  members.value = members.value.filter((m) => m.id !== id);
}

function onSubmit() {
  if (!canSubmit.value) return;

  emit("submit", {
    form: { ...form },
    members: members.value.map((m) => ({ ...m })),
    evidences: evidences.value.slice(),
    baseHours: baseHours.value,
    principalHours: principalHours.value,
    memberHoursEach: memberHoursEach.value,
  });
}
</script>
