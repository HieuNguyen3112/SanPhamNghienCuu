<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
    aria-label="Quản lý năm học và đợt tính giờ"
  >
    <!-- Header -->
    <header
      class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
    >
      <div class="min-w-0">
        <h2 class="text-sm font-semibold text-slate-900">
          Năm học / Đợt tính giờ
        </h2>
        <p class="mt-1 text-xs text-slate-500">
          Quản lý năm học áp dụng tính giờ NCKH.
        </p>
      </div>

      <button
        type="button"
        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="vm.loading"
        @click="vm.openCreateYear"
        aria-label="Thêm năm học hoặc đợt"
      >
        <Plus class="h-4 w-4" />
        Thêm năm học / đợt
      </button>
    </header>

    <!-- Filters -->
    <div
      class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/40 p-3 md:p-4"
    >
      <div class="grid gap-3 md:grid-cols-12 md:items-end">
        <div class="md:col-span-3">
          <label class="text-xs font-medium text-slate-700" for="filter-kind"
            >Loại</label
          >
          <select
            id="filter-kind"
            v-model="vm.filter.kind"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
          >
            <option value="ALL">Tất cả</option>
            <option value="academic_year">Năm học</option>
            <option value="period">Đợt</option>
          </select>
        </div>

        <div class="md:col-span-3">
          <label class="text-xs font-medium text-slate-700" for="filter-status"
            >Trạng thái</label
          >
          <select
            id="filter-status"
            v-model="vm.filter.status"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
          >
            <option value="ALL">Tất cả</option>
            <option value="active">Đang áp dụng</option>
            <option value="inactive">Chưa áp dụng</option>
            <option value="locked">Đã khóa</option>
          </select>
        </div>

        <div class="md:col-span-6">
          <label class="text-xs font-medium text-slate-700" for="filter-q"
            >Tìm kiếm</label
          >
          <div class="relative mt-1">
            <input
              id="filter-q"
              v-model.trim="vm.filter.q"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
              placeholder="Tìm theo tên..."
              autocomplete="off"
            />
          </div>
        </div>
      </div>

      <div
        v-if="vm.error"
        class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
        role="alert"
      >
        {{ vm.error }}
      </div>
    </div>

    <!-- Content -->
    <div
      class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white"
    >
      <!-- Desktop table -->
      <div class="hidden md:block">
        <div class="max-h-[560px] overflow-auto">
          <table class="min-w-full text-sm">
            <thead class="sticky top-0 z-10 bg-slate-50">
              <tr class="text-left text-xs font-semibold text-slate-600">
                <th class="px-4 py-3" scope="col">Tên năm học / đợt</th>
                <th class="px-4 py-3" scope="col">Từ ngày</th>
                <th class="px-4 py-3" scope="col">Đến ngày</th>
                <th class="px-4 py-3" scope="col">Trạng thái</th>
                <th class="px-4 py-3 text-right" scope="col">Thao tác</th>
              </tr>
            </thead>

            <tbody>
              <!-- Loading skeleton -->
              <tr
                v-if="vm.loading"
                v-for="i in 6"
                :key="`sk-${i}`"
                class="border-t border-slate-100"
              >
                <td class="px-4 py-4" colspan="5">
                  <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                      <div
                        class="h-4 w-2/3 animate-pulse rounded bg-slate-200"
                      ></div>
                      <div
                        class="mt-2 h-3 w-1/3 animate-pulse rounded bg-slate-100"
                      ></div>
                    </div>
                    <div class="flex items-center gap-2">
                      <div
                        class="h-9 w-20 animate-pulse rounded-xl bg-slate-100"
                      ></div>
                      <div
                        class="h-9 w-24 animate-pulse rounded-xl bg-slate-100"
                      ></div>
                    </div>
                  </div>
                </td>
              </tr>

              <!-- Empty -->
              <tr v-else-if="vm.rows.length === 0">
                <td class="px-4 py-10 text-slate-600" colspan="5">
                  <div
                    class="flex flex-col items-center justify-center text-center"
                  >
                    <div class="text-2xl">📅</div>
                    <div class="mt-2 text-sm font-medium text-slate-900">
                      Chưa có mốc thời gian
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                      Hãy tạo năm học/đợt để bắt đầu cấu hình phạm vi tính giờ.
                    </div>
                    <button
                      type="button"
                      class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
                      @click="vm.openCreateYear"
                    >
                      <Plus class="h-4 w-4" />
                      Thêm năm học / đợt
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Rows -->
              <tr
                v-else
                v-for="r in vm.rows"
                :key="`${r.kind}-${r.id}`"
                class="border-t border-slate-100 hover:bg-slate-50"
              >
                <td class="px-4 py-3">
                  <div class="text-slate-900">{{ r.name }}</div>
                  <div class="mt-0.5 text-xs text-slate-500">
                    {{ r.kind === "academic_year" ? "Năm học" : "Đợt" }}
                  </div>
                </td>

                <td class="px-4 py-3 text-slate-700">{{ r.startDate }}</td>
                <td class="px-4 py-3 text-slate-700">{{ r.endDate }}</td>

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
                    aria-live="polite"
                  >
                    <CheckCircle2
                      v-if="r.status === 'active'"
                      class="h-3.5 w-3.5"
                    />
                    <Lock
                      v-else-if="r.status === 'locked'"
                      class="h-3.5 w-3.5"
                    />
                    <PauseCircle v-else class="h-3.5 w-3.5" />
                    {{ vm.statusLabel(r.status) }}
                  </span>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="r.isLocked || r.kind !== 'academic_year'"
                      @click="
                        r.kind === 'academic_year' &&
                        !r.isLocked &&
                        vm.openEditYear(r)
                      "
                      :title="
                        r.kind !== 'academic_year'
                          ? 'Chỉ cho phép sửa năm học'
                          : r.isLocked
                            ? 'Đã khóa'
                            : ''
                      "
                      aria-label="Sửa năm học"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                      Sửa
                    </button>

                    <button
                      v-if="r.kind === 'academic_year' && r.status !== 'active'"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 shadow-sm transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700/15"
                      @click="askActivateYear(r)"
                      aria-label="Áp dụng năm học"
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
      </div>

      <!-- Mobile cards -->
      <div class="md:hidden">
        <!-- Loading skeleton -->
        <div v-if="vm.loading" class="divide-y divide-slate-100">
          <div v-for="i in 6" :key="`m-sk-${i}`" class="p-4">
            <div class="h-4 w-2/3 animate-pulse rounded bg-slate-200"></div>
            <div
              class="mt-2 h-3 w-1/3 animate-pulse rounded bg-slate-100"
            ></div>
            <div class="mt-3 flex items-center gap-2">
              <div
                class="h-7 w-24 animate-pulse rounded-full bg-slate-100"
              ></div>
              <div
                class="h-7 w-20 animate-pulse rounded-full bg-slate-100"
              ></div>
            </div>
            <div
              class="mt-3 h-9 w-full animate-pulse rounded-xl bg-slate-100"
            ></div>
          </div>
        </div>

        <!-- Empty -->
        <div v-else-if="vm.rows.length === 0" class="p-6">
          <div class="flex flex-col items-center justify-center text-center">
            <div class="text-2xl">📅</div>
            <div class="mt-2 text-sm font-semibold text-slate-900">
              Không có dữ liệu
            </div>
            <div class="mt-1 text-xs text-slate-500">
              Tạo năm học/đợt để bắt đầu.
            </div>
            <button
              type="button"
              class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
              @click="vm.openCreateYear"
            >
              <Plus class="h-4 w-4" />
              Thêm năm học / đợt
            </button>
          </div>
        </div>

        <!-- Cards -->
        <div v-else class="divide-y divide-slate-100">
          <article
            v-for="r in vm.rows"
            :key="`m-${r.kind}-${r.id}`"
            class="p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-900">
                  {{ r.name }}
                </div>
                <div class="mt-0.5 text-xs text-slate-500">
                  {{ r.kind === "academic_year" ? "Năm học" : "Đợt" }}
                </div>
              </div>

              <span
                :class="[
                  'shrink-0 inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ring-1',
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
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
              <div class="rounded-xl bg-slate-50 px-3 py-2">
                <div class="text-slate-500">Từ ngày</div>
                <div class="mt-0.5 font-medium text-slate-900">
                  {{ r.startDate }}
                </div>
              </div>
              <div class="rounded-xl bg-slate-50 px-3 py-2">
                <div class="text-slate-500">Đến ngày</div>
                <div class="mt-0.5 font-medium text-slate-900">
                  {{ r.endDate }}
                </div>
              </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="r.isLocked || r.kind !== 'academic_year'"
                @click="
                  r.kind === 'academic_year' &&
                  !r.isLocked &&
                  vm.openEditYear(r)
                "
                :title="
                  r.kind !== 'academic_year'
                    ? 'Chỉ cho phép sửa năm học'
                    : r.isLocked
                      ? 'Đã khóa'
                      : ''
                "
              >
                <Pencil class="h-3.5 w-3.5" />
                Sửa
              </button>

              <button
                v-if="r.kind === 'academic_year' && r.status !== 'active'"
                type="button"
                class="inline-flex flex-1 items-center justify-center gap-1 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 shadow-sm transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-700/15"
                @click="askActivateYear(r)"
              >
                <CheckCircle2 class="h-3.5 w-3.5" />
                Áp dụng
              </button>
            </div>
          </article>
        </div>
      </div>

      <!-- Pagination -->
      <div class="border-t border-slate-200 bg-white px-4 py-3">
        <SharedPaginationControls
          :total-item-count="vm.totalItems"
          :current-page-number="vm.page"
          :page-size="vm.pageSize"
          display-mode="FULL"
          record-summary-mode="PAGE_COUNT"
          record-summary-unit-label="mốc thời gian"
          container-class-name="w-full"
          @update:currentPageNumber="vm.setPage"
          @update:pageSize="vm.setPageSize"
        />
      </div>
    </div>

    <!-- Modal -->
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
          <label class="text-xs font-medium text-slate-700" for="draft-code">
            Tên năm học <span class="text-rose-600">*</span>
          </label>
          <input
            id="draft-code"
            v-model.trim="vm.draft.code"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
            placeholder="VD: 2024-2025"
            :aria-invalid="Boolean(vm.draftErrors.code)"
            aria-describedby="draft-code-help"
          />
          <p
            id="draft-code-help"
            class="mt-1 text-xs"
            :class="vm.draftErrors.code ? 'text-rose-600' : 'text-slate-500'"
          >
            {{
              vm.draftErrors.code
                ? vm.draftErrors.code
                : "Nhập theo định dạng năm học, ví dụ 2024-2025."
            }}
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-700" for="draft-status"
            >Trạng thái</label
          >
          <select
            id="draft-status"
            v-model="vm.draft.status"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
          >
            <option value="inactive">Chưa áp dụng</option>
            <option value="active">Đang áp dụng</option>
          </select>
          <p class="mt-1 text-xs text-slate-500">
            Hệ thống chỉ cho phép một năm học “Đang áp dụng”.
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-700" for="draft-start">
            Ngày bắt đầu <span class="text-rose-600">*</span>
          </label>
          <input
            id="draft-start"
            v-model="vm.draft.startDate"
            type="date"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
            :aria-invalid="Boolean(vm.draftErrors.startDate)"
            aria-describedby="draft-start-help"
          />
          <p
            id="draft-start-help"
            v-if="vm.draftErrors.startDate"
            class="mt-1 text-xs text-rose-600"
          >
            {{ vm.draftErrors.startDate }}
          </p>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-700" for="draft-end">
            Ngày kết thúc <span class="text-rose-600">*</span>
          </label>
          <input
            id="draft-end"
            v-model="vm.draft.endDate"
            type="date"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
            :aria-invalid="Boolean(vm.draftErrors.endDate)"
            aria-describedby="draft-end-help"
          />
          <p
            id="draft-end-help"
            v-if="vm.draftErrors.endDate"
            class="mt-1 text-xs text-rose-600"
          >
            {{ vm.draftErrors.endDate }}
          </p>
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
        <CheckCircle2 class="h-5 w-5 text-emerald-700" />
      </template>
    </ConfirmActionModal>
  </section>
</template>

<script setup lang="ts">
import { reactive } from "vue";
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
    rows: AcademicYearPeriodRow[];
    page: number;
    pageSize: number;
    totalItems: number;

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
    setPage: (page: number) => void;
    setPageSize: (pageSize: number) => void;
  };
}>();

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
