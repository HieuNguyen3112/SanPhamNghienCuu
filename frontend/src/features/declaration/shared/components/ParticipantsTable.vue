<template>
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <!-- Header -->
    <header
      class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between md:p-6"
    >
      <div class="min-w-0">
        <h2 class="text-sm font-semibold text-slate-900">
          Danh sách người tham gia
        </h2>
        <p class="mt-1 text-xs text-slate-500">
          Thêm/xóa chỉ khả dụng khi đang ở trạng thái bản nháp.
        </p>
      </div>

      <!-- Actions -->
      <form
        class="flex w-full flex-col gap-2 md:w-auto md:flex-row md:items-center"
        @submit.prevent="$emit('request-search', search)"
      >
        <div class="relative w-full md:w-[280px]">
          <input
            v-model.trim="search"
            type="text"
            class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-3 pr-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
            placeholder="Tìm giảng viên..."
            :disabled="readOnly"
            aria-label="Tìm giảng viên"
          />
        </div>

        <div class="flex items-center gap-2">
          <button
            type="submit"
            class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-900 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="readOnly"
            @click.prevent="$emit('request-search', search)"
            aria-label="Tìm"
          >
            <Search class="h-4 w-4" />
            <span class="hidden sm:inline">Tìm</span>
          </button>

          <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="readOnly"
            @click="addRow"
            aria-label="Thêm người tham gia"
          >
            <Plus class="h-4 w-4" />
            <span>Thêm</span>
          </button>
        </div>
      </form>
    </header>

    <!-- Desktop Table -->
    <div class="hidden md:block">
      <div class="overflow-hidden">
        <div class="max-h-[520px] overflow-auto">
          <table class="min-w-full text-sm">
            <colgroup>
              <col class="w-12" />
              <col class="w-[38%]" />
              <col class="w-[22%]" />
              <col class="w-[20%]" />
              <col class="w-[14%]" />
              <col v-if="!readOnly" class="w-[6%]" />
            </colgroup>

            <thead class="sticky top-0 z-10 bg-slate-50">
              <tr>
                <th
                  class="px-4 py-3 text-center text-xs font-semibold text-slate-600 md:px-6"
                  scope="col"
                  title="Giảng viên ngoài"
                >
                  Ngoài
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-semibold text-slate-600 md:px-6"
                  scope="col"
                >
                  Họ tên
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                  scope="col"
                >
                  Đơn vị
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                  scope="col"
                >
                  Vai trò
                </th>
                <th
                  class="px-4 py-3 text-right text-xs font-semibold text-slate-600"
                  scope="col"
                >
                  Giờ NCKH
                </th>
                <th
                  v-if="!readOnly"
                  class="px-4 py-3 text-right text-xs font-semibold text-slate-600 md:px-6"
                  scope="col"
                ></th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 bg-white">
              <tr
                v-for="(row, idx) in modelValue"
                :key="idx"
                class="group hover:bg-slate-50"
                :class="[
                  row.lecturer_id === currentLecturerId ? 'bg-slate-50/60' : '',
                ]"
              >
                <!-- Ngoài (cột đầu) -->
                <td class="px-4 py-3 text-center align-top md:px-6">
                  <div class="pt-2">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="readOnly"
                      :checked="!!row.is_external"
                      :title="'Giảng viên ngoài'"
                      :aria-label="`Giảng viên ngoài - dòng ${idx + 1}`"
                      @change="
                        toggleExternal(
                          idx,
                          ($event.target as HTMLInputElement).checked,
                        )
                      "
                    />
                  </div>
                </td>

                <!-- Họ tên -->
                <td class="px-4 py-3 align-top md:px-6">
                  <div class="flex flex-col gap-2">
                    <template v-if="row.is_external">
                      <input
                        type="text"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                        placeholder="Nhập họ tên..."
                        :disabled="readOnly"
                        :value="row.external_full_name ?? ''"
                        @input="
                          updateRow(idx, {
                            external_full_name: (
                              $event.target as HTMLInputElement
                            ).value,
                          })
                        "
                        :aria-label="`Họ tên giảng viên ngoài - dòng ${idx + 1}`"
                      />
                    </template>

                    <template v-else>
                      <select
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                        :disabled="readOnly"
                        :value="row.lecturer_id ?? ''"
                        @change="
                          updateRow(idx, { lecturer_id: toNumber($event) })
                        "
                        :aria-label="`Chọn giảng viên - dòng ${idx + 1}`"
                      >
                        <option value="">— Chọn giảng viên —</option>
                        <option
                          v-for="l in lecturers"
                          :key="l.id"
                          :value="l.id"
                        >
                          {{ l.full_name }} ({{ l.code }})
                        </option>
                      </select>
                    </template>
                  </div>
                </td>

                <!-- Đơn vị -->
                <td class="px-4 py-3 align-top text-slate-700">
                  <template v-if="row.is_external">
                    <input
                      type="text"
                      class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                      placeholder="Nhập đơn vị..."
                      :disabled="readOnly"
                      :value="row.external_department_name ?? ''"
                      @input="
                        updateRow(idx, {
                          external_department_name: (
                            $event.target as HTMLInputElement
                          ).value,
                        })
                      "
                      :aria-label="`Đơn vị giảng viên ngoài - dòng ${idx + 1}`"
                    />
                  </template>
                  <template v-else>
                    <div class="pt-2">
                      <div class="block">
                        {{ lecturerDepartmentName(row.lecturer_id) || "—" }}
                      </div>
                      <span
                        v-if="isOutsideFaculty(row)"
                        class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                      >
                        Ngoài khoa
                      </span>
                    </div>
                  </template>
                </td>

                <!-- Vai trò -->
                <td class="px-4 py-3 align-top">
                  <select
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                    :disabled="readOnly"
                    :value="row.member_role_id ?? ''"
                    @change="
                      updateRow(idx, { member_role_id: toNumber($event) })
                    "
                    :aria-label="`Chọn vai trò - dòng ${idx + 1}`"
                  >
                    <option value="">— Chọn vai trò —</option>
                    <option v-for="r in memberRoles" :key="r.id" :value="r.id">
                      {{ r.name }}
                    </option>
                  </select>
                </td>

                <!-- Giờ -->
                <td
                  class="px-4 py-3 align-top text-right font-medium text-slate-900"
                >
                  <div class="pt-2">
                    <span v-if="row.is_external" class="text-slate-400">—</span>
                    <span v-else>
                      {{
                        formatHours(
                          hoursByLecturerId[row.lecturer_id ?? -1] ?? 0,
                        )
                      }}
                    </span>
                  </div>
                </td>

                <!-- Remove -->
                <td
                  v-if="!readOnly"
                  class="px-4 py-3 text-right align-top md:px-6"
                >
                  <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                    @click="removeRow(idx)"
                    :aria-label="`Xóa dòng ${idx + 1}`"
                    title="Xóa"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </td>
              </tr>

              <tr v-if="modelValue.length === 0">
                <td
                  :colspan="readOnly ? 5 : 6"
                  class="px-6 py-10 text-center text-sm text-slate-500"
                >
                  <div class="flex flex-col items-center gap-2">
                    <div class="text-2xl">👥</div>
                    <div>Chưa có danh sách người tham gia.</div>
                    <button
                      v-if="!readOnly"
                      type="button"
                      class="mt-2 inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
                      @click="addRow"
                    >
                      <Plus class="h-4 w-4" />
                      Thêm người tham gia
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Mobile Cards (giữ nguyên như bạn đang có) -->
    <div class="md:hidden">
      <div v-if="modelValue.length === 0" class="p-6 text-center">
        <div class="text-2xl">👥</div>
        <div class="mt-2 text-sm font-medium text-slate-900">
          Chưa có danh sách người tham gia
        </div>
        <div class="mt-1 text-xs text-slate-500">
          Thêm người tham gia để phân công vai trò và theo dõi giờ NCKH.
        </div>
        <button
          v-if="!readOnly"
          type="button"
          class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
          @click="addRow"
        >
          <Plus class="h-4 w-4" />
          Thêm người tham gia
        </button>
      </div>

      <div v-else class="divide-y divide-slate-200">
        <article
          v-for="(row, idx) in modelValue"
          :key="`m-${idx}`"
          class="p-4"
          :class="row.lecturer_id === currentLecturerId ? 'bg-slate-50/60' : ''"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs font-semibold text-slate-600">
                Người tham gia #{{ idx + 1 }}
              </div>

              <!-- Mobile: checkbox vẫn giữ trong card -->
              <label
                class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1 text-xs text-slate-600 hover:bg-slate-50"
                :class="readOnly ? 'cursor-not-allowed opacity-60' : ''"
              >
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-2 focus:ring-slate-900/20"
                  :disabled="readOnly"
                  :checked="!!row.is_external"
                  @change="
                    toggleExternal(
                      idx,
                      ($event.target as HTMLInputElement).checked,
                    )
                  "
                />
                Giảng viên ngoài
              </label>
            </div>

            <button
              v-if="!readOnly"
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
              @click="removeRow(idx)"
              :aria-label="`Xóa dòng ${idx + 1}`"
              title="Xóa"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>

          <div class="mt-3 grid gap-3">
            <!-- Name -->
            <div>
              <div class="text-xs font-medium text-slate-700">Họ tên</div>
              <div class="mt-1">
                <template v-if="row.is_external">
                  <input
                    type="text"
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                    placeholder="Nhập họ tên..."
                    :disabled="readOnly"
                    :value="row.external_full_name ?? ''"
                    @input="
                      updateRow(idx, {
                        external_full_name: ($event.target as HTMLInputElement)
                          .value,
                      })
                    "
                  />
                </template>
                <template v-else>
                  <select
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                    :disabled="readOnly"
                    :value="row.lecturer_id ?? ''"
                    @change="updateRow(idx, { lecturer_id: toNumber($event) })"
                  >
                    <option value="">— Chọn giảng viên —</option>
                    <option v-for="l in lecturers" :key="l.id" :value="l.id">
                      {{ l.full_name }} ({{ l.code }})
                    </option>
                  </select>
                </template>
              </div>
            </div>

            <!-- Department -->
            <div>
              <div class="text-xs font-medium text-slate-700">Đơn vị</div>
              <div class="mt-1">
                <template v-if="row.is_external">
                  <input
                    type="text"
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                    placeholder="Nhập đơn vị..."
                    :disabled="readOnly"
                    :value="row.external_department_name ?? ''"
                    @input="
                      updateRow(idx, {
                        external_department_name: (
                          $event.target as HTMLInputElement
                        ).value,
                      })
                    "
                  />
                </template>
                <template v-else>
                  <div
                    class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700"
                  >
                    {{ lecturerDepartmentName(row.lecturer_id) || "—" }}
                  </div>
                  <span
                    v-if="isOutsideFaculty(row)"
                    class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                  >
                    Ngoài khoa
                  </span>
                </template>
              </div>
            </div>

            <!-- Role + Hours -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <div class="text-xs font-medium text-slate-700">Vai trò</div>
                <select
                  class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                  :disabled="readOnly"
                  :value="row.member_role_id ?? ''"
                  @change="updateRow(idx, { member_role_id: toNumber($event) })"
                >
                  <option value="">— Chọn vai trò —</option>
                  <option v-for="r in memberRoles" :key="r.id" :value="r.id">
                    {{ r.name }}
                  </option>
                </select>
              </div>

              <div>
                <div class="text-xs font-medium text-slate-700">Giờ NCKH</div>
                <div
                  class="mt-1 flex h-10 items-center justify-end rounded-xl bg-slate-50 px-3 text-sm font-semibold text-slate-900"
                >
                  <span v-if="row.is_external" class="text-slate-400">—</span>
                  <span v-else>
                    {{
                      formatHours(hoursByLecturerId[row.lecturer_id ?? -1] ?? 0)
                    }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>

    <!-- Warnings -->
    <div v-if="warnings.length" class="border-t border-slate-200 p-4 md:p-6">
      <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3">
        <div class="flex items-start gap-2">
          <AlertTriangle class="mt-0.5 h-4 w-4 text-amber-700" />
          <div class="text-xs font-semibold text-amber-900">Cảnh báo</div>
        </div>

        <div class="mt-2 space-y-2">
          <div
            v-for="(w, i) in warnings"
            :key="i"
            class="flex items-start gap-2 text-xs text-amber-800"
          >
            <span
              class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-amber-700"
            ></span>
            <div>{{ w }}</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { AlertTriangle, Plus, Search, Trash2 } from "lucide-vue-next";
import type {
  LecturerOptionDto,
  MemberRoleDto,
} from "../contracts/declarationSharedContract";

export type ParticipantRowModel = {
  lecturer_id: number | null;
  member_role_id: number | null;

  // UI-only helper (NOT persisted)
  member_role_code?: string | null;

  // UI-only: participant external/outside school
  is_external?: boolean;
  external_full_name?: string | null;
  external_department_name?: string | null;
};

const props = defineProps<{
  modelValue: ParticipantRowModel[];
  lecturers: LecturerOptionDto[];
  memberRoles: MemberRoleDto[];
  readOnly: boolean;
  currentLecturerId: number | null;
  hoursByLecturerId: Record<number, number>;
  ownerFacultyId?: number | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: ParticipantRowModel[]): void;
  (e: "request-search", query: string): void;
}>();

