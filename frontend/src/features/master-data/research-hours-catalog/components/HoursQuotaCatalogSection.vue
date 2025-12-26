<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div
      class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm text-slate-600">
        <div class="font-medium text-slate-900">Định mức giờ NCKH</div>
        <div class="mt-0.5 text-xs text-slate-500">
          TODO(P0): schema hiện thiếu dimension “đối tượng” — tab này đang chạy
          mock.
        </div>
      </div>

      <button
        type="button"
        class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
        :disabled="vm.loading"
        @click="vm.openCreate"
      >
        <Plus class="h-4 w-4" />
        Thêm định mức
      </button>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-12 md:items-end">
      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-600">Năm học</label>
        <select
          v-model.number="vm.filter.academicYearId"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
        >
          <option :value="0">Tất cả</option>
          <option
            v-for="y in vm.academicYearOptions"
            :key="y.value"
            :value="y.value"
          >
            {{ y.label }}
          </option>
        </select>
      </div>

      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-600">Trạng thái</label>
        <select
          v-model="vm.filter.status"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
        >
          <option value="ALL">Tất cả</option>
          <option value="ACTIVE">Đang áp dụng</option>
          <option value="INACTIVE">Ngừng áp dụng</option>
        </select>
      </div>

      <div class="md:col-span-6">
        <label class="text-xs font-medium text-slate-600">Tìm kiếm</label>
        <input
          v-model.trim="vm.filter.q"
          type="text"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          placeholder="Tìm theo năm học / đối tượng..."
        />
      </div>
    </div>

    <div
      v-if="vm.error"
      class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
    >
      {{ vm.error }}
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
      <div class="max-h-[560px] overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="sticky top-0 z-10 bg-slate-50">
            <tr class="text-left text-xs font-semibold text-slate-600">
              <th class="px-4 py-3">Năm học</th>
              <th class="px-4 py-3">Đối tượng</th>
              <th class="px-4 py-3">Định mức giờ</th>
              <th class="px-4 py-3">Ghi chú</th>
              <th class="px-4 py-3">Trạng thái</th>
              <th class="px-4 py-3 text-right">Thao tác</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="vm.loading">
              <td class="px-4 py-4 text-slate-600" colspan="6">Đang tải...</td>
            </tr>

            <tr v-else-if="vm.filteredRows.length === 0">
              <td class="px-4 py-4 text-slate-600" colspan="6">
                Không có dữ liệu.
              </td>
            </tr>

            <tr
              v-else
              v-for="r in vm.filteredRows"
              :key="r.id"
              class="border-t border-slate-100 hover:bg-slate-50"
            >
              <td class="px-4 py-3">{{ r.academicYearCode }}</td>
              <td class="px-4 py-3">{{ r.academicRankName }}</td>
              <td class="px-4 py-3">{{ r.requiredHours }}</td>
              <td class="px-4 py-3 text-slate-600">{{ r.notes || "—" }}</td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1',
                    r.isActive
                      ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                      : 'bg-slate-100 text-slate-700 ring-slate-200',
                  ]"
                >
                  <CheckCircle2 v-if="r.isActive" class="h-3.5 w-3.5" />
                  <PauseCircle v-else class="h-3.5 w-3.5" />
                  {{ r.isActive ? "Đang áp dụng" : "Ngừng áp dụng" }}
                </span>

                <span
                  v-if="r.isLocked"
                  class="ml-2 inline-flex items-center gap-1 text-xs text-slate-500"
                >
                  <Lock class="h-3.5 w-3.5" />
                  Đã dùng
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 hover:bg-slate-50"
                    @click="vm.openEdit(r)"
                  >
                    <Pencil class="h-3.5 w-3.5" />
                    Sửa
                  </button>

                  <button
                    v-if="r.isActive"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 hover:bg-rose-100"
                    @click="askDeactivate(r)"
                  >
                    <PauseCircle class="h-3.5 w-3.5" />
                    Ngừng
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="border-t border-slate-200 bg-white px-4 py-3">
        <SharedPaginationControls
          :total-item-count="vm.filteredRows.length"
          :current-page-number="currentPageNumber"
          :page-size="pageSize"
          display-mode="FULL"
          record-summary-mode="PAGE_COUNT"
          record-summary-unit-label="định mức"
          container-class-name="w-full"
          @update:currentPageNumber="currentPageNumber = $event"
          @update:pageSize="pageSize = $event"
        />
      </div>
    </div>

    <CatalogModalShell
      :open="vm.modal.open"
      :title="
        vm.modal.mode === 'create' ? 'Thêm định mức' : 'Chỉnh sửa định mức'
      "
      subtitle="Cấu hình hệ thống — hạn chế chỉnh sửa khi đã dùng tính KPI."
      confirm-text="Lưu"
      :confirm-disabled="vm.saving"
      @close="vm.closeModal"
      @confirm="vm.save"
    >
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="text-xs font-medium text-slate-600">Năm học *</label>
          <select
            v-model.number="vm.draft.academicYearId"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          >
            <option :value="null" disabled>Chọn năm học</option>
            <option
              v-for="y in vm.academicYearOptions"
              :key="y.value"
              :value="y.value"
            >
              {{ y.label }}
            </option>
          </select>
          <p
            v-if="vm.draftErrors.academicYearId"
            class="mt-1 text-xs text-rose-600"
          >
            {{ vm.draftErrors.academicYearId }}
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600"
            >Đối tượng áp dụng *</label
          >
          <select
            v-model.number="vm.draft.academicRankId"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          >
            <option :value="null" disabled>Chọn đối tượng</option>
            <option v-for="o in vm.rankOptions" :key="o.value" :value="o.value">
              {{ o.label }}
            </option>
          </select>
          <p
            v-if="vm.draftErrors.academicRankId"
            class="mt-1 text-xs text-rose-600"
          >
            {{ vm.draftErrors.academicRankId }}
          </p>
        </div>

        <div class="md:col-span-2">
          <label class="text-xs font-medium text-slate-600"
            >Số giờ định mức *</label
          >
          <input
            v-model.number="vm.draft.requiredHours"
            type="number"
            min="0"
            step="0.25"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm disabled:bg-slate-50"
            :disabled="vm.modal.mode === 'edit' && vm.modal.isLocked"
          />
          <p
            v-if="vm.modal.mode === 'edit' && vm.modal.isLocked"
            class="mt-1 text-xs text-slate-500"
          >
            <Lock class="mr-1 inline-block h-3.5 w-3.5" />
            Định mức đã dùng — không thể sửa số giờ. Chỉ có thể ngừng áp dụng.
          </p>
          <p
            v-if="vm.draftErrors.requiredHours"
            class="mt-1 text-xs text-rose-600"
          >
            {{ vm.draftErrors.requiredHours }}
          </p>
        </div>

        <div class="md:col-span-2">
          <label class="text-xs font-medium text-slate-600">Ghi chú</label>
          <textarea
            v-model.trim="vm.draft.notes"
            rows="3"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          ></textarea>
          <p v-if="vm.draftErrors.notes" class="mt-1 text-xs text-rose-600">
            {{ vm.draftErrors.notes }}
          </p>
        </div>

        <div class="md:col-span-2">
          <label class="text-xs font-medium text-slate-600">Trạng thái</label>
          <select
            v-model="vm.draft.isActive"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          >
            <option :value="true">Đang áp dụng</option>
            <option :value="false">Ngừng áp dụng</option>
          </select>
        </div>

        <div
          class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
          TODO(P0): cần schema/endpoint để lưu “đối tượng áp dụng”
          (academic_rank_id) + trạng thái.
        </div>
      </div>
    </CatalogModalShell>

    <ConfirmActionModal
      :open="confirm.open"
      title="Ngừng áp dụng định mức"
      :description="confirm.description"
      confirm-text="Xác nhận ngừng"
      :loading="vm.saving"
      @cancel="confirm.open = false"
      @confirm="doDeactivate"
    >
      <template #icon>
        <PauseCircle class="h-5 w-5 text-rose-700" />
      </template>
    </ConfirmActionModal>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { Plus, Pencil, CheckCircle2, PauseCircle, Lock } from "lucide-vue-next";
