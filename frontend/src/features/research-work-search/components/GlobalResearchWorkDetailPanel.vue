<template>
  <Teleport to="body">
    <!-- Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-slate-900/30"
        @click="emit('close')"
        aria-hidden="true"
      />
    </Transition>

    <!-- Drawer (desktop) / Fullscreen (mobile) -->
    <Transition
      enter-active-class="transition-transform duration-200 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-180 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200 md:w-[560px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <!-- Header -->
          <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span
                    v-if="detail"
                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                    :class="typeBadgeClass(detail.typeKey)"
                  >
                    <component
                      :is="typeIcon(detail.typeKey)"
                      class="mr-1 h-4 w-4"
                    />
                    {{ typeLabel(detail.typeKey) }}
                  </span>

                  <span
                    v-if="detail"
                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1"
                    :class="statusPillClass(detail.statusCode)"
                  >
                    {{ statusLabel(detail.statusCode) }}
                  </span>

                  <span v-if="detail" class="text-xs text-slate-500">
                    • Năm {{ detail.year }}
                  </span>
                </div>

                <div class="mt-2 truncate text-sm font-semibold text-slate-900">
                  {{ detail?.title ?? "Chi tiết công trình" }}
                </div>
              </div>

              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
                aria-label="Đóng"
                title="Đóng"
              >
                <X class="h-5 w-5" />
              </button>
            </div>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-auto p-4">
            <div v-if="loading" class="text-sm text-slate-700">
              Đang tải chi tiết…
            </div>

            <div
              v-else-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 p-4"
            >
              <div class="text-sm font-medium text-rose-700">
                Không tải được chi tiết
              </div>
              <div class="mt-1 text-xs text-rose-700">{{ error }}</div>
            </div>

            <template v-else-if="detail">
              <!-- Card 1: Info -->
              <div
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Thông tin công trình
                </div>

                <div
                  v-if="detail.abstract"
                  class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
                >
                  <div class="text-xs font-medium text-slate-700">Tóm tắt</div>
                  <div class="mt-1 text-sm text-slate-700">
                    {{ detail.abstract }}
                  </div>
                </div>

                <div class="mt-3 space-y-2">
                  <div
                    v-for="row in detail.infoRows"
                    :key="row.label"
                    class="flex items-start justify-between gap-4"
                  >
                    <div class="text-xs text-slate-500">{{ row.label }}</div>
                    <div class="text-sm font-medium text-slate-900 text-right">
                      {{ row.value }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 2: Participants -->
              <div
                class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Danh sách giảng viên tham gia
                </div>

                <div
                  class="mt-3 overflow-hidden rounded-xl border border-slate-200"
                >
                  <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-600">
                      <tr class="[&>th]:px-3 [&>th]:py-2">
                        <th>Họ tên</th>
                        <th>Đơn vị</th>
                        <th>Vai trò</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                      <tr
                        v-for="p in detail.participants"
                        :key="p.name + p.roleLabel"
                      >
                        <td class="px-3 py-2 font-medium text-slate-900">
                          {{ p.name }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ p.facultyName }}
                        </td>
                        <td class="px-3 py-2 text-slate-700">
                          {{ p.roleLabel }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Card 3: Files -->
              <div
                class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Minh chứng & tệp đính kèm
                </div>

                <div
                  v-if="detail.files.length === 0"
                  class="mt-3 text-sm text-slate-600"
                >
                  Không có tệp/đường dẫn công khai.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="f in detail.files"
                    :key="f.fileId"
                    class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2"
                  >
                    <div class="flex min-w-0 items-center gap-2">
                      <div class="rounded-lg bg-slate-100 p-2 text-slate-700">
                        <component :is="fileIcon(f.kind)" class="h-4 w-4" />
                      </div>
                      <div class="min-w-0">
                        <div
                          class="truncate text-sm font-medium text-slate-900"
                        >
                          {{ f.label }}
                        </div>
                        <div class="mt-0.5 truncate text-xs text-slate-500">
                          {{ f.url }}
                        </div>
                      </div>
                    </div>

                    <div class="flex items-center gap-2">
                      <a
                        v-if="f.kind === 'pdf'"
                        :href="f.url"
                        download
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                      >
                        <Download class="h-4 w-4" />
                        Tải xuống
                      </a>

                      <a
                        v-else
                        :href="f.url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50"
                      >
                        <ExternalLink class="h-4 w-4" />
                        Mở
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </template>

            <div v-else class="text-sm text-slate-700">
              Không có dữ liệu chi tiết.
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t border-slate-200 bg-white px-4 py-3">
            <button
              type="button"
              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
              @click="emit('close')"
            >
              Đóng
            </button>
          </div>
        </div>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
import {
  BookOpen,
  Download,
  ExternalLink,
  FileText,
  FlaskConical,
  Link2,
  Presentation,
  X,
} from "lucide-vue-next";
import type {
  ResearchWorkDetail,
  ResearchWorkFileKind,
  ResearchWorkTypeKey,
} from "../contracts/globalResearchWorkSearch.contract";
import {
  statusLabel,
  statusPillClass,
  typeBadgeClass,
  typeLabel,
} from "../contracts/globalResearchWorkSearch.contract";

const props = defineProps<{
  open: boolean;
  detail: ResearchWorkDetail | null;
  loading: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
}>();

const detail = computed(() => props.detail);

function typeIcon(typeKey: ResearchWorkTypeKey) {
  if (typeKey === "article") return FileText;
  if (typeKey === "project") return FlaskConical;
  if (typeKey === "book") return BookOpen;
  return Presentation;
}

function fileIcon(kind: ResearchWorkFileKind) {
  if (kind === "pdf") return FileText;
  return Link2;
}
</script>
