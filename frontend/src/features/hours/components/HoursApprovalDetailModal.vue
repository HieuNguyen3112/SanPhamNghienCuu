<!-- src/features/hours/components/HoursApprovalDetailModal.vue -->
<template>
  <Teleport to="body">
    <div
      v-if="request"
      class="fixed inset-0 z-40 flex items-center justify-center"
      aria-modal="true"
      role="dialog"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/40" @click="onClose"></div>

      <!-- Modal content -->
      <div
        class="relative z-50 w-full max-w-4xl rounded-xl bg-white p-6 shadow-xl"
        @click.stop
      >
        <!-- Header -->
        <div class="mb-4 flex items-start justify-between">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">
              Chi tiết yêu cầu duyệt giờ NCKH
            </h3>
            <p class="mt-1 text-sm text-slate-500">
              Kiểm tra danh sách công trình được tính giờ trong yêu cầu này.
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

        <!-- Thông tin tổng quan -->
        <div
          class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700"
        >
          <p class="text-sm font-semibold text-slate-900">
            {{ request.lecturerName }}
            <span class="ml-1 text-[11px] text-slate-500">
              (Mã GV: {{ request.lecturerId }})
            </span>
          </p>
          <p class="mt-0.5 text-[11px] text-slate-500">
            Đơn vị:
            <span class="font-medium">{{ request.departmentName }}</span>
          </p>
          <p class="mt-0.5 text-[11px] text-slate-500">
            Năm học:
            <span class="font-medium">{{ request.academicYear }}</span> · Học
            kỳ:
            <span class="font-medium">
              {{ semesterLabel(request.semester) }}
            </span>
          </p>
          <p class="mt-1 text-[11px]">
            Giờ đề nghị duyệt:
            <span class="font-semibold text-emerald-700 text-sm">
              {{ request.totalHours }} giờ
            </span>
            (từ {{ request.works.length }} công trình)
          </p>
          <p v-if="request.note" class="mt-1 text-[11px] text-slate-500">
            Ghi chú từ giảng viên: {{ request.note }}
          </p>
        </div>

        <!-- Bảng công trình -->
        <div class="rounded-lg border border-slate-200">
          <table class="min-w-full text-left text-sm">
            <thead
              class="border-b border-slate-200 bg-slate-50 text-xs font-semibold text-slate-500"
            >
              <tr>
                <th class="px-3 py-2">Công trình</th>
                <th class="px-3 py-2">Loại</th>
                <th class="px-3 py-2">Vai trò</th>
                <th class="px-3 py-2 text-right">Hệ số</th>
                <th class="px-3 py-2 text-right">Giờ quy đổi</th>
                <th class="px-3 py-2">Ghi chú</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="work in request.works" :key="work.id">
                <td class="px-3 py-2 align-top">
                  <p class="text-sm font-medium text-slate-800">
                    {{ work.title }}
                  </p>
                  <p class="text-xs text-slate-400">Mã: {{ work.id }}</p>
                </td>
                <td class="px-3 py-2 align-top text-xs text-slate-700">
                  {{ work.workTypeLabel }}
                </td>
                <td class="px-3 py-2 align-top text-xs text-slate-700">
                  {{ work.roleLabel }}
                </td>
                <td
                  class="px-3 py-2 align-top text-right text-xs text-slate-700"
                >
                  {{ work.coefficient }}
                </td>
                <td
                  class="px-3 py-2 align-top text-right text-xs font-semibold text-emerald-700"
                >
                  {{ work.hours }}
                </td>
                <td class="px-3 py-2 align-top text-xs text-slate-600">
                  {{ work.note || "—" }}
                </td>
              </tr>
              <tr class="bg-slate-50/60">
                <td colspan="4" class="px-3 py-2 text-right text-xs">
                  Tổng giờ đề nghị duyệt:
                </td>
                <td
                  class="px-3 py-2 text-right text-sm font-semibold text-emerald-700"
                >
                  {{ request.totalHours }}
                </td>
                <td />
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Action -->
        <div
          class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <p class="text-[11px] text-slate-500">
            Lưu ý: Duyệt giờ NCKH không tự động duyệt công trình khoa học. Công
            trình cần được duyệt ở màn hình quản lý công trình riêng (nếu có quy
            trình).
          </p>

          <div class="flex justify-end gap-2 text-sm">
            <button
              type="button"
              class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
              @click="onClose"
            >
              Để sau
            </button>
            <button
              v-if="request.status === 'PENDING'"
              type="button"
              class="rounded-md border border-red-500 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
              @click="onConfirm('REJECTED')"
            >
              Từ chối yêu cầu
            </button>
            <button
              v-if="request.status === 'PENDING'"
              type="button"
              class="rounded-md bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
              @click="onConfirm('APPROVED')"
            >
              Duyệt giờ NCKH
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
type HoursApprovalStatus = "PENDING" | "APPROVED" | "REJECTED";
type Semester = "ALL" | "HK1" | "HK2";

interface HoursApprovalWork {
  id: string;
  title: string;
  workTypeLabel: string;
  roleLabel: string;
  coefficient: number;
  hours: number;
  note?: string;
}

export interface HoursApprovalRequest {
  id: string;
  lecturerId: string;
  lecturerName: string;
  departmentName: string;
  academicYear: string;
  semester: Semester;
  totalHours: number;
  works: HoursApprovalWork[];
  status: HoursApprovalStatus;
  note?: string;
}

const props = defineProps<{
  request: HoursApprovalRequest | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "confirm", status: HoursApprovalStatus): void;
}>();

const onClose = () => emit("close");

const onConfirm = (status: HoursApprovalStatus) => emit("confirm", status);

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
</script>
