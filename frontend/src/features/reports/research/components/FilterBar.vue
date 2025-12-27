<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <!-- ONE LINE -->
    <div class="overflow-x-auto overflow-y-visible">
      <div class="flex flex-nowrap items-end gap-3">
        <!-- Year -->
        <div class="w-[140px]">
          <label class="mb-1 block text-xs font-medium text-slate-600"
            >Năm</label
          >

          <div class="relative">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="year"
              @change="emitYear(($event.target as HTMLSelectElement).value)"
            >
              <option value="all">Tất cả</option>
              <option
                v-for="yearItem in years"
                :key="yearItem"
                :value="yearItem"
              >
                {{ yearItem }}
              </option>
            </select>

            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Department -->
        <div class="w-[260px]">
          <label class="mb-1 block text-xs font-medium text-slate-600">
            Khoa / Đơn vị
          </label>

          <div class="relative">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="departmentId"
              @change="
                emitDepartment(($event.target as HTMLSelectElement).value)
              "
            >
              <option value="all">Tất cả</option>
              <option
                v-for="dept in departments"
                :key="dept.id"
                :value="dept.id"
              >
                {{ dept.name }}
              </option>
            </select>

            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Type -->
        <div class="w-60">
          <label class="mb-1 block text-xs font-medium text-slate-600">
            Loại công trình
          </label>

          <div class="relative">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
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

            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Lecturer (Search Select) -->
        <div ref="lecturerBoxRef" class="relative z-50 w-[320px]">
          <label class="mb-1 block text-xs font-medium text-slate-600">
            Giảng viên
          </label>

          <div class="relative">
            <Search
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />

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
          <!-- Dropdown (teleport ra body để không bị overflow cắt) -->
          <Teleport to="body">
            <div
              v-if="dropdownOpen"
              ref="dropdownRef"
              class="z-9999 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
              :style="dropdownStyle"
            >
              <div class="max-h-72 overflow-auto p-1">
                <!-- giữ nguyên toàn bộ nội dung dropdown của bạn ở đây -->
                <button
                  type="button"
                  class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                  :class="lecturerId === 'all' ? 'bg-slate-50' : ''"
                  @click="selectLecturer('all')"
                >
                  <span>Tất cả giảng viên</span>
                  <span
                    v-if="lecturerId === 'all'"
                    class="text-xs text-slate-400"
                  >
                    Đang chọn
                  </span>
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
          </Teleport>
        </div>

        <!-- Reset at the END (rightmost) -->
        <div class="ml-auto w-10">
          <!-- giữ chiều cao label để canh đáy -->
          <label
            class="mb-1 block text-xs font-medium text-transparent select-none"
          >
            Reset
          </label>

          <button
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.99]"
            title="Đặt lại bộ lọc"
            @click="$emit('reset')"
          >
            <RotateCcw class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  nextTick,
  watch,
} from "vue";
import type { Department, Lecturer } from "../useResearchMockData";
import { RotateCcw, ChevronDown, Search } from "lucide-vue-next";

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
const dropdownRef = ref<HTMLElement | null>(null);
const dropdownStyle = ref<Record<string, string>>({});

const deptNameById = computed(
  () => new Map(props.departments.map((dept) => [dept.id, dept.name]))
);

const selectedLecturerName = computed(() => {
  if (props.lecturerId === "all") return "";
  return props.lecturers.find((lec) => lec.id === props.lecturerId)?.name ?? "";
});

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

function onDocumentMouseDown(event: MouseEvent) {
  if (!dropdownOpen.value) return;

  const target = event.target as Node | null;
  const box = lecturerBoxRef.value;
  const drop = dropdownRef.value;

  if (target && (box?.contains(target) || drop?.contains(target))) return;

  dropdownOpen.value = false;
}

onMounted(() => {
  document.addEventListener("mousedown", onDocumentMouseDown);
  window.addEventListener("resize", onWindowReposition);
  window.addEventListener("scroll", onWindowReposition, true); // bắt cả scroll của div overflow
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onDocumentMouseDown);
  window.removeEventListener("resize", onWindowReposition);
  window.removeEventListener("scroll", onWindowReposition, true);
});

function updateDropdownPosition() {
  const anchor = lecturerBoxRef.value;
  if (!anchor) return;

  const rect = anchor.getBoundingClientRect();

  // mặc định mở xuống dưới
  let top = rect.bottom + 8;
  let left = rect.left;
  let width = rect.width;

  // nếu gần đáy màn hình quá thì mở lên trên (optional nhưng rất hữu ích)
  const estimatedHeight = 288; // ~ max-h-72
  if (top + estimatedHeight > window.innerHeight - 8) {
    top = Math.max(8, rect.top - 8 - estimatedHeight);
  }

  dropdownStyle.value = {
    position: "fixed",
    left: `${left}px`,
    top: `${top}px`,
    width: `${width}px`,
  };
}

watch(
  () => dropdownOpen.value,
  async (open) => {
    if (!open) return;
    await nextTick();
    updateDropdownPosition();
  }
);

function onWindowReposition() {
  if (!dropdownOpen.value) return;
  updateDropdownPosition();
}
</script>