const search = ref("");

const warnings = computed(() => {
  const list: string[] = [];

  // chỉ check trùng cho giảng viên nội bộ (is_external != true)
  const ids = props.modelValue
    .filter((r) => !r.is_external)
    .map((r) => r.lecturer_id)
    .filter((x): x is number => typeof x === "number");

  const dup = ids.filter((id, idx) => ids.indexOf(id) !== idx);
  if (dup.length) {
    list.push(
      "Có giảng viên bị trùng trong danh sách (unique(activity_id, lecturer_id)).",
    );
  }

  return list;
});

function addRow() {
  const next = [
    ...props.modelValue,
    {
      lecturer_id: null,
      member_role_id: null,
      member_role_code: null,

      is_external: false,
      external_full_name: null,
      external_department_name: null,
    },
  ];
  emit("update:modelValue", next);
}

function removeRow(idx: number) {
  const next = props.modelValue.filter((_, i) => i !== idx);
  emit("update:modelValue", next);
}

function updateRow(idx: number, patch: Partial<ParticipantRowModel>) {
  const next = props.modelValue.map((r, i) =>
    i === idx ? { ...r, ...patch } : r,
  );
  emit("update:modelValue", next);
}

function toggleExternal(idx: number, checked: boolean) {
  if (checked) {
    // chuyển sang GV ngoài: bỏ lecturer_id
    updateRow(idx, {
      is_external: true,
      lecturer_id: null,
      external_full_name: props.modelValue[idx]?.external_full_name ?? "",
      external_department_name:
        props.modelValue[idx]?.external_department_name ?? "",
    });
  } else {
    // chuyển về GV nội bộ: clear các field external
    updateRow(idx, {
      is_external: false,
      external_full_name: null,
      external_department_name: null,
    });
  }
}

function toNumber(e: Event): number | null {
  const v = (e.target as HTMLSelectElement).value;
  if (!v) return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

function lecturerDepartmentName(lecturer_id: number | null) {
  if (!lecturer_id) return null;
  return (
    props.lecturers.find((l) => l.id === lecturer_id)?.department_name ?? null
  );
}

function lecturerFacultyId(lecturer_id: number | null) {
  if (!lecturer_id) return null;
  return props.lecturers.find((l) => l.id === lecturer_id)?.faculty_id ?? null;
}

function isOutsideFaculty(row: ParticipantRowModel): boolean {
  if (row.is_external) return false;
  if (!props.ownerFacultyId) return false;
  const memberFacultyId = lecturerFacultyId(row.lecturer_id);
  if (!memberFacultyId) return false;
  return Number(memberFacultyId) !== Number(props.ownerFacultyId);
}

function formatHours(v: number) {
  const n = Math.round(v * 100) / 100;
  return `${n.toFixed(2)} giờ`;
}
</script>
