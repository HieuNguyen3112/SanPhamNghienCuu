<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
      <div class="space-y-1">
        <label class="text-xs font-medium text-slate-700">Khoa</label>
        <select
          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none"
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
      </div>

      <div class="space-y-1">
        <label class="text-xs font-medium text-slate-700"
          >Trình độ học vấn</label
        >
        <select
          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none"
          :value="lecturerFilterConditions.selectedEducationLevelCategory"
          @change="
            updateSelectedEducationLevelCategory(
              ($event.target as HTMLSelectElement).value
            )
          "
        >
          <option value="AllEducationLevels">Tất cả trình độ</option>
          <option value="Doctor">Tiến sĩ</option>
          <option value="Master">Thạc sĩ</option>
          <option value="Bachelor">Đại học</option>
        </select>
      </div>

      <div class="space-y-1">
        <label class="text-xs font-medium text-slate-700">Học hàm</label>
        <select
          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none"
          :value="lecturerFilterConditions.selectedAcademicRankCategory"
          @change="
            updateSelectedAcademicRankCategory(
              ($event.target as HTMLSelectElement).value
            )
          "
        >
          <option value="AllAcademicRanks">Tất cả học hàm</option>
          <option value="Professor">Giáo sư</option>
          <option value="AssociateProfessor">Phó Giáo sư</option>
          <option value="None">Không</option>
        </select>
      </div>

      <div class="space-y-1">
        <label class="text-xs font-medium text-slate-700">Giới tính</label>
        <select
          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none"
          :value="lecturerFilterConditions.selectedGenderCategory"
          @change="
            updateSelectedGenderCategory(
              ($event.target as HTMLSelectElement).value
            )
          "
        >
          <option value="AllGenders">Tất cả giới tính</option>
          <option value="Male">Nam</option>
          <option value="Female">Nữ</option>
          <option value="Other">Khác</option>
        </select>
      </div>

      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50"
          @click="emitComponentEvent('resetLecturerFilterConditionsRequested')"
        >
          Đặt lại bộ lọc
        </button>
      </div>
    </div>

    <p class="mt-3 text-xs text-slate-500">
      <!-- Vì bộ lọc được đặt ở một hàng ngang, người quản trị có thể rà soát nhanh toàn bộ điều kiện trước khi đọc số liệu -->
      Bộ lọc theo khoa, trình độ, học hàm và giới tính được hiển thị trên một
      hàng để giảm thao tác cuộn trang.
    </p>
  </div>
</template>

<script setup lang="ts">
import type {
  LecturerFilterConditions,
  AcademicRankCategory,
  EducationLevelCategory,
  GenderCategory,
} from "../lecturerStatisticsTypes";

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
