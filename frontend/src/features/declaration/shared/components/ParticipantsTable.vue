<template>
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div
      class="flex flex-col gap-2 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between md:p-6"
    >
      <div>
        <div class="text-sm font-semibold text-slate-900">
          Danh sách người tham gia
        </div>
        <div class="mt-0.5 text-xs text-slate-500">
          Thêm/xóa chỉ khả dụng khi đang ở trạng thái bản nháp.
        </div>
      </div>

      <div class="flex items-center gap-2">
        <input
          v-model.trim="search"
          type="text"
          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-300 focus:outline-none md:w-[260px]"
          placeholder="Tìm giảng viên..."
          :disabled="readOnly"
        />
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 hover:bg-slate-50 disabled:opacity-50"
          :disabled="readOnly"
          @click="$emit('request-search', search)"
        >
          <Search class="h-4 w-4" />
          Tìm
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
          :disabled="readOnly"
          @click="addRow"
        >
          <Plus class="h-4 w-4" />
          Thêm
        </button>
      </div>
    </div>

    <div class="overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th
                class="px-4 py-3 text-left text-xs font-semibold text-slate-600 md:px-6"
              >
                Họ tên
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
              >
                Đơn vị
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
              >
                Vai trò
              </th>
              <th
                class="px-4 py-3 text-right text-xs font-semibold text-slate-600"
              >
                Giờ NCKH
              </th>
              <th
                v-if="!readOnly"
                class="px-4 py-3 text-right text-xs font-semibold text-slate-600 md:px-6"
              ></th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr
              v-for="(row, idx) in modelValue"
              :key="idx"
              class="hover:bg-slate-50"
              :class="
                row.lecturer_id === currentLecturerId ? 'bg-slate-50/60' : ''
              "
            >
              <!-- Họ tên -->
              <td class="px-4 py-3 md:px-6">
                <div class="flex flex-col gap-2">
                  <label
                    class="inline-flex items-center gap-2 text-xs text-slate-600"
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-slate-900"
                      :disabled="readOnly"
                      :checked="!!row.is_external"
                      @change="
                        toggleExternal(
                          idx,
                          ($event.target as HTMLInputElement).checked
                        )
                      "
                    />
                    Giảng viên ngoài
                  </label>

                  <template v-if="row.is_external">
                    <input
                      type="text"
                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                    />
                  </template>

                  <template v-else>
                    <select
                      class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                      :disabled="readOnly"
                      :value="row.lecturer_id ?? ''"
                      @change="
                        updateRow(idx, { lecturer_id: toNumber($event) })
                      "
                    >
                      <option value="">— Chọn giảng viên —</option>
                      <option v-for="l in lecturers" :key="l.id" :value="l.id">
                        {{ l.full_name }} ({{ l.code }})
                      </option>
                    </select>
                  </template>
                </div>
              </td>

              <!-- Đơn vị -->
              <td class="px-4 py-3 text-slate-600">
                <template v-if="row.is_external">
                  <input
                    type="text"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
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
                  {{ lecturerDepartmentName(row.lecturer_id) || "—" }}
                </template>
              </td>

              <!-- Vai trò -->
              <td class="px-4 py-3">
                <select
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
                  :disabled="readOnly"
                  :value="row.member_role_id ?? ''"
                  @change="updateRow(idx, { member_role_id: toNumber($event) })"
                >
                  <option value="">— Chọn vai trò —</option>
                  <option v-for="r in memberRoles" :key="r.id" :value="r.id">
                    {{ r.name }}
                  </option>
                </select>
              </td>

              <!-- Giờ -->
              <td class="px-4 py-3 text-right font-medium text-slate-900">
                <span v-if="row.is_external" class="text-slate-400">—</span>
                <span v-else>
                  {{
                    formatHours(hoursByLecturerId[row.lecturer_id ?? -1] ?? 0)
                  }}
                </span>
              </td>

              <!-- Remove -->
              <td v-if="!readOnly" class="px-4 py-3 text-right md:px-6">
                <button
                  type="button"
                  class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="removeRow(idx)"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </td>
            </tr>

            <tr v-if="modelValue.length === 0">
              <td
                :colspan="readOnly ? 4 : 5"
                class="px-4 py-8 text-center text-sm text-slate-500 md:px-6"
              >
                Chưa có danh sách người tham gia.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div
      v-if="warnings.length"
      class="border-t border-slate-200 p-4 text-xs text-amber-700 md:p-6"
    >
      <div v-for="(w, i) in warnings" :key="i" class="flex items-start gap-2">
        <AlertTriangle class="mt-0.5 h-4 w-4" />
        <div>{{ w }}</div>
      </div>
    </div>
  </div>
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
      "Có giảng viên bị trùng trong danh sách (unique(activity_id, lecturer_id))."
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
    i === idx ? { ...r, ...patch } : r
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

function formatHours(v: number) {
  const n = Math.round(v * 100) / 100;
  return `${n.toFixed(2)} giờ`;
}
</script>
