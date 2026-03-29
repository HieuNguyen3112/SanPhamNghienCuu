// File: src/features/lecturer/profile/components/LecturerResearchWorksCard.vue
<template>
  <div
    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="text-sm font-semibold text-slate-900">
          Công trình khoa học
        </div>
        <p class="mt-1 text-sm text-slate-500">
          Chỉ hiển thị công trình
          <span class="font-medium text-slate-700">đã duyệt</span>
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-60"
          :disabled="loading"
          @click="goToDeclare(activeTab)"
        >
          <Plus class="h-4 w-4" />
          Kê khai mới
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mt-4 flex flex-wrap gap-2">
      <button
        v-for="t in tabs"
        :key="t.kind"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-medium shadow-sm"
        :class="
          activeTab === t.kind
            ? 'border-slate-900 bg-slate-900 text-white'
            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
        "
        @click="activeTab = t.kind"
      >
        <component :is="t.icon" class="h-4 w-4" />
        {{ t.label }}
        <span
          class="ml-1 rounded-full px-2 py-0.5 text-[11px]"
          :class="
            activeTab === t.kind
              ? 'bg-white/15 text-white'
              : 'bg-slate-100 text-slate-700'
          "
        >
          {{ (approvedWorksByKind[t.kind] ?? []).length }}
        </span>
      </button>
    </div>

    <!-- Table -->
    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
      <div class="max-w-full overflow-x-auto">
        <table class="min-w-[860px] w-full text-left text-sm">
          <thead class="sticky top-0 bg-slate-50 text-xs text-slate-600">
            <tr>
              <th class="px-3 py-2 font-semibold">Tên công trình</th>
              <th class="px-3 py-2 font-semibold">Thông tin</th>
              <th class="px-3 py-2 font-semibold">Năm</th>
              <th class="px-3 py-2 font-semibold">Vai trò</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200">
            <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50">
              <td class="px-3 py-2">
                <button
                  type="button"
                  class="text-left text-sm font-semibold text-slate-900 hover:underline"
                  @click="openDetail(row)"
                >
                  {{ row.title }}
                </button>
              </td>

              <td class="px-3 py-2 text-sm text-slate-600">
                {{ row.metaLine }}
              </td>

              <td class="px-3 py-2 text-sm text-slate-600">
                {{ row.year ?? "—" }}
              </td>

              <td class="px-3 py-2 text-sm text-slate-600">
                {{ row.role || "—" }}
              </td>

              <!-- <td class="px-3 py-2 text-right">
                <div class="inline-flex items-center justify-end gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                    title="Xem"
                    @click="openDetail(row)"
                  >
                    <Eye class="h-4 w-4" />
                  </button>

                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                    title="Minh chứng"
                    @click="openEvidence(row)"
                  >
                    <Paperclip class="h-4 w-4" />
                  </button>
                </div>
              </td> -->
            </tr>

            <tr v-if="!loading && rows.length === 0">
              <td
                class="px-3 py-6 text-center text-sm text-slate-500"
                colspan="5"
              >
                {{ emptyTextByKind(activeTab) }}
                <div class="mt-3">
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                    @click="goToDeclare(activeTab)"
                  >
                    <Plus class="h-4 w-4" />
                    Đi tới trang kê khai
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="loading">
              <td
                class="px-3 py-6 text-center text-sm text-slate-500"
                colspan="5"
              >
                Đang tải dữ liệu…
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detail drawer -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="detailOpen"
          class="fixed inset-0 z-40 bg-slate-900/20"
          @click="closeDetail"
        />
      </Transition>

      <Transition
        enter-active-class="transition-transform duration-250 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
      >
        <aside
          v-if="detailOpen && selected"
          class="fixed right-0 top-0 z-50 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[520px]"
          role="dialog"
          aria-modal="true"
        >
          <div class="flex h-full flex-col">
            <div class="border-b border-slate-200 px-4 py-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-slate-900">
                    Chi tiết công trình
                  </div>
                  <div class="mt-1 text-xs text-slate-500">
                    {{ kindLabel(selected.kind) }}
                  </div>
                </div>

                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
                  @click="closeDetail"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm font-semibold text-slate-900">
                  {{ selected.title }}
                </div>

                <div class="mt-2 space-y-2 text-sm text-slate-700">
                  <div>
                    <span class="text-slate-500">Năm:</span>
                    <span class="font-medium text-slate-900">{{
                      selected.year ?? "—"
                    }}</span>
                  </div>

                  <div>
                    <span class="text-slate-500">Vai trò:</span>
                    <span class="font-medium text-slate-900">{{
                      selected.role || "—"
                    }}</span>
                  </div>

                  <div>
                    <span class="text-slate-500">Thông tin:</span>
                    <span class="font-medium text-slate-900">{{
                      selected.metaLine || "—"
                    }}</span>
                  </div>
                </div>
              </div>

              <div
                class="mt-4 rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Minh chứng
                </div>
                <p class="mt-1 text-sm text-slate-600">
                  TODO: mở danh sách file minh chứng theo activity.
                </p>
                <button
                  type="button"
                  class="mt-3 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                  @click="openEvidence(selected)"
                >
                  <Paperclip class="h-4 w-4" />
                  Xem minh chứng
                </button>
              </div>
            </div>

            <div class="border-t border-slate-200 p-4">
              <div class="flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                  @click="closeDetail"
                >
                  Đóng
                </button>
              </div>
            </div>
          </div>
        </aside>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import {
  BookOpen,
  FileText,
  FlaskConical,
  Paperclip,
  Plus,
  Presentation,
  X,
} from "lucide-vue-next";
import { useRouter } from "vue-router";
import type {
  ResearchWorkItem,
  ResearchWorkKind,
} from "../composables/useLecturerProfile";

