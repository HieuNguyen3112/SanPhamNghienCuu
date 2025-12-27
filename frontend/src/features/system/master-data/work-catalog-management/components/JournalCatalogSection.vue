<template>
  <div class="space-y-3">
    <!-- Toolbar -->
    <div
      class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm text-slate-600">
        Đang hiển thị
        <span class="font-semibold text-slate-900">Tạp chí khoa học</span>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <input
          :value="search"
          @input="
            emit('update:search', ($event.target as HTMLInputElement).value)
          "
          type="text"
          class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-300 sm:w-80"
          placeholder="Tìm theo tên/ISSN..."
        />

        <button
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-semibold text-white hover:bg-slate-800"
          @click="emit('create')"
        >
          <Plus class="h-4 w-4" />
          Thêm tạp chí khoa học
        </button>
      </div>
    </div>

    <!-- Table -->
    <div
      class="max-h-[560px] overflow-auto rounded-2xl border border-slate-200"
    >
      <table class="w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr>
            <th class="w-16 px-4 py-3">STT</th>
            <th class="px-4 py-3">Tên tạp chí</th>
            <th class="w-36 px-4 py-3">ISSN</th>
            <th class="w-[280px] px-4 py-3">Địa chỉ</th>
            <th class="w-32 px-4 py-3">Hạng hiện tại</th>
            <th class="w-36 px-4 py-3">Ngày áp dụng</th>
            <th class="w-32 px-4 py-3">Trạng thái</th>
            <th class="w-28 px-4 py-3 text-right">Thao tác</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200">
          <tr
            v-for="(row, idx) in rows"
            :key="row.id"
            class="hover:bg-slate-50"
          >
            <td class="px-4 py-3 text-slate-600">{{ startIndex + idx + 1 }}</td>

            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900">{{ row.name }}</div>
              <div v-if="row.notes" class="mt-1 text-xs text-slate-500">
                {{ row.notes }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">{{ row.issn ?? "—" }}</td>

            <td class="px-4 py-3 text-slate-700">
              <!-- fallback để không vỡ khi backend chưa có address -->
              <div class="line-clamp-2 whitespace-pre-wrap">
                {{ row.address ?? row.country ?? "—" }}
              </div>
            </td>

            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                :class="rankBadgeClass(row.currentRank ?? null)"
              >
                {{ row.currentRank ?? "—" }}
              </span>
            </td>

            <td class="px-4 py-3 text-slate-600">
              {{
                row.currentRankEffectiveFrom
                  ? formatDate(row.currentRankEffectiveFrom)
                  : "—"
              }}
            </td>

            <td class="px-4 py-3">
              <span
                class="rounded-full px-2 py-1 text-xs font-semibold"
                :class="
                  row.isActive
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-slate-100 text-slate-600'
                "
              >
                {{ row.isActive ? "Đang dùng" : "Ngừng dùng" }}
              </span>
            </td>

            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <!-- Set Ranking: luôn hiện nút, nhưng modal có thể chưa wiring -->
                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                  title="Thiết lập hạng"
                  @click="emit('set-ranking', row.id)"
                >
                  <TrendingUp class="h-4 w-4" />
                </button>

                <button
                  type="button"
                  class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                  title="Sửa thông tin"
                  @click="emit('edit', row.id)"
                >
                  <Pencil class="h-4 w-4" />
                </button>
              </div>
            </td>
          </tr>

          <tr v-if="rows.length === 0">
            <td
              colspan="8"
              class="px-4 py-10 text-center text-sm text-slate-500"
            >
              Không có dữ liệu phù hợp.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-end">
      <!-- TODO: chỉnh import/path + props theo SharedPaginationControls thật của dự án -->
      <!-- <SharedPaginationControls
        :page="page"
        :page-size="pageSize"
        :total="total"
        @update:page="emit('update:page', $event)"
        @update:page-size="emit('update:pageSize', $event)"
      /> -->
    </div>

    <!-- Modal: Add/Edit Journal -->
    <CatalogUpsertModal
      :open="modalOpen"
      :title="modalTitle"
      :submitting="submitting"
      @close="emit('close-modal')"
      @submit="emit('submit')"
    >
      <div class="grid gap-3">
        <div>
          <label class="text-xs font-medium text-slate-600"
            >Tên tạp chí *</label
          >
          <input
            :value="form.name"
            @input="
              emit('update:form', {
                ...form,
                name: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="errors.name" class="mt-1 text-xs text-rose-600">
            {{ errors.name }}
          </p>
        </div>

        <!-- Address: thêm field mới, nhưng vẫn optional để không vỡ form cũ -->
        <div>
          <label class="text-xs font-medium text-slate-600">Địa chỉ</label>
          <input
            :value="form.address ?? ''"
            @input="
              emit('update:form', {
                ...form,
                address: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            placeholder="VD: 123 Đường ABC, Quận 1, TP.HCM"
          />
          <p v-if="errors.address" class="mt-1 text-xs text-rose-600">
            {{ errors.address }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600">ISSN</label>
            <input
              :value="form.issn"
              @input="
                emit('update:form', {
                  ...form,
                  issn: ($event.target as HTMLInputElement).value,
                })
              "
              type="text"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            />
            <p v-if="errors.issn" class="mt-1 text-xs text-rose-600">
              {{ errors.issn }}
            </p>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600">Trạng thái</label>
            <div class="mt-2 flex items-center gap-3">
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  :checked="form.isActive"
                  @change="emit('update:form', { ...form, isActive: true })"
                />
                Đang sử dụng
              </label>
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  :checked="!form.isActive"
                  @change="emit('update:form', { ...form, isActive: false })"
                />
                Ngừng sử dụng
              </label>
            </div>
          </div>
        </div>
        <!-- Current ranking (read-only) -->
        <div
          class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="font-semibold text-slate-900">Hạng hiện tại</div>

              <div class="mt-1 flex flex-wrap items-center gap-2">
                <span
                  class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                  :class="rankBadgeClass(activeRank ?? null)"
                >
                  {{ activeRank ?? "—" }}
                </span>

                <span class="text-slate-500">
                  {{ activeRankEffectiveFromText }}
                </span>
              </div>

              <div v-if="form.id === 0" class="mt-1 text-slate-500">
                Cần lưu tạp chí trước khi thiết lập hạng (để ghi lịch sử theo
                ngày áp dụng).
              </div>
            </div>

            <button
              type="button"
              class="inline-flex h-9 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="form.id === 0"
              @click="form.id !== 0 && emit('set-ranking', form.id)"
              :title="form.id === 0 ? 'Lưu tạp chí trước' : 'Thiết lập hạng'"
            >
              <TrendingUp class="h-4 w-4" />
              Thiết lập hạng
            </button>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Ghi chú</label>
          <textarea
            :value="form.notes"
            @input="
              emit('update:form', {
                ...form,
                notes: ($event.target as HTMLTextAreaElement).value,
              })
            "
            rows="3"
            class="mt-1 w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="errors.notes" class="mt-1 text-xs text-rose-600">
            {{ errors.notes }}
          </p>
        </div>

        <div
          class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
        >
          Hạng (Q1/Q2/...) được thiết lập ở màn hình
          <span class="font-semibold">Thiết lập hạng</span> để đảm bảo lưu lịch
          sử theo ngày áp dụng.
          <span class="ml-1 text-slate-500"
            >(Nếu backend chưa có, phần này đang để TODO.)</span
          >
        </div>
      </div>
    </CatalogUpsertModal>

    <!-- Modal: Set Ranking (optional wiring) -->
    <CatalogUpsertModal
      v-if="rankingEnabled"
      :open="rankingModalOpen"
      :title="rankingModalTitle"
      :submitting="submitting"
      subtitle="Thiết lập hạng mới theo ngày áp dụng. Hệ thống sẽ lưu lịch sử hạng theo thời gian."
      @close="emit('close-ranking-modal')"
      @submit="emit('submit-ranking')"
    >
      <div class="grid gap-3">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600">Hạng *</label>
            <select
              :value="rankingForm.rank"
              @change="
                emit('update:rankingForm', {
                  ...rankingForm,
                  rank: ($event.target as HTMLSelectElement)
                    .value as JournalRank,
                })
              "
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            >
              <option value="Q1">Q1</option>
              <option value="Q2">Q2</option>
              <option value="Q3">Q3</option>
              <option value="Q4">Q4</option>
              <option value="Q5">Q5</option>
              <option value="OTHER">Khác</option>
            </select>
            <p v-if="rankingErrors.rank" class="mt-1 text-xs text-rose-600">
              {{ rankingErrors.rank }}
            </p>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Ngày áp dụng *</label
            >
            <input
              :value="rankingForm.effectiveFrom"
              @input="
                emit('update:rankingForm', {
                  ...rankingForm,
                  effectiveFrom: ($event.target as HTMLInputElement).value,
                })
              "
              type="date"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            />
            <p
              v-if="rankingErrors.effectiveFrom"
              class="mt-1 text-xs text-rose-600"
            >
              {{ rankingErrors.effectiveFrom }}
            </p>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Ghi chú</label>
          <textarea
            :value="rankingForm.note"
            @input="
              emit('update:rankingForm', {
                ...rankingForm,
                note: ($event.target as HTMLTextAreaElement).value,
              })
            "
            rows="3"
            class="mt-1 w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="rankingErrors.note" class="mt-1 text-xs text-rose-600">
            {{ rankingErrors.note }}
          </p>
        </div>

        <div
          class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
          Lưu ý: Thiết lập hạng mới cần đúng “ngày áp dụng” để map điểm công
          trình theo thời điểm.
        </div>
      </div>
    </CatalogUpsertModal>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Pencil, Plus, TrendingUp } from "lucide-vue-next";
import CatalogUpsertModal from "./CatalogUpsertModal.vue";

// TODO: chỉnh path theo project của bạn

export type JournalRank = "Q1" | "Q2" | "Q3" | "Q4" | "Q5" | "OTHER";

export interface JournalRow {
  id: number;
  name: string;
  issn: string | null;
  notes: string | null;
  isActive: boolean;

  /** NEW (optional for migration) */
  address?: string | null;
  currentRank?: JournalRank | null;
  currentRankEffectiveFrom?: string | null;

  /** Legacy optional: để map tạm từ data cũ nếu cần */
  country?: string | null;
  updatedAt?: string;
}

type JournalFormModel = {
  id: number;
  name: string;
  issn: string;
  notes: string;
  isActive: boolean;
  /** NEW optional (để không vỡ form cũ) */
  address?: string;
};

type JournalFormErrors = Partial<
  Record<"name" | "address" | "issn" | "notes", string>
>;

type RankingFormModel = {
  journalId: number;
  rank: JournalRank;
  effectiveFrom: string;
  note: string;
};
type RankingErrors = Partial<Record<"rank" | "effectiveFrom" | "note", string>>;

const props = withDefaults(
  defineProps<{
    // table
    rows: JournalRow[];
    startIndex: number;
    total: number;
    search: string;
    page: number;
    pageSize: number;

    // journal modal
    modalOpen: boolean;
    modalTitle: string;
    submitting: boolean;
    form: JournalFormModel;
    errors: JournalFormErrors;

    // ranking modal (optional wiring)
    rankingModalOpen?: boolean;
    rankingModalTitle?: string;
    rankingForm?: RankingFormModel;
    rankingErrors?: RankingErrors;
  }>(),
  {
    rankingModalOpen: false,
    rankingModalTitle: "Thiết lập hạng tạp chí",
    rankingForm: () => ({
      journalId: 0,
      rank: "Q4",
      effectiveFrom: "",
      note: "",
    }),
    rankingErrors: () => ({}),
  }
);
const activeRow = computed(() =>
  props.rows.find((x) => x.id === props.form.id)
);

const activeRank = computed<JournalRank | null>(() => {
  return activeRow.value?.currentRank ?? null;
});

const activeRankEffectiveFromText = computed(() => {
  const from = activeRow.value?.currentRankEffectiveFrom;
  if (!from) return "";
  return `Từ ${formatDate(from)}`;
});

const emit = defineEmits<{
  // list
  (e: "update:search", v: string): void;
  (e: "update:page", v: number): void;
  (e: "update:pageSize", v: number): void;

  // journal actions
  (e: "create"): void;
  (e: "edit", id: number): void;
  (e: "close-modal"): void;
  (e: "submit"): void;
  (e: "update:form", v: JournalFormModel): void;

  // ranking actions (optional)
  (e: "set-ranking", id: number): void;
  (e: "close-ranking-modal"): void;
  (e: "submit-ranking"): void;
  (e: "update:rankingForm", v: RankingFormModel): void;
}>();

const rankingEnabled = computed(() => true);

const rankingModalOpen = computed(() => props.rankingModalOpen ?? false);
const rankingModalTitle = computed(
  () => props.rankingModalTitle ?? "Thiết lập hạng tạp chí"
);
const rankingForm = computed(() => props.rankingForm!);
const rankingErrors = computed(() => props.rankingErrors!);

function rankBadgeClass(rank: JournalRank | null): string {
  if (!rank) return "bg-slate-100 text-slate-700";
  if (rank === "Q1") return "bg-emerald-50 text-emerald-700";
  if (rank === "Q2") return "bg-sky-50 text-sky-700";
  if (rank === "Q3") return "bg-amber-50 text-amber-800";
  if (rank === "Q4") return "bg-orange-50 text-orange-800";
  if (rank === "Q5") return "bg-slate-900/5 text-slate-800";
  return "bg-slate-100 text-slate-700";
}

function formatDate(value: string): string {
  const d = new Date(value.length === 10 ? `${value}T00:00:00` : value);
  if (Number.isNaN(d.getTime())) return value;
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
}
</script>
