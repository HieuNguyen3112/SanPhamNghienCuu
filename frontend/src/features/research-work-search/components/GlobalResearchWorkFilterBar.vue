<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div class="min-w-0">
        <div class="text-base font-semibold text-slate-900 md:text-lg">
          Tra cứu công trình khoa học
        </div>
        <div class="mt-1 text-sm text-slate-600">
          Tìm kiếm công trình NCKH theo tên, giảng viên, khoa, loại, năm...
        </div>
      </div>

      <div class="flex gap-2">
        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="emit('reset')"
        >
          <RotateCcw class="h-4 w-4" />
          Xóa lọc
        </button>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60"
          :disabled="loading"
          @click="emit('search')"
        >
          <Search class="h-4 w-4" />
          Tìm kiếm
        </button>
      </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
      <!-- Keyword -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-700">
          Từ khóa
        </label>
        <div class="relative">
          <Search
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.keyword"
            placeholder="Tên công trình / tạp chí / NXB / DOI..."
            @input="onChangeKeyword"
            @keydown.enter.prevent="emit('search')"
          />
        </div>
      </div>

      <!-- Lecturer -->
      <div class="relative">
        <label class="mb-1 block text-xs font-medium text-slate-700">
          Giảng viên
        </label>

        <div class="relative">
          <User
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.lecturerKeyword"
            placeholder="Nhập tên / mã giảng viên..."
            @input="onChangeLecturerKeyword"
            @focus="isLecturerDropdownOpen = true"
            @keydown.enter.prevent="emit('search')"
          />
        </div>

        <div
          v-if="isLecturerDropdownOpen && lecturerSuggestionList.length > 0"
          class="absolute z-20 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
          @mousedown.prevent
        >
          <button
            v-for="s in lecturerSuggestionList"
            :key="s.lecturer_id"
            type="button"
            class="flex w-full items-start justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-slate-50"
            @click="pickLecturerSuggestion(s)"
          >
            <div class="min-w-0">
              <div class="truncate font-medium text-slate-900">
                {{ s.lecturer_name }}
              </div>
              <div class="mt-0.5 text-xs text-slate-500">
                {{ s.lecturer_code }} • {{ s.unit_name || "Chưa rõ đơn vị" }}
              </div>
            </div>
            <ChevronRight class="mt-1 h-4 w-4 text-slate-400" />
          </button>
        </div>
      </div>

      <!-- Faculty -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-700">
          Khoa / Đơn vị
        </label>
        <select
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
          :value="filter.facultyId ?? ''"
          @change="onChangeFaculty"
        >
          <option value="">Tất cả</option>
          <option v-for="f in facultyOptions" :key="f.id" :value="f.id">
            {{ f.name }}
          </option>
        </select>
      </div>

      <!-- Type -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-700">
          Loại công trình
        </label>
        <select
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
          :value="filter.workTypeId ?? ''"
          @change="onChangeType"
        >
          <option value="">Tất cả</option>
          <option v-for="t in workTypeOptions" :key="t.id" :value="t.id">
            {{ t.name }}
          </option>
        </select>
      </div>

      <!-- Role -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-700">
          Vai trò (của giảng viên lọc)
        </label>
        <select
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
          :value="filter.authorRole ?? ''"
          @change="onChangeRole"
        >
          <option value="">Tất cả</option>
          <option v-for="r in authorRoleOptions" :key="r.id" :value="r.code">
            {{ r.name }}
          </option>
        </select>
      </div>

      <!-- Year range -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-700">
            Từ năm
          </label>
          <select
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.yearFrom ?? ''"
            @change="onChangeYearFrom"
          >
            <option value="">-</option>
            <option v-for="y in yearOptions" :key="y" :value="y">
              {{ y }}
            </option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-slate-700">
            Đến năm
          </label>
          <select
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
            :value="filter.yearTo ?? ''"
            @change="onChangeYearTo"
          >
            <option value="">-</option>
            <option v-for="y in yearOptions" :key="y" :value="y">
              {{ y }}
            </option>
          </select>
        </div>
      </div>

      <!-- Status -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-700">
          Trạng thái
        </label>
        <select
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
          :value="filter.status ?? ''"
          @change="onChangeStatus"
        >
          <option value="">Tất cả</option>
          <option v-for="s in statusOptions" :key="s.id" :value="s.code">
            {{ s.name }}
          </option>
        </select>
      </div>

      <!-- Optional management level -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-700">
          Cấp quản lý (optional)
        </label>
        <select
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:ring-0"
          :value="filter.managementLevel ?? ''"
          @change="onChangeLevel"
        >
          <option value="">Tất cả</option>
          <option
            v-for="lvl in managementLevelOptions"
            :key="lvl.id"
            :value="lvl.code"
          >
            {{ lvl.name }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="resultCountText" class="mt-3 text-xs text-slate-600">
      {{ resultCountText }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from "vue";
import { ChevronRight, RotateCcw, Search, User } from "lucide-vue-next";
import type {
  AuthorRoleOption,
  FacultyOption,
  GlobalResearchWorkSearchFilter,
  LecturerSuggestion,
  ManagementLevelOption,
  StatusOption,
  WorkTypeOption,
} from "../contracts/globalResearchWorkSearch.contract";
import { normalizeText } from "../contracts/globalResearchWorkSearch.contract";

const props = defineProps<{
  filter: GlobalResearchWorkSearchFilter;
  loading: boolean;

  facultyOptions: FacultyOption[];
  workTypeOptions: WorkTypeOption[];
  authorRoleOptions: AuthorRoleOption[];
  statusOptions: StatusOption[];
  managementLevelOptions: ManagementLevelOption[];
  yearOptions: number[];
  lecturerSuggestions: LecturerSuggestion[];

  resultCountText: string;
}>();

const emit = defineEmits<{
  (e: "update:filter", partial: Partial<GlobalResearchWorkSearchFilter>): void;
  (e: "search"): void;
  (e: "reset"): void;
}>();

function onChangeKeyword(event: Event) {
  emit("update:filter", { keyword: (event.target as HTMLInputElement).value });
}

function onChangeLecturerKeyword(event: Event) {
  emit("update:filter", {
    lecturerKeyword: (event.target as HTMLInputElement).value,
  });
}

function onChangeFaculty(event: Event) {
  const v = (event.target as HTMLSelectElement).value;
  emit("update:filter", { facultyId: v ? Number(v) : null });
}

function onChangeType(event: Event) {
  const v = (event.target as HTMLSelectElement).value;
  emit("update:filter", { workTypeId: v ? Number(v) : null });
}

function onChangeRole(event: Event) {
  emit("update:filter", {
    authorRole: (event.target as HTMLSelectElement).value || null,
  });
}

function onChangeYearFrom(event: Event) {
  const v = (event.target as HTMLSelectElement).value;
  emit("update:filter", { yearFrom: v ? Number(v) : null });
}

function onChangeYearTo(event: Event) {
  const v = (event.target as HTMLSelectElement).value;
  emit("update:filter", { yearTo: v ? Number(v) : null });
}

function onChangeStatus(event: Event) {
  emit("update:filter", {
    status: (event.target as HTMLSelectElement).value || null,
  });
}

function onChangeLevel(event: Event) {
  emit("update:filter", {
    managementLevel: (event.target as HTMLSelectElement).value || null,
  });
}

const isLecturerDropdownOpen = ref(false);

const lecturerSuggestionList = computed(() => {
  const kw = normalizeText(props.filter.lecturerKeyword);
  if (!kw) return props.lecturerSuggestions.slice(0, 6);

  return props.lecturerSuggestions
    .filter((s) => {
      const hay = normalizeText(
        `${s.lecturer_name} ${s.lecturer_code} ${s.unit_name ?? ""}`
      );
      return hay.includes(kw);
    })
    .slice(0, 6);
});

function pickLecturerSuggestion(s: LecturerSuggestion) {
  emit("update:filter", {
    lecturerKeyword: `${s.lecturer_name} (${s.lecturer_code})`,
  });
  isLecturerDropdownOpen.value = false;
}

function onGlobalClick() {
  isLecturerDropdownOpen.value = false;
}

window.addEventListener("click", onGlobalClick);
onBeforeUnmount(() => {
  window.removeEventListener("click", onGlobalClick);
});
</script>
