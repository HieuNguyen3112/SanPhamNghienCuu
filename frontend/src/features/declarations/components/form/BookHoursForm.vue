<!-- src/features/declarations/components/BookHoursForm.vue -->
<template>
  <form class="space-y-6" @submit.prevent="onSubmit">
    <!-- 1. Thông tin giáo trình / tài liệu -->
    <div class="space-y-4">
      <h2 class="text-sm font-semibold text-slate-700">
        Thông tin giáo trình / tài liệu tham khảo
      </h2>

      <div class="grid gap-4 md:grid-cols-2">
        <!-- Loại -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Loại tài liệu <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.type"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            required
          >
            <option value="" disabled>-- Chọn loại --</option>
            <option value="TEXTBOOK">Giáo trình (900 giờ)</option>
            <option value="REFERENCE">Tài liệu tham khảo (600 giờ)</option>
          </select>
          <p class="mt-1 text-[11px] text-slate-500">
            Giờ chuẩn:
            <span class="font-semibold">
              {{ baseHours }}
            </span>
            giờ.
          </p>
        </div>

        <!-- Chủ biên -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Chủ biên <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.principalEditor"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Họ và tên chủ biên"
            required
          />
        </div>
      </div>

      <!-- Tên, NXB, năm XB, ISBN -->
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Tên giáo trình / tài liệu <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.title"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Nhập tên giáo trình / tài liệu"
            required
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700">
            Nhà xuất bản
          </label>
          <input
            v-model="form.publisher"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: NXB Giáo dục"
          />
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Năm xuất bản
          </label>
          <input
            v-model="form.publishedYear"
            type="text"
            inputmode="numeric"
            maxlength="4"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="2024"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700"> ISBN </label>
          <input
            v-model="form.isbn"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: 978-604-xx-xxxxx"
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
          placeholder="Ví dụ: Đã được hội đồng thông qua, dùng cho học phần X..."
        />
      </div>
    </div>

    <!-- 2. Danh sách người tham gia viết -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">
          Danh sách người tham gia viết
          <span class="text-[11px] font-normal text-slate-500">
            (không tính chủ biên – hệ thống sẽ tự tính chung)
          </span>
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
                  placeholder="Đồng chủ biên, tác giả..."
                />
              </td>

              <!-- Giờ được tính cho mỗi thành viên -->
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

      <!-- Tóm tắt công thức giờ -->
      <div
        v-if="baseHours"
        class="rounded-lg bg-slate-50 p-3 text-[11px] text-slate-600"
      >
        <p class="mb-1">
          Tổng giờ chuẩn:
          <span class="font-semibold text-slate-800">{{ baseHours }}</span> giờ.
          (Giáo trình = 900, Tài liệu tham khảo = 600)
        </p>
        <p class="mb-1">
          <span class="font-semibold">Chủ biên</span> được:
          <span class="font-semibold text-emerald-700">
            {{ principalHours }}
          </span>
          giờ (1/5 tổng giờ =
          {{ baseHours }} × 1/5), cộng thêm phần chia đều từ 4/5.
        </p>
        <p>
          Số người tham gia viết (kể cả chủ biên):
          <span class="font-semibold text-slate-800">
            {{ totalParticipants }}
          </span>
          ⇒ phần 4/5 còn lại
          <span class="font-semibold"> ({{ remainingHours }} giờ) </span>
          được chia đều, mỗi người:
          <span class="font-semibold text-emerald-700">
            {{ memberHoursEach }}
          </span>
          giờ.
        </p>
      </div>
    </div>

    <!-- 3. Nút hành động -->
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
import type {
  BookHoursFormModel,
  Member,
  BookMaterialType,
} from "@/features/declarations/types";

const emit = defineEmits<{
  (
    e: "submit",
    payload: {
      form: BookHoursFormModel;
      members: Member[];
      baseHours: number;
      principalHours: number;
      memberHoursEach: number;
    }
  ): void;
}>();

const form = reactive<BookHoursFormModel>({
  type: "",
  title: "",
  isbn: "",
  publisher: "",
  publishedYear: "",
  principalEditor: "",
  note: "",
});

const members = ref<Member[]>([]);
const nextMemberId = ref(1);

// giờ chuẩn theo loại
const baseHours = computed(() => {
  switch (form.type as BookMaterialType) {
    case "TEXTBOOK":
      return 900;
    case "REFERENCE":
      return 600;
    default:
      return 0;
  }
});

// 1/5 cho chủ biên
const principalHours = computed(() => {
  if (!baseHours.value) return 0;
  const base = baseHours.value / 5;
  // ngoài phần 1/5, chủ biên còn nhận thêm phần chia đều từ 4/5
  const totalParticipants = members.value.length + 1; // +1 chủ biên
  if (!totalParticipants) return 0;
  const remaining = (baseHours.value * 4) / 5;
  const shareEach = remaining / totalParticipants;
  const totalForPrincipal = base + shareEach;
  return Math.round(totalForPrincipal * 100) / 100;
});

// 4/5 còn lại chia đều cho tất cả người tham gia viết (kể cả chủ biên)
const remainingHours = computed(() => {
  if (!baseHours.value) return 0;
  return Math.round(((baseHours.value * 4) / 5) * 100) / 100;
});

const totalParticipants = computed(() => members.value.length + 1); // +1 chủ biên

const memberHoursEach = computed(() => {
  if (!baseHours.value) return 0;
  if (!totalParticipants.value) return 0;
  const share = remainingHours.value / totalParticipants.value;
  return Math.round(share * 100) / 100;
});

const canSubmit = computed(
  () => !!form.type && !!form.title && !!form.principalEditor
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
    baseHours: baseHours.value,
    principalHours: principalHours.value,
    memberHoursEach: memberHoursEach.value,
  });
}
</script>
