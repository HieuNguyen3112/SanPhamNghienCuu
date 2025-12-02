<!-- src/features/declarations/components/ArticleDeclarationForm.vue -->
<template>
  <form class="space-y-6" @submit.prevent="onSubmit">
    <!-- Thông tin bài báo -->
    <div class="space-y-4">
      <div class="grid gap-4 md:grid-cols-2">
        <!-- Loại bài báo -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Loại bài báo <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.category"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            required
          >
            <option value="" disabled>-- Chọn loại bài báo --</option>
            <option value="HDGSNN_1_2">HDGSNN từ 1 → 2 điểm (900 giờ)</option>
            <option value="HDGSNN_TO_1">HDGSNN đến 1 điểm (600 giờ)</option>
            <option value="ISSN_ISBN">Chỉ số ISSN/ISBN (300 giờ)</option>
          </select>
          <p class="mt-1 text-[11px] text-slate-500">
            Giờ chuẩn tương ứng:
            <span class="font-semibold">{{ baseHours }}</span> giờ.
          </p>
        </div>

        <!-- Tạp chí -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Tạp chí <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.journal"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Tên tạp chí"
            required
          />
        </div>
      </div>

      <!-- Tên bài báo -->
      <div>
        <label class="block text-xs font-medium text-slate-700">
          Tên bài báo <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.title"
          type="text"
          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="Nhập tên bài báo"
          required
        />
      </div>

      <!-- Số, trang, DOI -->
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <label class="block text-xs font-medium text-slate-700"> Số </label>
          <input
            v-model="form.issue"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: Vol. 10, No. 2"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700">
            Trang
          </label>
          <input
            v-model="form.pages"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: 25–34"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700"> DOI </label>
          <input
            v-model="form.doi"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="10.xxxx/xxxxxxxx"
          />
        </div>
      </div>

      <!-- Link public & ghi chú minh chứng -->
      <div class="grid gap-4 md:grid-cols-3">
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Link public
          </label>
          <input
            v-model="form.publicLink"
            type="url"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="https://..."
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-slate-700">
            Ghi chú minh chứng
          </label>
          <input
            v-model="form.evidenceNote"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ghi chú về file minh chứng, bản scan, v.v."
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Năm xuất bản <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.publishedYear"
            type="text"
            inputmode="numeric"
            maxlength="4"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="2024"
            required
          />
        </div>
      </div>
    </div>

    <!-- Danh sách thành viên -->
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

              <!-- Chức danh khoa học (ngoài trường) -->
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
                  placeholder="Tác giả chính, đồng tác giả..."
                />
              </td>

              <!-- Giờ được tính (chia đều) -->
              <td class="px-2 py-2 align-top text-right text-xs">
                <span
                  class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700"
                >
                  {{ memberHours }} giờ
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

      <!-- Tóm tắt chia giờ -->
      <div
        v-if="members.length && baseHours"
        class="rounded-lg bg-slate-50 p-3 text-[11px] text-slate-600"
      >
        <p>
          Tổng giờ chuẩn:
          <span class="font-semibold text-slate-800">{{ baseHours }}</span> giờ.
          Số thành viên:
          <span class="font-semibold text-slate-800">
            {{ members.length }}
          </span>
          ⇒ mỗi thành viên được tính
          <span class="font-semibold text-emerald-700">
            {{ memberHours }}
          </span>
          giờ (chia đều).
        </p>
      </div>
    </div>

    <!-- Tài liệu minh chứng (nằm trong form) -->
    <div class="space-y-3">
      <h2 class="text-sm font-semibold text-slate-700">Tài liệu minh chứng</h2>

      <WorkEvidenceUpload v-model="evidences" work-type="ARTICLE" />

      <p class="mt-1 text-[11px] text-slate-400">
        ⚠️ Vui lòng đính kèm bản scan bài báo, trang bìa tạp chí, index, quyết
        định công nhận (nếu có) và các minh chứng liên quan.
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
  ArticleFormModel,
  Member,
  WorkEvidenceFile,
} from "@/features/declarations/types";

const emit = defineEmits<{
  (
    e: "submit",
    payload: {
      form: ArticleFormModel;
      members: Member[];
      evidences: WorkEvidenceFile[];
      baseHours: number;
      memberHours: number;
    }
  ): void;
}>();

const form = reactive<ArticleFormModel>({
  category: "",
  title: "",
  issue: "",
  pages: "",
  journal: "",
  doi: "",
  publicLink: "",
  evidenceNote: "",
  publishedYear: "",
});

const members = ref<Member[]>([]);
const nextMemberId = ref(1);

const evidences = ref<WorkEvidenceFile[]>([]);

const baseHours = computed(() => {
  switch (form.category) {
    case "HDGSNN_1_2":
      return 900;
    case "HDGSNN_TO_1":
      return 600;
    case "ISSN_ISBN":
      return 300;
    default:
      return 0;
  }
});

const memberHours = computed(() => {
  if (!baseHours.value || !members.value.length) return 0;
  const raw = baseHours.value / members.value.length;
  return Math.round(raw * 100) / 100;
});

const canSubmit = computed(
  () =>
    !!form.category &&
    !!form.title &&
    !!form.journal &&
    members.value.length > 0
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
    memberHours: memberHours.value,
  });
}
</script>
