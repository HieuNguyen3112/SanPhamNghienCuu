<!-- src/features/declarations/pages/WorksApprovalsView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Duyệt công trình NCKH
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Xem danh sách công trình khoa học do giảng viên kê khai và thực hiện
        duyệt / từ chối để đưa vào hồ sơ khoa học của giảng viên.
      </p>
    </div>

    <!-- Bộ lọc -->
    <div
      class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:items-end lg:justify-between"
    >
      <div class="flex flex-wrap gap-3">
        <div class="w-40">
          <label class="block text-xs font-medium text-slate-600"> Năm </label>
          <select
            v-model="yearFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option v-for="y in yearOptions" :key="y" :value="String(y)">
              {{ y }}
            </option>
          </select>
        </div>

        <div class="w-40">
          <label class="block text-xs font-medium text-slate-600">
            Loại công trình
          </label>
          <select
            v-model="workTypeFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="ARTICLE">Bài báo</option>
            <option value="PROJECT">Đề tài</option>
            <option value="BOOK">Sách / Giáo trình</option>
            <option value="OTHER">Khác</option>
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
          placeholder="Tìm theo tên GV hoặc tên công trình..."
          class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 lg:w-72"
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
            công trình đang ở trạng thái
            <span class="font-semibold text-amber-700">chờ duyệt</span>.
          </p>
          <p class="mt-0.5">
            Tổng số công trình (theo bộ lọc hiện tại):
            <span class="font-semibold text-slate-800">
              {{ filteredWorks.length }}
            </span>
          </p>
        </div>
      </div>
    </div>

    <!-- Bảng duyệt công trình -->
    <div
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead
          class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          <tr>
            <th class="px-3 py-2 text-left">Công trình</th>
            <th class="px-3 py-2 text-left">Loại</th>
            <th class="px-3 py-2 text-left">Giảng viên kê khai</th>
            <th class="px-3 py-2 text-left">Năm</th>
            <th class="px-3 py-2 text-left">Trạng thái</th>
            <th class="px-3 py-2 text-right">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">
              Đang tải dữ liệu...
            </td>
          </tr>

          <tr v-else-if="!filteredWorks.length">
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">
              Không có công trình nào phù hợp với bộ lọc hiện tại.
            </td>
          </tr>

          <tr
            v-for="work in filteredWorks"
            :key="work.id"
            class="align-top hover:bg-slate-50"
          >
            <td class="px-3 py-3">
              <p class="text-sm font-medium text-slate-800">
                {{ work.title }}
              </p>
              <p v-if="work.journalOrPlace" class="text-xs text-slate-500">
                {{ work.journalOrPlace }}
              </p>
            </td>
            <td class="px-3 py-3 text-xs text-slate-700">
              {{ workTypeLabel(work.workType) }}
            </td>
            <td class="px-3 py-3 text-sm text-slate-700">
              {{ work.declarerName }}
              <p v-if="work.declarerUnit" class="text-xs text-slate-400">
                {{ work.declarerUnit }}
              </p>
            </td>
            <td class="px-3 py-3 text-sm text-slate-700">
              {{ work.year }}
            </td>
            <td class="px-3 py-3 text-sm">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                :class="statusClass(work.status)"
              >
                {{ statusLabel(work.status) }}
              </span>
            </td>
            <td class="px-3 py-3 text-right text-xs">
              <button
                type="button"
                class="mr-2 inline-flex items-center rounded-md border border-slate-300 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
                @click="openDetail(work)"
              >
                Chi tiết
              </button>
              <button
                type="button"
                class="mr-1 rounded-md border border-emerald-500 px-2 py-1 text-[11px] font-medium text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="work.status !== 'PENDING'"
                @click="updateStatus(work, 'APPROVED')"
              >
                Duyệt
              </button>
              <button
                type="button"
                class="rounded-md border border-red-500 px-2 py-1 text-[11px] font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="work.status !== 'PENDING'"
                @click="updateStatus(work, 'REJECTED')"
              >
                Từ chối
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal chi tiết -->
    <WorkApprovalDetailModal
      v-if="detailWork"
      :work="detailWork"
      @close="closeDetail"
      @confirm="onModalConfirm"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import WorkApprovalDetailModal, {
  type WorkApprovalItem,
} from "@/features/research-works/components/WorkApprovalDetailModal.vue";

type WorkType = "ARTICLE" | "PROJECT" | "BOOK" | "OTHER";
type WorkApprovalStatus = "PENDING" | "APPROVED" | "REJECTED";

