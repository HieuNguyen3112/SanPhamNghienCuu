<!-- src/features/profile/pages/ProfileAcademicRankView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">
        Học vị – chức danh khoa học
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Thông tin về các học vị và chức danh khoa học đã được công nhận.
      </p>
    </header>

    <section class="relative rounded-md bg-white p-6 shadow-sm">
      <!-- Header + nút -->
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
          Học vị – chức danh
        </h2>
        <button
          type="button"
          class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
          @click="openAddForm"
        >
          Thêm thông tin
        </button>
      </div>

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
              <th class="px-3 py-2">Học vị</th>
              <th class="px-3 py-2">Chuyên ngành</th>
              <th class="px-3 py-2">Nơi đào tạo</th>
              <th class="px-3 py-2">Năm bảo vệ</th>
              <th class="px-3 py-2">Chức danh</th>
              <th class="px-3 py-2">Năm phong</th>
              <th class="px-3 py-2">Nơi phong</th>
              <th class="px-3 py-2">Ghi chú</th>
              <th class="px-3 py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="item in entries"
              :key="item.id"
              class="hover:bg-slate-50"
            >
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ highestDegreeLabel(item.highestDegree) }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ item.degreeMajor }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                <div class="flex flex-col">
                  <span>{{ item.degreeInstitution }}</span>
                  <span
                    v-if="item.degreeCountry"
                    class="text-xs text-slate-500"
                  >
                    {{ item.degreeCountry }}
                  </span>
                </div>
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ item.degreeYear || "—" }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ academicTitleLabel(item.academicTitle) }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ item.academicTitleYear || "—" }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ item.academicTitleInstitution || "—" }}
              </td>
              <td class="px-3 py-2 align-top text-xs text-slate-600">
                {{ item.note || "—" }}
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
        Chưa có thông tin học vị – chức danh. Hãy bấm
        <span class="font-semibold">"Thêm thông tin"</span> để tạo dòng đầu
        tiên.
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
                      ? "Chỉnh sửa học vị – chức danh"
                      : "Thêm học vị – chức danh"
                  }}
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                  Nhập thông tin chi tiết về học vị và (nếu có) chức danh khoa
                  học.
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

            <ProfileAcademicRankForm
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
import ProfileAcademicRankForm, {
  type AcademicRankFormModel,
  type HighestDegree,
  type AcademicTitle,
} from "@/features/profile/components/ProfileAcademicRankForm.vue";

interface AcademicRankEntry extends AcademicRankFormModel {
  id: number;
}

// mock data – sau này thay bằng dữ liệu từ API
const entries = ref<AcademicRankEntry[]>([]);

const showForm = ref(false);
const editingEntry = ref<AcademicRankEntry | null>(null);

let nextId = 1;

const openAddForm = () => {
  editingEntry.value = null;
  showForm.value = true;
};

const onEdit = (item: AcademicRankEntry) => {
  editingEntry.value = { ...item };
  showForm.value = true;
};

const onDelete = (id: number) => {
  if (!confirm("Bạn có chắc chắn muốn xóa dòng này?")) return;
  entries.value = entries.value.filter((x) => x.id !== id);
};

const closeForm = () => {
  showForm.value = false;
  editingEntry.value = null;
};

const handleSubmit = (payload: AcademicRankFormModel) => {
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

  // TODO: sau này gọi API lưu danh sách học vị – chức danh ở đây

  showForm.value = false;
  editingEntry.value = null;
};

const highestDegreeLabel = (value: HighestDegree): string => {
  switch (value) {
    case "BACHELOR":
      return "Cử nhân / Kỹ sư";
    case "MASTER":
      return "Thạc sĩ";
    case "PHD":
      return "Tiến sĩ";
    case "OTHER":
    default:
      return "Khác";
  }
};

const academicTitleLabel = (value: AcademicTitle): string => {
  switch (value) {
    case "ASSOCIATE_PROFESSOR":
      return "Phó Giáo sư";
    case "PROFESSOR":
      return "Giáo sư";
    case "NONE":
    default:
      return "Không";
  }
};
</script>
