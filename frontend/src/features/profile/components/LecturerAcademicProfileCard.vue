// File:
src/features/lecturer/profile/components/LecturerAcademicProfileCard.vue
<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="text-sm font-semibold text-slate-900">Hồ sơ khoa học</div>
        <p class="mt-1 text-sm text-slate-500">
          Trình độ, lĩnh vực nghiên cứu và ngoại ngữ.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="!isEditing"
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
          :disabled="pending || loading"
          @click="startEdit"
        >
          <Pencil class="h-4 w-4" />
          Chỉnh sửa
        </button>

        <template v-else>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
            :disabled="pending"
            @click="cancelEdit"
          >
            <X class="h-4 w-4" />
            Hủy
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-slate-800 disabled:opacity-60"
            :disabled="pending || !canSave"
            @click="save"
          >
            <Save class="h-4 w-4" />
            Lưu
          </button>
        </template>
      </div>
    </div>

    <div class="mt-4 space-y-4">
      <!-- 3.1 -->
      <div class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center gap-2">
          <GraduationCap class="h-4 w-4 text-slate-600" />
          <div class="text-sm font-semibold text-slate-900">
            Trình độ & học hàm
          </div>
        </div>

        <div v-if="!isEditing" class="mt-3 grid gap-3 sm:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Badge</div>
            <div class="mt-1 flex flex-wrap gap-2">
              <span
                v-if="academicProfile.academicDegree"
                class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700"
              >
                {{ academicProfile.academicDegree }}
              </span>
              <span
                v-if="academicProfile.academicRank"
                class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700"
              >
                {{ academicProfile.academicRank }}
              </span>
              <span
                v-if="
                  !academicProfile.academicDegree &&
                  !academicProfile.academicRank
                "
                class="text-sm text-slate-600"
              >
                —
              </span>
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Năm ghi nhận</div>
            <div class="mt-1 text-sm font-medium text-slate-900">
              <span class="text-slate-600">Học vị:</span>
              {{ academicProfile.degreeYear ?? "—" }}
              <span class="mx-2 text-slate-300">•</span>
              <span class="text-slate-600">Học hàm:</span>
              {{ academicProfile.rankYear ?? "—" }}
            </div>
          </div>

          <div
            class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:col-span-2"
          >
            <div class="text-xs text-slate-500">Trình độ / ngành đào tạo</div>
            <div class="mt-1 text-sm font-medium text-slate-900">
              {{ academicProfile.highestQualification || "—" }}
              <span class="mx-2 text-slate-300">•</span>
              {{ academicProfile.major || "—" }}
            </div>
          </div>
        </div>

        <div v-else class="mt-3 grid gap-3 sm:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600"
              >Trình độ chuyên môn cao nhất</label
            >
            <input
              v-model.trim="draft.highestQualification"
              maxlength="255"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: Tiến sĩ / Thạc sĩ / Kỹ sư..."
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Ngành đào tạo</label
            >
            <input
              v-model.trim="draft.major"
              maxlength="255"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: Khoa học máy tính"
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600">Học vị</label>
            <input
              v-model.trim="draft.academicDegree"
              maxlength="50"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: TS / ThS / CN..."
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600">Học hàm</label>
            <input
              v-model.trim="draft.academicRank"
              maxlength="50"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: PGS / GS..."
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Năm nhận học vị cao nhất</label
            >
            <input
              v-model.number="draft.degreeYear"
              type="number"
              min="1900"
              max="2100"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: 2019"
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Năm được phong học hàm</label
            >
            <input
              v-model.number="draft.rankYear"
              type="number"
              min="1900"
              max="2100"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: 2023"
            />
          </div>
        </div>
      </div>

      <!-- 3.2 -->
      <div class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center gap-2">
          <FlaskConical class="h-4 w-4 text-slate-600" />
          <div class="text-sm font-semibold text-slate-900">
            Lĩnh vực nghiên cứu
          </div>
        </div>

        <div v-if="!isEditing" class="mt-3 grid gap-3 sm:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Lĩnh vực chính</div>
            <div class="mt-1 text-sm font-medium text-slate-900">
              {{ academicProfile.primaryArea || "—" }}
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="text-xs text-slate-500">Lĩnh vực phụ</div>
            <div class="mt-1 text-sm font-medium text-slate-900">
              {{ academicProfile.secondaryArea || "—" }}
            </div>
          </div>

          <div
            class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:col-span-2"
          >
            <div class="text-xs text-slate-500">Từ khóa (keywords)</div>
            <div class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="k in academicProfile.keywords"
                :key="k"
                class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700"
              >
                #{{ k }}
              </span>
              <span
                v-if="academicProfile.keywords.length === 0"
                class="text-sm text-slate-600"
                >—</span
              >
            </div>
          </div>
        </div>

        <div v-else class="mt-3 grid gap-3 sm:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600"
              >Lĩnh vực chính</label
            >
            <input
              v-model.trim="draft.primaryArea"
              maxlength="255"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: Trí tuệ nhân tạo"
            />
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600"
              >Lĩnh vực phụ</label
            >
            <input
              v-model.trim="draft.secondaryArea"
              maxlength="255"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: Khai phá dữ liệu"
            />
          </div>

          <div class="sm:col-span-2">
            <label class="text-xs font-medium text-slate-600"
              >Từ khóa (phân tách bằng dấu phẩy)</label
            >
            <input
              v-model.trim="keywordsText"
              maxlength="500"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="pending"
              placeholder="VD: AI, Machine Learning, Data Mining"
            />
            <div class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="k in parsedKeywords"
                :key="k"
                class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700"
              >
                #{{ k }}
              </span>
              <span
                v-if="parsedKeywords.length === 0"
                class="text-xs text-slate-500"
                >Chưa có từ khóa.</span
              >
            </div>
          </div>
        </div>
      </div>

      <!-- 3.3 -->
      <div class="rounded-2xl border border-slate-200 p-4">
        <div class="flex items-center gap-2">
          <Languages class="h-4 w-4 text-slate-600" />
          <div class="text-sm font-semibold text-slate-900">Ngoại ngữ</div>
        </div>

        <div class="mt-3 overflow-hidden rounded-xl border border-slate-200">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs text-slate-600">
              <tr>
                <th class="px-3 py-2 font-semibold">Ngoại ngữ</th>
                <th class="px-3 py-2 font-semibold">Trình độ</th>
                <th v-if="isEditing" class="px-3 py-2 text-right font-semibold">
                  Thao tác
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr
                v-for="row in isEditing
                  ? draft.languages
                  : academicProfile.languages"
                :key="row.id"
                class="hover:bg-slate-50"
              >
                <td class="px-3 py-2">
                  <template v-if="!isEditing">
                    <div class="font-medium text-slate-900">
                      {{ row.language }}
                    </div>
                  </template>
                  <template v-else>
                    <input
                      v-model.trim="row.language"
                      maxlength="80"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      :disabled="pending"
                      placeholder="VD: English"
                    />
                  </template>
                </td>

                <td class="px-3 py-2">
                  <template v-if="!isEditing">
                    <span
                      class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700"
                    >
                      {{ row.level }}
                    </span>
                  </template>
                  <template v-else>
                    <input
                      v-model.trim="row.level"
                      maxlength="80"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      :disabled="pending"
                      placeholder="VD: IELTS 7.0 / B2 / JLPT N3"
                    />
                  </template>
                </td>

                <td v-if="isEditing" class="px-3 py-2 text-right">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                    :disabled="pending"
                    @click="removeLanguage(row.id)"
                    title="Xóa"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </td>
              </tr>

              <tr
                v-if="
                  (isEditing
                    ? draft.languages.length
                    : academicProfile.languages.length) === 0
                "
              >
                <td
                  class="px-3 py-6 text-center text-sm text-slate-500"
                  :colspan="isEditing ? 3 : 2"
                >
                  Chưa có ngoại ngữ nào.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="isEditing" class="mt-3">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
            :disabled="pending"
            @click="addLanguage"
          >
            <Plus class="h-4 w-4" />
            Thêm ngoại ngữ
          </button>
        </div>
      </div>

      <div
        v-if="loading"
        class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600"
      >
        Đang tải dữ liệu...
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import {
  FlaskConical,
  GraduationCap,
  Languages,
  Pencil,
  Plus,
  Save,
  Trash2,
  X,
} from "lucide-vue-next";
import type { LecturerAcademicProfile } from "../composables/useLecturerProfile";

