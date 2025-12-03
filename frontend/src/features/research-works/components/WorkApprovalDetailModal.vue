<!-- src/features/declarations/components/WorkApprovalDetailModal.vue -->
<template>
  <Teleport to="body">
    <div
      v-if="work"
      class="fixed inset-0 z-40 flex items-center justify-center"
      aria-modal="true"
      role="dialog"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-slate-900/40" @click="onClose"></div>

      <!-- Modal content -->
      <div
        class="relative z-50 w-full max-w-5xl rounded-xl bg-white p-6 shadow-xl"
        @click.stop
      >
        <!-- Header -->
        <div class="mb-4 flex items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">
              Duyệt công trình khoa học
            </h3>
            <p class="mt-1 text-sm text-slate-500">
              Kiểm tra thông tin chi tiết công trình do giảng viên kê khai trước
              khi quyết định duyệt / từ chối.
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

        <!-- Thông tin công trình tổng quan -->
        <section
          class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700"
        >
          <p class="text-sm font-semibold text-slate-900">
            {{ work.title }}
          </p>

          <p
            v-if="work.journalOrPlace || work.year"
            class="mt-1 text-[11px] text-slate-600"
          >
            <span v-if="work.journalOrPlace">{{ work.journalOrPlace }}</span>
            <span v-if="work.journalOrPlace && work.year"> · </span>
            <span v-if="work.year">Năm {{ work.year }}</span>
          </p>

          <div class="mt-2 grid gap-2 md:grid-cols-2">
            <p class="text-[11px] text-slate-500">
              Loại công trình:
              <span class="font-medium text-slate-800">
                {{ workTypeLabel(work.workType) }}
              </span>
            </p>

            <p v-if="work.field" class="text-[11px] text-slate-500">
              Lĩnh vực:
              <span class="font-medium text-slate-800">
                {{ work.field }}
              </span>
            </p>

            <p class="text-[11px] text-slate-500">
              Người kê khai:
              <span class="font-medium text-slate-800">
                {{ work.declarerName }}
              </span>
              <span v-if="work.declarerUnit" class="text-slate-400">
                – {{ work.declarerUnit }}
              </span>
            </p>

            <p v-if="work.createdAt" class="text-[11px] text-slate-500">
              Ngày kê khai:
              <span class="font-medium text-slate-800">
                {{ formatDate(work.createdAt) }}
              </span>
            </p>
          </div>

          <!-- Link bài báo / DOI -->
          <div
            v-if="work.externalUrl || work.doi"
            class="mt-3 flex flex-wrap gap-2 text-[11px]"
          >
            <a
              v-if="work.externalUrl"
              :href="work.externalUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center rounded-full bg-sky-50 px-3 py-1 font-medium text-sky-700 hover:bg-sky-100"
            >
              <span class="mr-1 text-base">🔗</span>
              Mở trang công bố / bài báo
            </a>

            <a
              v-if="work.doi"
              :href="doiLink(work.doi)"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700 hover:bg-slate-200"
            >
              <span class="mr-1 text-base">📄</span>
              DOI: {{ work.doi }}
            </a>
          </div>
        </section>

        <!-- Thông tin xuất bản / phát hành (bài báo, sách) -->
        <section
          v-if="hasPublicationInfo"
          class="mb-4 rounded-lg border border-slate-100 bg-white p-4 text-xs text-slate-700"
        >
          <p
            class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
          >
            Thông tin xuất bản / phát hành
          </p>

          <div class="mt-2 grid gap-x-6 gap-y-1 md:grid-cols-2">
            <p v-if="work.publisher">
              <span class="font-medium text-slate-600"
                >Nhà xuất bản / Đơn vị:</span
              >
              <span class="ml-1 text-slate-800">{{ work.publisher }}</span>
            </p>
            <p v-if="work.issnIsbn">
              <span class="font-medium text-slate-600">ISSN / ISBN:</span>
              <span class="ml-1 text-slate-800">{{ work.issnIsbn }}</span>
            </p>
            <p v-if="work.volume">
              <span class="font-medium text-slate-600">Tập (Volume):</span>
              <span class="ml-1 text-slate-800">{{ work.volume }}</span>
            </p>
            <p v-if="work.issue">
              <span class="font-medium text-slate-600">Số (Issue):</span>
              <span class="ml-1 text-slate-800">{{ work.issue }}</span>
            </p>
            <p v-if="work.pages">
              <span class="font-medium text-slate-600">Trang:</span>
              <span class="ml-1 text-slate-800">{{ work.pages }}</span>
            </p>
            <p v-if="work.publishDate">
              <span class="font-medium text-slate-600">Ngày xuất bản:</span>
              <span class="ml-1 text-slate-800">
                {{ formatDate(work.publishDate) }}
              </span>
            </p>
            <p v-if="work.indexing">
              <span class="font-medium text-slate-600"
                >Chỉ mục / xếp hạng:</span
              >
              <span class="ml-1 text-slate-800">{{ work.indexing }}</span>
            </p>
          </div>
        </section>

        <!-- Thông tin đề tài (nếu là PROJECT) -->
        <section
          v-if="work.workType === 'PROJECT'"
          class="mb-4 rounded-lg border border-slate-100 bg-white p-4 text-xs text-slate-700"
        >
          <p
            class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
          >
            Thông tin đề tài
          </p>

          <div class="mt-2 grid gap-x-6 gap-y-1 md:grid-cols-2">
            <p v-if="work.code">
              <span class="font-medium text-slate-600">Mã số đề tài:</span>
              <span class="ml-1 text-slate-800">{{ work.code }}</span>
            </p>
            <p v-if="work.level">
              <span class="font-medium text-slate-600">Cấp quản lý:</span>
              <span class="ml-1 text-slate-800">{{ work.level }}</span>
            </p>
            <p v-if="work.startDate">
              <span class="font-medium text-slate-600"
                >Thời gian thực hiện:</span
              >
              <span class="ml-1 text-slate-800">
                {{ formatDate(work.startDate) }}
                <span v-if="work.endDate">
                  – {{ formatDate(work.endDate) }}</span
                >
              </span>
            </p>
            <p v-if="work.funding">
              <span class="font-medium text-slate-600">Kinh phí:</span>
              <span class="ml-1 text-slate-800">{{ work.funding }}</span>
            </p>
            <p v-if="work.acceptanceResult">
              <span class="font-medium text-slate-600"
                >Kết quả nghiệm thu:</span
              >
              <span class="ml-1 text-slate-800">{{
                work.acceptanceResult
              }}</span>
            </p>
          </div>
        </section>

        <!-- Danh sách tác giả -->
        <section class="mb-4">
          <p
            class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
          >
            Danh sách tác giả
          </p>
          <ul class="mt-1 list-disc space-y-0.5 pl-5 text-sm">
            <li
              v-for="author in work.authors"
              :key="author.id"
              :class="author.isLecturer ? 'font-semibold text-[#234a74]' : ''"
            >
              {{ author.name }}
              <span v-if="author.isLecturer" class="text-xs">
                (Giảng viên trong đơn vị)
              </span>
              <span v-if="author.role" class="text-xs text-slate-500">
                – {{ author.role }}
              </span>
            </li>
          </ul>
        </section>

        <!-- Thông tin giờ NCKH liên quan (optional) -->
        <section
          v-if="work.calculatedHours"
          class="mb-4 grid gap-3 md:grid-cols-2"
        >
          <div class="rounded-md bg-emerald-50 px-3 py-2 text-xs">
            <p class="font-semibold text-emerald-800">
              Giờ NCKH quy đổi đề nghị
            </p>
            <p class="mt-0.5 text-sm text-emerald-700">
              {{ work.calculatedHours }} giờ
              <span
                v-if="work.coefficient"
                class="text-[11px] text-emerald-800"
              >
                (Hệ số: {{ work.coefficient }})
              </span>
            </p>
            <p
              v-if="work.hoursNote"
              class="mt-0.5 text-[11px] text-emerald-900"
            >
              {{ work.hoursNote }}
            </p>
          </div>
        </section>

        <!-- Minh chứng / tệp đính kèm -->
        <section
          v-if="work.evidenceFiles && work.evidenceFiles.length"
          class="mb-4 rounded-lg border border-slate-100 bg-white p-4 text-xs text-slate-700"
        >
          <p
            class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
          >
            Minh chứng đính kèm
          </p>
          <ul class="mt-2 space-y-1">
            <li
              v-for="file in work.evidenceFiles"
              :key="file.id"
              class="flex items-center text-[11px]"
            >
              <span class="mr-2 text-slate-400">📎</span>
              <a
                v-if="file.url"
                :href="file.url"
                target="_blank"
                rel="noopener noreferrer"
                class="truncate text-sky-700 hover:underline"
              >
                {{ file.name }}
              </a>
              <span v-else class="truncate text-slate-700">
                {{ file.name }}
              </span>
            </li>
          </ul>
        </section>

        <!-- Ghi chú -->
        <section v-if="work.note" class="mb-4">
          <p
            class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"
          >
            Ghi chú từ người kê khai
          </p>
          <p class="mt-1 whitespace-pre-line text-sm text-slate-700">
            {{ work.note }}
          </p>
        </section>

        <!-- Action -->
        <div
          class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <p class="text-[11px] text-slate-500">
            Việc duyệt công trình này sẽ ảnh hưởng đến danh mục công trình khoa
            học của giảng viên và (nếu có) việc tính giờ NCKH liên quan.
          </p>

          <div class="flex justify-end gap-2 text-xs">
            <button
              type="button"
              class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
              @click="onClose"
            >
              Để sau
            </button>
            <button
              v-if="work.status === 'PENDING'"
              type="button"
              class="rounded-md border border-red-500 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50"
              @click="onConfirm('REJECTED')"
            >
              Từ chối công trình
            </button>
            <button
              v-if="work.status === 'PENDING'"
              type="button"
              class="rounded-md bg-emerald-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
              @click="onConfirm('APPROVED')"
            >
              Duyệt công trình
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
type WorkType = "ARTICLE" | "PROJECT" | "BOOK" | "OTHER";
type WorkApprovalStatus = "PENDING" | "APPROVED" | "REJECTED";