const loading = ref(false);
const search = ref("");
const yearFilter = ref<"ALL" | string>("ALL");
const workTypeFilter = ref<WorkType | "ALL">("ALL");
const statusFilter = ref<WorkApprovalStatus | "ALL">("PENDING");

const yearOptions = [2025, 2024, 2023];

const works = ref<WorkApprovalItem[]>([
  {
    id: "W-001",
    title: "Ứng dụng học sâu trong phân tích dữ liệu giáo dục",
    workType: "ARTICLE",
    journalOrPlace: "Tạp chí Khoa học Giáo dục",
    year: 2024,
    field: "Trí tuệ nhân tạo, Giáo dục",
    authors: [
      {
        id: "u1",
        name: "Nguyễn Văn A",
        role: "Tác giả chính",
        isLecturer: true,
      },
      { id: "u2", name: "Trần Thị B", role: "Đồng tác giả" },
    ],
    declarerId: "u1",
    declarerName: "Nguyễn Văn A",
    declarerUnit: "Khoa Công nghệ Thông tin",
    status: "PENDING",
    note: "Bài báo Q2, đã được chấp nhận đăng.",
    calculatedHours: 40,
    coefficient: 1.0,
    hoursNote: "Tính giờ theo quy định bài báo quốc tế Q2.",
  },
  {
    id: "W-002",
    title: "Xây dựng hệ thống hỗ trợ ra quyết định cho quản lý đào tạo",
    workType: "PROJECT",
    year: 2023,
    field: "Hệ thống thông tin, Quản lý đào tạo",
    authors: [
      {
        id: "u3",
        name: "Lê Văn C",
        role: "Chủ nhiệm đề tài",
        isLecturer: true,
      },
      { id: "u1", name: "Nguyễn Văn A", role: "Thành viên" },
    ],
    declarerId: "u3",
    declarerName: "Lê Văn C",
    declarerUnit: "Phòng QLKH",
    status: "APPROVED",
  },
  {
    id: "W-003",
    title: "Giáo trình Lập trình Web với Vue.js",
    workType: "BOOK",
    year: 2024,
    field: "Công nghệ phần mềm, Phát triển Web",
    authors: [
      { id: "u1", name: "Nguyễn Văn A", role: "Chủ biên", isLecturer: true },
      { id: "u4", name: "Phạm Thị D", role: "Đồng tác giả" },
    ],
    declarerId: "u1",
    declarerName: "Nguyễn Văn A",
    declarerUnit: "Khoa Công nghệ Thông tin",
    status: "PENDING",
  },
]);

const filteredWorks = computed(() => {
  let data = works.value;

  if (yearFilter.value !== "ALL") {
    const y = Number(yearFilter.value);
    data = data.filter((w) => w.year === y);
  }

  if (workTypeFilter.value !== "ALL") {
    data = data.filter((w) => w.workType === workTypeFilter.value);
  }

  if (statusFilter.value !== "ALL") {
    data = data.filter((w) => w.status === statusFilter.value);
  }

  if (search.value.trim()) {
    const q = search.value.toLowerCase();
    data = data.filter(
      (w) =>
        w.title.toLowerCase().includes(q) ||
        w.declarerName.toLowerCase().includes(q)
    );
  }

  return data;
});

const pendingCount = computed(
  () => works.value.filter((w) => w.status === "PENDING").length
);

const detailWork = ref<WorkApprovalItem | null>(null);

function reload() {
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
  }, 300);
}

function workTypeLabel(t: WorkType): string {
  switch (t) {
    case "ARTICLE":
      return "Bài báo";
    case "PROJECT":
      return "Đề tài";
    case "BOOK":
      return "Sách / Giáo trình";
    case "OTHER":
    default:
      return "Khác";
  }
}

function statusLabel(status: WorkApprovalStatus): string {
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

function statusClass(status: WorkApprovalStatus): string {
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

function openDetail(work: WorkApprovalItem) {
  detailWork.value = { ...work };
}

function closeDetail() {
  detailWork.value = null;
}

function updateStatus(work: WorkApprovalItem, newStatus: WorkApprovalStatus) {
  // TODO: nối API duyệt công trình ở đây
  works.value = works.value.map((w) =>
    w.id === work.id ? { ...w, status: newStatus } : w
  );

  if (detailWork.value?.id === work.id) {
    detailWork.value = { ...detailWork.value, status: newStatus };
  }
}

function onModalConfirm(newStatus: WorkApprovalStatus) {
  if (!detailWork.value) return;
  updateStatus(detailWork.value, newStatus);
  closeDetail();
}
</script>
