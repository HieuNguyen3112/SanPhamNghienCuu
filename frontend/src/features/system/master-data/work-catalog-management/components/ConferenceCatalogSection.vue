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
                {{ row.notes ?? "—" }}
              </div>
            </td>
            <td class="px-4 py-3 text-slate-700">
              {{
                row.level === "FACULTY"
                  ? "Khoa"
                  : row.level === "UNIVERSITY"
                    ? "Trường"
                    : row.level === "NATIONAL"
                      ? "Quốc gia"
                      : "Quốc tế"
              }}
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
              colspan="5"
              class="px-4 py-10 text-center text-sm text-slate-500"
            >
              Không có dữ liệu phù hợp.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-end">
      <!-- <SharedPaginationControls
        :page="page"
        :page-size="pageSize"
        :total="total"
        @update:page="emit('update:page', $event)"
        @update:page-size="emit('update:pageSize', $event)"
      /> -->
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

        <div>
          <label class="text-xs font-medium text-slate-600">Cấp hội nghị</label>
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
            <option value="FACULTY">Khoa</option>
            <option value="UNIVERSITY">Trường</option>
            <option value="NATIONAL">Quốc gia</option>
            <option value="INTERNATIONAL">Quốc tế</option>
          </select>
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
  </div>
</template>

<script setup lang="ts">
import { Pencil, Plus } from "lucide-vue-next";
import CatalogUpsertModal from "./CatalogUpsertModal.vue";
import type { Conference } from "../contracts/conferences.contract";
// import SharedPaginationControls from "@/shared/components/layout/SharedPaginationControls.vue";

defineProps<{
  rows: Conference[];
  startIndex: number;
  total: number;
  search: string;
  page: number;
  pageSize: number;

  modalOpen: boolean;
  modalTitle: string;
  submitting: boolean;
  form: {
    id: number;
    name: string;
    level: "FACULTY" | "UNIVERSITY" | "NATIONAL" | "INTERNATIONAL";
    notes: string;
    isActive: boolean;
  };
  errors: Partial<Record<"name" | "notes", string>>;
}>();

const emit = defineEmits<{
  (e: "update:search", v: string): void;
  (e: "update:page", v: number): void;
  (e: "update:pageSize", v: number): void;
  (e: "create"): void;
  (e: "edit", id: number): void;
  (e: "close-modal"): void;
  (e: "submit"): void;
  (
    e: "update:form",
    v: {
      id: number;
      name: string;
      level: "FACULTY" | "UNIVERSITY" | "NATIONAL" | "INTERNATIONAL";
      notes: string;
      isActive: boolean;
    },
  ): void;
}>();
</script>