const props = defineProps<{
  loading: boolean;
  worksByKind: Partial<Record<ResearchWorkKind, ResearchWorkItem[]>>;
}>();

const emit = defineEmits<{
  (
    e: "request-toast",
    payload: { type: "success" | "error"; message: string },
  ): void;
}>();

const router = useRouter();

const tabs: Array<{ kind: ResearchWorkKind; label: string; icon: unknown }> = [
  { kind: "paper", label: "Bài báo khoa học", icon: FileText },
  { kind: "project", label: "Đề tài nghiên cứu", icon: FlaskConical },
  { kind: "book", label: "Sách / Giáo trình", icon: BookOpen },
  { kind: "conference", label: "Báo cáo hội thảo", icon: Presentation },
];

const activeTab = ref<ResearchWorkKind>("paper");

const approvedWorksByKind = computed(() => {
  const result: Partial<Record<ResearchWorkKind, ResearchWorkItem[]>> = {};
  for (const t of tabs) {
    const src = props.worksByKind[t.kind] ?? [];
    result[t.kind] = src.filter((x) => x.status === "APPROVED");
  }
  return result;
});

const rows = computed(() => approvedWorksByKind.value[activeTab.value] ?? []);

const detailOpen = ref(false);
const selected = ref<ResearchWorkItem | null>(null);

function openDetail(row: ResearchWorkItem) {
  selected.value = row;
  detailOpen.value = true;
}
function closeDetail() {
  detailOpen.value = false;
  selected.value = null;
}

function kindLabel(kind: ResearchWorkKind) {
  if (kind === "paper") return "Bài báo khoa học";
  if (kind === "project") return "Đề tài nghiên cứu";
  if (kind === "book") return "Sách / Giáo trình";
  return "Hội thảo / Hội nghị";
}

function emptyTextByKind(kind: ResearchWorkKind) {
  if (kind === "paper") return "Bạn chưa có bài báo khoa học đã duyệt.";
  if (kind === "project") return "Bạn chưa có đề tài nghiên cứu đã duyệt.";
  if (kind === "book") return "Bạn chưa có sách / giáo trình đã duyệt.";
  return "Bạn chưa có báo cáo hội thảo đã duyệt.";
}

function goToDeclare(kind: ResearchWorkKind) {
  const map: Record<ResearchWorkKind, string> = {
    paper: "declarations.articles",
    project: "declarations.projects",
    book: "declarations.books",
    conference: "declarations.others",
  };
  router.push({ name: map[kind] });
}

function openEvidence(work: ResearchWorkItem) {
  emit("request-toast", {
    type: "success",
    message: `Minh chứng cho "${work.title}" (TODO backend/UI).`,
  });
}
</script>