const props = defineProps<{
  academicProfile: LecturerAcademicProfile;
  pending?: boolean;
  loading?: boolean;
}>();

const emit = defineEmits<{
  (e: "update:academic-profile", value: LecturerAcademicProfile): void;
}>();

const pending = computed(() => props.pending ?? false);
const loading = computed(() => props.loading ?? false);

const isEditing = ref(false);

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}

const draft = reactive<LecturerAcademicProfile>(clone(props.academicProfile));
const keywordsText = ref<string>(props.academicProfile.keywords.join(", "));

watch(
  () => props.academicProfile,
  (next) => {
    if (!isEditing.value) {
      Object.assign(draft, clone(next));
      keywordsText.value = next.keywords.join(", ");
    }
  },
  { deep: true }
);

const parsedKeywords = computed(() => {
  const raw = keywordsText.value
    .split(/[;,]/g)
    .map((s) => s.trim())
    .filter(Boolean);
  // unique
  return Array.from(new Set(raw));
});

const canSave = computed(() => {
  // language rows basic validation
  for (const l of draft.languages) {
    if (!l.language.trim()) return false;
    if (!l.level.trim()) return false;
  }
  return true;
});

function startEdit() {
  Object.assign(draft, clone(props.academicProfile));
  keywordsText.value = props.academicProfile.keywords.join(", ");
  isEditing.value = true;
}
function cancelEdit() {
  Object.assign(draft, clone(props.academicProfile));
  keywordsText.value = props.academicProfile.keywords.join(", ");
  isEditing.value = false;
}
function save() {
  if (!canSave.value) return;
  const next = clone(draft);
  next.keywords = parsedKeywords.value;
  isEditing.value = false;
  emit("update:academic-profile", next);
}

function createId(prefix: string) {
  return `${prefix}_${Math.random().toString(16).slice(2)}_${Date.now()}`;
}

function addLanguage() {
  draft.languages.push({
    id: createId("lang"),
    language: "",
    level: "",
  });
}
function removeLanguage(id: string) {
  draft.languages = draft.languages.filter((x) => x.id !== id);
}
</script>
