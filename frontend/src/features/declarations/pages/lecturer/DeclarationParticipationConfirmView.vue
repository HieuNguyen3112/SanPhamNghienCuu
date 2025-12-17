<!-- src/features/declarations/pages/DeclarationParticipationConfirmView.vue -->
<template>
  <div class="space-y-4">
    <!-- Header -->
    <header
      class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <h1 class="text-xl font-semibold text-slate-900">
          Xác nhận tham gia công trình
        </h1>
        <p class="mt-1 text-sm text-slate-500">
          Khi người khác kê khai công trình có tên bạn, hệ thống sẽ gửi yêu cầu
          xác nhận tại đây.
        </p>
      </div>

      <!-- (Optional) tổng quan số lượng -->
      <div class="flex items-center gap-3 text-xs text-slate-500">
        <span
          class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-800"
        >
          Đang chờ: {{ pendingCount }}
        </span>
        <span
          class="hidden items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 font-medium text-emerald-800 sm:inline-flex"
        >
          Đã xác nhận: {{ acceptedCount }}
        </span>
        <span
          class="hidden items-center gap-1 rounded-full bg-rose-50 px-3 py-1 font-medium text-rose-800 sm:inline-flex"
        >
          Từ chối: {{ rejectedCount }}
        </span>
      </div>
    </header>

    <!-- Tabs filter -->
    <div class="flex gap-2 text-sm">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        type="button"
        class="rounded-full border px-3 py-1.5"
        :class="
          currentFilter === tab.value
            ? 'border-[#234a74] bg-[#234a74] text-white'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
        "
        @click="currentFilter = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- List -->
    <section class="rounded-md bg-white p-6 shadow-sm">
      <div v-if="filteredRequests.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead
            class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            <tr>
              <th class="px-3 py-2">Công trình</th>
              <th class="px-3 py-2">Loại</th>
              <th class="px-3 py-2">Vai trò của bạn</th>
              <th class="px-3 py-2">Người kê khai</th>
              <th class="px-3 py-2">Ngày yêu cầu</th>
              <th class="px-3 py-2">Trạng thái</th>
              <th class="px-3 py-2 text-center">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="item in filteredRequests"
              :key="item.id"
              class="hover:bg-slate-50"
            >
              <td class="px-3 py-2 align-top text-sm text-slate-800">
                <p class="font-medium">
                  {{ item.title }}
                </p>
                <p v-if="item.journalOrPlace" class="text-xs text-slate-500">
                  {{ item.journalOrPlace }}
                </p>
                <p class="text-xs text-slate-400">Năm: {{ item.year }}</p>
              </td>

              <td class="px-3 py-2 align-top text-xs text-slate-700">
                {{ workTypeLabel(item.workType) }}
              </td>

              <td class="px-3 py-2 align-top text-xs text-slate-700">
                {{ roleLabel(item.yourRole) }}
              </td>

              <td class="px-3 py-2 align-top text-xs text-slate-700">
                <p>{{ item.declarerName }}</p>
                <p class="text-[11px] text-slate-500">
                  {{ item.declarerUnit }}
                </p>
              </td>

              <td class="px-3 py-2 align-top text-xs text-slate-700">
                {{ formatDate(item.requestedAt) }}
              </td>

              <td class="px-3 py-2 align-top text-xs">
                <span
                  :class="[
                    'inline-flex rounded-full px-2 py-0.5 font-medium',
                    statusBadgeClass(item.status),
                  ]"
                >
                  {{ statusLabel(item.status) }}
                </span>
              </td>

              <td class="px-3 py-2 align-top text-right">
                <div class="inline-flex items-center gap-1">
                  <!-- Chi tiết -->
                  <button
                    type="button"
                    class="inline-flex h-8 items-center justify-center rounded-full border border-slate-200 bg-white px-3 text-xs text-slate-600 hover:bg-slate-50"
                    @click="openDetail(item)"
                    title="Xem chi tiết công trình"
                  >
                    <img src="/eye.png" alt="Chi tiết" class="h-4 w-4" />
                  </button>

                  <!-- Xác nhận / Từ chối -->
                  <template v-if="item.status === 'PENDING'">
                    <!-- Xác nhận -->
                    <button
                      type="button"
                      class="inline-flex h-8 items-center justify-center rounded-full border border-emerald-600 bg-emerald-600 px-3 text-xs font-medium text-white hover:bg-emerald-700"
                      @click="confirmParticipation(item, 'ACCEPTED')"
                      title="Xác nhận tham gia công trình này"
                    >
                      <img src="/checked.png" alt="Xác nhận" class="h-4 w-4" />
                    </button>

                    <!-- Không tham gia -->
                    <button
                      type="button"
                      class="inline-flex h-8 items-center justify-center rounded-full border border-rose-500 bg-white px-3 text-xs font-medium text-rose-600 hover:bg-rose-50"
                      @click="confirmParticipation(item, 'REJECTED')"
                      title="Xác nhận KHÔNG tham gia công trình này"
                    >
                      <img
                        src="/cancel.png"
                        alt="Không tham gia"
                        class="h-4 w-4"
                      />
                    </button>
                  </template>
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
        Hiện chưa có yêu cầu xác nhận tham gia công trình nào.
      </div>
    </section>

    <!-- Modal chi tiết (tách component) -->
    <ParticipationConfirmDetailModal
      v-if="detailItem"
      :item="detailItem"
      @close="closeDetail"
      @confirm="onModalConfirm"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import ParticipationConfirmDetailModal from "@/features/declarations/components/ParticipationConfirmDetailModal.vue";

