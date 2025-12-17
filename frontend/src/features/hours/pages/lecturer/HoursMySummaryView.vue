<!-- src/features/hours/pages/HoursMySummaryView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">Giờ NCKH của tôi</h1>
      <p class="mt-1 text-sm text-slate-500">
        Tổng hợp giờ nghiên cứu khoa học đã và đang thực hiện theo năm học / học
        kỳ.
      </p>
    </div>

    <!-- Bộ lọc năm học / học kỳ -->
    <div
      class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-end md:justify-between"
    >
      <div class="flex flex-wrap gap-3">
        <div class="w-40">
          <label class="block text-xs font-medium text-slate-600">
            Năm học
          </label>
          <select
            v-model="academicYear"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="2024-2025">2024–2025</option>
            <option value="2023-2024">2023–2024</option>
            <option value="2022-2023">2022–2023</option>
          </select>
        </div>

        <div class="w-32">
          <label class="block text-xs font-medium text-slate-600">
            Học kỳ
          </label>
          <select
            v-model="semester"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Cả năm</option>
            <option value="HK1">Học kỳ 1</option>
            <option value="HK2">Học kỳ 2</option>
          </select>
        </div>
      </div>

      <button
        type="button"
        class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
      >
        Làm mới dữ liệu
      </button>
    </div>

    <!-- Tổng quan giờ NCKH -->
    <div class="grid gap-4 md:grid-cols-3">
      <!-- Tổng hợp -->
      <div
        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:col-span-2"
      >
        <h2 class="mb-3 text-sm font-semibold text-slate-700">
          Tổng quan giờ NCKH
        </h2>

        <HoursProgressBar
          :current="completedHours"
          :target="requiredHours"
          label="Giờ đã được tính"
          :helper-text="`Trong đó có ${pendingHours} giờ đang chờ duyệt`"
        />

        <div class="mt-4 grid grid-cols-3 gap-3 text-xs text-slate-600">
          <div class="rounded-lg bg-slate-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-slate-400">
              Định mức
            </p>
            <p class="mt-1 text-lg font-semibold text-slate-800">
              {{ requiredHours }}
            </p>
            <p class="mt-0.5 text-[11px] text-slate-500">giờ/năm học</p>
          </div>
          <div class="rounded-lg bg-emerald-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-emerald-600">
              Đã được tính
            </p>
            <p class="mt-1 text-lg font-semibold text-emerald-700">
              {{ completedHours }}
            </p>
            <p class="mt-0.5 text-[11px] text-emerald-600">
              bao gồm giờ đã hoàn thành
            </p>
          </div>
          <div class="rounded-lg bg-amber-50 p-3">
            <p class="text-[11px] uppercase tracking-wide text-amber-700">
              Chờ duyệt
            </p>
            <p class="mt-1 text-lg font-semibold text-amber-700">
              {{ pendingHours }}
            </p>
            <p class="mt-0.5 text-[11px] text-amber-700">
              giờ từ các công trình đang chờ duyệt
            </p>
          </div>
        </div>
      </div>

      <!-- Ghi chú / quy định -->
      <div
        class="rounded-xl border border-slate-200 bg-white p-4 text-xs leading-relaxed text-slate-600 shadow-sm"
      >
        <h2 class="mb-2 text-sm font-semibold text-slate-700">Ghi chú</h2>
        <ul class="list-inside list-disc space-y-1.5">
          <li>
            Giờ NCKH chỉ được tính khi công trình đã được duyệt bởi đơn vị quản
            lý.
          </li>
          <li>
            Các công trình đang chờ duyệt sẽ xuất hiện trong phần
            <span class="font-medium text-amber-700"> giờ chờ duyệt</span>.
          </li>
          <li>
            Nếu số liệu chưa chính xác, vui lòng kiểm tra lại kê khai hoặc liên
            hệ phòng QLKH.
          </li>
        </ul>
      </div>
    </div>

    <!-- Bảng phân bổ theo loại công trình (mock) -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h2 class="mb-3 text-sm font-semibold text-slate-700">
        Phân bổ giờ theo loại công trình
      </h2>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead
            class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500"
          >
            <tr>
              <th class="px-3 py-2">Loại công trình</th>
              <th class="px-3 py-2">Giờ đã tính</th>
              <th class="px-3 py-2">Giờ chờ duyệt</th>
              <th class="px-3 py-2">Tổng</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="row in breakdown" :key="row.type">
              <td class="px-3 py-2 text-slate-700">
                {{ row.label }}
              </td>
              <td class="px-3 py-2 text-emerald-700">
                {{ row.completed }}
              </td>
              <td class="px-3 py-2 text-amber-700">
                {{ row.pending }}
              </td>
              <td class="px-3 py-2 text-slate-800">
                {{ row.completed + row.pending }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 🔔 Danh sách các đợt tính giờ / yêu cầu duyệt -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold text-slate-700">
          Các đợt tính giờ NCKH & yêu cầu duyệt
        </h2>

        <p class="text-[11px] text-slate-500">
          Bạn có
          <span class="font-semibold text-amber-700">
            {{ waitingApprovalCount }}
          </span>
          đợt đang ở trạng thái
          <span class="font-semibold text-amber-700">chờ duyệt</span>.
        </p>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead
            class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500"
          >
            <tr>
              <th class="px-3 py-2">Đợt tính giờ</th>
              <th class="px-3 py-2">Trạng thái</th>
              <th class="px-3 py-2">Ngày yêu cầu</th>
              <th class="px-3 py-2">Ngày duyệt</th>
              <th class="px-3 py-2 text-right">Tổng giờ</th>
              <th class="px-3 py-2 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!batchesForView.length">
              <td
                colspan="6"
                class="px-3 py-4 text-center text-sm text-slate-500"
              >
                Chưa có đợt tính giờ nào cho năm học / học kỳ hiện tại.
              </td>
            </tr>

            <tr v-for="batch in batchesForView" :key="batch.id">
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                <p class="font-medium text-slate-800">
                  Năm học {{ batch.academicYear }}
                </p>
                <p class="text-xs text-slate-500">
                  {{ batch.semesterLabel }}
                </p>
              </td>
              <td class="px-3 py-2 align-top text-sm">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                  :class="batchStatusClass(batch.status)"
                >
                  {{ batchStatusLabel(batch.status) }}
                </span>
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ batch.requestedAt }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ batch.approvedAt || "—" }}
              </td>
              <td
                class="px-3 py-2 align-top text-right text-sm font-semibold text-emerald-700"
              >
                {{ batch.totalHours }}
              </td>
              <td class="px-3 py-2 align-top text-right text-xs">
                <RouterLink
                  :to="{
                    name: 'hours.batchDetail',
                    params: { batchId: batch.id },
                  }"
                  class="rounded-md border border-slate-300 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
                >
                  Xem chi tiết
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p class="mt-3 text-[11px] text-slate-400">
        Gợi ý: Sau khi được duyệt, kết quả giờ NCKH tương ứng sẽ được cộng vào
        phần <span class="font-semibold">Giờ đã được tính</span> ở trên.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import HoursProgressBar from "@/features/hours/components/HoursProgressBar.vue";

