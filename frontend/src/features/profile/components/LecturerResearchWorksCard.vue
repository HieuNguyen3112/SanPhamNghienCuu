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
          @click="goToPersonalWorks"
        >
          <FileText class="h-4 w-4" />
          Công trình của tôi
        </button>

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
                <div class="text-sm font-semibold text-slate-900">
                  {{ row.title }}
                </div>

                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                  <span
                    class="rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-700"
                  >
                    {{ kindLabel(row.kind) }}
                  </span>
                  <span class="text-slate-500">{{ row.typeName || "—" }}</span>
                </div>
              </td>

              <td class="px-3 py-2 text-sm text-slate-600">
                {{ row.metaLine || "—" }}
              </td>

              <td class="px-3 py-2 text-sm text-slate-600">
                {{ row.year ?? "—" }}
              </td>

              <td class="px-3 py-2 text-sm text-slate-600">
                {{ row.role || "—" }}
              </td>
            </tr>

            <tr v-if="!loading && rows.length === 0">
              <td
                class="px-3 py-6 text-center text-sm text-slate-500"
                colspan="4"
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
                colspan="4"
              >
                Đang tải dữ liệu…
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import {
  BookOpen,
  FileText,
  FlaskConical,
  Plus,
  Presentation,
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
  void router.push({ name: map[kind] });
}

function goToPersonalWorks() {
  void router.push({ name: "works.personal" }).catch(() => {
    emit("request-toast", {
      type: "error",
      message: "Không thể mở trang Công trình của tôi. Vui lòng thử lại.",
    });
  });
}
</script>
