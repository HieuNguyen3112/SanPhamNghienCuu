<!-- src/features/profile/pages/ProfileResearchAreaView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Lĩnh vực nghiên cứu</h1>
      <p class="mt-1 text-sm text-slate-500">
        Khai báo các lĩnh vực nghiên cứu chính và phụ của giảng viên, kèm theo
        mô tả và từ khóa liên quan.
      </p>
    </header>

    <section class="relative rounded-md bg-white p-6 shadow-sm">
      <!-- Header + nút -->
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
          Danh sách lĩnh vực nghiên cứu
        </h2>
        <button
          type="button"
          class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
          @click="openAddForm"
        >
          Thêm lĩnh vực
        </button>
      </div>

      <p class="mb-4 text-sm text-slate-500">
        Ưu tiên khai báo rõ 1–2 lĩnh vực chính và các lĩnh vực phụ liên quan để
        phục vụ tra cứu, thống kê, phân nhóm chuyên môn.
      </p>

      <!-- Bảng danh sách -->
      <div
        v-if="entries.length"
        class="overflow-x-auto rounded-md border border-slate-200"
      >
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead
            class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-3 py-2">Lĩnh vực</th>
              <th class="px-3 py-2">Vai trò</th>
              <th class="px-3 py-2">Từ năm</th>
              <th class="px-3 py-2">Từ khóa</th>
              <th class="px-3 py-2">Mô tả</th>
              <th class="px-3 py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="item in entries"
              :key="item.id"
              class="hover:bg-slate-50"
            >
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                {{ item.name }}
              </td>
              <td class="px-3 py-2 align-top text-xs font-medium">
                <span
                  :class="[
                    'inline-flex rounded-full px-2 py-0.5',
                    item.type === 'PRIMARY'
                      ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
                      : 'bg-slate-50 text-slate-700 border border-slate-200',
                  ]"
                >
                  {{ researchTypeLabel(item.type) }}
                </span>
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ item.startYear || "—" }}
              </td>
              <td class="px-3 py-2 align-top text-xs text-slate-600">
                <span v-if="item.keywords">
                  {{ item.keywords }}
                </span>
                <span v-else>—</span>
              </td>
              <td class="px-3 py-2 align-top text-xs text-slate-600">
                <p class="max-w-xs whitespace-pre-line wrap-break-word">
                  {{ item.description || "—" }}
                </p>
              </td>
              <td class="px-3 py-2 align-top text-right">
                <div class="inline-flex items-center gap-1">
                  <!-- Sửa -->
                  <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-50 hover:text-sky-700"
                    title="Chỉnh sửa"
                    @click="onEdit(item)"
                  >
                    ✏
                  </button>

                  <!-- Xóa -->
                  <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-xs text-red-600 hover:bg-red-50"
                    title="Xóa"
                    @click="onDelete(item.id)"
                  >
                    🗑
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty state -->
      <p
        v-else
        class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500"
      >
        Chưa có lĩnh vực nghiên cứu nào. Hãy bấm
        <span class="font-semibold">"Thêm lĩnh vực"</span> để khai báo.
      </p>

      <!-- Modal thêm / chỉnh sửa -->
      <Teleport to="body">
        <div
          v-if="showForm"
          class="fixed inset-0 z-40 flex items-center justify-center"
          aria-modal="true"
          role="dialog"
        >
          <!-- Backdrop -->
          <div
            class="absolute inset-0 bg-slate-900/40"
            @click="closeForm"
          ></div>

          <!-- Modal content -->
          <div
            class="relative z-50 w-full max-w-3xl rounded-lg bg-white p-6 shadow-xl"
            @click.stop
          >
            <div class="mb-4 flex items-start justify-between">
              <div>
                <h3 class="text-lg font-semibold text-slate-900">
                  {{
                    editingEntry
                      ? "Chỉnh sửa lĩnh vực nghiên cứu"
                      : "Thêm lĩnh vực nghiên cứu"
                  }}
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                  Nhập thông tin chi tiết về lĩnh vực nghiên cứu, vai trò và từ
                  khóa liên quan.
                </p>
              </div>
              <button
                type="button"
                class="inline-flex items-center rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                @click="closeForm"
              >
                <span class="sr-only">Đóng</span>
                ✕
              </button>
            </div>

            <ProfileResearchAreaForm
              :model-value="editingEntry"
              @submit="handleSubmit"
              @cancel="closeForm"
            />
          </div>
        </div>
      </Teleport>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import ProfileResearchAreaForm, {
  type ResearchAreaFormModel,
  type ResearchAreaType,
} from "@/features/profile/components/ProfileResearchAreaForm.vue";

interface ResearchAreaEntry extends ResearchAreaFormModel {
  id: number;
}

// mock data – sau này thay bằng dữ liệu từ API
const entries = ref<ResearchAreaEntry[]>([
  // Ví dụ:
  // {
  //   id: 1,
  //   name: 'Trí tuệ nhân tạo',
  //   type: 'PRIMARY',
  //   startYear: 2018,
  //   keywords: 'machine learning, deep learning, NLP',
  //   description: 'Nghiên cứu mô hình học sâu, xử lý ngôn ngữ tự nhiên.'
  // },
]);

const showForm = ref(false);
const editingEntry = ref<ResearchAreaEntry | null>(null);
let nextId = 1;

const openAddForm = () => {
  editingEntry.value = null;
  showForm.value = true;
};

const onEdit = (item: ResearchAreaEntry) => {
  editingEntry.value = { ...item };
  showForm.value = true;
};

const onDelete = (id: number) => {
  if (!confirm("Bạn có chắc chắn muốn xóa lĩnh vực này?")) return;
  entries.value = entries.value.filter((x) => x.id !== id);
};

const closeForm = () => {
  showForm.value = false;
  editingEntry.value = null;
};

const handleSubmit = (payload: ResearchAreaFormModel) => {
  if (editingEntry.value) {
    const index = entries.value.findIndex(
      (x) => x.id === editingEntry.value?.id
    );
    if (index !== -1) {
      entries.value[index] = {
        id: entries.value[index]!.id,
        ...payload,
      };
    }
  } else {
    entries.value.push({
      id: nextId++,
      ...payload,
    });
  }

  // TODO: sau này gọi API lưu danh sách lĩnh vực nghiên cứu ở đây

  showForm.value = false;
  editingEntry.value = null;
};

const researchTypeLabel = (value: ResearchAreaType): string => {
  switch (value) {
    case "PRIMARY":
      return "Chính";
    case "SECONDARY":
    default:
      return "Phụ";
  }
};
</script>
