<!-- src/features/hours/pages/HoursBatchDetailView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-slate-800">
          Chi tiết đợt tính giờ NCKH
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Xem danh sách các công trình đã được chọn tính giờ trong đợt
          <span class="font-semibold text-slate-800">{{ batchCode }}</span
          >.
        </p>
      </div>

      <RouterLink
        :to="{ name: 'hours.my' }"
        class="inline-flex items-center rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
      >
        ← Quay về tổng quan giờ NCKH
      </RouterLink>
    </div>

    <!-- Thông tin chung -->
    <div
      class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm md:grid-cols-3"
    >
      <div>
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Mã đợt tính giờ
        </p>
        <p class="mt-1 text-sm font-semibold text-slate-800">
          {{ batchCode }}
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Năm học: {{ meta.academicYear }} · {{ meta.semesterLabel }}
        </p>
      </div>

      <div>
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Trạng thái
        </p>
        <p class="mt-1">
          <span
            class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
            :class="statusClass(meta.status)"
          >
            {{ statusLabel(meta.status) }}
          </span>
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Ngày yêu cầu: {{ meta.requestedAt }}
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Ngày duyệt: {{ meta.approvedAt || "—" }}
        </p>
      </div>

      <div>
        <p class="text-[11px] uppercase tracking-wide text-slate-400">
          Thống kê giờ
        </p>
        <p class="mt-1 text-sm font-semibold text-emerald-700">
          Tổng giờ: {{ totalHours }} giờ
        </p>
        <p class="mt-0.5 text-[11px] text-slate-500">
          Từ {{ items.length }} công trình được tính.
        </p>
      </div>
    </div>

    <!-- Bảng chi tiết + nút xuất PDF -->
    <HoursBatchDetailTable :batch-code="batchCode" :items="items" />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import HoursBatchDetailTable from "@/features/hours/components/HoursBatchDetailTable.vue";

type BatchStatus = "DRAFT" | "WAITING_APPROVAL" | "APPROVED" | "REJECTED";

interface BatchMeta {
  academicYear: string;
  semester: "ALL" | "HK1" | "HK2";
  semesterLabel: string;
  status: BatchStatus;
  requestedAt: string;
  approvedAt?: string | null;
}

interface BatchDetailRow {
  id: string;
  title: string;
  workTypeLabel: string;
  roleLabel: string;
  coefficient: number;
  hours: number;
  note?: string;
}

const route = useRoute();

// batchId lấy từ param hoặc query
const batchCode = computed(() =>
  String(route.params.batchId ?? route.query.batchId ?? "UNKNOWN")
);

// mock meta theo batchCode (sau này thay bằng API)
const meta = computed<BatchMeta>(() => ({
  academicYear: "2024-2025",
  semester: "HK2",
  semesterLabel: "Học kỳ 2",
  status: "WAITING_APPROVAL",
  requestedAt: "01/06/2025 14:35",
  approvedAt: null,
}));

// mock các công trình trong đợt này
const items = computed<BatchDetailRow[]>(() => [
  {
    id: "CT-001",
    title: "Bài báo về trí tuệ nhân tạo trong dạy học trực tuyến",
    workTypeLabel: "Bài báo khoa học",
    roleLabel: "Tác giả chính",
    coefficient: 1.0,
    hours: 40,
    note: "Tạp chí quốc tế, Q2",
  },
  {
    id: "CT-002",
    title: "Đề tài cấp trường: Phân tích dữ liệu học tập",
    workTypeLabel: "Đề tài NCKH",
    roleLabel: "Chủ nhiệm đề tài",
    coefficient: 1.2,
    hours: 60,
    note: "Đã nghiệm thu loại Khá",
  },
  {
    id: "CT-003",
    title: "Giáo trình Lập trình Web với Vue.js",
    workTypeLabel: "Sách / Giáo trình",
    roleLabel: "Đồng chủ biên",
    coefficient: 0.8,
    hours: 50,
    note: "Nhà xuất bản ĐHQG",
  },
]);

const totalHours = computed(() =>
  items.value.reduce((sum, row) => sum + row.hours, 0)
);

function statusLabel(status: BatchStatus): string {
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

function statusClass(status: BatchStatus): string {
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
