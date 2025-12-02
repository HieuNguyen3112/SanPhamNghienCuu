<!-- src/features/profile/pages/ProfileEducationView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Quá trình đào tạo</h1>
      <p class="mt-1 text-sm text-slate-500">
        Thông tin về các chương trình đào tạo, bằng cấp đã tham gia.
      </p>
    </header>

    <section class="relative rounded-md bg-white p-6 shadow-sm">
      <!-- Header + actions -->
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
          Quá trình đào tạo
        </h2>
        <button
          type="button"
          class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
          @click="openAddForm"
        >
          Thêm thông tin
        </button>
      </div>

      <p class="mb-4 text-sm text-slate-500">
        Khai báo đầy đủ các bậc đào tạo như Đại học, Thạc sĩ, Tiến sĩ... của
        giảng viên.
      </p>

      <!-- Danh sách quá trình đào tạo -->
      <div
        v-if="entries.length"
        class="overflow-x-auto rounded-md border border-slate-200"
      >
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead
            class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-3 py-2">Bậc đào tạo</th>
              <th class="px-3 py-2">Chuyên ngành</th>
              <th class="px-3 py-2">Cơ sở đào tạo</th>
              <th class="px-3 py-2">Quốc gia</th>
              <th class="px-3 py-2">Thời gian</th>
              <th class="px-3 py-2">Hình thức</th>
              <th class="px-3 py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr v-for="item in entries" :key="item.id" class="relative">
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                {{ mapDegreeLabel(item.degreeLevel) }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                {{ item.major }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                {{ item.institution }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                {{ item.country }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                {{ item.startYear }} - {{ item.endYear }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                {{ mapTrainingTypeLabel(item.trainingType) }}
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
        Chưa có thông tin đào tạo. Hãy bấm
        <span class="font-semibold">"Thêm thông tin"</span> để tạo dòng đầu
        tiên.
      </p>

      <!-- Modal thêm / chỉnh sửa thông tin (giống WorkHistoryView) -->
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
                  ? "Chỉnh sửa quá trình đào tạo"
                  : "Thêm thông tin quá trình đào tạo"
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

          <ProfileEducationForm
            :model-value="editingEntry"
            :degree-options="degreeOptions"
            :training-type-options="trainingTypeOptions"
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
import ProfileEducationForm, {
  type EducationFormModel,
  type DegreeLevel,
  type TrainingType,
} from "@/features/profile/components/form/ProfileEducationForm.vue";

interface EducationEntry extends EducationFormModel {
  id: number;
}

const degreeOptions: { value: DegreeLevel; label: string }[] = [
  { value: "UNDERGRADUATE", label: "Đại học" },
  { value: "MASTER", label: "Thạc sĩ" },
  { value: "PHD", label: "Tiến sĩ" },
  { value: "POSTDOC", label: "Sau tiến sĩ" },
  { value: "OTHER", label: "Khác" },
];

const trainingTypeOptions: { value: TrainingType; label: string }[] = [
  { value: "FULL_TIME", label: "Chính quy / Tập trung" },
  { value: "PART_TIME", label: "Không tập trung" },
  { value: "IN_SERVICE", label: "Vừa làm vừa học" },
  { value: "DISTANCE", label: "Đào tạo từ xa" },
  { value: "OTHER", label: "Khác" },
];

// mock data – sau này thay bằng dữ liệu từ API
const entries = reactive<EducationEntry[]>([]);

const showForm = ref(false);
const editingEntry = ref<EducationEntry | null>(null);
let nextId = 1;

function openAddForm() {
  editingEntry.value = null;
  showForm.value = true;
}

function onEdit(item: EducationEntry) {
  editingEntry.value = { ...item };
  showForm.value = true;
}

function onDelete(id: number) {
  if (!confirm("Bạn có chắc chắn muốn xóa dòng quá trình đào tạo này?")) {
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

function handleSubmit(payload: EducationFormModel) {
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

function mapDegreeLabel(value: DegreeLevel): string {
  return degreeOptions.find((opt) => opt.value === value)?.label ?? value;
}

function mapTrainingTypeLabel(value: TrainingType): string {
  return trainingTypeOptions.find((opt) => opt.value === value)?.label ?? value;
}
</script>