import CatalogModalShell from "./CatalogModalShell.vue";
import ConfirmActionModal from "./ConfirmActionModal.vue";
import type { HoursQuotaErrors } from "../contracts/researchHoursCatalog.contract";
import SharedPaginationControls from "@/shared/components/SharedPaginationControls.vue";

import type { HoursQuotaRow } from "../contracts/researchHoursCatalog.contract";

const props = defineProps<{
  vm: {
    loading: boolean;
    saving: boolean;
    error: string | null;

    filter: {
      academicYearId: number;
      status: "ALL" | "ACTIVE" | "INACTIVE";
      q: string;
    };
    filteredRows: HoursQuotaRow[];

    academicYearOptions: Array<{ value: number; label: string }>;
    rankOptions: Array<{ value: number; label: string }>;

    modal: { open: boolean; mode: "create" | "edit"; isLocked: boolean };
    draft: {
      academicYearId: number | null;
      academicRankId: number | null;
      requiredHours: number | null;
      notes: string;
      isActive: boolean;
    };
    draftErrors: HoursQuotaErrors;
    openCreate: () => void;
    openEdit: (row: HoursQuotaRow) => void;
    closeModal: () => void;
    save: () => Promise<void> | void;
    setActiveWithConfirm: (id: number, isActive: boolean) => Promise<void>;
  };
}>();

// ✅ Pagination state đúng signature component
const currentPageNumber = ref(1);
const pageSize = ref(10);

// ✅ Slice theo trang
const pagedRows = computed<HoursQuotaRow[]>(() => {
  const start = (currentPageNumber.value - 1) * pageSize.value;
  return props.vm.filteredRows.slice(start, start + pageSize.value);
});

// ✅ Reset trang khi filter/search đổi
watch(
  () => [
    props.vm.filter.academicYearId,
    props.vm.filter.status,
    props.vm.filter.q,
  ],
  () => {
    currentPageNumber.value = 1;
  }
);

// ✅ Clamp page khi data/pageSize đổi
watch(
  () => [props.vm.filteredRows.length, pageSize.value],
  () => {
    const total = props.vm.filteredRows.length;
    const maxPage = Math.max(1, Math.ceil(total / pageSize.value));
    if (currentPageNumber.value > maxPage) currentPageNumber.value = maxPage;
  }
);

const confirm = reactive({
  open: false,
  rowId: 0,
  description: "",
});

function askDeactivate(r: HoursQuotaRow) {
  confirm.open = true;
  confirm.rowId = r.id;
  confirm.description = `Bạn sắp ngừng áp dụng định mức: ${r.academicYearCode} • ${r.academicRankName}.`;
}

async function doDeactivate() {
  const id = confirm.rowId;
  confirm.open = false;
  await props.vm.setActiveWithConfirm(id, false);
}
</script>
