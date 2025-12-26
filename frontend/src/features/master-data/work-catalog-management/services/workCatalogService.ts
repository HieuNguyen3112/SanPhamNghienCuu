import type {
  ConferenceDTO,
  JournalDTO,
  JournalRankingDTO,
  ResearchFieldDTO,
  WorkLevelDTO,
  WorkTypeDTO,
  JournalRankDTO,
} from "../contracts/workCatalog.contract";
import {
  workCatalogMockDb,
  type JournalBaseDTO,
} from "../mock-data/workCatalog.mock";

function delay(ms = 250): Promise<void> {
  return new Promise((r) => setTimeout(r, ms));
}

function nowIso(): string {
  return new Date().toISOString();
}

function todayYmd(): string {
  const d = new Date();
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function clone<T>(v: T): T {
  // structuredClone tốt hơn, fallback JSON cho môi trường không hỗ trợ
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const sc = (globalThis as any).structuredClone as
    | undefined
    | ((x: any) => any);
  if (typeof sc === "function") return sc(v);
  return JSON.parse(JSON.stringify(v)) as T;
}

function nextId(list: Array<{ id: number }>): number {
  return (list.reduce((m, x) => Math.max(m, x.id), 0) || 0) + 1;
}

// ===== Journal ranking helpers =====
function getCurrentJournalRanking(
  journalId: number,
  ymd: string
): { rank: JournalRankDTO; effective_from: string } | null {
  const items = workCatalogMockDb.state.journal_rankings
    .filter((r) => r.journal_id === journalId)
    // chỉ lấy các ranking đã "có hiệu lực" tới hôm nay
    .filter((r) => r.effective_from <= ymd)
    .sort((a, b) => a.effective_from.localeCompare(b.effective_from));

  if (items.length === 0) return null;
  const last = items[items.length - 1];
  if (!last) return null;
  return { rank: last.rank, effective_from: last.effective_from };
}

function toJournalDto(base: JournalBaseDTO): JournalDTO {
  const cur = getCurrentJournalRanking(base.id, todayYmd());
  return {
    ...base,
    current_rank: cur?.rank ?? null,
    current_rank_effective_from: cur?.effective_from ?? null,
  };
}

export const workCatalogService = {
  // ===== LIST =====
  async listWorkTypes(): Promise<WorkTypeDTO[]> {
    await delay();
    return clone(workCatalogMockDb.state.work_types);
  },

  async listWorkLevels(): Promise<WorkLevelDTO[]> {
    await delay();
    return clone(workCatalogMockDb.state.work_levels);
  },

  async listJournals(): Promise<JournalDTO[]> {
    await delay();
    const list = workCatalogMockDb.state.journals.map(toJournalDto);
    return clone(list);
  },

  async listJournalRankings(journalId: number): Promise<JournalRankingDTO[]> {
    await delay();
    const list = workCatalogMockDb.state.journal_rankings
      .filter((x) => x.journal_id === journalId)
      .sort((a, b) => b.effective_from.localeCompare(a.effective_from));
    return clone(list);
  },

  async listConferences(): Promise<ConferenceDTO[]> {
    await delay();
    return clone(workCatalogMockDb.state.conferences);
  },

  async listResearchFields(): Promise<ResearchFieldDTO[]> {
    await delay();
    return clone(workCatalogMockDb.state.research_fields);
  },

  // ===== UPSERT =====
  async upsertWorkType(
    payload: Omit<WorkTypeDTO, "updated_at">
  ): Promise<void> {
    await delay();
    const list = workCatalogMockDb.state.work_types;

    if (!payload.id) {
      list.unshift({
        ...payload,
        id: nextId(list),
        updated_at: nowIso(),
      });
      return;
    }

    const idx = list.findIndex((x) => x.id === payload.id);
    if (idx === -1) throw new Error("Không tìm thấy WorkType để cập nhật.");
    list[idx] = { ...list[idx], ...payload, updated_at: nowIso() };
  },

  async upsertWorkLevel(
    payload: Omit<WorkLevelDTO, "updated_at">
  ): Promise<void> {
    await delay();
    const list = workCatalogMockDb.state.work_levels;

    if (!payload.id) {
      list.unshift({
        ...payload,
        id: nextId(list),
        updated_at: nowIso(),
      });
      return;
    }

    const idx = list.findIndex((x) => x.id === payload.id);
    if (idx === -1) throw new Error("Không tìm thấy WorkLevel để cập nhật.");
    list[idx] = { ...list[idx], ...payload, updated_at: nowIso() };
  },

  async upsertJournal(
    payload: Omit<JournalBaseDTO, "updated_at">
  ): Promise<number> {
    await delay();
    const list = workCatalogMockDb.state.journals;

    if (!payload.id) {
      const id = nextId(list);
      list.unshift({
        ...payload,
        id,
        updated_at: nowIso(),
      });
      return id;
    }

    const idx = list.findIndex((x) => x.id === payload.id);
    if (idx === -1) throw new Error("Không tìm thấy Journal để cập nhật.");
    list[idx] = { ...list[idx], ...payload, updated_at: nowIso() };
    return payload.id;
  },

  async setJournalRanking(payload: {
    journal_id: number;
    rank: JournalRankDTO;
    effective_from: string; // YYYY-MM-DD
    note: string | null;
  }): Promise<void> {
    await delay();
    const journals = workCatalogMockDb.state.journals;
    const j = journals.find((x) => x.id === payload.journal_id);
    if (!j) throw new Error("Không tìm thấy tạp chí để thiết lập hạng.");

    const rankings = workCatalogMockDb.state.journal_rankings;
    rankings.unshift({
      id: nextId(rankings),
      journal_id: payload.journal_id,
      rank: payload.rank,
      effective_from: payload.effective_from,
      note: payload.note,
      created_at: nowIso(),
    });

    // update timestamp để reflect thay đổi
    j.updated_at = nowIso();
  },

  async upsertConference(
    payload: Omit<ConferenceDTO, "updated_at">
  ): Promise<void> {
    await delay();
    const list = workCatalogMockDb.state.conferences;

    if (!payload.id) {
      list.unshift({
        ...payload,
        id: nextId(list),
        updated_at: nowIso(),
      });
      return;
    }

    const idx = list.findIndex((x) => x.id === payload.id);
    if (idx === -1) throw new Error("Không tìm thấy Conference để cập nhật.");
    list[idx] = { ...list[idx], ...payload, updated_at: nowIso() };
  },

  async upsertResearchField(
    payload: Omit<ResearchFieldDTO, "updated_at">
  ): Promise<void> {
    await delay();
    const list = workCatalogMockDb.state.research_fields;

    if (!payload.id) {
      list.unshift({
        ...payload,
        id: nextId(list),
        updated_at: nowIso(),
      });
      return;
    }

    const idx = list.findIndex((x) => x.id === payload.id);
    if (idx === -1)
      throw new Error("Không tìm thấy ResearchField để cập nhật.");
    list[idx] = { ...list[idx], ...payload, updated_at: nowIso() };
  },
};
