<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="min-w-0">
      <div class="flex items-center gap-2">
        <ShieldCheck class="h-5 w-5 text-slate-700" />
        <h1 class="truncate text-base font-semibold text-slate-900">
          Nhật ký hệ thống
        </h1>
      </div>
      <p class="mt-1 text-sm text-slate-500">
        Theo dõi hoạt động và sự kiện quan trọng trong hệ thống.
      </p>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-12 md:items-end">
      <!-- Row 1 -->
      <div class="md:col-span-4">
        <label class="text-xs font-medium text-slate-600">Từ khóa</label>
        <div class="relative mt-1">
          <Search
            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          />
          <input
            class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-300 focus:outline-none"
            :value="modelValue.keyword"
            placeholder="Tìm theo người thực hiện / hành động / đối tượng / đường dẫn..."
            @input="
              update('keyword', ($event.target as HTMLInputElement).value)
            "
            @keydown.enter.prevent="emit('apply')"
          />
        </div>
      </div>

      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-600">
          Người thực hiện
        </label>
        <select
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="String(modelValue.actorUserId)"
          @change="onActorChange"
        >
          <option value="ALL">Tất cả</option>
          <option v-for="a in actors" :key="a.userId" :value="String(a.userId)">
            {{ a.name }} ({{ a.email }})
          </option>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Nhóm hành động</label>
        <select
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="modelValue.actionGroup"
          @change="
            update(
              'actionGroup',
              ($event.target as HTMLSelectElement).value as any,
            )
          "
        >
          <option value="ALL">Tất cả</option>
          <option v-for="g in groupOptions" :key="g.value" :value="g.value">
            {{ g.label }}
          </option>
        </select>
      </div>

      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-600">Hành động</label>
        <select
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="modelValue.actionCode"
          @change="
            update(
              'actionCode',
              ($event.target as HTMLSelectElement).value as any,
            )
          "
        >
          <option value="ALL">Tất cả</option>
          <option v-for="c in actionCodeOptions" :key="c.code" :value="c.code">
            {{ c.label }}
          </option>
        </select>
      </div>

      <!-- Row 2 -->
      <div v-if="showFacultyFilter" class="md:col-span-3">
        <label class="text-xs font-medium text-slate-600">Đơn vị (Khoa)</label>
        <select
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="String(modelValue.facultyId)"
          @change="onFacultyChange"
        >
          <option value="ALL">Tất cả</option>
          <option v-for="f in faculties" :key="f.id" :value="String(f.id)">
            {{ f.name }}
          </option>
        </select>
      </div>

      <div :class="showFacultyFilter ? 'md:col-span-2' : 'md:col-span-3'">
        <label class="text-xs font-medium text-slate-600">Từ ngày</label>
        <input
          type="date"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="modelValue.dateFrom ?? ''"
          @change="
            update(
              'dateFrom',
              (($event.target as HTMLInputElement).value || null) as any,
            )
          "
        />
      </div>

      <div :class="showFacultyFilter ? 'md:col-span-2' : 'md:col-span-3'">
        <label class="text-xs font-medium text-slate-600">Đến ngày</label>
        <input
          type="date"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="modelValue.dateTo ?? ''"
          @change="
            update(
              'dateTo',
              (($event.target as HTMLInputElement).value || null) as any,
            )
          "
        />
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Mức độ</label>
        <select
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="modelValue.severity"
          @change="
            update(
              'severity',
              ($event.target as HTMLSelectElement).value as any,
            )
          "
        >
          <option value="ALL">Tất cả</option>
          <option value="normal">Thông thường</option>
          <option value="important">Quan trọng</option>
          <option value="dangerous">Nguy hiểm</option>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Kết quả</label>
        <select
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
          :value="modelValue.result"
          @change="
            update('result', ($event.target as HTMLSelectElement).value as any)
          "
        >
          <option value="ALL">Tất cả</option>
          <option value="success">Thành công</option>
          <option value="failure">Thất bại</option>
        </select>
      </div>

      <!-- Action buttons -->
      <div
        class="md:col-span-12 flex flex-col gap-2 md:flex-row md:items-end md:justify-end"
      >
        <button
          type="button"
          class="inline-flex h-[42px] items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
          :disabled="isLoading"
          @click="emit('reset')"
        >
          <RotateCcw class="h-4 w-4" />
          Xóa lọc
        </button>

        <button
          type="button"
          class="inline-flex h-[42px] items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
          :disabled="isLoading"
          @click="emit('apply')"
        >
          <Filter class="h-4 w-4" />
          Lọc
        </button>
      </div>
    </div>

    <div
      v-if="dateRangeInvalid"
      class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
    >
      Khoảng ngày không hợp lệ: “Từ ngày” phải nhỏ hơn hoặc bằng “Đến ngày”.
    </div>
  </div>
</template>

<script setup lang="ts">
import type {
  ActorOption,
  AuditActionCodeOption,
  AuditLogFilters,
  AuditActionGroup,
  FacultyOption,
} from "../contracts/audit-log.contract";
import { Filter, RotateCcw, Search, ShieldCheck } from "lucide-vue-next";

const props = defineProps<{
  modelValue: AuditLogFilters;
  actors: ActorOption[];
  faculties: FacultyOption[];
  actionCodeOptions: AuditActionCodeOption[];
  groupOptions: Array<{ value: AuditActionGroup; label: string }>;
  showFacultyFilter: boolean;
  isLoading: boolean;
  dateRangeInvalid: boolean;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", v: AuditLogFilters): void;
  (e: "apply"): void;
  (e: "reset"): void;
}>();

function update<K extends keyof AuditLogFilters>(
  key: K,
  value: AuditLogFilters[K],
) {
  emit("update:modelValue", { ...props.modelValue, [key]: value });
}

function onActorChange(e: Event) {
  const v = (e.target as HTMLSelectElement).value;
  update("actorUserId", v === "ALL" ? "ALL" : Number(v));
}

function onFacultyChange(e: Event) {
  const v = (e.target as HTMLSelectElement).value;
  update("facultyId", v === "ALL" ? "ALL" : Number(v));
}
</script>
