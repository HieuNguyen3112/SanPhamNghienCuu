<!-- src/features/declarations/components/ParticipationConfirmDetailModal.vue -->
<template>
  <Teleport to="body">
    <div
      v-if="item"
      class="fixed inset-0 z-40 flex items-center justify-center"
      aria-modal="true"
      role="dialog"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/40" @click="onClose"></div>

      <!-- Modal content -->
      <div
        class="relative z-50 w-full max-w-3xl rounded-lg bg-white p-6 shadow-xl"
        @click.stop
      >
        <div class="mb-4 flex items-start justify-between">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">
              Xác nhận tham gia công trình
            </h3>
            <p class="mt-1 text-sm text-slate-500">
              Kiểm tra thông tin công trình bên dưới trước khi xác nhận.
            </p>
          </div>
          <button
            type="button"
            class="inline-flex items-center rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
            @click="onClose"
          >
            <span class="sr-only">Đóng</span>
            ✕
          </button>
        </div>

        <div class="space-y-3 text-sm text-slate-800">
          <div>
            <p
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Tên công trình
            </p>
            <p class="mt-1 font-medium">
              {{ item.title }}
            </p>
            <p v-if="item.journalOrPlace" class="text-xs text-slate-500">
              {{ item.journalOrPlace }} – Năm {{ item.year }}
            </p>
          </div>

          <div class="grid gap-3 md:grid-cols-2">
            <div>
              <p
                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Loại công trình
              </p>
              <p class="mt-1">
                {{ workTypeLabel(item.workType) }}
              </p>
            </div>

            <div>
              <p
                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Vai trò của bạn
              </p>
              <p class="mt-1">
                {{ roleLabel(item.yourRole) }}
              </p>
            </div>
          </div>

          <div>
            <p
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Danh sách tác giả
            </p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5 text-sm">
              <li
                v-for="author in item.authors"
                :key="author.id"
                :class="[
                  author.isCurrentUser ? 'font-semibold text-[#234a74]' : '',
                ]"
              >
                {{ author.name }}
                <span v-if="author.isCurrentUser" class="text-xs"> (Bạn) </span>
                <span v-if="author.role" class="text-xs text-slate-500">
                  – {{ author.role }}
                </span>
              </li>
            </ul>
          </div>

          <div class="grid gap-3 md:grid-cols-2">
            <div>
              <p
                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Người kê khai
              </p>
              <p class="mt-1">
                {{ item.declarerName }}
              </p>
              <p class="text-xs text-slate-500">
                {{ item.declarerUnit }}
              </p>
            </div>
            <div>
              <p
                class="text-xs font-semibold uppercase tracking-wide text-slate-500"
              >
                Ngày gửi yêu cầu
              </p>
              <p class="mt-1">
                {{ formatDate(item.requestedAt) }}
              </p>
            </div>
          </div>

          <div v-if="item.note">
            <p
              class="text-xs font-semibold uppercase tracking-wide text-slate-500"
            >
              Ghi chú từ người kê khai
            </p>
            <p class="mt-1 whitespace-pre-line text-sm">
              {{ item.note }}
            </p>
          </div>
        </div>

        <!-- Action buttons -->
        <div
          class="mt-6 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <p class="text-xs text-slate-500">
            Sau khi xác nhận, kết quả sẽ được lưu vào hồ sơ công trình khoa học
            của bạn.
          </p>

          <div class="flex justify-end gap-2">
            <!-- <button
              type="button"
              class="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
              @click="onClose"
            >
              Để sau
            </button> -->
            <button
              v-if="item.status === 'PENDING'"
              type="button"
              class="rounded-md border border-rose-500 bg-white px-3 py-1.5 text-sm font-medium text-rose-600 hover:bg-rose-50"
              @click="onConfirm('REJECTED')"
              title="Xác nhận KHÔNG tham gia công trình này"
            >
              <img src="/cancel.png" alt="Không tham gia" class="h-6 w-6" />
            </button>
            <button
              v-if="item.status === 'PENDING'"
              type="button"
              class="rounded-md bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700"
              @click="onConfirm('ACCEPTED')"
              title="Xác nhận tham gia công trình này"
            >
              <img src="/checked.png" alt="Không tham gia" class="h-6 w-6" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
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

const props = defineProps<{
  item: ParticipationRequest | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "confirm", status: Extract<RequestStatus, "ACCEPTED" | "REJECTED">): void;
}>();

const onClose = () => {
  emit("close");
};

const onConfirm = (status: Extract<RequestStatus, "ACCEPTED" | "REJECTED">) => {
  emit("confirm", status);
};

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

function formatDate(value: string): string {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const d = String(date.getDate()).padStart(2, "0");
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const y = date.getFullYear();
  return `${d}/${m}/${y}`;
}
</script>
