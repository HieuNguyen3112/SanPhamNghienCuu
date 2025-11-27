<!-- src/features/hours/pages/HoursFacultySummaryView.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Giờ NCKH giảng viên trong khoa
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Tổng hợp giờ nghiên cứu khoa học của giảng viên theo năm học, phục vụ
        theo dõi và quản lý của BCN khoa.
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

        <div class="w-52">
          <label class="block text-xs font-medium text-slate-600">
            Bộ môn (tuỳ chọn)
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
      </div>

      <div class="flex flex-wrap gap-2">
        <input
          v-model="search"
          type="text"
          placeholder="Tìm theo tên giảng viên..."
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

    <!-- Bảng danh sách giảng viên -->
    <div
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead
          class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          <tr>
            <th class="px-4 py-2 text-left">Giảng viên</th>
            <th class="px-4 py-2 text-left">Đơn vị</th>
            <th class="px-4 py-2 text-left">Tiến độ giờ</th>
            <th class="px-4 py-2 text-right">Định mức</th>
            <th class="px-4 py-2 text-right">Đã tính</th>
            <th class="px-4 py-2 text-right">Chờ duyệt</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">
              Đang tải dữ liệu...
            </td>
          </tr>

          <tr v-else-if="!rows.length">
            <td colspan="6" class="px-4 py-6 text-center text-slate-500">
              Không có giảng viên nào phù hợp với bộ lọc hiện tại.
            </td>
          </tr>

          <tr
            v-for="row in rows"
            v-else
            :key="row.teacherId"
            class="align-top hover:bg-slate-50"
          >
            <td class="px-4 py-3">
              <p class="text-sm font-medium text-slate-800">
                {{ row.teacherName }}
              </p>
              <p class="text-xs text-slate-400">Mã: {{ row.teacherId }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700">
              {{ row.departmentName || "—" }}
            </td>
            <td class="px-4 py-3">
              <HoursProgressBar
                :current="row.completedHours"
                :target="row.quota.requiredHours"
                :label="`${row.completedHours}/${row.quota.requiredHours} giờ`"
                :helper-text="`Chờ duyệt: ${row.pendingHours} giờ`"
              />
            </td>
            <td class="px-4 py-3 text-right text-sm text-slate-700">
              {{ row.quota.requiredHours }}
            </td>
            <td class="px-4 py-3 text-right text-sm text-emerald-700">
              {{ row.completedHours }}
            </td>
            <td class="px-4 py-3 text-right text-sm text-amber-700">
              {{ row.pendingHours }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Phân trang đơn giản (mock) -->
    <div
      v-if="!loading && rows.length"
      class="flex items-center justify-between text-xs text-slate-500"
    >
      <p>
        Tổng cộng
        <span class="font-medium">{{ rows.length }}</span> giảng viên (mock).
      </p>
      <p class="italic text-slate-400">
        Phân trang / xuất Excel sẽ được bổ sung sau.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import HoursProgressBar from "@/features/hours/components/HoursProgressBar.vue";
import type { FacultyHoursItem } from "@/features/hours/types";

const academicYear = ref("2024-2025");
const semester = ref<"ALL" | "HK1" | "HK2">("ALL");
const department = ref<"ALL" | "CNTT" | "TOAN">("ALL");
const search = ref("");
const loading = ref(false);

// mock dữ liệu giảng viên
const allRows = ref<FacultyHoursItem[]>([
  {
    teacherId: "GV001",
    teacherName: "Nguyễn Văn A",
    departmentName: "Bộ môn Công nghệ thông tin",
    quota: {
      academicYear: "2024-2025",
      requiredHours: 150,
    },
    completedHours: 120,
    pendingHours: 10,
  },
  {
    teacherId: "GV002",
    teacherName: "Trần Thị B",
    departmentName: "Bộ môn Toán",
    quota: {
      academicYear: "2024-2025",
      requiredHours: 130,
    },
    completedHours: 80,
    pendingHours: 25,
  },
  {
    teacherId: "GV003",
    teacherName: "Lê Văn C",
    departmentName: "Bộ môn Công nghệ thông tin",
    quota: {
      academicYear: "2024-2025",
      requiredHours: 150,
    },
    completedHours: 40,
    pendingHours: 5,
  },
]);

const rows = computed(() => {
  // hiện tại chỉ filter mock rất đơn giản
  let data = allRows.value;

  if (department.value === "CNTT") {
    data = data.filter((x) =>
      x.departmentName?.includes("Công nghệ thông tin")
    );
  } else if (department.value === "TOAN") {
    data = data.filter((x) => x.departmentName?.includes("Toán"));
  }

  if (search.value.trim()) {
    const keyword = search.value.toLowerCase();
    data = data.filter((x) => x.teacherName.toLowerCase().includes(keyword));
  }

  // chưa xử lý academicYear / semester vì mock
  return data;
});

function reload() {
  // sau này gọi API, hiện tại chỉ mô phỏng loading
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
  }, 400);
}
</script>
