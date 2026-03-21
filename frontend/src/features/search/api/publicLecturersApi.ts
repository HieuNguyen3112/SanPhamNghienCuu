import http from "@/lib/http";

export type PublicLecturerItem = {
  id: number;
  code: string;
  full_name: string;
  department_name: string | null;
  degree_name: string | null;
  academic_rank_name: string | null;
  profile: {
    current_unit: string | null;
    current_position: string | null;
    teaching_specialization: string | null;
    research_area: string | null;
  } | null;
  research_works: {
    counts_by_kind: {
      paper: number;
      project: number;
      conference: number;
      book_only: number;
      textbook: number;
      total: number;
    };
  };
};

export type PublicLecturerListRes = {
  success: boolean;
  data: {
    items: PublicLecturerItem[];
    pagination: { page: number; per_page: number; total: number; last_page: number };
  };
};

export type PublicLecturerDetailRes = {
  success: boolean;
  data: {
    lecturer: {
      id: number;
      code: string;
      full_name: string;
      department_name: string | null;
      degree_name: string | null;
      academic_rank_name: string | null;
      email?: string | null;
      phone?: string | null;
    };
    profile: {
      gender?: string | null;
      date_of_birth?: string | null;
      address?: string | null;
      current_position?: string | null;
      current_unit?: string | null;
      research_area?: string | null;
      teaching_specialization?: string | null;
      personal_email?: string | null;
    } | null;
    research_works: {
      counts_by_kind: {
        paper: number;
        project: number;
        conference: number;
        book_only: number;
        textbook: number;
        total: number;
      };
    };
    work_histories: Array<any>;
    educations: Array<any>;
  };
};

export async function fetchPublicLecturers(params: {
  q?: string;
  department_id?: number | null;
  academic_year_id?: number | null;
  page?: number;
  per_page?: number;
}): Promise<PublicLecturerListRes> {
  const { data } = await http.get<PublicLecturerListRes>("/api/public/lecturers", {
    params: {
      q: params.q,
      department_id: params.department_id,
      academic_year_id: params.academic_year_id,
      page: params.page ?? 1,
      per_page: params.per_page ?? 12,
    },
  });

  return data;
}

export async function fetchPublicLecturerDetail(
  code: string
): Promise<PublicLecturerDetailRes> {
  const { data } = await http.get<PublicLecturerDetailRes>(
    `/api/public/lecturers/${encodeURIComponent(code)}`
  );

  return data;
}