type WorkType = "ARTICLE" | "PROJECT" | "BOOK" | "OTHER";
type YourRole = "MAIN_AUTHOR" | "CO_AUTHOR" | "PI" | "MEMBER";
type RequestStatus = "PENDING" | "ACCEPTED" | "REJECTED";

interface AuthorInfo {
  id: string;
  name: string;
  role?: string;
  isCurrentUser?: boolean;
}

interface ParticipationRequest {
  id: string;
  title: string;
  workType: WorkType;
  journalOrPlace?: string;
  year: number;
  yourRole: YourRole;
  authors: AuthorInfo[];
  declarerId: string;
  declarerName: string;
  declarerUnit?: string;
  requestedAt: string;
  status: RequestStatus;
  note?: string;
}

// mock data – sau này thay bằng dữ liệu từ API
const requests = ref<ParticipationRequest[]>([
  {
    id: "1",
    title: "Ứng dụng học sâu trong phân tích dữ liệu giáo dục",
    workType: "ARTICLE",
    journalOrPlace: "Tạp chí Khoa học Giáo dục",
    year: 2024,
    yourRole: "CO_AUTHOR",
    authors: [
      { id: "u1", name: "Nguyễn Văn A", role: "Tác giả chính" },
      { id: "me", name: "Bạn", role: "Đồng tác giả", isCurrentUser: true },
    ],
    declarerId: "u1",
    declarerName: "Nguyễn Văn A",
    declarerUnit: "Khoa Công nghệ Thông tin",
    requestedAt: "2025-10-01",
    status: "PENDING",
    note: "Công trình thuộc nhóm AI trong giáo dục, nhờ thầy/cô xác nhận tham gia.",
  },
  {
    id: "2",
    title: "Xây dựng hệ thống hỗ trợ ra quyết định cho quản lý đào tạo",
    workType: "PROJECT",
    year: 2023,
    yourRole: "MEMBER",
    authors: [
      { id: "me", name: "Bạn", role: "Thành viên", isCurrentUser: true },
      { id: "u2", name: "Trần Thị B", role: "Chủ nhiệm đề tài" },
    ],
    declarerId: "u2",
    declarerName: "Trần Thị B",
    declarerUnit: "Phòng QLKH",
    requestedAt: "2025-09-15",
    status: "ACCEPTED",
  },
]);

const tabs = [
  { value: "ALL", label: "Tất cả" },
  { value: "PENDING", label: "Đang chờ" },
  { value: "ACCEPTED", label: "Đã xác nhận" },
  { value: "REJECTED", label: "Đã từ chối" },
] as const;

type TabFilter = (typeof tabs)[number]["value"];

const currentFilter = ref<TabFilter>("PENDING");

const filteredRequests = computed(() => {
  if (currentFilter.value === "ALL") return requests.value;
  return requests.value.filter((r) => r.status === currentFilter.value);
});

const pendingCount = computed(
  () => requests.value.filter((r) => r.status === "PENDING").length
);
const acceptedCount = computed(
  () => requests.value.filter((r) => r.status === "ACCEPTED").length
);
const rejectedCount = computed(
  () => requests.value.filter((r) => r.status === "REJECTED").length
);

// Detail modal state
const detailItem = ref<ParticipationRequest | null>(null);

const openDetail = (item: ParticipationRequest) => {
  detailItem.value = { ...item };
};

const closeDetail = () => {
  detailItem.value = null;
};

// Helpers
function workTypeLabel(type: WorkType): string {
  switch (type) {
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

function roleLabel(role: YourRole): string {
  switch (role) {
    case "MAIN_AUTHOR":
      return "Tác giả chính";
    case "CO_AUTHOR":
      return "Đồng tác giả";
    case "PI":
      return "Chủ nhiệm đề tài";
    case "MEMBER":
    default:
      return "Thành viên";
  }
}

function statusLabel(status: RequestStatus): string {
  switch (status) {
    case "PENDING":
      return "Đang chờ xác nhận";
    case "ACCEPTED":
      return "Đã xác nhận";
    case "REJECTED":
      return "Đã từ chối";
  }
}

function statusBadgeClass(status: RequestStatus): string {
  switch (status) {
    case "PENDING":
      return "bg-amber-50 text-amber-800 border border-amber-100";
    case "ACCEPTED":
      return "bg-emerald-50 text-emerald-700 border border-emerald-100";
    case "REJECTED":
      return "bg-rose-50 text-rose-700 border border-rose-100";
  }
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

// Xác nhận / từ chối tham gia (từ list row)
function confirmParticipation(
  item: ParticipationRequest,
  newStatus: Extract<RequestStatus, "ACCEPTED" | "REJECTED">
) {
  // TODO: gọi API:
  // POST /api/declarations/{workId}/confirm-participation
  // body: { status: newStatus }

  const index = requests.value.findIndex((r) => r.id === item.id);
  if (index !== -1) {
    requests.value[index] = {
      ...requests.value[index]!,
      status: newStatus,
    };
  }

  if (detailItem.value?.id === item.id) {
    detailItem.value = {
      ...detailItem.value,
      status: newStatus,
    };
  }
}

// Xác nhận / từ chối từ modal
function onModalConfirm(
  newStatus: Extract<RequestStatus, "ACCEPTED" | "REJECTED">
) {
  if (!detailItem.value) return;
  confirmParticipation(detailItem.value, newStatus);
  closeDetail();
}
</script>
