<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div
      class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
    >
      <div class="min-w-0">
        <div class="text-sm font-semibold text-slate-900">
          Giờ NCKH tạm tính
        </div>
        <div class="mt-0.5 text-xs text-slate-500">
          Giờ được tính tự động theo loại công trình và phân bổ theo vai trò/số
          thành viên.
        </div>
      </div>

      <div
        class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm"
      >
        <div class="text-xs text-slate-500">Tổng giờ chuẩn</div>
        <div class="text-lg font-semibold text-slate-900">
          {{ totalHoursText }}
        </div>
      </div>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs text-slate-500">Giờ của giảng viên hiện tại</div>
        <div class="mt-1 text-base font-semibold text-slate-900">
          {{ currentLecturerHoursText }}
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs text-slate-500">Số người tham gia</div>
        <div class="mt-1 text-base font-semibold text-slate-900">
          {{ totalParticipants }}
        </div>
        <div v-if="hasExternal" class="mt-1 text-xs text-slate-500">
          Trong trường: {{ membersDistribution.length }} • Ngoài trường:
          {{ externalMembers.length }}
        </div>
      </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
            >
              Thành viên
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
            >
              Vai trò
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-semibold text-slate-600"
            >
              Giờ
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-200 bg-white">
          <tr v-for="row in tableRows" :key="row.key" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium text-slate-900">
              <div class="flex items-center gap-2">
                <span>{{ row.name }}</span>
                <span
                  v-if="row.kind === 'external'"
                  class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"
                >
                  Ngoài trường
                </span>
              </div>

              <div
                v-if="row.kind === 'external' && row.organization"
                class="mt-0.5 text-xs font-normal text-slate-500"
              >
                {{ row.organization }}
              </div>
            </td>

            <td class="px-4 py-3 text-slate-600">{{ row.roleName }}</td>

            <td class="px-4 py-3 text-right font-medium text-slate-900">
              <span v-if="row.kind === 'internal'">{{ fmt(row.hours) }}</span>
              <span v-else class="text-slate-400">—</span>
            </td>
          </tr>

          <tr v-if="tableRows.length === 0">
            <td
              colspan="3"
              class="px-4 py-8 text-center text-sm text-slate-500"
            >
              Chưa đủ dữ liệu để phân bổ giờ.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="finalNote" class="mt-3 text-xs text-amber-700">
      {{ finalNote }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { HoursDistributionItem } from "../contracts/declarationSharedContract";

export type ExternalMemberItem = {
  full_name: string;
  organization?: string | null; // đơn vị/ tổ chức ngoài trường
  member_role_name: string;
};

const props = defineProps<{
  totalHours: number;
  currentLecturerHours: number;
  membersDistribution: HoursDistributionItem[];
  note?: string | null;

  // ✅ optional: thêm người ngoài trường
  externalMembers?: ExternalMemberItem[] | null;
}>();

const externalMembers = computed(() => props.externalMembers ?? []);
const hasExternal = computed(() => externalMembers.value.length > 0);

const totalParticipants = computed(
  () => props.membersDistribution.length + externalMembers.value.length
);

const totalHoursText = computed(() => fmt(props.totalHours));
const currentLecturerHoursText = computed(() =>
  fmt(props.currentLecturerHours)
);

type TableRow =
  | {
      kind: "internal";
      key: string;
      name: string;
      roleName: string;
      hours: number;
      organization?: null;
    }
  | {
      kind: "external";
      key: string;
      name: string;
      roleName: string;
      organization?: string | null;
    };

const tableRows = computed<TableRow[]>(() => {
  const internalRows: TableRow[] = props.membersDistribution.map((m) => ({
    kind: "internal",
    key: `internal:${m.lecturer_id}`,
    name: m.lecturer_name,
    roleName: m.member_role_name,
    hours: m.hours,
    organization: null,
  }));

  const externalRows: TableRow[] = externalMembers.value.map((m, idx) => ({
    kind: "external",
    key: `external:${idx}:${m.full_name}`,
    name: m.full_name,
    roleName: m.member_role_name,
    organization: m.organization ?? null,
  }));

  return [...internalRows, ...externalRows];
});

const finalNote = computed(() => {
  const notes: string[] = [];
  if (props.note) notes.push(props.note);

  if (hasExternal.value) {
    notes.push(
      `Có ${externalMembers.value.length} đồng tác giả ngoài trường. Phần giờ của tác giả ngoài trường không được phân bổ lại.`
    );
  }

  return notes.length ? notes.join(" ") : null;
});

function fmt(v: number) {
  const n = Math.round(v * 100) / 100;
  return `${n.toFixed(2)} giờ`;
}
</script>
