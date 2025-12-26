// File: src/features/lecturer/profile/pages/LecturerProfilePage.vue
<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <!-- Header card -->
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

      <!-- Toast -->
      <Transition
        enter-active-class="transition duration-150"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-150"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
      >
        <div
          v-if="toast"
          class="fixed bottom-4 right-4 z-50 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-lg"
        >
          <div
            class="font-semibold"
            :class="
              toast.type === 'success' ? 'text-emerald-700' : 'text-rose-700'
            "
          >
            {{ toast.type === "success" ? "Thành công" : "Có lỗi" }}
          </div>
          <div class="mt-0.5 text-slate-700">{{ toast.message }}</div>
        </div>
      </Transition>

      <!-- ONE COLUMN: mỗi dòng 1 khung -->
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
          @request-toast="showToast"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import PageHeader from "@/shared/components/layout/PageHeader.vue";

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

type ToastState = { type: "success" | "error"; message: string } | null;

const toast = ref<ToastState>(null);
let toastTimer: number | null = null;

function showToast(payload: ToastState) {
  toast.value = payload;
  if (toastTimer) window.clearTimeout(toastTimer);
  toastTimer = window.setTimeout(() => {
    toast.value = null;
    toastTimer = null;
  }, 2200);
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
    showToast({ type: "success", message: "Đã cập nhật thông tin cá nhân." });
  } catch (e) {
    showToast({ type: "error", message: (e as Error).message });
  } finally {
    saving.personal = false;
  }
}

async function onUpdateContact(next: LecturerContactInfo) {
  saving.contact = true;
  try {
    await updateContactInfo(next);
    showToast({ type: "success", message: "Đã cập nhật thông tin liên hệ." });
  } catch (e) {
    showToast({ type: "error", message: (e as Error).message });
  } finally {
    saving.contact = false;
  }
}

async function onUpdateAcademic(next: LecturerAcademicProfile) {
  saving.academic = true;
  try {
    await updateAcademicProfile(next);
    showToast({ type: "success", message: "Đã cập nhật hồ sơ khoa học." });
  } catch (e) {
    showToast({ type: "error", message: (e as Error).message });
  } finally {
    saving.academic = false;
  }
}

onMounted(async () => {
  try {
    await loadProfile();
  } catch (e) {
    showToast({ type: "error", message: (e as Error).message });
  }
});
</script>
