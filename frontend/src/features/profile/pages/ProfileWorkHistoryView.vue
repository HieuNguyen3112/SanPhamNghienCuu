<!-- src/features/profile/pages/ProfileWorkHistoryView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Quá trình công tác</h1>
      <p class="mt-1 text-sm text-slate-500">
        Danh sách các đơn vị, chức vụ mà giảng viên đã và đang công tác.
      </p>
    </header>

    <section class="relative rounded-md bg-white p-6 shadow-sm">
      <!-- Header + nút -->
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
          Quá trình công tác
        </h2>
        <button
          type="button"
          class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
          @click="openAddForm"
        >
          Thêm thông tin
        </button>
      </div>

      <!-- Bảng danh sách quá trình công tác -->
      <div v-if="entries.length" class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead
            class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-3 py-2">Thời gian</th>
              <th class="px-3 py-2">Đơn vị / cơ quan</th>
              <th class="px-3 py-2">Chức vụ</th>
              <th class="px-3 py-2">Hình thức</th>
              <th class="px-3 py-2">Ghi chú</th>
              <th class="px-3 py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="item in entries" :key="item.id" class="relative">
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                <p>
                  {{ formatDate(item.fromDate) }}
                  –
                  <span
                    v-if="item.isCurrent"
                    class="font-medium text-emerald-700"
                  >
                    Hiện tại
                  </span>
                  <span v-else>
                    {{ formatDate(item.toDate) || "—" }}
                  </span>
                </p>
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                <p class="font-medium">{{ item.organization }}</p>
                <p v-if="item.department" class="text-xs text-slate-500">
                  {{ item.department }}
                </p>
                <p v-if="item.workplace" class="text-xs text-slate-400">
                  {{ item.workplace }}
                </p>
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ item.position }}
              </td>
              <td class="px-3 py-2 align-top text-xs text-slate-600">
                {{ workTypeLabel(item.workType) }}
              </td>
              <td class="px-3 py-2 align-top text-xs text-slate-600">
                {{ item.note || "—" }}
              </td>

              <td class="px-3 py-2 align-top text-right">
                <div class="inline-flex items-center gap-1">
                  <!-- Sửa -->
                  <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 text-xs hover:bg-slate-50 hover:text-sky-700"
                    title="Chỉnh sửa"
                    @click="onEdit(item)"
                  >
                    ✏
                  </button>

                  <!-- Xóa -->
                  <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-red-600 text-xs hover:bg-red-50"
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

      <p
        v-else
        class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500"
      >
        Chưa có thông tin đào tạo. Hãy bấm
        <span class="font-semibold">"Thêm thông tin"</span> để tạo dòng đầu
        tiên.
      </p>

      <!-- Modal thêm / chỉnh sửa thông tin -->
      <div
        v-if="showForm"
        class="fixed inset-0 z-30 flex items-center justify-center bg-slate-900/40"
      >
        <div
          class="w-full max-w-3xl rounded-lg bg-white p-5 shadow-xl"
          @click.stop
        >
          <div class="mb-3 flex items-center justify-between">
            <h3 class="text-base font-semibold text-slate-800">
              {{
                editingEntry
                  ? "Chỉnh sửa quá trình công tác"
                  : "Thêm thông tin quá trình công tác"
              }}
            </h3>
            <button
              type="button"
              class="rounded-full p-1 text-slate-500 hover:bg-slate-100"
              @click="closeForm"
            >
              <span class="sr-only">Đóng</span>
              ✕
            </button>
          </div>

          <WorkHistoryForm
            :model-value="editingEntry"
            @submit="handleSubmit"
            @cancel="closeForm"
          />
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from "vue";
import WorkHistoryForm, {
  type WorkHistoryFormModel,
} from "@/features/profile/components/form/ProfileWorkHistoryForm.vue";

interface WorkHistoryEntry extends WorkHistoryFormModel {
  id: number;
}

// mock data – sau này thay bằng dữ liệu từ API
const entries = reactive<WorkHistoryEntry[]>([
  {
    id: 1,
    fromDate: "2015-09-01",
    toDate: "",
    isCurrent: true,
    organization: "Trường ĐH Sư phạm TP.HCM",
    department: "Khoa Công nghệ Thông tin",
    position: "Giảng viên",
    workplace: "Cơ sở chính",
    workType: "BIEN_CHE",
    note: "Giảng dạy các học phần Công nghệ phần mềm, Phát triển Web.",
  },
]);

const showForm = ref(false);
const editingEntry = ref<WorkHistoryEntry | null>(null);
const rowMenuOpenId = ref<number | null>(null);

let nextId = 2;

function openAddForm() {
  editingEntry.value = null;
  showForm.value = true;
  rowMenuOpenId.value = null;
}

function onEdit(item: WorkHistoryEntry) {
  editingEntry.value = { ...item };
  showForm.value = true;
  rowMenuOpenId.value = null;
}

function onDelete(id: number) {
  rowMenuOpenId.value = null;
  if (!confirm("Bạn có chắc chắn muốn xóa dòng quá trình công tác này?")) {
    return;
  }
  const index = entries.findIndex((e) => e.id === id);
  if (index !== -1) {
    entries.splice(index, 1);
  }
}

function closeForm() {
  showForm.value = false;
  editingEntry.value = null;
}

function handleSubmit(payload: WorkHistoryFormModel) {
  if (editingEntry.value) {
    // update
    const index = entries.findIndex((e) => e.id === editingEntry.value?.id);
    if (index !== -1) {
      entries[index] = {
        id: entries[index]!.id,
        ...payload,
      };
    }
  } else {
    // create
    entries.push({
      id: nextId++,
      ...payload,
    });
  }
  showForm.value = false;
  editingEntry.value = null;
}

function toggleRowMenu(id: number) {
  rowMenuOpenId.value = rowMenuOpenId.value === id ? null : id;
}

function formatDate(value: string): string {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const d = String(date.getDate()).padStart(2, "0");
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const y = date.getFullYear();
  return `${d}/${m}/${y}`;
}

function workTypeLabel(code: string): string {
  switch (code) {
    case "BIEN_CHE":
      return "Biên chế";
    case "HOP_DONG":
      return "Hợp đồng";
    case "KIEM_NHIEM":
      return "Kiêm nhiệm";
    case "THINH_GIANG":
      return "Thỉnh giảng";
    case "KHAC":
      return "Khác";
    default:
      return "";
  }
}
</script>