const academicYear = ref("2024-2025");
const semester = ref<"ALL" | "HK1" | "HK2">("ALL");

// mock số liệu, sau này thay bằng API / logic thật
const requiredHours = ref(150);
const completedHours = ref(96);
const pendingHours = ref(24);

const breakdown = computed(() => [
  {
    type: "ARTICLE",
    label: "Bài báo khoa học",
    completed: 40,
    pending: 8,
  },
  {
    type: "PROJECT",
    label: "Đề tài NCKH",
    completed: 36,
    pending: 10,
  },
  {
    type: "BOOK",
    label: "Sách / Giáo trình",
    completed: 20,
    pending: 6,
  },
]);

// ==== ĐỢT TÍNH GIỜ / YÊU CẦU DUYỆT (mock) ====

type BatchStatus = "DRAFT" | "WAITING_APPROVAL" | "APPROVED" | "REJECTED";

interface HourBatchSummary {
  id: string;
  academicYear: string;
  semester: "ALL" | "HK1" | "HK2";
  status: BatchStatus;
  requestedAt: string; // ví dụ: "01/06/2025 14:35"
  approvedAt?: string | null;
  totalHours: number;
}

const allBatches = ref<HourBatchSummary[]>([
  {
    id: "BATCH-2024-HK2-01",
    academicYear: "2024-2025",
    semester: "HK2",
    status: "WAITING_APPROVAL",
    requestedAt: "01/06/2025 14:35",
    approvedAt: null,
    totalHours: 145,
  },
  {
    id: "BATCH-2024-HK1-01",
    academicYear: "2024-2025",
    semester: "HK1",
    status: "APPROVED",
    requestedAt: "10/01/2025 09:10",
    approvedAt: "20/01/2025 16:20",
    totalHours: 75,
  },
  {
    id: "BATCH-2023-ALL-01",
    academicYear: "2023-2024",
    semester: "ALL",
    status: "APPROVED",
    requestedAt: "05/06/2024 08:00",
    approvedAt: "15/06/2024 10:15",
    totalHours: 150,
  },
]);

const batchesForView = computed(() => {
  return allBatches.value
    .filter((b) => b.academicYear === academicYear.value)
    .filter((b) => {
      if (semester.value === "ALL") return true;
      return b.semester === semester.value;
    })
    .map((b) => ({
      ...b,
      semesterLabel:
        b.semester === "ALL"
          ? "Cả năm"
          : b.semester === "HK1"
          ? "Học kỳ 1"
          : "Học kỳ 2",
    }));
});

const waitingApprovalCount = computed(
  () =>
    batchesForView.value.filter((b) => b.status === "WAITING_APPROVAL").length
);

function batchStatusLabel(status: BatchStatus): string {
  switch (status) {
    case "DRAFT":
      return "Bản nháp";
    case "WAITING_APPROVAL":
      return "Chờ duyệt";
    case "APPROVED":
      return "Đã duyệt";
    case "REJECTED":
      return "Từ chối";
    default:
      return status;
  }
}

function batchStatusClass(status: BatchStatus): string {
  switch (status) {
    case "DRAFT":
      return "bg-slate-50 text-slate-700 border border-slate-200";
    case "WAITING_APPROVAL":
      return "bg-amber-50 text-amber-700 border border-amber-200";
    case "APPROVED":
      return "bg-emerald-50 text-emerald-700 border border-emerald-200";
    case "REJECTED":
      return "bg-red-50 text-red-700 border border-red-200";
    default:
      return "bg-slate-50 text-slate-600 border border-slate-200";
  }
}
</script>
