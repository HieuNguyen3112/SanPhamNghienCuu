<!-- src/features/hours/pages/HoursCalculateView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">Tính giờ NCKH</h1>
      <p class="mt-1 text-sm text-slate-500">
        Sau khi hoàn tất kê khai công trình khoa học, bạn có thể dùng chức năng
        này để hệ thống tự động tính giờ NCKH theo quy định, xem lại kết quả và
        gửi yêu cầu duyệt.
      </p>
    </div>

    <!-- Step hướng dẫn -->
    <div
      class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm md:grid-cols-3"
    >
      <div>
        <p
          class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
        >
          Bước 1
        </p>
        <p class="mt-1 font-medium text-slate-800">
          Hoàn tất kê khai công trình
        </p>
        <p class="mt-1">
          Đảm bảo bạn đã kê khai đầy đủ các bài báo, đề tài, sách/giáo trình và
          công trình khác trong năm học hiện tại.
        </p>
        <RouterLink
          :to="{ name: 'works-my-declarations' }"
          class="mt-2 inline-flex text-[11px] font-medium text-sky-700 hover:underline"
        >
          → Xem công trình đã kê khai
        </RouterLink>
      </div>

      <div>
        <p
          class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
        >
          Bước 2
        </p>
        <p class="mt-1 font-medium text-slate-800">Bấm "Tính giờ NCKH"</p>
        <p class="mt-1">
          Hệ thống sẽ quét các công trình đủ điều kiện, áp dụng quy tắc tính
          điểm/giờ và sinh ra bảng kết quả tạm tính.
        </p>
      </div>

      <div>
        <p
          class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
        >
          Bước 3
        </p>
        <p class="mt-1 font-medium text-slate-800">
          Kiểm tra, chọn công trình cần tính và gửi duyệt
        </p>
        <p class="mt-1">
          Bạn có thể bỏ chọn những công trình chưa muốn tính vào giờ NCKH trong
          đợt này, sau đó gửi yêu cầu duyệt.
        </p>
      </div>
    </div>

    <!-- Bộ lọc & nút tính -->
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

      <div class="flex flex-wrap items-end gap-2">
        <p class="text-[11px] text-slate-500">
          Trạng thái hiện tại:
          <span
            class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700"
          >
            {{ statusLabel }}
          </span>
        </p>

        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="calculating"
          @click="calculate"
        >
          <span
            v-if="calculating"
            class="mr-2 h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"
          />
          Tính giờ NCKH
        </button>

        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="!canSubmit"
          @click="submitForApproval"
        >
          Gửi yêu cầu duyệt
        </button>
      </div>
    </div>

    <!-- Tóm tắt kết quả -->
    <div
      class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm md:grid-cols-3"
    >
      <div>
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Định mức năm học {{ academicYear }}
        </p>
        <p class="mt-1 text-lg font-semibold text-slate-800">
          {{ requiredHours }} giờ
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          (bao gồm tất cả công trình đủ điều kiện trong năm học này)
        </p>
      </div>

      <div>
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Giờ NCKH tạm tính
        </p>
        <p class="mt-1 text-lg font-semibold text-emerald-700">
          {{ calculatedHours }} giờ
        </p>
        <p class="mt-0.5 text-[11px] text-emerald-700">
          Tính từ {{ worksCount }} công trình đang được chọn
        </p>
      </div>

      <div>
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Chênh lệch so với định mức
        </p>
        <p class="mt-1 text-lg font-semibold" :class="gapClass">
          {{ gapText }}
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Kết quả này chỉ có hiệu lực khi được đơn vị quản lý duyệt.
        </p>
      </div>
    </div>

    <!-- Bảng chi tiết công trình & giờ quy đổi -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div
        class="mb-2 flex items-center justify-between text-[11px] text-slate-500"
      >
        <p>
          Tick vào cột <span class="font-semibold">Tính giờ</span> để chọn các
          công trình muốn tính vào giờ NCKH trong đợt này.
        </p>
        <button
          v-if="rows.length"
          type="button"
          class="rounded-md border border-slate-300 px-2 py-1 text-[11px] hover:bg-slate-50"
          @click="toggleSelectAllRows"
        >
          {{ allSelected ? "Bỏ chọn tất cả" : "Chọn tất cả" }}
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead
            class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500"
          >
            <tr>
              <th class="px-3 py-2 text-center">Tính giờ</th>
              <th class="px-3 py-2">Công trình</th>
              <th class="px-3 py-2">Loại</th>
              <th class="px-3 py-2">Vai trò</th>
              <th class="px-3 py-2 text-right">Hệ số</th>
              <th class="px-3 py-2 text-right">Giờ quy đổi</th>
              <th class="px-3 py-2">Ghi chú</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="!rows.length">
              <td
                colspan="7"
                class="px-3 py-4 text-center text-sm text-slate-500"
              >
                Chưa có dữ liệu tạm tính. Hãy bấm "Tính giờ NCKH" để hệ thống
                sinh kết quả.
              </td>
            </tr>
            <tr v-for="row in rows" :key="row.id">
              <td class="px-3 py-2 align-top text-center">
                <input v-model="row.included" type="checkbox" class="h-4 w-4" />
              </td>
              <td class="px-3 py-2 align-top">
                <p class="text-sm font-medium text-slate-800">
                  {{ row.title }}
                </p>
                <p class="text-xs text-slate-400">Mã: {{ row.id }}</p>
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ row.workTypeLabel }}
              </td>
              <td class="px-3 py-2 align-top text-sm text-slate-700">
                {{ row.roleLabel }}
              </td>
              <td class="px-3 py-2 align-top text-right text-sm text-slate-700">
                {{ row.coefficient }}
              </td>
              <td
                class="px-3 py-2 align-top text-right text-sm font-semibold"
                :class="
                  row.included
                    ? 'text-emerald-700'
                    : 'text-slate-400 line-through'
                "
              >
                {{ row.hours }}
              </td>
              <td class="px-3 py-2 align-top text-xs text-slate-600">
                {{ row.note || "—" }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="lastCalculatedAt" class="mt-3 text-[11px] text-slate-400">
        Lần gần nhất tính giờ NCKH: {{ lastCalculatedAt }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

type CalcStatus = "NOT_CALCULATED" | "DRAFT" | "WAITING_APPROVAL" | "APPROVED";

interface CalculatedRow {
  id: string;
  title: string;
  workTypeLabel: string;
  roleLabel: string;
  coefficient: number;
  hours: number;
  note?: string;
  included: boolean; // 👈 có tính vào giờ NCKH hay không
}

const academicYear = ref("2024-2025");
const semester = ref<"ALL" | "HK1" | "HK2">("ALL");

const calculating = ref(false);
const status = ref<CalcStatus>("NOT_CALCULATED");
const lastCalculatedAt = ref<string | null>(null);

// mock dữ liệu
const requiredHours = ref(150);
const rows = ref<CalculatedRow[]>([]);

// các dòng được chọn để tính
const effectiveRows = computed(() => rows.value.filter((r) => r.included));

const worksCount = computed(() => effectiveRows.value.length);

const calculatedHours = computed(() =>
  effectiveRows.value.reduce((sum, row) => sum + row.hours, 0)
);

const statusLabel = computed(() => {
  switch (status.value) {
    case "NOT_CALCULATED":
      return "Chưa tính";
    case "DRAFT":
      return "Bản nháp (chưa gửi duyệt)";
    case "WAITING_APPROVAL":
      return "Đang chờ duyệt";
    case "APPROVED":
      return "Đã được duyệt";
    default:
      return status.value;
  }
});

const gap = computed(() => calculatedHours.value - requiredHours.value);

const gapText = computed(() => {
  if (status.value === "NOT_CALCULATED") return "Chưa có kết quả";
  if (gap.value === 0) return "Đúng bằng định mức";
  if (gap.value > 0) return `Vượt định mức ${gap.value} giờ`;
  return `Thiếu ${Math.abs(gap.value)} giờ so với định mức`;
});

const gapClass = computed(() => {
  if (status.value === "NOT_CALCULATED") return "text-slate-500";
  if (gap.value >= 0) return "text-emerald-700";
  return "text-red-600";
});

const canSubmit = computed(
  () =>
    status.value === "DRAFT" && calculatedHours.value > 0 && !calculating.value
);

const allSelected = computed(
  () => rows.value.length > 0 && rows.value.every((r) => r.included)
);

function toggleSelectAllRows() {
  const target = !allSelected.value;
  rows.value = rows.value.map((r) => ({ ...r, included: target }));
}

function calculate() {
  calculating.value = true;

  // Giả lập tính toán: trong thực tế, gọi API hoặc dùng logic/calculators.ts
  setTimeout(() => {
    rows.value = [
      {
        id: "CT-001",
        title: "Bài báo về trí tuệ nhân tạo trong dạy học trực tuyến",
        workTypeLabel: "Bài báo khoa học",
        roleLabel: "Tác giả chính",
        coefficient: 1.0,
        hours: 40,
        note: "Tạp chí quốc tế, Q2",
        included: true,
      },
      {
        id: "CT-002",
        title: "Đề tài cấp trường: Phân tích dữ liệu học tập",
        workTypeLabel: "Đề tài NCKH",
        roleLabel: "Chủ nhiệm đề tài",
        coefficient: 1.2,
        hours: 60,
        note: "Đã nghiệm thu loại Khá",
        included: true,
      },
      {
        id: "CT-003",
        title: "Giáo trình Lập trình Web với Vue.js",
        workTypeLabel: "Sách / Giáo trình",
        roleLabel: "Đồng chủ biên",
        coefficient: 0.8,
        hours: 50,
        note: "Nhà xuất bản ĐHQG",
        included: true,
      },
    ];

    status.value = "DRAFT";
    lastCalculatedAt.value = new Date().toLocaleString();
    calculating.value = false;
  }, 600);
}

function submitForApproval() {
  if (!canSubmit.value) return;
  status.value = "WAITING_APPROVAL";
}
</script>
