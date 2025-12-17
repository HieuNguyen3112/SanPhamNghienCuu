<!-- src/features/hours/pages/HoursApprovalsView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">Duyệt giờ NCKH</h1>
      <p class="mt-1 text-sm text-slate-500">
        Xem các yêu cầu tính giờ NCKH do giảng viên gửi lên, kiểm tra chi tiết
        công trình và thực hiện duyệt / từ chối.
      </p>
    </div>

    <!-- Bộ lọc -->
    <div
      class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-end lg:justify-between"
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
            <option value="ALL">Tất cả</option>
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

        <div class="w-52">
          <label class="block text-xs font-medium text-slate-600">
            Bộ môn
          </label>
          <select
            v-model="department"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả bộ môn</option>
            <option value="CNTT">Công nghệ thông tin</option>
            <option value="TOAN">Toán</option>
          </select>
        </div>

        <div class="w-40">
          <label class="block text-xs font-medium text-slate-600">
            Trạng thái
          </label>
          <select
            v-model="statusFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="PENDING">Đang chờ duyệt</option>
            <option value="APPROVED">Đã duyệt</option>
            <option value="REJECTED">Từ chối</option>
            <option value="ALL">Tất cả</option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap items-end gap-2">
        <input
          v-model="search"
          type="text"
          placeholder="Tìm theo tên GV, mã GV..."
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 lg:w-64"
          @keyup.enter="reload"
        />

        <button
          type="button"
          class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
          @click="reload"
        >
          Lọc
        </button>
      </div>
    </div>

    <!-- Tóm tắt -->
    <div
      class="rounded-xl border border-slate-200 bg-white p-4 text-xs text-slate-600 shadow-sm"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p>
            Có
            <span class="font-semibold text-slate-800">
              {{ pendingCount }}
            </span>
            yêu cầu đang ở trạng thái
            <span class="font-semibold text-amber-700">chờ duyệt</span>.
          </p>
          <p class="mt-0.5">
            Tổng giờ NCKH đề nghị duyệt (theo bộ lọc hiện tại):
            <span class="font-semibold text-emerald-700">
              {{ totalHoursOfFiltered }} giờ
            </span>
          </p>
        </div>
      </div>
    </div>

    <!-- Bảng yêu cầu duyệt giờ -->
    <div
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead
          class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          <tr>
            <th class="px-3 py-2 text-left">Giảng viên</th>
            <th class="px-3 py-2 text-left">Đơn vị</th>
            <th class="px-3 py-2 text-left">Năm học / Học kỳ</th>
            <th class="px-3 py-2 text-right">Giờ đề nghị</th>
            <th class="px-3 py-2 text-center">Số công trình</th>
            <th class="px-3 py-2 text-left">Trạng thái</th>
            <th class="px-3 py-2 text-right">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td colspan="7" class="px-4 py-6 text-center text-slate-500">
              Đang tải dữ liệu...
            </td>
          </tr>

          <tr v-else-if="!filteredRequests.length">
            <td colspan="7" class="px-4 py-6 text-center text-slate-500">
              Không có yêu cầu nào phù hợp với bộ lọc hiện tại.
            </td>
          </tr>

          <tr
            v-for="req in filteredRequests"
            :key="req.id"
            class="align-top hover:bg-slate-50"
          >
            <td class="px-3 py-3 text-sm text-slate-800">
              {{ req.lecturerName }}
              <p class="text-xs text-slate-400">Mã GV: {{ req.lecturerId }}</p>
            </td>
            <td class="px-3 py-3 text-sm text-slate-700">
              {{ req.departmentName }}
            </td>
            <td class="px-3 py-3 text-xs text-slate-700">
              <p>{{ req.academicYear }}</p>
              <p class="text-[11px] text-slate-500">
                {{ semesterLabel(req.semester) }}
              </p>
            </td>
            <td
              class="px-3 py-3 text-right text-sm font-semibold text-emerald-700"
            >
              {{ req.totalHours }}
            </td>
            <td class="px-3 py-3 text-center text-sm text-slate-700">
              {{ req.works.length }}
            </td>
            <td class="px-3 py-3 text-sm">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                :class="statusClass(req.status)"
              >
                {{ statusLabel(req.status) }}
              </span>
            </td>
            <td class="px-3 py-3 text-right text-xs">
              <button
                type="button"
                class="mr-2 inline-flex items-center rounded-md border border-slate-300 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
                @click="openDetail(req)"
              >
                Xem chi tiết
              </button>
              <button
                type="button"
                class="mr-1 rounded-md border border-emerald-500 px-2 py-1 text-[11px] font-medium text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="req.status !== 'PENDING'"
                @click="updateStatus(req, 'APPROVED')"
              >
                Duyệt
              </button>
              <button
                type="button"
                class="rounded-md border border-red-500 px-2 py-1 text-[11px] font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="req.status !== 'PENDING'"
                @click="updateStatus(req, 'REJECTED')"
              >
                Từ chối
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="text-[11px] text-slate-400">
      Lưu ý: Màn hình này chỉ quyết định việc duyệt giờ NCKH. Việc duyệt/chấp
      nhận công trình khoa học (bài báo, đề tài, sách...) có thể có quy trình
      riêng ở module quản lý công trình.
    </p>

    <!-- Modal chi tiết -->
    <HoursApprovalDetailModal
      v-if="detailRequest"
      :request="detailRequest"
      @close="closeDetail"
      @confirm="onModalConfirm"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import HoursApprovalDetailModal, {
  type HoursApprovalRequest,
} from "@/features/hours/components/HoursApprovalDetailModal.vue";

