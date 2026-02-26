<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Hồ sơ khoa học & Công trình NCKH"
          subtitle="Xem và cập nhật thông tin cá nhân, hồ sơ khoa học; tổng hợp công trình đã duyệt."
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <div class="space-y-4">
        <LecturerPersonalInfoCard
          :profile="personalInfo"
          :pending="saving.personal"
          :loading="loading"
          @update:profile="onUpdatePersonal"
        />

        <LecturerContactInfoCard
          :contact="contactInfo"
          :pending="saving.contact"
          :loading="loading"
          @update:contact="onUpdateContact"
        />

        <LecturerAcademicProfileCard
          :academic-profile="academicProfile"
          :pending="saving.academic"
          :loading="loading"
          @update:academic-profile="onUpdateAcademic"
        />

        <LecturerResearchWorksCard
          :loading="loading"
          :works-by-kind="worksByKind"
          @request-toast="showCardResult"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

import LecturerPersonalInfoCard from "../components/LecturerPersonalInfoCard.vue";
import LecturerContactInfoCard from "../components/LecturerContactInfoCard.vue";
import LecturerAcademicProfileCard from "../components/LecturerAcademicProfileCard.vue";
import LecturerResearchWorksCard from "../components/LecturerResearchWorksCard.vue";

import {
  type LecturerAcademicProfile,
  type LecturerContactInfo,
  type LecturerPersonalInfo,
  type ResearchWorkKind,
  useLecturerProfile,
} from "../composables/useLecturerProfile";

const {
  showSuccessModal,
  showErrorModal,
} = useActionResultModal();

function normalizeProfileError(error: unknown) {
  const raw =
    error instanceof Error
      ? error.message
      : typeof error === "string"
        ? error
        : "";
  const message = raw.split("\n")[0]?.trim() ?? "";

  if (!message) {
    return "Không thể cập nhật thông tin. Vui lòng thử lại.";
  }

  if (message === "Khong tim thay hoc vi trong danh muc.") {
    return "Không tìm thấy học vị trong danh mục. Vui lòng chọn lại.";
  }
  if (message === "Khong tim thay hoc ham trong danh muc.") {
    return "Không tìm thấy học hàm trong danh mục. Vui lòng chọn lại.";
  }
  if (/Unable to update|Unable to load/i.test(message)) {
    return "Không thể cập nhật thông tin. Vui lòng thử lại.";
  }
  if (/Network Error|timeout|ECONN/i.test(message)) {
    return "Không thể kết nối máy chủ. Vui lòng thử lại.";
  }

  return message;
}

function showCardResult(payload: { type: "success" | "error"; message: string }) {
  if (payload.type === "success") {
    showSuccessModal(payload.message);
    return;
  }
  showErrorModal(payload.message, "Có lỗi xảy ra");
}

const saving = reactive({ personal: false, contact: false, academic: false });

const {
  loading,
  personalInfo,
  contactInfo,
  academicProfile,
  works,
  loadProfile,
  updatePersonalInfo,
  updateContactInfo,
  updateAcademicProfile,
} = useLecturerProfile();

const worksByKind = computed<Record<ResearchWorkKind, typeof works.value>>(
  () => {
    const grouped: Record<ResearchWorkKind, typeof works.value> = {
      paper: [],
      project: [],
      book: [],
      conference: [],
    };
    for (const w of works.value) grouped[w.kind].push(w);
    return grouped;
  }
);

async function onUpdatePersonal(next: LecturerPersonalInfo) {
  saving.personal = true;
  try {
    await updatePersonalInfo(next);
    showSuccessModal("Cập nhật thông tin thành công.");
  } catch (e) {
    showErrorModal(normalizeProfileError(e), "Cập nhật thất bại", e);
  } finally {
    saving.personal = false;
  }
}

async function onUpdateContact(next: LecturerContactInfo) {
  saving.contact = true;
  try {
    await updateContactInfo(next);
    showSuccessModal("Cập nhật thông tin thành công.");
  } catch (e) {
    showErrorModal(normalizeProfileError(e), "Cập nhật thất bại", e);
  } finally {
    saving.contact = false;
  }
}

async function onUpdateAcademic(next: LecturerAcademicProfile) {
  saving.academic = true;
  try {
    await updateAcademicProfile(next);
    showSuccessModal("Cập nhật thông tin thành công.");
  } catch (e) {
    showErrorModal(normalizeProfileError(e), "Cập nhật thất bại", e);
  } finally {
    saving.academic = false;
  }
}

onMounted(async () => {
  try {
    await loadProfile();
  } catch (e) {
    showErrorModal(normalizeProfileError(e), "Không thể tải hồ sơ", e);
  }
});
</script>
