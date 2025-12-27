<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div
      class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm text-slate-600">
        <div class="font-medium text-slate-900">Năm học / Đợt tính giờ</div>
        <div class="mt-0.5 text-xs text-slate-500">
          TODO(P0): “Đợt” + “Đã khóa” cần schema/DTO; hiện hiển thị mock để đúng
          UI spec.
        </div>
      </div>

      <button
        type="button"
        class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
        :disabled="vm.loading"
        @click="vm.openCreateYear"
      >
        <Plus class="h-4 w-4" />
        Thêm năm học / đợt
      </button>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-12 md:items-end">
      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-600">Loại</label>
        <select
          v-model="vm.filter.kind"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
        >
          <option value="ALL">Tất cả</option>
          <option value="academic_year">Năm học</option>
          <option value="period">Đợt</option>
        </select>
      </div>

      <div class="md:col-span-3">
        <label class="text-xs font-medium text-slate-600">Trạng thái</label>
        <select
          v-model="vm.filter.status"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
        >
          <option value="ALL">Tất cả</option>
          <option value="active">Đang áp dụng</option>
          <option value="inactive">Chưa áp dụng</option>
          <option value="locked">Đã khóa</option>
        </select>
      </div>

      <div class="md:col-span-6">
        <label class="text-xs font-medium text-slate-600">Tìm kiếm</label>
        <input
          v-model.trim="vm.filter.q"
          type="text"
          class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          placeholder="Tìm theo tên..."
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
              <th class="px-4 py-3">Tên năm học / đợt</th>
              <th class="px-4 py-3">Từ ngày</th>
              <th class="px-4 py-3">Đến ngày</th>
              <th class="px-4 py-3">Trạng thái</th>
              <th class="px-4 py-3 text-right">Thao tác</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="vm.loading">
              <td class="px-4 py-4 text-slate-600" colspan="5">Đang tải...</td>
            </tr>

            <tr v-else-if="vm.filteredRows.length === 0">
              <td class="px-4 py-4 text-slate-600" colspan="5">
                Không có dữ liệu.
              </td>
            </tr>

            <tr
              v-else
              v-for="r in pagedRows"
              :key="`${r.kind}-${r.id}`"
              class="border-t border-slate-100 hover:bg-slate-50"
            >
              <td class="px-4 py-3">
                <div class="text-slate-900">{{ r.name }}</div>
                <div class="mt-0.5 text-xs text-slate-500">
                  {{ r.kind === "academic_year" ? "Năm học" : "Đợt" }}
                </div>
              </td>
              <td class="px-4 py-3">{{ r.startDate }}</td>
              <td class="px-4 py-3">{{ r.endDate }}</td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1',
                    r.status === 'active'
                      ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                      : r.status === 'locked'
                      ? 'bg-slate-100 text-slate-700 ring-slate-200'
                      : 'bg-white text-slate-700 ring-slate-200',
                  ]"
                >
                  <CheckCircle2
                    v-if="r.status === 'active'"
                    class="h-3.5 w-3.5"
                  />
                  <Lock v-else-if="r.status === 'locked'" class="h-3.5 w-3.5" />
                  <PauseCircle v-else class="h-3.5 w-3.5" />
                  {{ vm.statusLabel(r.status) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                    :disabled="r.isLocked || r.kind !== 'academic_year'"
                    @click="r.kind === 'academic_year' && vm.openEditYear(r)"
                    :title="
                      r.kind !== 'academic_year'
                        ? 'TODO(P0): edit period cần backend'
                        : r.isLocked
                        ? 'Đã khóa'
                        : 'Sửa'
                    "
                  >
                    <Pencil class="h-3.5 w-3.5" />
                    Sửa
                  </button>

                  <button
                    v-if="r.kind === 'academic_year' && r.status !== 'active'"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700 hover:bg-emerald-100"
                    @click="askActivateYear(r)"
                  >
                    <CheckCircle2 class="h-3.5 w-3.5" />
                    Áp dụng
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
          record-summary-unit-label="mốc thời gian"
          container-class-name="w-full"
          @update:currentPageNumber="currentPageNumber = $event"
          @update:pageSize="pageSize = $event"
        />
      </div>
    </div>

    <CatalogModalShell
      :open="vm.modal.open"
      :title="vm.modal.mode === 'create' ? 'Thêm năm học' : 'Chỉnh sửa năm học'"
      subtitle="Năm học ảnh hưởng phạm vi tính giờ. Khi đã khóa sẽ không thể chỉnh sửa/xóa."
      confirm-text="Lưu"
      :confirm-disabled="vm.saving"
      @close="vm.closeModal"
      @confirm="vm.saveYear"
    >
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label class="text-xs font-medium text-slate-600"
            >Tên năm học *</label
          >
          <input
            v-model.trim="vm.draft.code"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
            placeholder="VD: 2024-2025"
          />
          <p v-if="vm.draftErrors.code" class="mt-1 text-xs text-rose-600">
            {{ vm.draftErrors.code }}
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Trạng thái</label>
          <select
            v-model="vm.draft.status"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          >
            <option value="inactive">Chưa áp dụng</option>
            <option value="active">Đang áp dụng</option>
          </select>
          <p class="mt-1 text-xs text-slate-500">
            TODO: backend cần enforce chỉ 1 năm học “Đang áp dụng”.
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600"
            >Ngày bắt đầu *</label
          >
          <input
            v-model="vm.draft.startDate"
            type="date"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          />
          <p v-if="vm.draftErrors.startDate" class="mt-1 text-xs text-rose-600">
            {{ vm.draftErrors.startDate }}
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600"
            >Ngày kết thúc *</label
          >
          <input
            v-model="vm.draft.endDate"
            type="date"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
          />
          <p v-if="vm.draftErrors.endDate" class="mt-1 text-xs text-rose-600">
            {{ vm.draftErrors.endDate }}
          </p>
        </div>

        <div
          class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
          TODO(P0): “Đợt tính giờ” cần bảng/endpoint riêng. “Đã khóa” nên là
          status/flag từ backend.
        </div>
      </div>
    </CatalogModalShell>

    <ConfirmActionModal
      :open="confirm.open"
      title="Đặt năm học đang áp dụng"
      :description="confirm.description"
      confirm-text="Xác nhận áp dụng"
      :loading="vm.saving"
      @cancel="confirm.open = false"
      @confirm="doActivateYear"
    >
      <template #icon>
        <CheckCircle2 class="h-5 w-5 text-rose-700" />
      </template>
    </ConfirmActionModal>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { Plus, Pencil, CheckCircle2, PauseCircle, Lock } from "lucide-vue-next";
import CatalogModalShell from "./CatalogModalShell.vue";
import ConfirmActionModal from "./ConfirmActionModal.vue";

import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";

import type { AcademicYearPeriodRow } from "../contracts/researchHoursCatalog.contract";

const props = defineProps<{
  vm: {
    loading: boolean;
    saving: boolean;
    error: string | null;

    filter: {
      kind: "ALL" | "academic_year" | "period";
      status: "ALL" | "active" | "inactive" | "locked";
      q: string;
    };
    filteredRows: AcademicYearPeriodRow[];

    statusLabel: (s: "active" | "inactive" | "locked") => string;

    modal: { open: boolean; mode: "create" | "edit" };
    draft: {
      code: string;
      startDate: string;
      endDate: string;
      status: "active" | "inactive";
    };
    draftErrors: Record<string, string | undefined>;

    openCreateYear: () => void;
    openEditYear: (row: AcademicYearPeriodRow) => void;
    closeModal: () => void;
    saveYear: () => Promise<void> | void;
    setActiveYearWithConfirm: (id: number) => Promise<void>;
  };
}>();

// ✅ Pagination state đúng theo SharedPaginationControls
const currentPageNumber = ref(1);
const pageSize = ref(10);

// ✅ Slice rows theo trang
const pagedRows = computed<AcademicYearPeriodRow[]>(() => {
  const start = (currentPageNumber.value - 1) * pageSize.value;
  return props.vm.filteredRows.slice(start, start + pageSize.value);
});

// ✅ Reset về trang 1 khi filter/search thay đổi
watch(
  () => [props.vm.filter.kind, props.vm.filter.status, props.vm.filter.q],
  () => {
    currentPageNumber.value = 1;
  }
);

// ✅ Clamp page nếu pageSize đổi làm “vượt trang”
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
  yearId: 0,
  description: "",
});

function askActivateYear(r: AcademicYearPeriodRow) {
  confirm.open = true;
  confirm.yearId = r.id;
  confirm.description = `Bạn sắp đặt “${r.name}” là năm học đang áp dụng. Hệ thống nên đảm bảo chỉ 1 năm học active.`;
}

async function doActivateYear() {
  const id = confirm.yearId;
  confirm.open = false;
  await props.vm.setActiveYearWithConfirm(id);
}
</script>
