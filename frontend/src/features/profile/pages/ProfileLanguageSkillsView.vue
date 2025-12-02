<!-- src/features/profile/pages/ProfileLanguageView.vue -->
<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Trình độ ngoại ngữ</h1>
      <p class="mt-1 text-sm text-slate-500">
        Thông tin về các ngoại ngữ và chứng chỉ mà giảng viên sở hữu.
      </p>
    </header>

    <section class="rounded-md bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">Ngoại ngữ</h2>
        <button
          type="button"
          class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
          @click="onAddClick"
        >
          Thêm thông tin
        </button>
      </div>

      <p class="mb-4 text-sm text-slate-500">
        Khai báo các ngoại ngữ sử dụng được, trình độ và minh chứng bằng/chứng
        chỉ (nếu có).
      </p>

      <!-- Modal thêm / chỉnh sửa ngoại ngữ -->
      <Teleport to="body">
        <div
          v-if="isModalOpen"
          class="fixed inset-0 z-40 flex items-center justify-center"
          aria-modal="true"
          role="dialog"
        >
          <!-- Backdrop -->
          <div class="absolute inset-0 bg-slate-900/40" @click="onCancel"></div>

          <!-- Modal content -->
          <div
            class="relative z-50 w-full max-w-3xl rounded-lg bg-white p-6 shadow-xl"
          >
            <div class="mb-4 flex items-start justify-between">
              <div>
                <h3 class="text-lg font-semibold text-slate-900">
                  {{
                    editingRecord
                      ? "Chỉnh sửa trình độ ngoại ngữ"
                      : "Thêm trình độ ngoại ngữ"
                  }}
                </h3>
                <p class="mt-1 text-sm text-slate-500">
                  Nhập thông tin chi tiết về ngoại ngữ và tải lên minh chứng
                  (nếu có).
                </p>
              </div>
              <button
                type="button"
                class="inline-flex items-center rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                @click="onCancel"
              >
                <span class="sr-only">Đóng</span>
                ✕
              </button>
            </div>

            <ProfileLanguageForm
              :level-options="levelOptions"
              :model-value="editingRecord"
              @cancel="onCancel"
              @submit="onFormSubmit"
            />
          </div>
        </div>
      </Teleport>

      <!-- Danh sách ngoại ngữ -->
      <div
        v-if="languageList.length"
        class="overflow-x-auto rounded-md border border-slate-200"
      >
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th
                scope="col"
                class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Ngoại ngữ
              </th>
              <th
                scope="col"
                class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Trình độ
              </th>
              <th
                scope="col"
                class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Chứng chỉ
              </th>
              <th
                scope="col"
                class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Điểm / bậc
              </th>
              <th
                scope="col"
                class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Thời hạn
              </th>
              <th
                scope="col"
                class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Minh chứng
              </th>
              <th
                scope="col"
                class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Thao tác
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="item in languageList"
              :key="item.id"
              class="hover:bg-slate-50"
            >
              <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-800">
                {{ item.language }}
              </td>
              <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-800">
                {{ mapLevelLabel(item.level) }}
              </td>
              <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-800">
                <div class="flex flex-col">
                  <span>{{ item.certificateName || "—" }}</span>
                  <span
                    v-if="item.certificateIssuer"
                    class="text-xs text-slate-500"
                  >
                    {{ item.certificateIssuer }}
                  </span>
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-800">
                {{ item.certificateScore || "—" }}
              </td>
              <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-800">
                <span v-if="item.issueDate || item.expireDate">
                  {{ item.issueDate || "?" }} -
                  {{ item.expireDate || "Không thời hạn" }}
                </span>
                <span v-else>—</span>
              </td>
              <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-800">
                <span v-if="item.attachmentName" class="text-xs text-[#234a74]">
                  {{ item.attachmentName }}
                </span>
                <span v-else class="text-xs text-slate-400">
                  Chưa có minh chứng
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-2 text-right text-sm">
                <div class="inline-flex items-center gap-1">
                  <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-xs text-slate-600 hover:bg-slate-50 hover:text-sky-700"
                    title="Chỉnh sửa"
                    @click="onEdit(item)"
                  >
                    ✏
                  </button>
                  <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-xs text-red-600 hover:bg-red-50"
                    title="Xóa"
                    @click="onRemove(item.id)"
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
      <div
        v-else
        class="rounded-md border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500"
      >
        Chưa có thông tin ngoại ngữ. Nhấn
        <span class="font-semibold">"Thêm thông tin"</span> để bắt đầu khai báo.
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import ProfileLanguageForm, {
  type LanguageFormPayload,
  type LanguageLevel,
} from "@/features/profile/components/form/ProfileLanguageForm.vue";

interface LanguageRecord {
  id: string;
  language: string;
  level: LanguageLevel;
  certificateName?: string;
  certificateIssuer?: string;
  certificateScore?: string;
  issueDate?: string;
  expireDate?: string;
  note?: string;
  attachmentName?: string;
}

const levelOptions: { value: LanguageLevel; label: string }[] = [
  { value: "BASIC", label: "Cơ bản" },
  { value: "INTERMEDIATE", label: "Khá" },
  { value: "ADVANCED", label: "Tốt" },
  { value: "A1", label: "A1" },
  { value: "A2", label: "A2" },
  { value: "B1", label: "B1" },
  { value: "B2", label: "B2" },
  { value: "C1", label: "C1" },
  { value: "C2", label: "C2" },
];

const languageList = ref<LanguageRecord[]>([]);
const isModalOpen = ref(false);
const editingRecord = ref<LanguageRecord | null>(null);

const onAddClick = () => {
  editingRecord.value = null;
  isModalOpen.value = true;
};

const onEdit = (item: LanguageRecord) => {
  editingRecord.value = { ...item };
  isModalOpen.value = true;
};

const onCancel = () => {
  isModalOpen.value = false;
  editingRecord.value = null;
};

const onFormSubmit = (payload: LanguageFormPayload) => {
  if (editingRecord.value) {
    // update
    const index = languageList.value.findIndex(
      (x) => x.id === editingRecord.value?.id
    );
    if (index !== -1) {
      const current = languageList.value[index];
      languageList.value[index] = {
        id: current!.id,
        language: payload.language,
        level: payload.level as LanguageLevel,
        certificateName: payload.certificateName,
        certificateIssuer: payload.certificateIssuer,
        certificateScore: payload.certificateScore,
        issueDate: payload.issueDate,
        expireDate: payload.expireDate,
        note: payload.note,
        attachmentName: payload.file?.name ?? current!.attachmentName,
      };
    }
  } else {
    // create
    const record: LanguageRecord = {
      id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
      language: payload.language,
      level: payload.level as LanguageLevel,
      certificateName: payload.certificateName,
      certificateIssuer: payload.certificateIssuer,
      certificateScore: payload.certificateScore,
      issueDate: payload.issueDate,
      expireDate: payload.expireDate,
      note: payload.note,
      attachmentName: payload.file?.name,
    };
    // TODO: sau này gọi API tạo + upload file
    languageList.value.push(record);
  }

  isModalOpen.value = false;
  editingRecord.value = null;
};

const onRemove = (id: string) => {
  if (!confirm("Bạn có chắc chắn muốn xóa ngoại ngữ này?")) return;
  languageList.value = languageList.value.filter((item) => item.id !== id);
};

const mapLevelLabel = (value: LanguageLevel): string => {
  return levelOptions.find((opt) => opt.value === value)?.label ?? value;
};
</script>
