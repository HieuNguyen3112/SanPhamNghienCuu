<template>
  <div class="space-y-6">
    <!-- === PHẦN TIÊU ĐỀ PAGE === -->
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Hồ sơ khoa học</h1>
      <p class="mt-1 text-sm text-slate-500">
        Tổng quan thông tin hồ sơ khoa học của giảng viên: liên hệ, công tác,
        đào tạo, lĩnh vực nghiên cứu, học vị – chức danh và trình độ ngoại ngữ.
      </p>
    </header>

    <!-- === GRID 2 CỘT === -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- ====== THÔNG TIN GIẢNG VIÊN ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Thông tin giảng viên
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Họ và tên:</strong> <span class="ml-1">{{ displayValue(profile.fullName) }}</span></p>
          <p><strong>Giới tính:</strong> <span class="ml-1">{{ displayValue(profile.gender) }}</span></p>
          <p><strong>Ngày sinh:</strong> <span class="ml-1">{{ displayValue(profile.birthDate) }}</span></p>
          <p><strong>Nơi sinh:</strong> <span class="ml-1">{{ displayValue(profile.birthPlace) }}</span></p>
          <p><strong>Dân tộc:</strong> <span class="ml-1">{{ displayValue(profile.ethnicity) }}</span></p>
          <p><strong>Quê quán:</strong> <span class="ml-1">{{ displayValue(profile.hometown) }}</span></p>
          <p><strong>Chức vụ:</strong> <span class="ml-1">{{ displayValue(profile.position) }}</span></p>
          <p><strong>Đơn vị công tác:</strong> <span class="ml-1">{{ displayValue(profile.department) }}</span></p>
          <p><strong>Địa chỉ liên hệ:</strong> <span class="ml-1">{{ displayValue(profile.address) }}</span></p>
          <p><strong>Học vị:</strong> <span class="ml-1">{{ displayValue(profile.degree) }}</span></p>
          <p><strong>Chức danh:</strong> <span class="ml-1">{{ displayValue(profile.academicTitle) }}</span></p>
          <p><strong>Chuyên ngành giảng dạy:</strong> <span class="ml-1">{{ displayValue(profile.teachingSpecialty) }}</span></p>
          <p><strong>Lĩnh vực nghiên cứu:</strong> <span class="ml-1">{{ displayValue(profile.researchAreas) }}</span></p>
          <p><strong>Ngoại ngữ:</strong> <span class="ml-1">{{ displayValue(profile.languages) }}</span></p>
        </div>
      </section>

      <!-- ====== QUÁ TRÌNH CÔNG TÁC ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Quá trình công tác
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Nơi công tác:</strong> <span class="ml-1">{{ displayValue(profile.workSummary) }}</span></p>
        </div>
      </section>

      <!-- ====== QUÁ TRÌNH ĐÀO TẠO ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Quá trình đào tạo
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Nơi đào tạo:</strong> <span class="ml-1">{{ displayValue(profile.educationSummary) }}</span></p>
        </div>
      </section>

      <!-- ====== THÔNG TIN ĐẢNG ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Thông tin Đảng
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Đảng viên:</strong> <span class="ml-1">{{ displayValue(profile.partyMember) }}</span></p>
          <p><strong>Ngày vào Đảng:</strong> <span class="ml-1">{{ displayValue(profile.partyJoinedAt) }}</span></p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive } from "vue";
import { fetchProfile } from "@/features/profile/api";

interface ScientificProfile {
  fullName: string;
  gender: string;
  birthDate: string;
  birthPlace: string;
  ethnicity: string;
  hometown: string;
  position: string;
  department: string;
  address: string;
  degree: string;
  academicTitle: string;
  teachingSpecialty: string;
  researchAreas: string;
  languages: string;
  workSummary: string;
  educationSummary: string;
  partyMember: string;
  partyJoinedAt: string;
}

const profile = reactive<ScientificProfile>({
  fullName: "",
  gender: "",
  birthDate: "",
  birthPlace: "",
  ethnicity: "",
  hometown: "",
  position: "",
  department: "",
  address: "",
  degree: "",
  academicTitle: "",
  teachingSpecialty: "",
  researchAreas: "",
  languages: "",
  workSummary: "",
  educationSummary: "",
  partyMember: "",
  partyJoinedAt: "",
});

const dash = "-";

const displayValue = (value?: string | null) => {
  if (value === null || value === undefined) {
    return dash;
  }

  const trimmed = String(value).trim();
  return trimmed ? trimmed : dash;
};

const formatLanguages = (items: any[]): string => {
  if (!Array.isArray(items) || items.length === 0) {
    return "";
  }

  return items
    .map((item) => {
      if (!item) return "";
      const label = item.language || "";
      const details = [item.level, item.certificate_name, item.certificate_score]
        .filter(Boolean)
        .join(", ");
      return details ? `${label} (${details})` : label;
    })
    .filter(Boolean)
    .join("; ");
};

const formatWorkSummary = (work: any): string => {
  if (!work) return "";
  return [work.organization, work.department, work.position]
    .filter(Boolean)
    .join(" - ");
};

const formatEducationSummary = (education: any): string => {
  if (!education) return "";
  const degreeLabel = education.degree_name || education.degree_title || "";
  return [education.institution, education.major, degreeLabel]
    .filter(Boolean)
    .join(" - ");
};

onMounted(async () => {
  try {
    const { data } = await fetchProfile();
    const payload = data?.data ?? {};
    const lec = payload.lecturer ?? {};
    const prof = payload.profile ?? {};
    const party = payload.party_membership ?? {};
    const work = payload.latest_work_history ?? null;
    const education = payload.latest_education ?? null;

    profile.fullName = lec.full_name || payload.user?.name || "";
    profile.gender = prof.gender || "";
    profile.birthDate = prof.date_of_birth || "";
    profile.birthPlace = prof.place_of_birth || "";
    profile.ethnicity = prof.ethnicity || "";
    profile.hometown = prof.hometown || "";
    profile.position = prof.current_position || "";
    profile.department = prof.current_unit || lec.department_name || "";
    profile.address = prof.address || "";
    profile.degree = lec.degree_name || "";
    profile.academicTitle = lec.academic_rank_name || "";
    profile.teachingSpecialty = prof.teaching_specialization || "";
    profile.researchAreas = prof.research_area || "";
    profile.languages = formatLanguages(payload.languages ?? []);
    profile.workSummary = formatWorkSummary(work);
    profile.educationSummary = formatEducationSummary(education);
    profile.partyMember = party?.is_member ? "Co" : "Khong";
    profile.partyJoinedAt = party?.joined_at || "";
  } catch (e) {
    // keep defaults when API fails
  }
});
</script>
