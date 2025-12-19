<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end">
      <!-- Year -->
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-medium text-slate-600">Năm</label>

        <div class="relative">
          <select
            class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
            :value="year"
            @change="emitYear(($event.target as HTMLSelectElement).value)"
          >
            <option value="all">Tất cả</option>
            <option v-for="yearItem in years" :key="yearItem" :value="yearItem">
              {{ yearItem }}
            </option>
          </select>

          <!-- caret -->
          <span
            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
          >
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
        </div>
      </div>

      <!-- Department -->
      <div class="md:col-span-3">
        <label class="mb-1 block text-xs font-medium text-slate-600"
          >Khoa / Đơn vị</label
        >

        <div class="relative">
          <select
            class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
            :value="departmentId"
            @change="emitDepartment(($event.target as HTMLSelectElement).value)"
          >
            <option value="all">Tất cả</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>

          <span
            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
          >
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
        </div>
      </div>

      <!-- Type -->
      <div class="md:col-span-3">
        <label class="mb-1 block text-xs font-medium text-slate-600"
          >Loại công trình</label
        >

        <div class="relative">
          <select
            class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
            :value="researchType"
            @change="emitType(($event.target as HTMLSelectElement).value)"
          >
            <option value="all">Tất cả</option>
            <option
              v-for="typeItem in researchTypes"
              :key="typeItem.value"
              :value="typeItem.value"
            >
              {{ typeItem.label }}
            </option>
          </select>

          <span
            class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
          >
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
        </div>
      </div>

      <!-- Lecturer (Search Select) -->
      <div ref="lecturerBoxRef" class="relative md:col-span-3">
        <label class="mb-1 block text-xs font-medium text-slate-600"
          >Giảng viên</label
        >

        <div class="relative">
          <!-- search icon -->
          <span
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
          >
            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
              <path
                fill-rule="evenodd"
                d="M8.5 3.5a5 5 0 104.03 7.97l2.25 2.25a.75.75 0 101.06-1.06l-2.25-2.25A5 5 0 008.5 3.5zM5 8.5a3.5 3.5 0 117 0 3.5 3.5 0 01-7 0z"
                clip-rule="evenodd"
              />
            </svg>
          </span>

          <input
            class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-24 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
            :value="lecturerInputValue"
            placeholder="Tìm giảng viên..."
            @focus="dropdownOpen = true"
            @input="handleLecturerInput"
            @keydown.escape="dropdownOpen = false"
          />

          <button
            v-if="lecturerId !== 'all'"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50"
            @click="clearLecturer"
          >
            Clear
          </button>
        </div>

        <!-- Dropdown -->
        <div
          v-if="dropdownOpen"
          class="absolute z-10 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
        >
          <div class="max-h-72 overflow-auto p-1">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
              :class="lecturerId === 'all' ? 'bg-slate-50' : ''"
              @click="selectLecturer('all')"
            >
              <span>Tất cả giảng viên</span>
              <span v-if="lecturerId === 'all'" class="text-xs text-slate-400"
                >Đang chọn</span
              >
            </button>

            <div class="my-1 border-t border-slate-100"></div>

            <button
              v-for="lecturer in filteredLecturers"
              :key="lecturer.id"
              type="button"
              class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm hover:bg-slate-50"
              :class="lecturer.id === lecturerId ? 'bg-slate-50' : ''"
              @click="selectLecturer(lecturer.id)"
            >
              <div class="min-w-0">
                <div class="truncate font-medium text-slate-900">
                  {{ lecturer.name }}
                </div>
                <div class="truncate text-xs text-slate-500">
                  {{ deptNameById.get(lecturer.departmentId) }}
                </div>
              </div>

              <span
                v-if="lecturer.id === lecturerId"
                class="shrink-0 text-slate-400"
              >
                ✓
              </span>
            </button>

            <div
              v-if="filteredLecturers.length === 0"
              class="px-3 py-4 text-sm text-slate-500"
            >
              Không tìm thấy giảng viên phù hợp
            </div>
          </div>
        </div>
      </div>

      <!-- Reset -->
      <div class="md:col-span-1">
        <button
          type="button"
          class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
          @click="$emit('reset')"
        >
          Reset
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { Department, Lecturer } from "../useResearchMockData";

type ResearchTypeOption = { value: string; label: string };

const props = defineProps<{
  years: string[];
  departments: Department[];
  researchTypes: ResearchTypeOption[];
  lecturers: Lecturer[];

  year: string;
  departmentId: string;
  researchType: string;
  lecturerId: string;
}>();

const emit = defineEmits<{
  (event: "update:year", value: string): void;
  (event: "update:departmentId", value: string): void;
  (event: "update:researchType", value: string): void;
  (event: "update:lecturerId", value: string): void;
  (event: "reset"): void;
}>();

function emitYear(value: string) {
  emit("update:year", value);
}
function emitDepartment(value: string) {
  emit("update:departmentId", value);
}
function emitType(value: string) {
  emit("update:researchType", value);
}

/** Lecturer search select */
const dropdownOpen = ref(false);
const lecturerQuery = ref("");
const lecturerBoxRef = ref<HTMLElement | null>(null);

const deptNameById = computed(
  () => new Map(props.departments.map((dept) => [dept.id, dept.name]))
);

const selectedLecturerName = computed(() => {
  if (props.lecturerId === "all") return "";
  return props.lecturers.find((lec) => lec.id === props.lecturerId)?.name ?? "";
});

// ✅ show selected lecturer as the input value (not placeholder)
const lecturerInputValue = computed(() => {
  return lecturerQuery.value.length > 0
    ? lecturerQuery.value
    : selectedLecturerName.value;
});

const filteredLecturers = computed(() => {
  const query = lecturerQuery.value.trim().toLowerCase();
  if (!query) return props.lecturers.slice(0, 30);

  return props.lecturers
    .filter((lec) => lec.name.toLowerCase().includes(query))
    .slice(0, 30);
});

function handleLecturerInput(event: Event) {
  const nextValue = (event.target as HTMLInputElement).value;
  lecturerQuery.value = nextValue;
  dropdownOpen.value = true;
}

function selectLecturer(nextLecturerId: string) {
  emit("update:lecturerId", nextLecturerId);
  lecturerQuery.value = "";
  dropdownOpen.value = false;
}

function clearLecturer() {
  emit("update:lecturerId", "all");
  lecturerQuery.value = "";
  dropdownOpen.value = false;
}

/** Close dropdown when clicking outside */
function onDocumentMouseDown(event: MouseEvent) {
  if (!dropdownOpen.value) return;
  const root = lecturerBoxRef.value;
  if (!root) return;
  const target = event.target as Node | null;
  if (target && !root.contains(target)) dropdownOpen.value = false;
}

onMounted(() => document.addEventListener("mousedown", onDocumentMouseDown));
onBeforeUnmount(() =>
  document.removeEventListener("mousedown", onDocumentMouseDown)
);
</script>
