<!-- src/features/declarations/components/ConferenceHoursForm.vue -->
<template>
  <form class="space-y-6" @submit.prevent="onSubmit">
    <!-- Thông tin chung -->
    <div class="space-y-4">
      <h2 class="text-sm font-semibold text-slate-700">
        Tính giờ tham gia Hội nghị / Hội thảo khoa học
      </h2>

      <div class="grid gap-4 md:grid-cols-3">
        <!-- Năm học -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Năm học
          </label>
          <select
            v-model="form.academicYear"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="2024-2025">2024–2025</option>
            <option value="2023-2024">2023–2024</option>
            <option value="2022-2023">2022–2023</option>
          </select>
        </div>

        <!-- Học kỳ -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Học kỳ
          </label>
          <select
            v-model="form.semester"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Cả năm</option>
            <option value="HK1">Học kỳ 1</option>
            <option value="HK2">Học kỳ 2</option>
          </select>
        </div>

        <!-- Ghi chú -->
        <div>
          <label class="block text-xs font-medium text-slate-700">
            Ghi chú
          </label>
          <input
            v-model="form.note"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
            placeholder="Ví dụ: Hội nghị trong/ngoài nước..."
          />
        </div>
      </div>
    </div>

    <!-- Danh sách hội nghị / hội thảo -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold text-slate-700">
            Danh sách hội nghị / hội thảo tham gia
          </p>
          <p class="text-[11px] text-slate-500">
            Báo cáo: 40 giờ/lần. Tham dự: 4 giờ/lần, tối đa 40 lần được tính.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200"
          @click="addEntry"
        >
          + Thêm hội nghị / hội thảo
        </button>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-xs">
          <thead
            class="border-b border-slate-200 bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-2 py-2 text-center">#</th>
              <th class="px-2 py-2">Loại</th>
              <th class="px-2 py-2">Tên hội nghị / hội thảo</th>
              <th class="px-2 py-2">Ngày tổ chức</th>
              <th class="px-2 py-2">Địa điểm</th>
              <th class="px-2 py-2">Minh chứng</th>
              <th class="px-2 py-2 text-right">Giờ được tính</th>
              <th class="px-2 py-2 text-center">Xóa</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!entries.length">
              <td
                colspan="8"
                class="px-3 py-4 text-center text-xs text-slate-500"
              >
                Chưa kê khai hội nghị / hội thảo nào. Hãy bấm
                <span class="font-semibold">"Thêm hội nghị / hội thảo"</span>.
              </td>
            </tr>

            <tr v-for="(item, index) in entries" :key="item.id">
              <!-- STT -->
              <td class="px-2 py-2 text-center align-top">
                {{ index + 1 }}
              </td>

              <!-- Loại: Báo cáo / Tham dự -->
              <td class="px-2 py-2 align-top">
                <select
                  v-model="item.role"
                  class="w-32 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                >
                  <option value="PRESENTATION">Báo cáo</option>
                  <option value="ATTENDANCE">Tham dự</option>
                </select>
              </td>

              <!-- Tên hội nghị -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="item.name"
                  type="text"
                  class="w-56 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  placeholder="Tên hội nghị / hội thảo"
                />
              </td>

              <!-- Ngày tổ chức -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="item.date"
                  type="date"
                  class="w-40 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                />
              </td>

              <!-- Địa điểm -->
              <td class="px-2 py-2 align-top">
                <input
                  v-model="item.location"
                  type="text"
                  class="w-40 rounded-lg border border-slate-300 px-2 py-1 text-xs shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  placeholder="Địa điểm tổ chức"
                />
              </td>

              <!-- Minh chứng: upload file -->
              <td class="px-2 py-2 align-top">
                <div class="space-y-1">
                  <label
                    class="inline-flex cursor-pointer items-center rounded-md bg-sky-50 px-2 py-1 text-[11px] font-medium text-sky-700 hover:bg-sky-100"
                  >
                    Chọn file...
                    <input
                      type="file"
                      class="hidden"
                      multiple
                      @change="onFilesSelected($event, item)"
                    />
                  </label>

                  <p
                    v-if="!item.evidences.length"
                    class="text-[11px] text-slate-400"
                  >
                    Chưa có file minh chứng.
                  </p>
                  <ul
                    v-else
                    class="max-h-20 space-y-0.5 overflow-y-auto text-[11px] text-slate-600"
                  >
                    <li
                      v-for="file in item.evidences"
                      :key="file.id"
                      class="flex items-center justify-between gap-1"
                    >
                      <span class="truncate max-w-[130px]">
                        {{ file.name }}
                      </span>
                      <button
                        type="button"
                        class="shrink-0 text-[10px] text-red-500 hover:underline"
                        @click="removeEvidence(item, file.id!)"
                      >
                        x
                      </button>
                    </li>
                  </ul>
                </div>
              </td>

              <!-- Giờ được tính cho dòng này -->
              <td class="px-2 py-2 align-top text-right text-xs">
                <span
                  class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700"
                >
                  {{ entryHours(item) }} giờ
                </span>
              </td>

              <!-- Xóa -->
              <td class="px-2 py-2 align-top text-center">
                <button
                  type="button"
                  class="rounded-md px-2 py-1 text-[11px] text-red-600 hover:bg-red-50"
                  @click="removeEntry(item.id)"
                >
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="text-[11px] text-slate-500">
        Quy định:
        <span class="font-semibold">Báo cáo</span> 40 giờ/lần.
        <span class="font-semibold">Tham dự</span> 4 giờ/lần, tối đa
        {{ maxAttendanceTimes }} lần được tính trong năm học ({{
          maxAttendanceTimes * 4
        }}
        giờ).
      </p>
    </div>

    <!-- Tổng hợp giờ -->
    <div
      class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600"
    >
      <p class="mb-1">
        Số lần <span class="font-semibold">báo cáo</span>:
        <span class="font-semibold text-slate-800">
          {{ presentationsCount }}
        </span>
        ⇒ giờ:
        <span class="font-semibold text-emerald-700">
          {{ presentationHours }}
        </span>
        giờ.
      </p>
      <p class="mb-1">
        Số lần <span class="font-semibold">tham dự</span>:
        <span class="font-semibold text-slate-800">
          {{ attendanceCount }}
        </span>
        (được tính tối đa
        <span class="font-semibold">{{ validAttendanceCount }}</span>
        lần) ⇒ giờ:
        <span class="font-semibold text-emerald-700">
          {{ attendanceHours }}
        </span>
        giờ.
      </p>
      <p>
        <span class="font-semibold">Tổng cộng:</span>
        <span class="font-semibold text-emerald-700">
          {{ totalHours }}
        </span>
        giờ NCKH (năm học {{ form.academicYear }}, {{ semesterLabel }}).
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
import type {
  ConferenceHoursFormModel,
  ConferenceEntry,
  WorkEvidenceFile,
} from "@/features/declarations/types";

