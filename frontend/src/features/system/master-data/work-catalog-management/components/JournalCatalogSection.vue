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
              {{ formatPointRange(row.pointMin ?? null, row.pointMax ?? null) }}
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
            <label class="text-xs font-medium text-slate-600">ISSN/ISBN</label>
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
            <label class="text-xs font-medium text-slate-600">Trạng thái</label>
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

        <div>
          <label class="text-xs font-medium text-slate-600"
            >Cơ quan xuất bản</label
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
          <label class="text-xs font-medium text-slate-600">
            Nguồn uy tín (link/ghi chú)
          </label>
          <input
            :value="localForm.sourceName ?? ''"
            @input="
              patchForm({
                sourceName: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            placeholder="Ví dụ: HDGSNN 2025 / Scopus / Web of Science"
          />
          <p v-if="errors.sourceName" class="mt-1 text-xs text-rose-600">
            {{ errors.sourceName }}
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
          <div class="text-xs font-semibold text-slate-900">
            Điểm công trình (theo nguồn uy tín)
          </div>

          <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <label class="text-xs font-medium text-slate-600"
                >Điểm tối thiểu</label
              >
              <input
                :value="localForm.pointMin ?? ''"
                @input="
                  patchForm({
                    pointMin: toNumberOrNull(
                      ($event.target as HTMLInputElement).value,
                    ),
                  })
                "
                type="number"
                step="0.1"
                min="0"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
                placeholder="Ví dụ: 0"
              />
              <p v-if="errors.pointMin" class="mt-1 text-xs text-rose-600">
                {{ errors.pointMin }}
              </p>
            </div>

            <div>
              <label class="text-xs font-medium text-slate-600"
                >Điểm tối đa</label
              >
              <input
                :value="localForm.pointMax ?? ''"
                @input="
                  patchForm({
                    pointMax: toNumberOrNull(
                      ($event.target as HTMLInputElement).value,
                    ),
                  })
                "
                type="number"
                step="0.1"
                min="0"
                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
                placeholder="Ví dụ: 1.5 / 2 / 3"
              />
              <p v-if="errors.pointMax" class="mt-1 text-xs text-rose-600">
                {{ errors.pointMax }}
              </p>
            </div>
          </div>

          <div
            class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <span class="font-semibold text-slate-900">Khoảng điểm:</span>
                {{ derivedRangeText }}
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
              Xếp loại theo điểm/ISSN. Giờ NCKH khi lưu sẽ lấy theo cấu hình
              Quy đổi giờ theo công trình (paper: HDGSNN 900/600/300) hiện hành.
            </div>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Địa chỉ</label>
          <input
            :value="localForm.address ?? ''"
            @input="
              patchForm({ address: ($event.target as HTMLInputElement).value })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="errors.address" class="mt-1 text-xs text-rose-600">
            {{ errors.address }}
          </p>
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
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Pencil, Plus } from "lucide-vue-next";
import CatalogUpsertModal from "./CatalogUpsertModal.vue";

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
  pointMin?: number | null;
  pointMax?: number | null;
  classification?: DerivedCategory;
  researchHours?: number;
}

export interface JournalFormModel {
  id: number;
  name: string;
  issn: string;
  address: string;
  country: string;
  notes: string;
  publisher: string;
  isActive: boolean;
  sourceName: string;
  pointMin: number | null;
  pointMax: number | null;
}

type JournalFormErrors = Partial<
  Record<
    | "name"
    | "address"
    | "country"
    | "issn"
    | "notes"
    | "publisher"
    | "sourceName"
    | "pointMin"
    | "pointMax",
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
}>();

const emit = defineEmits<{
  (e: "update:search", v: string): void;
  (e: "update:page", v: number): void;
  (e: "update:pageSize", v: number): void;

  (e: "create"): void;
  (e: "edit", id: number): void;
  (e: "close-modal"): void;
  (e: "submit"): void;
  (e: "update:form", v: JournalFormModel): void;
}>();

const localForm = ref<JournalFormModel>({ ...props.form });

watch(
  () => props.form,
  (v) => {
    localForm.value = { ...v };
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

function formatPointRange(min: number | null, max: number | null): string {
  if (min === null && max === null) return "—";
  if (min !== null && max === null) return `>= ${min}`;
  if (min === null && max !== null) return `<= ${max}`;
  return `${min} – ${max}`;
}

function hasIssnIsbn(v: string | null): boolean {
  return !!(v && v.trim().length > 0);
}

function normalizePoints(pointMin: number | null, pointMax: number | null) {
  if (pointMin === null && pointMax === null) {
    return {
      min: null as number | null,
      max: null as number | null,
      maxPoint: null as number | null,
    };
  }

  if (pointMin !== null && pointMax === null) {
    return { min: pointMin, max: null, maxPoint: pointMin };
  }

  if (pointMin === null && pointMax !== null) {
    return { min: null, max: pointMax, maxPoint: pointMax };
  }

  const min = Math.min(pointMin!, pointMax!);
  const max = Math.max(pointMin!, pointMax!);

  return { min, max, maxPoint: max };
}

function deriveCategory(
  issn: string | null,
  pointMin: number | null,
  pointMax: number | null,
): DerivedCategory {
  const { maxPoint } = normalizePoints(pointMin, pointMax);

  if (maxPoint !== null && maxPoint >= 2) return "POINT_GE_2";
  if (maxPoint !== null && maxPoint >= 1) return "POINT_GE_1";
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
    label: "HDGSNN >= 1 điểm",
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

  return deriveCategory(
    row.issn ?? null,
    row.pointMin ?? null,
    row.pointMax ?? null,
  );
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

const derivedCategoryValue = computed(() =>
  deriveCategory(
    localForm.value.issn ?? null,
    localForm.value.pointMin ?? null,
    localForm.value.pointMax ?? null,
  ),
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
const derivedRangeText = computed(() =>
  formatPointRange(
    localForm.value.pointMin ?? null,
    localForm.value.pointMax ?? null,
  ),
);
</script>
