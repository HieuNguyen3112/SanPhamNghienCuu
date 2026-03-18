<template>
  <div class="space-y-3">
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
          class="relative inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          @click="emit('open-suggestions')"
        >
          <Mail class="h-4 w-4" />
          Đề xuất
          <span
            class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-bold text-amber-700"
          >
            {{ suggestions.length }}
          </span>
        </button>

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

    <div
      class="max-h-[560px] overflow-auto rounded-2xl border border-slate-200"
    >
      <table class="w-full text-left text-sm">
        <thead class="sticky top-0 z-10 bg-slate-50 text-xs text-slate-600">
          <tr>
            <th class="w-16 px-4 py-3">STT</th>
            <th class="px-4 py-3">Tên tạp chí</th>
            <th class="w-36 px-4 py-3">ISSN/ISBN</th>
            <th class="w-[260px] px-4 py-3">Nguồn uy tín</th>
            <th class="w-[220px] px-4 py-3">Cơ quan xuất bản</th>
            <th class="w-32 px-4 py-3">Điểm</th>
            <th class="w-40 px-4 py-3">Xếp loại</th>
            <th class="w-28 px-4 py-3 text-right">Giờ NCKH</th>
            <th class="w-32 px-4 py-3">Trạng thái</th>
            <th class="w-24 px-4 py-3 text-right">Thao tác</th>
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
              <div class="line-clamp-2 whitespace-pre-wrap">
                {{ row.sourceName ?? "—" }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">
              <div class="line-clamp-2 whitespace-pre-wrap">
                {{ row.publisher ?? "—" }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-700">
              {{ formatPoint(row.point ?? null) }}
            </td>

            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold"
                :class="resolveCategoryMeta(row).badgeClass"
              >
                {{ resolveCategoryMeta(row).label }}
              </span>
            </td>

            <td class="px-4 py-3 text-right font-semibold text-slate-900">
              {{ resolveResearchHours(row) }} giờ
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

            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                title="Sửa thông tin"
                @click="emit('edit', row.id)"
              >
                <Pencil class="h-4 w-4" />
              </button>
            </td>
          </tr>

          <tr v-if="rows.length === 0">
            <td
              colspan="10"
              class="px-4 py-10 text-center text-sm text-slate-500"
            >
              Không có dữ liệu phù hợp.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

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
            :value="localForm.name"
            @input="
              patchForm({ name: ($event.target as HTMLInputElement).value })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="errors.name" class="mt-1 text-xs text-rose-600">
            {{ errors.name }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600">ISSN *</label>
            <input
              :value="localForm.issn"
              @input="
                patchForm({ issn: ($event.target as HTMLInputElement).value })
              "
              type="text"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
              placeholder="Ví dụ: 1234-5678"
            />
            <p v-if="errors.issn" class="mt-1 text-xs text-rose-600">
              {{ errors.issn }}
            </p>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Trạng thái *</label
            >
            <div class="mt-2 flex items-center gap-3">
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  :checked="localForm.isActive"
                  @change="patchForm({ isActive: true })"
                />
                Đang sử dụng
              </label>
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  :checked="!localForm.isActive"
                  @change="patchForm({ isActive: false })"
                />
                Ngừng sử dụng
              </label>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600"
              >Loại tạp chí</label
            >
            <select
              v-model="localForm.journalType"
              @change="patchForm({ journalType: localForm.journalType })"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            >
              <option value="" disabled>Chọn loại tạp chí</option>
              <option value="Tạp chí">Tạp chí</option>
              <option value="Báo cáo khoa học">Báo cáo khoa học</option>
              <option value="Thông báo khoa học">Thông báo khoa học</option>
              <option value="Chuyên san">Chuyên san</option>
              <option value="Tập san">Tập san</option>
              <option value="Thông tin khoa học">Thông tin khoa học</option>
            </select>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600">Phạm vi *</label>
            <select
              v-model="localForm.address"
              @change="patchForm({ address: localForm.address })"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            >
              <option value="" disabled>Chọn phạm vi</option>
              <option value="Trong nước">Trong nước</option>
              <option value="Quốc tế">Quốc tế</option>
            </select>
            <p v-if="errors.address" class="mt-1 text-xs text-rose-600">
              {{ errors.address }}
            </p>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600"
            >Cơ quan xuất bản *</label
          >
          <input
            :value="localForm.publisher ?? ''"
            @input="
              patchForm({
                publisher: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            placeholder="Ví dụ: Elsevier, Springer, IEEE"
          />
          <p v-if="errors.publisher" class="mt-1 text-xs text-rose-600">
            {{ errors.publisher }}
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600"
            >Nguồn xếp loại *</label
          >
          <input
            :value="localForm.sourceName ?? ''"
            @input="
              patchForm({
                sourceName: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            placeholder="Ví dụ: HDGSNN 2025 / Scopus / Web of Science / ISI"
          />
          <p v-if="errors.sourceName" class="mt-1 text-xs text-rose-600">
            {{ errors.sourceName }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
          <div class="text-xs font-semibold text-slate-900">
            Điểm tạp chí (theo nguồn xếp loại)
          </div>

          <div class="mt-2">
            <label class="text-xs font-medium text-slate-600"
              >Điểm tạp chí *</label
            >
            <input
              :value="localForm.point ?? ''"
              @input="
                patchForm({
                  point: toNumberOrNull(
                    ($event.target as HTMLInputElement).value,
                  ),
                })
              "
              type="number"
              step="0.1"
              min="0"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
              placeholder="Ví dụ: 1.5"
            />
            <p v-if="errors.point" class="mt-1 text-xs text-rose-600">
              {{ errors.point }}
            </p>
          </div>

          <div
            class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <span class="font-semibold text-slate-900">Điểm:</span>
                {{ formatPoint(localForm.point ?? null) }}
              </div>
              <div class="font-semibold text-slate-900">
                {{ derivedHours }} giờ NCKH
              </div>
            </div>

            <div class="mt-1">
              <span class="font-semibold text-slate-900">Xếp loại:</span>
              <span
                class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="derivedBadgeClass"
              >
                {{ derivedLabel }}
              </span>
            </div>

            <div class="mt-2 text-slate-600">
              Xếp loại theo điểm/ISSN. Giờ NCKH khi lưu sẽ lấy theo cấu hình Quy
              đổi giờ theo công trình (paper: HDGSNN 900/600/300) hiện hành.
            </div>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Lĩnh vực</label>
          <input
            :value="localForm.researchField ?? ''"
            @input="
              patchForm({
                researchField: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            placeholder="Ví dụ: Khoa học giáo dục"
          />
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Website</label>
          <input
            :value="localForm.website ?? ''"
            @input="
              patchForm({ website: ($event.target as HTMLInputElement).value })
            "
            type="url"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            placeholder="https://journal.example.com"
          />
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Quốc gia</label>
          <input
            :value="localForm.country ?? ''"
            @input="
              patchForm({ country: ($event.target as HTMLInputElement).value })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="errors.country" class="mt-1 text-xs text-rose-600">
            {{ errors.country }}
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Ghi chú</label>
          <textarea
            :value="localForm.notes"
            @input="
              patchForm({ notes: ($event.target as HTMLTextAreaElement).value })
            "
            rows="3"
            class="mt-1 w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="errors.notes" class="mt-1 text-xs text-rose-600">
            {{ errors.notes }}
          </p>
        </div>
      </div>
    </CatalogUpsertModal>

    <div v-if="suggestionsOpen" class="fixed inset-0 z-50">
      <div
        class="absolute inset-0 bg-slate-900/40"
        @click="emit('close-suggestions')"
      ></div>

      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div
          class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"
        >
          <div
            class="flex items-center justify-between border-b border-slate-200 px-4 py-3"
          >
            <div>
              <div class="text-sm font-semibold text-slate-900">
                Đề xuất tạp chí chờ duyệt
              </div>
              <div class="text-xs text-slate-500">
                Duyệt đề xuất để thêm vào danh mục tạp chí chính thức.
              </div>
            </div>

            <button
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-50 hover:text-slate-900"
              @click="emit('close-suggestions')"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <div class="min-h-0 flex-1 overflow-auto p-4">
            <div
              v-if="suggestionsLoading"
              class="py-10 text-center text-sm text-slate-500"
            >
              Đang tải danh sách đề xuất...
            </div>

            <div
              v-else-if="suggestions.length === 0"
              class="py-10 text-center text-sm text-slate-500"
            >
              Không có đề xuất tạp chí đang chờ duyệt.
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="suggestion in suggestions"
                :key="suggestion.id"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
              >
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <div class="text-sm font-semibold text-slate-900">
                      {{
                        suggestionField(suggestion, "name") !== "—"
                          ? suggestionField(suggestion, "name")
                          : suggestion.sourceName
                      }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                      Mã kê khai: #{{ suggestion.activityId }} • Tạo lúc:
                      {{ suggestion.createdAt }}
                    </div>
                  </div>

                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      class="inline-flex h-9 items-center rounded-xl border border-rose-300 bg-white px-3 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-60"
                      :disabled="approvingSuggestionId === suggestion.id"
                      @click="emit('reject-suggestion', suggestion.id, noteBySuggestion(suggestion.id))"
                    >
                      {{
                        approvingSuggestionId === suggestion.id
                          ? "Đang xử lý..."
                          : "Từ chối"
                      }}
                    </button>
                    <button
                      type="button"
                      class="inline-flex h-9 items-center rounded-xl bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                      :disabled="approvingSuggestionId === suggestion.id"
                      @click="emit('approve-suggestion', suggestion.id, noteBySuggestion(suggestion.id))"
                    >
                      {{
                        approvingSuggestionId === suggestion.id
                          ? "Đang xử lý..."
                          : "Duyệt"
                      }}
                    </button>
                  </div>
                </div>

                <div
                  class="mt-3 grid grid-cols-1 gap-2 text-xs text-slate-700 md:grid-cols-2"
                >
                  <div>
                    <span class="font-semibold text-slate-900">ISSN:</span>
                    {{ suggestionField(suggestion, "issn") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Điểm:</span>
                    {{ suggestionField(suggestion, "point") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900"
                      >Nguồn xếp loại:</span
                    >
                    {{ suggestionField(suggestion, "source_name") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900"
                      >Cơ quan xuất bản:</span
                    >
                    {{ suggestionField(suggestion, "publisher") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Lĩnh vực:</span>
                    {{ suggestionField(suggestion, "research_field") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Quốc gia:</span>
                    {{ suggestionField(suggestion, "country") }}
                  </div>
                </div>

                <div class="mt-3">
                  <label class="text-xs font-semibold text-slate-700"
                    >Ghi chú duyệt/từ chối</label
                  >
                  <textarea
                    :value="noteBySuggestion(suggestion.id)"
                    @input="setNoteBySuggestion(suggestion.id, ($event.target as HTMLTextAreaElement).value)"
                    rows="2"
                    maxlength="500"
                    class="mt-1 w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 outline-none focus:border-slate-300"
                    placeholder="Nhập ghi chú (không bắt buộc)..."
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Mail, Pencil, Plus, X } from "lucide-vue-next";
import CatalogUpsertModal from "./CatalogUpsertModal.vue";
import type { JournalSuggestion } from "../contracts/journals.contract";

export type DerivedCategory =
  | "POINT_GE_2"
  | "POINT_GE_1"
  | "ISSN_ISBN"
  | "OTHER";

export interface JournalRow {
  id: number;
  name: string;
  issn: string | null;
  notes: string | null;
  isActive: boolean;
  address?: string | null;
  country?: string | null;
  sourceName?: string | null;
  publisher?: string | null;
  point?: number | null;
  classification?: DerivedCategory;
  researchHours?: number;
  journalType?: string | null;
  researchField?: string | null;
  website?: string | null;
}

export interface JournalFormModel {
  id: number;
  name: string;
  issn: string;
  journalType: string;
  address: string;
  researchField: string;
  website: string;
  country: string;
  notes: string;
  publisher: string;
  isActive: boolean;
  sourceName: string;
  point: number | null;
}

type JournalFormErrors = Partial<
  Record<
    | "name"
    | "address"
    | "country"
    | "issn"
    | "journalType"
    | "notes"
    | "researchField"
    | "website"
    | "publisher"
    | "sourceName"
    | "point",
    string
  >
>;

const props = defineProps<{
  rows: JournalRow[];
  startIndex: number;
  total: number;
  search: string;
  page: number;
  pageSize: number;

  modalOpen: boolean;
  modalTitle: string;
  submitting: boolean;
  form: JournalFormModel;
  errors: JournalFormErrors;
  suggestionsOpen: boolean;
  suggestions: JournalSuggestion[];
  suggestionsLoading: boolean;
  approvingSuggestionId: number | null;
}>();

const emit = defineEmits<{
  (e: "update:search", v: string): void;
  (e: "update:page", v: number): void;
  (e: "update:pageSize", v: number): void;

  (e: "create"): void;
  (e: "edit", id: number): void;
  (e: "open-suggestions"): void;
  (e: "close-suggestions"): void;
  (e: "approve-suggestion", id: number, reviewNote?: string): void;
  (e: "reject-suggestion", id: number, reviewNote?: string): void;
  (e: "close-modal"): void;
  (e: "submit"): void;
  (e: "update:form", v: JournalFormModel): void;
}>();

const suggestionNotes = ref<Record<number, string>>({});

function normalizeJournalForm(form: JournalFormModel): JournalFormModel {
  return {
    ...form,
    journalType: form.journalType ?? "",
    address: form.address ?? "",
  };
}

const localForm = ref<JournalFormModel>(normalizeJournalForm(props.form));

watch(
  () => props.form,
  (v) => {
    localForm.value = normalizeJournalForm(v);
  },
  { deep: true },
);

function patchForm(patch: Partial<JournalFormModel>) {
  localForm.value = { ...localForm.value, ...patch };
  emit("update:form", localForm.value);
}

function toNumberOrNull(raw: string): number | null {
  if (raw === "") return null;
  const n = Number(raw);
  return Number.isFinite(n) ? n : null;
}

function formatPoint(point: number | null): string {
  if (point === null) return "—";
  return String(point);
}

function hasIssnIsbn(v: string | null): boolean {
  return !!(v && v.trim().length > 0);
}

function deriveCategory(
  issn: string | null,
  point: number | null,
): DerivedCategory {
  if (point !== null && point > 1) return "POINT_GE_2";
  if (point !== null && point > 0 && point <= 1) return "POINT_GE_1";
  if (hasIssnIsbn(issn)) return "ISSN_ISBN";
  return "OTHER";
}

const CATEGORY_RULES: Record<
  DerivedCategory,
  { label: string; hours: number; badgeClass: string }
> = {
  POINT_GE_2: {
    label: "HDGSNN 1-2 điểm",
    hours: 900,
    badgeClass: "bg-emerald-50 text-emerald-700",
  },
  POINT_GE_1: {
    label: "HDGSNN <= 1 điểm",
    hours: 600,
    badgeClass: "bg-sky-50 text-sky-700",
  },
  ISSN_ISBN: {
    label: "Có ISSN/ISBN",
    hours: 300,
    badgeClass: "bg-amber-50 text-amber-800",
  },
  OTHER: {
    label: "Khác",
    hours: 0,
    badgeClass: "bg-slate-100 text-slate-700",
  },
};

function resolveCategoryCode(row: JournalRow): DerivedCategory {
  const fromApi = row.classification;
  if (fromApi && CATEGORY_RULES[fromApi]) {
    return fromApi;
  }

  return deriveCategory(row.issn ?? null, row.point ?? null);
}

function resolveCategoryMeta(row: JournalRow) {
  return CATEGORY_RULES[resolveCategoryCode(row)];
}

function resolveResearchHours(row: JournalRow): number {
  if (
    typeof row.researchHours === "number" &&
    Number.isFinite(row.researchHours)
  ) {
    return row.researchHours;
  }

  return resolveCategoryMeta(row).hours;
}

function suggestionField(suggestion: JournalSuggestion, key: string): string {
  const payload = suggestion.payload ?? {};
  const value = (payload as Record<string, unknown>)[key];
  if (value === null || value === undefined) {
    return "—";
  }
  const text = String(value).trim();
  return text === "" ? "—" : text;
}

function noteBySuggestion(id: number): string {
  return suggestionNotes.value[id] ?? "";
}

function setNoteBySuggestion(id: number, value: string) {
  suggestionNotes.value = {
    ...suggestionNotes.value,
    [id]: value,
  };
}

const derivedCategoryValue = computed(() =>
  deriveCategory(localForm.value.issn ?? null, localForm.value.point ?? null),
);

const derivedHours = computed(
  () => CATEGORY_RULES[derivedCategoryValue.value].hours,
);
const derivedLabel = computed(
  () => CATEGORY_RULES[derivedCategoryValue.value].label,
);
const derivedBadgeClass = computed(
  () => CATEGORY_RULES[derivedCategoryValue.value].badgeClass,
);
</script>
