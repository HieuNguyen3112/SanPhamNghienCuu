<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <!-- ONE-LINE FILTER BAR -->
    <div class="overflow-x-auto">
      <div class="flex flex-nowrap items-end gap-3">
        <!-- Khoa -->
        <div class="w-[350px]">
          <label class="text-xs font-medium text-slate-700">Khoa</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="lecturerFilterConditions.selectedDepartmentName"
              @change="
                updateSelectedDepartmentName(
                  ($event.target as HTMLSelectElement).value
                )
              "
            >
              <option value="AllDepartments">Tất cả khoa</option>
              <option
                v-for="departmentName in availableDepartmentNames"
                :key="departmentName"
                :value="departmentName"
              >
                {{ departmentName }}
              </option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Trình độ -->
        <div class="w-[220px]">
          <label class="text-xs font-medium text-slate-700">Trình độ</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="lecturerFilterConditions.selectedEducationLevelCategory"
              @change="
                updateSelectedEducationLevelCategory(
                  ($event.target as HTMLSelectElement).value
                )
              "
            >
              <option value="AllEducationLevels">Tất cả</option>
              <option value="Doctor">Tiến sĩ</option>
              <option value="Master">Thạc sĩ</option>
              <option value="Bachelor">Đại học</option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Học hàm -->
        <div class="w-[220px]">
          <label class="text-xs font-medium text-slate-700">Học hàm</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="lecturerFilterConditions.selectedAcademicRankCategory"
              @change="
                updateSelectedAcademicRankCategory(
                  ($event.target as HTMLSelectElement).value
                )
              "
            >
              <option value="AllAcademicRanks">Tất cả</option>
              <option value="Professor">Giáo sư</option>
              <option value="AssociateProfessor">Phó GS</option>
              <option value="None">Không</option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>

        <!-- Giới tính -->
        <div class="w-[200px]">
          <label class="text-xs font-medium text-slate-700">Giới tính</label>
          <div class="relative mt-1">
            <select
              class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-slate-300 focus:ring-4 focus:ring-slate-100"
              :value="lecturerFilterConditions.selectedGenderCategory"
              @change="
                updateSelectedGenderCategory(
                  ($event.target as HTMLSelectElement).value
                )
              "
            >
              <option value="AllGenders">Tất cả</option>
              <option value="Male">Nam</option>
              <option value="Female">Nữ</option>
              <option value="Other">Khác</option>
            </select>
            <ChevronDown
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
          </div>
        </div>
        <div class="ml-auto w-10">
          <!-- label ẩn để canh đáy giống các ô select -->
          <label class="block text-xs font-medium text-transparent select-none">
            Đặt lại
          </label>

          <button
            type="button"
            class="mt-1 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 active:scale-[0.99]"
            title="Đặt lại bộ lọc"
            @click="
              emitComponentEvent('resetLecturerFilterConditionsRequested')
            "
          >
            <RotateCcw class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  LecturerFilterConditions,
  AcademicRankCategory,
  EducationLevelCategory,
  GenderCategory,
} from "../lecturerStatisticsTypes";
import { RotateCcw, ChevronDown } from "lucide-vue-next";

const componentProperties = defineProps<{
  lecturerFilterConditions: LecturerFilterConditions;
  availableDepartmentNames: string[];
}>();

const emitComponentEvent = defineEmits<{
  (
    eventName: "lecturerFilterConditionsUpdated",
    updatedLecturerFilterConditions: LecturerFilterConditions
  ): void;
  (eventName: "resetLecturerFilterConditionsRequested"): void;
}>();

function updateSelectedDepartmentName(selectedDepartmentName: string) {
  emitComponentEvent("lecturerFilterConditionsUpdated", {
    ...componentProperties.lecturerFilterConditions,
    selectedDepartmentName:
      selectedDepartmentName as LecturerFilterConditions["selectedDepartmentName"],
  });
}

function updateSelectedEducationLevelCategory(
  selectedEducationLevelCategory: string
) {
  emitComponentEvent("lecturerFilterConditionsUpdated", {
    ...componentProperties.lecturerFilterConditions,
    selectedEducationLevelCategory: selectedEducationLevelCategory as
      | EducationLevelCategory
      | "AllEducationLevels",
  });
}

function updateSelectedAcademicRankCategory(
  selectedAcademicRankCategory: string
) {
  emitComponentEvent("lecturerFilterConditionsUpdated", {
    ...componentProperties.lecturerFilterConditions,
    selectedAcademicRankCategory: selectedAcademicRankCategory as
      | AcademicRankCategory
      | "AllAcademicRanks",
  });
}

function updateSelectedGenderCategory(selectedGenderCategory: string) {
  emitComponentEvent("lecturerFilterConditionsUpdated", {
    ...componentProperties.lecturerFilterConditions,
    selectedGenderCategory: selectedGenderCategory as
      | GenderCategory
      | "AllGenders",
  });
}
</script>
