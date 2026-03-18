<template>
  <div class="space-y-3">
    <div
      class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
    >
      <div class="text-sm text-slate-600">
        Đang hiển thị
        <span class="font-semibold text-slate-900">Nhà xuất bản</span>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <input
          :value="search"
          @input="
            emit('update:search', ($event.target as HTMLInputElement).value)
          "
          type="text"
          class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-300 sm:w-80"
          placeholder="Tìm theo tên/mã/email..."
        />

        <button
          type="button"
          class="relative inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          @click="emit('open-suggestions')"
        >
          <Mail class="h-4 w-4" />
          Đề xuất
          <span
            class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-bold text-amber-700"
          >
            {{ suggestions.length }}
          </span>
        </button>

        <button
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-semibold text-white hover:bg-slate-800"
          @click="emit('create')"
        >
          <Plus class="h-4 w-4" />
          Thêm nhà xuất bản
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
            <th class="px-4 py-3">Tên NXB</th>
            <th class="w-36 px-4 py-3">Mã NXB</th>
            <th class="w-[220px] px-4 py-3">Địa chỉ</th>
            <th class="w-36 px-4 py-3">Điện thoại</th>
            <th class="w-[220px] px-4 py-3">Email</th>
            <th class="w-[220px] px-4 py-3">Website</th>
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
            <td class="px-4 py-3 font-semibold text-slate-900">
              {{ row.name }}
            </td>
            <td class="px-4 py-3 text-slate-700">{{ row.code }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.address ?? "—" }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.phone ?? "—" }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.email ?? "—" }}</td>
            <td class="px-4 py-3 text-slate-700">{{ row.website ?? "—" }}</td>
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
              colspan="9"
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
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600">Tên NXB *</label>
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
            <label class="text-xs font-medium text-slate-600">Mã NXB *</label>
            <input
              :value="form.code"
              @input="
                emit('update:form', {
                  ...form,
                  code: ($event.target as HTMLInputElement).value,
                })
              "
              type="text"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm uppercase outline-none focus:border-slate-300"
              placeholder="VD: NXB-GDVN"
            />
            <p v-if="errors.code" class="mt-1 text-xs text-rose-600">
              {{ errors.code }}
            </p>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Địa chỉ</label>
          <input
            :value="form.address"
            @input="
              emit('update:form', {
                ...form,
                address: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
          />
          <p v-if="errors.address" class="mt-1 text-xs text-rose-600">
            {{ errors.address }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="text-xs font-medium text-slate-600">Điện thoại</label>
            <input
              :value="form.phone"
              @input="
                emit('update:form', {
                  ...form,
                  phone: ($event.target as HTMLInputElement).value,
                })
              "
              type="text"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            />
            <p v-if="errors.phone" class="mt-1 text-xs text-rose-600">
              {{ errors.phone }}
            </p>
          </div>

          <div>
            <label class="text-xs font-medium text-slate-600">Email</label>
            <input
              :value="form.email"
              @input="
                emit('update:form', {
                  ...form,
                  email: ($event.target as HTMLInputElement).value,
                })
              "
              type="email"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            />
            <p v-if="errors.email" class="mt-1 text-xs text-rose-600">
              {{ errors.email }}
            </p>
          </div>
        </div>

        <div>
          <label class="text-xs font-medium text-slate-600">Website</label>
          <input
            :value="form.website"
            @input="
              emit('update:form', {
                ...form,
                website: ($event.target as HTMLInputElement).value,
              })
            "
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
            placeholder="https://..."
          />
          <p v-if="errors.website" class="mt-1 text-xs text-rose-600">
            {{ errors.website }}
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

    <div v-if="suggestionsOpen" class="fixed inset-0 z-50">
      <div
        class="absolute inset-0 bg-slate-900/40"
        @click="emit('close-suggestions')"
      ></div>

      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div
          class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"
        >
          <div
            class="flex items-center justify-between border-b border-slate-200 px-4 py-3"
          >
            <div>
              <div class="text-sm font-semibold text-slate-900">
                Đề xuất nhà xuất bản chờ duyệt
              </div>
              <div class="text-xs text-slate-500">
                Duyệt đề xuất để thêm vào danh mục nhà xuất bản chính thức.
              </div>
            </div>

            <button
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-50 hover:text-slate-900"
              @click="emit('close-suggestions')"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <div class="min-h-0 flex-1 overflow-auto p-4">
            <div
              v-if="suggestionsLoading"
              class="py-10 text-center text-sm text-slate-500"
            >
              Đang tải danh sách đề xuất...
            </div>

            <div
              v-else-if="suggestions.length === 0"
              class="py-10 text-center text-sm text-slate-500"
            >
              Không có đề xuất nhà xuất bản đang chờ duyệt.
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="suggestion in suggestions"
                :key="suggestion.id"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
              >
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <div class="text-sm font-semibold text-slate-900">
                      {{
                        suggestionField(suggestion, "name") !== "—"
                          ? suggestionField(suggestion, "name")
                          : suggestion.sourceName
                      }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                      Mã kê khai: #{{ suggestion.activityId }} • Tạo lúc:
                      {{ suggestion.createdAt }}
                    </div>
                  </div>

                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      class="inline-flex h-9 items-center rounded-xl border border-rose-300 bg-white px-3 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-60"
                      :disabled="approvingSuggestionId === suggestion.id"
                      @click="
                        emit(
                          'reject-suggestion',
                          suggestion.id,
                          noteBySuggestion(suggestion.id),
                        )
                      "
                    >
                      {{
                        approvingSuggestionId === suggestion.id
                          ? "Đang xử lý..."
                          : "Từ chối"
                      }}
                    </button>
                    <button
                      type="button"
                      class="inline-flex h-9 items-center rounded-xl bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                      :disabled="approvingSuggestionId === suggestion.id"
                      @click="
                        emit(
                          'approve-suggestion',
                          suggestion.id,
                          noteBySuggestion(suggestion.id),
                        )
                      "
                    >
                      {{
                        approvingSuggestionId === suggestion.id
                          ? "Đang xử lý..."
                          : "Duyệt"
                      }}
                    </button>
                  </div>
                </div>

                <div
                  class="mt-3 grid grid-cols-1 gap-2 text-xs text-slate-700 md:grid-cols-2"
                >
                  <div>
                    <span class="font-semibold text-slate-900">Mã NXB:</span>
                    {{ suggestionField(suggestion, "code") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Địa chỉ:</span>
                    {{ suggestionField(suggestion, "address") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900"
                      >Điện thoại:</span
                    >
                    {{ suggestionField(suggestion, "phone") }}
                  </div>
                  <div>
                    <span class="font-semibold text-slate-900">Email:</span>
                    {{ suggestionField(suggestion, "email") }}
                  </div>
                  <div class="md:col-span-2">
                    <span class="font-semibold text-slate-900">Website:</span>
                    {{ suggestionField(suggestion, "website") }}
                  </div>
                </div>

                <div class="mt-3">
                  <label class="text-xs font-medium text-slate-600"
                    >Ghi chú duyệt/từ chối</label
                  >
                  <textarea
                    rows="2"
                    :value="noteBySuggestion(suggestion.id)"
                    @input="
                      setNoteBySuggestion(
                        suggestion.id,
                        ($event.target as HTMLTextAreaElement).value,
                      )
                    "
                    class="mt-1 w-full resize-none rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-slate-300"
                    placeholder="Nhập ghi chú (không bắt buộc)..."
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Mail, Pencil, Plus, X } from "lucide-vue-next";
import CatalogUpsertModal from "./CatalogUpsertModal.vue";
import type {
  Publisher,
  PublisherSuggestion,
} from "../contracts/publishers.contract";

defineProps<{
  rows: Publisher[];
  startIndex: number;
  total: number;
  search: string;
  page: number;
  pageSize: number;

  modalOpen: boolean;
  modalTitle: string;
  submitting: boolean;
  suggestionsOpen: boolean;
  suggestions: PublisherSuggestion[];
  suggestionsLoading: boolean;
  approvingSuggestionId: number | null;
  form: {
    id: number;
    name: string;
    code: string;
    address: string;
    phone: string;
    email: string;
    website: string;
    isActive: boolean;
  };
  errors: Partial<
    Record<"name" | "code" | "address" | "phone" | "email" | "website", string>
  >;
}>();

const emit = defineEmits<{
  (e: "update:search", v: string): void;
  (e: "update:page", v: number): void;
  (e: "update:pageSize", v: number): void;
  (e: "create"): void;
  (e: "edit", id: number): void;
  (e: "open-suggestions"): void;
  (e: "close-suggestions"): void;
  (e: "approve-suggestion", id: number, reviewNote?: string): void;
  (e: "reject-suggestion", id: number, reviewNote?: string): void;
  (e: "close-modal"): void;
  (e: "submit"): void;
  (
    e: "update:form",
    v: {
      id: number;
      name: string;
      code: string;
      address: string;
      phone: string;
      email: string;
      website: string;
      isActive: boolean;
    },
  ): void;
}>();

const suggestionNotes = ref<Record<number, string>>({});

function suggestionField(suggestion: PublisherSuggestion, key: string): string {
  const payload = suggestion.payload ?? {};
  const value = (payload as Record<string, unknown>)[key];
  if (value === null || value === undefined) {
    return "—";
  }
  const text = String(value).trim();
  return text === "" ? "—" : text;
}

function noteBySuggestion(id: number): string {
  return suggestionNotes.value[id] ?? "";
}

function setNoteBySuggestion(id: number, value: string) {
  suggestionNotes.value = {
    ...suggestionNotes.value,
    [id]: value,
  };
}
</script>
