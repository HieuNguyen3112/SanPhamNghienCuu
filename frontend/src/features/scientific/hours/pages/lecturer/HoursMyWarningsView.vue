<!-- src/features/hours/pages/HoursMyWarningsView.vue -->
<template>
  <div class="container mx-auto p-4 md:p-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-semibold text-slate-800">
        Thông báo / cảnh báo giờ NCKH
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Xem các cảnh báo liên quan đến định mức giờ NCKH, thời hạn kê khai và
        tình trạng tính điểm giờ NCKH của bạn.
      </p>
    </div>

    <!-- Bộ lọc -->
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
            Trạng thái
          </label>
          <select
            v-model="statusFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="UNREAD">Chưa xem</option>
            <option value="READ">Đã xem</option>
          </select>
        </div>

        <div class="w-40">
          <label class="block text-xs font-medium text-slate-600">
            Mức độ
          </label>
          <select
            v-model="levelFilter"
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="ALL">Tất cả</option>
            <option value="CRITICAL">Nghiêm trọng</option>
            <option value="WARNING">Cảnh báo</option>
            <option value="INFO">Thông tin</option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-200"
          @click="reload"
        >
          Làm mới
        </button>
      </div>
    </div>

    <!-- Tóm tắt -->
    <div class="text-xs text-slate-600">
      <span v-if="filteredWarnings.length">
        Bạn có
        <span class="font-semibold text-red-600">
          {{ unreadCount }}
        </span>
        cảnh báo chưa xem và
        <span class="font-semibold text-slate-800">
          {{ filteredWarnings.length }}
        </span>
        thông báo trong năm học {{ academicYear }}.
      </span>
      <span v-else>
        Hiện tại bạn <span class="font-semibold">không có cảnh báo</span> nào về
        giờ NCKH trong năm học {{ academicYear }}.
      </span>
    </div>

    <!-- Danh sách cảnh báo -->
    <div class="space-y-3">
      <div
        v-if="!filteredWarnings.length"
        class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"
      >
        Không có thông báo nào phù hợp với bộ lọc hiện tại.
      </div>

      <article
        v-for="warning in filteredWarnings"
        :key="warning.id"
        class="space-y-2 rounded-xl border p-4 text-xs shadow-sm"
        :class="levelClass(warning.level)"
      >
        <header class="flex items-start justify-between gap-2">
          <div>
            <p class="text-[13px] font-semibold">
              {{ warning.message }}
            </p>
            <p class="mt-0.5 text-[11px] text-slate-500">
              Áp dụng cho năm học {{ warning.academicYear }}
              <span v-if="warning.semester">
                · Học kỳ {{ warning.semester }}</span
              >
            </p>
          </div>

          <div class="flex flex-col items-end gap-1">
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
              :class="badgeClass(warning.level)"
            >
              {{ levelLabel(warning.level) }}
            </span>
            <span
              v-if="!warning.read"
              class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-medium text-red-700"
            >
              Chưa xem
            </span>
          </div>
        </header>

        <p v-if="warning.detail" class="text-[11px] leading-relaxed opacity-90">
          {{ warning.detail }}
        </p>

        <p
          v-if="warning.suggestedAction"
          class="text-[11px] font-medium text-slate-800"
        >
          Gợi ý: {{ warning.suggestedAction }}
        </p>

        <!-- Liên kết hành động -->
        <footer class="mt-1 flex flex-wrap items-center gap-3 text-[11px]">
          <RouterLink
            :to="{ name: 'hours.my' }"
            class="inline-flex items-center text-sky-700 hover:underline"
          >
            Xem tổng quan giờ NCKH
          </RouterLink>
          <RouterLink
            :to="{ name: 'works-my-declarations' }"
            class="inline-flex items-center text-sky-700 hover:underline"
          >
            Xem danh sách công trình đã kê khai
          </RouterLink>
          <RouterLink
            :to="{ name: 'hours.calculate' }"
            class="inline-flex items-center text-sky-700 hover:underline"
          >
            Tính / kiểm tra lại giờ NCKH
          </RouterLink>
        </footer>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import type { HoursWarningLevel } from "@/features/scientific/hours/types";