interface WorkAuthor {
  id: string;
  name: string;
  role?: string;
  isLecturer?: boolean;
}

interface EvidenceFile {
  id: string;
  name: string;
  url?: string;
}

export interface WorkApprovalItem {
  id: string;
  title: string;
  workType: WorkType;
  journalOrPlace?: string;
  year: number;
  field?: string;
  authors: WorkAuthor[];
  declarerId: string;
  declarerName: string;
  declarerUnit?: string;
  note?: string;
  status: WorkApprovalStatus;

  // thời gian kê khai
  createdAt?: string;

  // thông tin xuất bản / phát hành
  publisher?: string;
  issnIsbn?: string;
  volume?: string;
  issue?: string;
  pages?: string;
  publishDate?: string;
  indexing?: string;

  // link ngoài
  externalUrl?: string; // link bài báo / trang công bố
  doi?: string;

  // thông tin đề tài (PROJECT)
  code?: string;
  level?: string;
  startDate?: string;
  endDate?: string;
  funding?: string;
  acceptanceResult?: string;

  // thông tin liên quan tới giờ (optional)
  calculatedHours?: number;
  coefficient?: number;
  hoursNote?: string;

  // minh chứng
  evidenceFiles?: EvidenceFile[];
}

const props = defineProps<{
  work: WorkApprovalItem | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "confirm", status: WorkApprovalStatus): void;
}>();

const onClose = () => emit("close");

const onConfirm = (status: WorkApprovalStatus) => emit("confirm", status);

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

function formatDate(value?: string): string {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  const day = String(d.getDate()).padStart(2, "0");
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

function doiLink(doi: string): string {
  if (doi.startsWith("http://") || doi.startsWith("https://")) return doi;
  return `https://doi.org/${doi}`;
}

const hasPublicationInfo = computed(() => {
  const w = props.work;
  if (!w) return false;
  return (
    !!w.publisher ||
    !!w.issnIsbn ||
    !!w.volume ||
    !!w.issue ||
    !!w.pages ||
    !!w.publishDate ||
    !!w.indexing
  );
});
</script>
