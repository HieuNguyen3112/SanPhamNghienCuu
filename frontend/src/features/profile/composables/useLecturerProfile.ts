// File: src/features/lecturer/profile/composables/useLecturerProfile.ts
import { ref } from "vue";

export type Gender = "male" | "female" | "other";
export type EmploymentType = "full_time" | "visiting";
export type WorkStatus = "active" | "on_leave" | "retired" | "resigned";

export interface LecturerPersonalInfo {
  lecturerCode: string;
  fullName: string;
  birthDate: string | null; // YYYY-MM-DD
  gender: Gender;

  departmentName: string; // read-only
  jobTitle: string;

  employmentType: EmploymentType;
  workStatus: WorkStatus;

  avatarUrl: string | null;
}

export interface LecturerContactInfo {
  officialEmail: string; // read-only
  personalEmail: string;
  phone: string;
  address: string;
  website: string;
  googleScholar: string;
  orcid: string;
}

export interface LanguageRow {
  id: string;
  language: string;
  level: string;
}

export interface LecturerAcademicProfile {
  highestQualification: string;
  major: string;
  academicDegree: string; // VD: TS/ThS...
  academicRank: string; // VD: PGS/GS...
  degreeYear: number | null;
  rankYear: number | null;

  primaryArea: string;
  secondaryArea: string;
  keywords: string[];

  languages: LanguageRow[];
}

export type ResearchWorkKind = "paper" | "project" | "book" | "conference";
export type ResearchWorkStatus =
  | "DRAFT"
  | "SUBMITTED"
  | "APPROVED"
  | "REJECTED";

export interface ResearchWorkItem {
  id: number;
  kind: ResearchWorkKind;
  title: string;
  metaLine: string; // journal/publisher/level/location...
  year: number | null;
  role: string;
  status: ResearchWorkStatus;
}

function delay(ms: number) {
  return new Promise<void>((resolve) => window.setTimeout(resolve, ms));
}

function createId(prefix: string) {
  return `${prefix}_${Math.random().toString(16).slice(2)}_${Date.now()}`;
}

export function useLecturerProfile() {
  const loading = ref(false);

  const personalInfo = ref<LecturerPersonalInfo>({
    lecturerCode: "GV001",
    fullName: "Nguyễn Văn A",
    birthDate: "1988-10-12",
    gender: "male",
    departmentName: "Bộ môn CNTT • Khoa Công nghệ",
    jobTitle: "Giảng viên",
    employmentType: "full_time",
    workStatus: "active",
    avatarUrl: null,
  });

  const contactInfo = ref<LecturerContactInfo>({
    officialEmail: "a.nguyen@university.edu.vn",
    personalEmail: "nguyenvana@gmail.com",
    phone: "0901 234 567",
    address: "12 Nguyễn Trãi, Q.1, TP.HCM",
    website: "https://your-site.com",
    googleScholar: "https://scholar.google.com/citations?user=abc123",
    orcid: "https://orcid.org/0000-0002-1825-0097",
  });

  const academicProfile = ref<LecturerAcademicProfile>({
    highestQualification: "Tiến sĩ",
    major: "Khoa học máy tính",
    academicDegree: "TS",
    academicRank: "PGS",
    degreeYear: 2018,
    rankYear: 2023,
    primaryArea: "Trí tuệ nhân tạo",
    secondaryArea: "Khai phá dữ liệu",
    keywords: ["AI", "MachineLearning", "DataMining"],
    languages: [
      { id: createId("lang"), language: "English", level: "IELTS 7.0" },
      { id: createId("lang"), language: "Japanese", level: "JLPT N3" },
    ],
  });

  const works = ref<ResearchWorkItem[]>([
    {
      id: 101,
      kind: "paper",
      title: "Ứng dụng AI trong phân tích dữ liệu giáo dục",
      metaLine: "Tạp chí Khoa học Trường X",
      year: 2025,
      role: "Tác giả chính",
      status: "DRAFT",
    },
    {
      id: 102,
      kind: "paper",
      title: "A Study on Student Behavior Prediction",
      metaLine: "Proceedings of ABC Conference",
      year: 2024,
      role: "Đồng tác giả",
      status: "APPROVED",
    },
    {
      id: 201,
      kind: "project",
      title: "Hệ thống giám sát chất lượng học tập bằng dữ liệu lớn",
      metaLine: "Cấp Trường",
      year: 2025,
      role: "Chủ nhiệm",
      status: "SUBMITTED",
    },
    {
      id: 301,
      kind: "book",
      title: "Giáo trình Cấu trúc dữ liệu và Giải thuật",
      metaLine: "NXB Giáo dục Việt Nam",
      year: 2023,
      role: "Chủ biên",
      status: "APPROVED",
    },
    {
      id: 401,
      kind: "conference",
      title: "Báo cáo: Ứng dụng LLM trong trợ giảng",
      metaLine: "Hội thảo Khoa học Quốc gia 2025 • Hà Nội",
      year: 2025,
      role: "Báo cáo",
      status: "APPROVED",
    },
  ]);

  async function loadProfile() {
    loading.value = true;
    try {
      await delay(350);
      // MOCK: dữ liệu đã có sẵn
    } finally {
      loading.value = false;
    }
  }

  async function updatePersonalInfo(next: LecturerPersonalInfo) {
    loading.value = true;
    try {
      await delay(400);
      personalInfo.value = { ...next };
    } finally {
      loading.value = false;
    }
  }

  async function updateContactInfo(next: LecturerContactInfo) {
    loading.value = true;
    try {
      await delay(400);
      contactInfo.value = { ...next };
    } finally {
      loading.value = false;
    }
  }

  async function updateAcademicProfile(next: LecturerAcademicProfile) {
    loading.value = true;
    try {
      await delay(450);
      academicProfile.value = { ...next };
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    personalInfo,
    contactInfo,
    academicProfile,
    works,

    loadProfile,
    updatePersonalInfo,
    updateContactInfo,
    updateAcademicProfile,
  };
}
