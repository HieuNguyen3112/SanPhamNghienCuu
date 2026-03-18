<template>
  <div class="space-y-3">
    <div
      class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm text-slate-600">
        Đang hiển thị
        <span class="font-semibold text-slate-900">Hội nghị khoa học</span>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <input
          :value="search"
          @input="
            emit('update:search', ($event.target as HTMLInputElement).value)
          "
          type="text"
          class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-300 sm:w-72"
          placeholder="Tìm theo tên..."
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
          Thêm hội nghị khoa học
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
            <th class="px-4 py-3">Tên hội nghị</th>
            <th class="w-40 px-4 py-3">Cấp hội nghị</th>
            <th class="w-28 px-4 py-3">Năm</th>
            <th class="w-40 px-4 py-3">Điểm</th>
            <th class="w-40 px-4 py-3">Giờ NCKH</th>
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
              <div class="mt-1 text-xs text-slate-500">
                {{ row.organization || "Chưa có đơn vị tổ chức" }}
                <span v-if="row.researchField"> • {{ row.researchField }}</span>
              </div>
              <div class="mt-1 flex flex-wrap gap-1 text-xs">
                <span
                  class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600"
                >
                  {{ row.hasProceedings ? "Có kỷ yếu" : "Không kỷ yếu" }}
                </span>
                <span
                  class="rounded-full px-2 py-0.5"
                  :class="
                    row.hasIsbn
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-slate-100 text-slate-600'
                  "
                >
                  {{ row.hasIsbn ? `ISBN: ${row.isbn || "—"}` : "Không ISBN" }}
                </span>
                <span
                  v-if="row.notes"
                  class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700"
                >
                  Có ghi chú
                </span>
              </div>
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ levelLabelMap[row.level] ?? row.level }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ row.year ?? "—" }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ formatPoint(row.point) }}
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{ formatHours(row.researchHours) }}
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
                title="Sửa"
                @click="emit('edit', row.id)"
              >
                <Pencil class="h-4 w-4" />
              </button>
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
            >Tên hội nghị *</label
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

        <div class="grid gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600"
              >Cấp hội nghị</label
            >
            <select
              :value="form.level"
              @change="
                emit('update:form', {
                  ...form,
                  level: ($event.target as HTMLSelectElement).value as any,
                })
              "
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            >
              <option value="NATIONAL">Quốc gia</option>
              <option value="INTERNATIONAL">Quốc tế</option>
            </select>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600">Lĩnh vực</label>
            <input
              :value="form.researchField"
              @input="
                emit('update:form', {
                  ...form,
                  researchField: ($event.target as HTMLInputElement).value,
                })
              "
              type="text"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
              placeholder="Ví dụ: Trí tuệ nhân tạo"
            />
            <p v-if="errors.researchField" class="mt-1 text-xs text-rose-600">
              {{ errors.researchField }}
            </p>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Năm tổ chức</label
            >
            <input
              :value="form.year"
              @input="
                emit('update:form', {
                  ...form,
                  year: ($event.target as HTMLInputElement).value,
                })
              "
              type="number"
              min="1900"
              max="2100"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
              placeholder="YYYY"
            />
            <p v-if="errors.year" class="mt-1 text-xs text-rose-600">
              {{ errors.year }}
            </p>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Đơn vị tổ chức</label
            >
            <input
              :value="form.organization"
              @input="
                emit('update:form', {
                  ...form,
                  organization: ($event.target as HTMLInputElement).value,
                })
              "
              type="text"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
              placeholder="Tên đơn vị tổ chức"
            />
            <p v-if="errors.organization" class="mt-1 text-xs text-rose-600">
              {{ errors.organization }}
            </p>
          </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-xs font-medium text-slate-600">Có kỷ yếu</p>
            <div class="mt-2 flex items-center gap-4">
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  name="conference-has-proceedings"
                  :checked="form.hasProceedings"
                  @change="
                    emit('update:form', { ...form, hasProceedings: true })
                  "
                />
                Có
              </label>
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  name="conference-has-proceedings"
                  :checked="!form.hasProceedings"
                  @change="
                    emit('update:form', { ...form, hasProceedings: false })
                  "
                />
                Không
              </label>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
            <p class="text-xs font-medium text-slate-600">Có ISBN</p>
            <div class="mt-2 flex items-center gap-4">
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  name="conference-has-isbn"
                  :checked="form.hasIsbn"
                  @change="emit('update:form', { ...form, hasIsbn: true })"
                />
                Có
              </label>
              <label
                class="inline-flex items-center gap-2 text-sm text-slate-700"
              >
                <input
                  type="radio"
                  name="conference-has-isbn"
                  :checked="!form.hasIsbn"
                  @change="
                    emit('update:form', { ...form, hasIsbn: false, isbn: '' })
                  "
                />
                Không
              </label>
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="text-xs font-medium text-slate-600">ISBN</label>
            <input
              :value="form.isbn"
              @input="
                emit('update:form', {
                  ...form,
                  isbn: ($event.target as HTMLInputElement).value,
                })
              "
              type="text"
              :disabled="!form.hasIsbn"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
              placeholder="Ví dụ: 978-604-xxxxxx-x"
            />
            <p v-if="errors.isbn" class="mt-1 text-xs text-rose-600">
              {{ errors.isbn }}
            </p>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Điểm quy đổi</label
            >
            <input
              :value="form.point"
              @input="
                emit('update:form', {
                  ...form,
                  point: ($event.target as HTMLInputElement).value,
                })
              "
              type="text"
              inputmode="decimal"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
              placeholder="Ví dụ: 1.50"
            />
            <p v-if="errors.point" class="mt-1 text-xs text-rose-600">
              {{ errors.point }}
            </p>
            <p
              v-else-if="derivedHoursPreview !== null"
              class="mt-1 text-xs font-medium text-emerald-700"
            >
              Giờ NCKH quy đổi: {{ derivedHoursPreview }} giờ
            </p>
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
                Đề xuất hội nghị chờ duyệt
              </div>
              <div class="text-xs text-slate-500">
                Duyệt đề xuất để thêm vào danh mục hội nghị chính thức.
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
              Không có đề xuất hội nghị đang chờ duyệt.
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
                    <span class="font-semibold text-slate-900"
                      >Cấp hội nghị:</span
                    >
                    {{ suggestionField(suggestion, "level") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Năm:</span>
                    {{ suggestionField(suggestion, "year") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900"
                      >Đơn vị tổ chức:</span
                    >
                    {{ suggestionField(suggestion, "organization") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Lĩnh vực:</span>
                    {{ suggestionField(suggestion, "research_field") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">ISBN:</span>
                    {{ suggestionField(suggestion, "isbn") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Điểm:</span>
                    {{ suggestionField(suggestion, "point") }}
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
import { computed, ref } from "vue";
import { Mail, Pencil, Plus, X } from "lucide-vue-next";
import CatalogUpsertModal from "./CatalogUpsertModal.vue";
import type {
  Conference,
  ConferenceSuggestion,
} from "../contracts/conferences.contract";

const levelLabelMap: Record<string, string> = {
  NATIONAL: "Quốc gia",
  INTERNATIONAL: "Quốc tế",
};

const props = defineProps<{
  rows: Conference[];
  startIndex: number;
  total: number;
  search: string;
  page: number;
  pageSize: number;

  modalOpen: boolean;
  modalTitle: string;
  submitting: boolean;
  suggestionsOpen: boolean;
  suggestions: ConferenceSuggestion[];
  suggestionsLoading: boolean;
  approvingSuggestionId: number | null;
  form: {
    id: number;
    name: string;
    level: "NATIONAL" | "INTERNATIONAL";
    researchField: string;
    year: string;
    organization: string;
    hasProceedings: boolean;
    hasIsbn: boolean;
    isbn: string;
    point: string;
    notes: string;
    isActive: boolean;
  };
  errors: Partial<
    Record<
      | "name"
      | "researchField"
      | "year"
      | "organization"
      | "isbn"
      | "point"
      | "notes",
      string
    >
  >;
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
  (
    e: "update:form",
    v: {
      id: number;
      name: string;
      level: "NATIONAL" | "INTERNATIONAL";
      researchField: string;
      year: string;
      organization: string;
      hasProceedings: boolean;
      hasIsbn: boolean;
      isbn: string;
      point: string;
      notes: string;
      isActive: boolean;
    },
  ): void;
}>();

const suggestionNotes = ref<Record<number, string>>({});

function formatPoint(value: number | null): string {
  if (value === null || value === undefined) return "—";
  return Number(value).toFixed(2);
}

function formatHours(value: number | null): string {
  if (value === null || value === undefined) return "—";
  return `${Math.max(0, Math.round(Number(value)))} giờ`;
}

const derivedHoursPreview = computed<number | null>(() => {
  const raw = props.form.point.trim().replace(",", ".");
  if (!raw) return null;

  const point = Number(raw);
  if (!Number.isFinite(point)) return null;
  if (point > 1) return 900;
  if (point > 0) return 600;
  return 0;
});

function suggestionField(
  suggestion: ConferenceSuggestion,
  key: string,
): string {
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
</script>