interface MyHoursWarning {
  id: string;
  level: HoursWarningLevel;
  message: string;
  detail?: string;
  suggestedAction?: string;
  academicYear: string;
  semester?: "HK1" | "HK2";
  read: boolean;
}

const academicYear = ref("2024-2025");
const statusFilter = ref<"ALL" | "READ" | "UNREAD">("ALL");
const levelFilter = ref<"ALL" | HoursWarningLevel>("ALL");
const loading = ref(false);

// mock cảnh báo cho giảng viên
const allWarnings = ref<MyHoursWarning[]>([
  {
    id: "w1",
    level: "CRITICAL",
    message: "Chưa đạt định mức giờ NCKH tối thiểu cho năm học 2024–2025.",
    detail:
      "Hiện tại tổng giờ NCKH của bạn mới đạt khoảng 60% định mức. Vui lòng kiểm tra lại các công trình đã thực hiện nhưng chưa kê khai.",
    suggestedAction:
      "Hoàn tất kê khai các công trình còn thiếu trước ngày 30/06 để tránh bị đánh giá không hoàn thành nghĩa vụ NCKH.",
    academicYear: "2024-2025",
    semester: "HK2",
    read: false,
  },
  {
    id: "w2",
    level: "WARNING",
    message:
      "Sắp đến hạn khóa chức năng kê khai giờ NCKH cho học kỳ 2 (2024–2025).",
    detail:
      "Sau thời hạn quy định, bạn sẽ không thể thêm mới hoặc chỉnh sửa kê khai công trình trong học kỳ này.",
    suggestedAction:
      "Kiểm tra lại toàn bộ công trình đã kê khai, gửi duyệt và tính giờ NCKH trước khi hệ thống khóa.",
    academicYear: "2024-2025",
    semester: "HK2",
    read: false,
  },
  {
    id: "w3",
    level: "INFO",
    message: "Đã có kết quả tính giờ NCKH tạm tính cho năm học 2023–2024.",
    detail:
      "Bạn có thể xem chi tiết phân bổ giờ theo từng công trình trong chức năng Tính giờ NCKH.",
    academicYear: "2023-2024",
    read: true,
  },
]);

const filteredWarnings = computed(() => {
  let data = allWarnings.value.filter(
    (w) => w.academicYear === academicYear.value
  );

  if (statusFilter.value === "READ") {
    data = data.filter((w) => w.read);
  } else if (statusFilter.value === "UNREAD") {
    data = data.filter((w) => !w.read);
  }

  if (levelFilter.value !== "ALL") {
    data = data.filter((w) => w.level === levelFilter.value);
  }

  return data;
});

const unreadCount = computed(
  () => filteredWarnings.value.filter((w) => !w.read).length
);

function reload() {
  loading.value = true;
  setTimeout(() => {
    loading.value = false;
  }, 300);
}

function levelLabel(level: HoursWarningLevel): string {
  switch (level) {
    case "CRITICAL":
      return "Mức nghiêm trọng";
    case "WARNING":
      return "Cần lưu ý";
    case "INFO":
    default:
      return "Thông tin";
  }
}

function levelClass(level: HoursWarningLevel): string {
  switch (level) {
    case "CRITICAL":
      return "border-red-200 bg-red-50 text-red-800";
    case "WARNING":
      return "border-amber-200 bg-amber-50 text-amber-800";
    case "INFO":
    default:
      return "border-sky-200 bg-sky-50 text-sky-800";
  }
}

function badgeClass(level: HoursWarningLevel): string {
  switch (level) {
    case "CRITICAL":
      return "bg-red-100 text-red-700";
    case "WARNING":
      return "bg-amber-100 text-amber-700";
    case "INFO":
    default:
      return "bg-sky-100 text-sky-700";
  }
}
</script>