type HoursApprovalStatus = "PENDING" | "APPROVED" | "REJECTED";
type Semester = "ALL" | "HK1" | "HK2";

const academicYear = ref<"ALL" | "2024-2025" | "2023-2024">("ALL");
const semester = ref<Semester>("ALL");
const department = ref<"ALL" | "CNTT" | "TOAN">("ALL");
const statusFilter = ref<HoursApprovalStatus | "ALL">("PENDING");
const search = ref("");
const loading = ref(false);

// MOCK DATA: mỗi phần tử là 1 yêu cầu tính giờ của 1 GV cho 1 năm học/học kỳ
const requests = ref<HoursApprovalRequest[]>([
  {
    id: "REQ-2024-001",
    lecturerId: "GV001",
    lecturerName: "Nguyễn Văn A",
    departmentName: "Bộ môn Công nghệ thông tin",
    academicYear: "2024-2025",
    semester: "ALL",
    totalHours: 150,
    status: "PENDING",
    note: "Đợt tính giờ NCKH cho năm 2024-2025.",
    works: [
      {
        id: "CT-001",
        title: "Bài báo về trí tuệ nhân tạo trong dạy học trực tuyến",
        workTypeLabel: "Bài báo khoa học",
        roleLabel: "Tác giả chính",
        coefficient: 1.0,
        hours: 40,
        note: "Q2, Scopus",
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
    ],
  },
  {
    id: "REQ-2024-002",
    lecturerId: "GV002",
    lecturerName: "Trần Thị B",
    departmentName: "Bộ môn Toán",
    academicYear: "2024-2025",
    semester: "HK1",
    totalHours: 80,
    status: "APPROVED",
    works: [
      {
        id: "CT-010",
        title: "Bài báo về tối ưu hóa trong kinh tế",
        workTypeLabel: "Bài báo khoa học",
        roleLabel: "Đồng tác giả",
        coefficient: 0.7,
        hours: 30,
      },
      {
        id: "CT-011",
        title: "Giáo trình Xác suất Thống kê cho kỹ sư",
        workTypeLabel: "Sách / Giáo trình",
        roleLabel: "Chủ biên",
        coefficient: 1.0,
        hours: 50,
      },
    ],
  },
]);

const filteredRequests = computed(() => {
  let data = requests.value;

  if (academicYear.value !== "ALL") {
    data = data.filter((r) => r.academicYear === academicYear.value);
  }

  if (semester.value !== "ALL") {
    data = data.filter((r) => r.semester === semester.value);
  }

  if (department.value === "CNTT") {
    data = data.filter((r) =>
      r.departmentName.toLowerCase().includes("công nghệ thông tin")
    );
  } else if (department.value === "TOAN") {
    data = data.filter((r) => r.departmentName.toLowerCase().includes("toán"));
  }

  if (statusFilter.value !== "ALL") {
    data = data.filter((r) => r.status === statusFilter.value);
  }

  if (search.value.trim()) {
    const q = search.value.toLowerCase();
    data = data.filter(
      (r) =>
        r.lecturerName.toLowerCase().includes(q) ||
        r.lecturerId.toLowerCase().includes(q)
    );
  }

  return data;
});

const pendingCount = computed(
  () => requests.value.filter((r) => r.status === "PENDING").length
);

const totalHoursOfFiltered = computed(() =>
  filteredRequests.value.reduce((sum, r) => sum + r.totalHours, 0)
);

// Modal state
const detailRequest = ref<HoursApprovalRequest | null>(null);

function openDetail(req: HoursApprovalRequest) {
  detailRequest.value = { ...req };
}

function closeDetail() {
  detailRequest.value = null;
}

function semesterLabel(s: Semester): string {
  switch (s) {
    case "HK1":
      return "Học kỳ 1";
    case "HK2":
      return "Học kỳ 2";
    case "ALL":
    default:
      return "Cả năm";
  }
}

function statusLabel(status: HoursApprovalStatus): string {
  switch (status) {
    case "PENDING":
      return "Chờ duyệt";
    case "APPROVED":
      return "Đã duyệt";
    case "REJECTED":
      return "Từ chối";
    default:
      return status;
  }
}

function statusClass(status: HoursApprovalStatus): string {
  switch (status) {
    case "PENDING":
      return "bg-amber-50 text-amber-700 border border-amber-100";
    case "APPROVED":
      return "bg-emerald-50 text-emerald-700 border border-emerald-100";
    case "REJECTED":
      return "bg-red-50 text-red-700 border border-red-100";
    default:
      return "bg-slate-50 text-slate-600 border border-slate-100";
  }
}

function reload() {
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
  }, 300);
}

function updateStatus(
  req: HoursApprovalRequest,
  newStatus: HoursApprovalStatus
) {
  // TODO: nối API thật ở đây
  requests.value = requests.value.map((r) =>
    r.id === req.id ? { ...r, status: newStatus } : r
  );

  if (detailRequest.value?.id === req.id) {
    detailRequest.value = { ...detailRequest.value, status: newStatus };
  }
}

function onModalConfirm(newStatus: HoursApprovalStatus) {
  if (!detailRequest.value) return;
  updateStatus(detailRequest.value, newStatus);
  closeDetail();
}
</script>
