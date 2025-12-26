<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="grid gap-3 md:grid-cols-12 md:items-end">
      <!-- Faculty -->
      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Khoa/Đơn vị</label>
        <div class="relative mt-1">
          <select
            class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2 pr-9 text-sm focus:border-slate-300 focus:outline-none"
            :disabled="facultySelectLocked || loading"
            :value="filter.selectedFacultyIdentifier"
            @change="onChangeFaculty"
          >
            <option value="ALL_FACULTIES" v-if="!facultySelectLocked">
              Tất cả
            </option>
            <option
              v-for="f in facultyOptions"
              :key="f.facultyIdentifier"
              :value="f.facultyIdentifier"
            >
              {{ f.facultyShortName }} — {{ f.facultyFullName }}
            </option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
        </div>
      </div>

      <!-- Academic year -->
      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Năm học</label>
        <div class="relative mt-1">
          <select
            class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2 pr-9 text-sm focus:border-slate-300 focus:outline-none"
            :disabled="loading"
            :value="filter.selectedAcademicYearIdentifier"
            @change="onChangeYear"
          >
            <option
              v-for="y in academicYearOptions"
              :key="y.academicYearIdentifier"
              :value="y.academicYearIdentifier"
            >
              {{ y.label }}
            </option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
        </div>
      </div>

      <!-- Severity -->
      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Mức thiếu</label>
        <div class="relative mt-1">
          <select
            class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2 pr-9 text-sm focus:border-slate-300 focus:outline-none"
            :disabled="loading"
            :value="filter.severityFilter"
            @change="onChangeSeverity"
          >
            <option value="ALL">Tất cả</option>
            <option value="SEVERE">Thiếu nhiều</option>
            <option value="MODERATE">Thiếu vừa</option>
            <option value="MILD">Thiếu ít</option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
        </div>
      </div>

      <!-- Notification state -->
      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600"
          >Trạng thái cảnh báo</label
        >
        <div class="relative mt-1">
          <select
            class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2 pr-9 text-sm focus:border-slate-300 focus:outline-none"
            :disabled="loading"
            :value="filter.notificationStateFilter"
            @change="onChangeNotifyState"
          >
            <option value="ALL">Tất cả</option>
            <option value="NOT_REQUESTED">Chưa cảnh báo</option>
            <option value="REQUESTED">Đã cảnh báo</option>
          </select>
          <ChevronDown
            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
        </div>
      </div>

      <!-- Keyword -->
      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Tìm giảng viên</label>
        <div class="relative mt-1">
          <Search
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-300 focus:outline-none"
            :disabled="loading"
            :value="filter.keyword"
            placeholder="Mã GV / họ tên…"
            @input="onChangeKeyword"
          />
        </div>
        <!-- Reset -->
      </div>
      <div class="md:col-span-2 md:flex md:justify-end">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="$emit('reset')"
        >
          <RotateCcw class="h-4 w-4" />
          Đặt lại
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ChevronDown, RotateCcw, Search } from "lucide-vue-next";
import type {
  AcademicYearOption,
  FacultyOption,
} from "../contracts/lecturerResearchHourWarning.contract";
import type { ResearchHourWarningFilterState } from "../composables/useResearchHourWarningManagement";

const props = defineProps<{
  filter: ResearchHourWarningFilterState;
  loading: boolean;

  facultyOptions: FacultyOption[];
  academicYearOptions: AcademicYearOption[];
  facultySelectLocked: boolean;
}>();

defineEmits<{
  (e: "update:filter", partial: Partial<ResearchHourWarningFilterState>): void;
  (e: "reset"): void;
}>();

function onChangeFaculty(e: Event) {
  const v = (e.target as HTMLSelectElement)
    .value as ResearchHourWarningFilterState["selectedFacultyIdentifier"];
  (props as any).$emit?.("update:filter", { selectedFacultyIdentifier: v });
}

function onChangeYear(e: Event) {
  const v = (e.target as HTMLSelectElement)
    .value as ResearchHourWarningFilterState["selectedAcademicYearIdentifier"];
  (props as any).$emit?.("update:filter", {
    selectedAcademicYearIdentifier: v,
  });
}

function onChangeSeverity(e: Event) {
  const v = (e.target as HTMLSelectElement)
    .value as ResearchHourWarningFilterState["severityFilter"];
  (props as any).$emit?.("update:filter", { severityFilter: v });
}

function onChangeNotifyState(e: Event) {
  const v = (e.target as HTMLSelectElement)
    .value as ResearchHourWarningFilterState["notificationStateFilter"];
  (props as any).$emit?.("update:filter", { notificationStateFilter: v });
}

function onChangeKeyword(e: Event) {
  const v = (e.target as HTMLInputElement).value;
  (props as any).$emit?.("update:filter", { keyword: v });
}
</script>
