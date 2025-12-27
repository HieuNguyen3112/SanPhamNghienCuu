import type {
  WorkTypeDTO,
  WorkLevelDTO,
  JournalDTO,
  JournalClassificationDTO,
  JournalRankingDTO,
  JournalRankDTO,
  ConferenceDTO,
  ConferenceLevelDTO,
  ResearchFieldDTO,
} from "../contracts/workCatalog.contract";

function isoDaysAgo(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() - days);
  return d.toISOString();
}

function ymdDaysAgo(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() - days);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

export type JournalBaseDTO = Omit<
  JournalDTO,
  "current_rank" | "current_rank_effective_from"
>;

export const workCatalogMockDb: {
  state: {
    work_types: WorkTypeDTO[];
    work_levels: WorkLevelDTO[];
    journals: JournalBaseDTO[];
    journal_rankings: JournalRankingDTO[];
    conferences: ConferenceDTO[];
    research_fields: ResearchFieldDTO[];
  };
} = {
  state: {
    work_types: [
      {
        id: 1,
        name: "Bài báo khoa học",
        description: "Bài báo đăng tạp chí/kỷ yếu",
        is_active: true,
        updated_at: isoDaysAgo(2),
      },
      {
        id: 2,
        name: "Đề tài nghiên cứu",
        description: "Đề tài cấp khoa/trường/nhà nước",
        is_active: true,
        updated_at: isoDaysAgo(6),
      },
      {
        id: 3,
        name: "Sách / Giáo trình",
        description: "Sách chuyên khảo, giáo trình, tài liệu tham khảo",
        is_active: true,
        updated_at: isoDaysAgo(10),
      },
      {
        id: 4,
        name: "Hội nghị khoa học",
        description: "Tham dự/đăng bài hội nghị",
        is_active: false,
        updated_at: isoDaysAgo(12),
      },
      {
        id: 5,
        name: "Khác",
        description: null,
        is_active: true,
        updated_at: isoDaysAgo(1),
      },
    ],

    work_levels: [
      {
        id: 1,
        name: "Cấp Quốc tế",
        priority: 1,
        notes: "Ưu tiên cao nhất",
        is_active: true,
        updated_at: isoDaysAgo(1),
      },
      {
        id: 2,
        name: "Cấp Quốc gia",
        priority: 2,
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(4),
      },
      {
        id: 3,
        name: "Cấp Trường",
        priority: 3,
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(7),
      },
      {
        id: 4,
        name: "Cấp Khoa",
        priority: 4,
        notes: "Dùng nội bộ",
        is_active: true,
        updated_at: isoDaysAgo(9),
      },
      {
        id: 5,
        name: "Khác",
        priority: 5,
        notes: null,
        is_active: false,
        updated_at: isoDaysAgo(12),
      },
    ],

    journals: [
      {
        id: 1,
        name: "Journal of Advanced Research",
        address: "Cairo University, Giza, Egypt",
        issn: "2090-1232",
        classification: "ISI" as JournalClassificationDTO,
        country: "Egypt",
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(2),
      },
      {
        id: 2,
        name: "Vietnam Journal of Science and Technology",
        address: "Vietnam Academy of Science and Technology, Hanoi, Vietnam",
        issn: "0866-708X",
        classification: "SCOPUS" as JournalClassificationDTO,
        country: "Vietnam",
        notes: "Có index Scopus một số năm.",
        is_active: true,
        updated_at: isoDaysAgo(5),
      },
      {
        id: 3,
        name: "International Journal of Computer Science",
        address: "USA (publisher address pending verification)",
        issn: null,
        classification: "OTHER" as JournalClassificationDTO,
        country: "USA",
        notes: "Cần xác minh ISSN.",
        is_active: false,
        updated_at: isoDaysAgo(9),
      },
      {
        id: 4,
        name: "IEEE Access",
        address: "IEEE, Piscataway, NJ, USA",
        issn: "2169-3536",
        classification: "ISI" as JournalClassificationDTO,
        country: "USA",
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(1),
      },
      {
        id: 5,
        name: "ACM Computing Surveys",
        address: "ACM, New York, USA",
        issn: "0360-0300",
        classification: "ISI" as JournalClassificationDTO,
        country: "USA",
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(3),
      },
      {
        id: 6,
        name: "Tạp chí Khoa học Trường X",
        address: "Trường X, TP.HCM, Việt Nam",
        issn: null,
        classification: "OTHER" as JournalClassificationDTO,
        country: "Vietnam",
        notes: "Tạp chí nội bộ.",
        is_active: true,
        updated_at: isoDaysAgo(15),
      },
    ],

    journal_rankings: [
      {
        id: 1,
        journal_id: 1,
        rank: "Q2" as JournalRankDTO,
        effective_from: ymdDaysAgo(180),
        note: "Theo danh mục năm trước",
        created_at: isoDaysAgo(60),
      },
      {
        id: 2,
        journal_id: 1,
        rank: "Q1" as JournalRankDTO,
        effective_from: ymdDaysAgo(20),
        note: "Cập nhật hạng mới",
        created_at: isoDaysAgo(2),
      },
      {
        id: 3,
        journal_id: 2,
        rank: "Q3" as JournalRankDTO,
        effective_from: ymdDaysAgo(90),
        note: null,
        created_at: isoDaysAgo(5),
      },
      {
        id: 4,
        journal_id: 4,
        rank: "Q1" as JournalRankDTO,
        effective_from: ymdDaysAgo(30),
        note: null,
        created_at: isoDaysAgo(1),
      },
      {
        id: 5,
        journal_id: 5,
        rank: "Q1" as JournalRankDTO,
        effective_from: ymdDaysAgo(365),
        note: "Ổn định nhiều năm",
        created_at: isoDaysAgo(3),
      },
    ],

    conferences: [
      {
        id: 1,
        name: "International Conference on AI (ICAI)",
        level: "INTERNATIONAL" as ConferenceLevelDTO,
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(2),
      },
      {
        id: 2,
        name: "Hội nghị Khoa học Trường X",
        level: "UNIVERSITY" as ConferenceLevelDTO,
        notes: "Hội nghị thường niên.",
        is_active: true,
        updated_at: isoDaysAgo(7),
      },
      {
        id: 3,
        name: "Hội thảo nghiên cứu cấp Khoa CNTT",
        level: "FACULTY" as ConferenceLevelDTO,
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(10),
      },
      {
        id: 4,
        name: "National Symposium on Education",
        level: "NATIONAL" as ConferenceLevelDTO,
        notes: null,
        is_active: false,
        updated_at: isoDaysAgo(12),
      },
      {
        id: 5,
        name: "Workshop on Data Science",
        level: "INTERNATIONAL" as ConferenceLevelDTO,
        notes: null,
        is_active: true,
        updated_at: isoDaysAgo(3),
      },
    ],

    research_fields: [
      {
        id: 1,
        code: "AI",
        name: "Trí tuệ nhân tạo",
        description: "Machine Learning, NLP, CV, v.v.",
        is_active: true,
        updated_at: isoDaysAgo(1),
      },
      {
        id: 2,
        code: "EDU",
        name: "Khoa học giáo dục",
        description: null,
        is_active: true,
        updated_at: isoDaysAgo(3),
      },
      {
        id: 3,
        code: "SE",
        name: "Công nghệ phần mềm",
        description: null,
        is_active: true,
        updated_at: isoDaysAgo(6),
      },
      {
        id: 4,
        code: null,
        name: "Kinh tế",
        description: null,
        is_active: false,
        updated_at: isoDaysAgo(9),
      },
      {
        id: 5,
        code: "BIO",
        name: "Công nghệ sinh học",
        description: null,
        is_active: true,
        updated_at: isoDaysAgo(11),
      },
    ],
  },
};