const emit = defineEmits<{
  (
    e: "submit",
    payload: {
      form: ConferenceHoursFormModel;
      entries: ConferenceEntry[];
      presentationsCount: number;
      attendanceCount: number;
      presentationHours: number;
      attendanceHours: number;
      totalHours: number;
    }
  ): void;
}>();

const form = reactive<ConferenceHoursFormModel>({
  academicYear: "2024-2025",
  semester: "ALL",
  note: "",
});

const entries = ref<ConferenceEntry[]>([]);
const nextId = ref(1);

const maxAttendanceTimes = 40;

// đếm số lần theo role
const presentationsCount = computed(
  () => entries.value.filter((e) => e.role === "PRESENTATION").length
);
const attendanceCount = computed(
  () => entries.value.filter((e) => e.role === "ATTENDANCE").length
);

// attendance được tính tối đa 40 lần
const validAttendanceCount = computed(() =>
  Math.min(attendanceCount.value, maxAttendanceTimes)
);

// giờ từ báo cáo
const presentationHours = computed(
  () => presentationsCount.value * 40 // 40 giờ / lần báo cáo
);

// giờ từ tham dự
const attendanceHours = computed(
  () => validAttendanceCount.value * 4 // 4 giờ / lần, tối đa 40 lần
);

const totalHours = computed(
  () => presentationHours.value + attendanceHours.value
);

const canSubmit = computed(
  () => entries.value.length > 0 && totalHours.value >= 0
);

const semesterLabel = computed(() => {
  switch (form.semester) {
    case "HK1":
      return "Học kỳ 1";
    case "HK2":
      return "Học kỳ 2";
    default:
      return "Cả năm";
  }
});

// giờ cho từng dòng
function entryHours(entry: ConferenceEntry): number {
  if (entry.role === "PRESENTATION") {
    return 40;
  }

  // ATTENDANCE: chỉ tính nếu dòng này nằm trong 40 lần attendance đầu tiên
  const attendanceEntries = entries.value.filter(
    (e) => e.role === "ATTENDANCE"
  );
  const counted = attendanceEntries.slice(0, maxAttendanceTimes);
  const isCounted = counted.some((e) => e.id === entry.id);

  return isCounted ? 4 : 0;
}

function addEntry() {
  entries.value.push({
    id: nextId.value++,
    name: "",
    date: "",
    location: "",
    role: "ATTENDANCE",
    evidences: [],
  });
}

function removeEntry(id: number) {
  entries.value = entries.value.filter((e) => e.id !== id);
}

function onFilesSelected(event: Event, entry: ConferenceEntry) {
  const input = event.target as HTMLInputElement;
  if (!input.files?.length) return;

  const selected: WorkEvidenceFile[] = Array.from(input.files).map((f) => ({
    id: crypto.randomUUID(),
    name: f.name,
    size: f.size,
    type: f.type,
    file: f,
  }));

  entry.evidences = [...entry.evidences, ...selected];

  // clear để chọn lại cùng tên file vẫn được nhận
  input.value = "";
}

function removeEvidence(entry: ConferenceEntry, fileId: string) {
  entry.evidences = entry.evidences.filter((f) => f.id !== fileId);
}

function onSubmit() {
  if (!canSubmit.value) return;

  emit("submit", {
    form: { ...form },
    entries: entries.value.map((e) => ({ ...e })),
    presentationsCount: presentationsCount.value,
    attendanceCount: attendanceCount.value,
    presentationHours: presentationHours.value,
    attendanceHours: attendanceHours.value,
    totalHours: totalHours.value,
  });
}
</script>
