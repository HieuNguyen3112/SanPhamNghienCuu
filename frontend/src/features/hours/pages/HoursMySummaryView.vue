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
            Các công trình đang chờ duyệt sẽ xuất hiện trong phần{" "}
            <span class="font-medium text-amber-700">giờ chờ duyệt</span>.
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
</script>
