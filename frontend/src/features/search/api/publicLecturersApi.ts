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

export async function fetchPublicLecturers(params: {
  q?: string;
  department_id?: number | null;
  academic_year_id?: number | null;
  page?: number;
  per_page?: number;
}): Promise<PublicLecturerListRes> {
  const sp = new URLSearchParams();
  if (params.q) sp.set("q", params.q);
  if (params.department_id != null) sp.set("department_id", String(params.department_id));
  if (params.academic_year_id != null) sp.set("academic_year_id", String(params.academic_year_id));
  sp.set("page", String(params.page ?? 1));
  sp.set("per_page", String(params.per_page ?? 12));

  const res = await fetch(`/api/public/lecturers?${sp.toString()}`, {
    method: "GET",
    headers: { Accept: "application/json" },
  });

  if (!res.ok) throw new Error(await res.text());
  return res.json();
}