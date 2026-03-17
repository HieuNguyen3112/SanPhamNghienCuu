<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[60]">
      <div class="absolute inset-0 bg-black/30" @click="$emit('close')"></div>

      <div class="absolute right-0 top-0 h-full w-full max-w-4xl bg-white shadow-xl">
        <div class="flex h-full flex-col">
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
              <div class="text-xs text-slate-500">
                Trang chủ / Công trình / {{ item ? workTypeLabel(item.workType) : "" }}
              </div>

              <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                  🟢 Đã được phê duyệt
                </span>

                <span v-if="item" class="text-xs text-slate-500">
                  {{ item.facultyName }} • {{ item.academicYearCode }}
                </span>
              </div>
            </div>

            <button
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50"
              @click="$emit('close')"
            >
              Đóng
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="!item" class="text-sm text-slate-500">Không có dữ liệu.</div>

            <div v-else class="grid gap-6 md:grid-cols-12">
              <div class="md:col-span-8">
                <h1 class="text-xl font-semibold leading-snug text-slate-900">
                  {{ item.title }}
                </h1>

                <div class="mt-3 text-sm font-semibold text-slate-900">
                  {{ item.lecturerName }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ item.lecturerCode }} • {{ item.facultyName }}
                </div>

                <div v-if="detailLoading" class="mt-6 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                  Đang tải thông tin chi tiết...
                </div>

                <div v-else-if="detailError" class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                  {{ detailError }}
                </div>

                <template v-else>
                  <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">Thông tin công trình</div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                      <div>
                        <div class="text-xs text-slate-500">Mã công trình</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                          {{ detail?.activityCode || "—" }}
                        </div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Năm học</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">
                          {{ item.academicYearCode }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="item.workType === 'ARTICLE'" class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">Thông tin công khai bài báo</div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                      <div>
                        <div class="text-xs text-slate-500">Tên tạp chí</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.article?.journalName ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Năm / Tập / Số</div>
                        <div class="mt-1 font-medium text-slate-900">
                          {{ formatArticlePublication(detail?.displayMeta.article) }}
                        </div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">DOI</div>
                        <div class="mt-1 font-medium text-slate-900">
                          {{ detail?.displayMeta.article?.doi ?? '—' }}
                        </div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Link bài báo</div>
                        <div class="mt-1 font-medium text-slate-900">
                          <a
                            v-if="detail?.displayMeta.article?.articleUrl"
                            :href="detail.displayMeta.article.articleUrl"
                            target="_blank"
                            rel="noreferrer"
                            class="text-blue-600 hover:underline"
                          >
                            Mở liên kết
                          </a>
                          <span v-else>—</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else-if="item.workType === 'PROJECT'" class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">Thông tin công khai đề tài</div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                      <div>
                        <div class="text-xs text-slate-500">Mã số đề tài</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.project?.projectCode ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Cấp quản lý</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.project?.managementLevel ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Thời gian thực hiện</div>
                        <div class="mt-1 font-medium text-slate-900">
                          {{ formatDateRange(detail?.displayMeta.project?.startMonth, detail?.displayMeta.project?.endMonth) }}
                        </div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Quyết định công nhận</div>
                        <div class="mt-1 font-medium text-slate-900">
                          {{ formatDecision(detail?.displayMeta.project?.decisionNo, detail?.displayMeta.project?.decisionDate) }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else-if="item.workType === 'BOOK'" class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">Thông tin công khai giáo trình</div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                      <div>
                        <div class="text-xs text-slate-500">Nhà xuất bản</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.book?.publisher ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Năm xuất bản</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.book?.year ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">ISBN</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.book?.isbn ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Quyết định phê duyệt</div>
                        <div class="mt-1 font-medium text-slate-900">
                          {{ formatDecision(detail?.displayMeta.book?.approvalDecisionNo, detail?.displayMeta.book?.approvalDecisionDate) }}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else-if="item.workType === 'CONFERENCE'" class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-sm font-semibold text-slate-900">Thông tin công khai hội thảo</div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 text-sm">
                      <div>
                        <div class="text-xs text-slate-500">Tên hội thảo</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.conference?.conferenceName ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Thời gian</div>
                        <div class="mt-1 font-medium text-slate-900">{{ formatDate(detail?.displayMeta.conference?.heldOn) }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Địa điểm</div>
                        <div class="mt-1 font-medium text-slate-900">{{ detail?.displayMeta.conference?.location ?? '—' }}</div>
                      </div>
                      <div>
                        <div class="text-xs text-slate-500">Vai trò giảng viên</div>
                        <div class="mt-1 font-medium text-slate-900">{{ displayLecturerRole }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-6">
                    <div class="text-sm font-semibold text-slate-900">Danh sách giảng viên tham gia</div>
                    <div class="mt-2 overflow-hidden rounded-xl border border-slate-200">
                      <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-700">
                          <tr>
                            <th class="px-4 py-3 font-medium">Họ tên</th>
                            <th class="px-4 py-3 font-medium">Đơn vị</th>
                            <th class="px-4 py-3 font-medium">Vai trò</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="p in detail?.participants ?? []" :key="`${p.lecturerCode}-${p.roleName}`" class="border-t">
                            <td class="px-4 py-3 font-normal text-slate-900">{{ p.lecturerName }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ p.facultyName }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ p.roleName }}</td>
                          </tr>
                          <tr v-if="(detail?.participants?.length ?? 0) === 0">
                            <td class="px-4 py-3 text-slate-500" colspan="3">—</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="mt-6" v-if="item.keywords.length > 0">
                    <div class="text-sm font-extrabold text-slate-900">Từ khóa</div>
                    <div class="mt-2 flex flex-wrap gap-2">
                      <span
                        v-for="keyword in item.keywords"
                        :key="keyword"
                        class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700"
                      >
                        {{ keyword }}
                      </span>
                    </div>
                  </div>

                  <div class="mt-6">
                    <div class="text-sm font-semibold text-slate-900">Minh chứng & tệp đính kèm</div>
                    <div v-if="(detail?.evidenceFiles?.length ?? 0) === 0" class="mt-2 text-sm text-slate-600">
                      Không có tệp/đường dẫn công khai.
                    </div>
                    <ul v-else class="mt-2 list-disc pl-5 text-sm">
                      <li v-for="(f, idx) in detail?.evidenceFiles ?? []" :key="`${f.url}-${idx}`">
                        <a class="text-blue-600 hover:underline" :href="f.url" target="_blank" rel="noreferrer">
                          {{ f.label }}
                        </a>
                      </li>
                    </ul>
                  </div>

                  <div class="mt-6">
                    <div class="text-sm font-extrabold text-slate-900">Tóm tắt</div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">
                      {{ item.abstract || "—" }}
                    </p>
                  </div>
                </template>
              </div>

              <aside class="md:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                  <div class="aspect-[3/4] w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                    <div class="flex h-full items-center justify-center text-xs font-semibold text-slate-400">
                      Ảnh bìa (mock)
                    </div>
                  </div>

                  <button
                    type="button"
                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="onPdfClick"
                  >
                    {{ resolvedPdfUrl ? "Mở PDF" : "PDF" }}
                  </button>

                  <a
                    v-if="resolvedPdfUrl"
                    class="mt-2 inline-flex w-full items-center justify-center rounded-lg bg-[#234a74] px-3 py-2 text-sm font-semibold text-white hover:brightness-110"
                    :href="resolvedPdfUrl"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Xem minh chứng
                  </a>

                  <div class="mt-4 text-xs text-slate-500">
                    <div class="flex justify-between gap-2 py-1">
                      <span>Chuyên mục</span>
                      <span class="font-medium text-slate-700">{{ workTypeLabel(item.workType) }}</span>
                    </div>
                    <div class="flex justify-between gap-2 py-1">
                      <span>Năm học</span>
                      <span class="font-medium text-slate-700">{{ item.academicYearCode }}</span>
                    </div>
                    <div class="flex justify-between gap-2 py-1">
                      <span>Khoa</span>
                      <span class="font-medium text-slate-700">{{ item.facultyName }}</span>
                    </div>
                  </div>
                </div>
              </aside>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { PublicResearchItem } from "@/features/public-research/models/publicResearchModels";
import type { PublicResearchDetail } from "@/features/public-research/models/publicResearchModels";
import { loadPublicResearchDetailService } from "@/features/public-research/services/publicResearchService";

const props = defineProps<{
  open: boolean;
  item: PublicResearchItem | null;
}>();

defineEmits<{ (e: "close"): void }>();

function workTypeLabel(type: PublicResearchItem["workType"]): string {
  if (type === "ARTICLE") return "BÀI BÁO";
  if (type === "BOOK") return "SÁCH - GIÁO TRÌNH";
  if (type === "PROJECT") return "ĐỀ TÀI";
  if (type === "CONFERENCE") return "HỘI THẢO";
  return "KHÁC";
}

const resolvedPdfUrl = computed(() => {
  const direct = props.item?.pdfUrl?.trim();
  if (direct) return direct;

  const links = detail.value?.evidenceFiles ?? [];
  const match = links.find((entry) => {
    const url = entry.url.toLowerCase();
    return url.endsWith(".pdf") || url.includes(".pdf?");
  });
  return match?.url ?? null;
});

function onPdfClick() {
  if (!resolvedPdfUrl.value) {
    alert("Công trình chưa có PDF công khai.");
    return;
  }

  window.open(resolvedPdfUrl.value, "_blank", "noopener,noreferrer");
}

const displayLecturerRole = computed(() => {
  const lead = detail.value?.participants.find(
    (p) => p.lecturerCode === props.item?.lecturerCode,
  );
  return lead?.roleName ?? "—";
});

function formatDate(value?: string | null): string {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return new Intl.DateTimeFormat("vi-VN").format(date);
}

function formatDateRange(start?: string | null, end?: string | null): string {
  if (!start && !end) return "—";
  return `${formatDate(start)} - ${formatDate(end)}`;
}

function formatDecision(no?: string | null, date?: string | null): string {
  if (!no && !date) return "—";
  if (no && date) return `${no} (${formatDate(date)})`;
  return no ?? formatDate(date);
}

function formatArticlePublication(
  article?: {
    year: number | null;
    volume: string | null;
    issue: string | null;
    pageStart: number | null;
    pageEnd: number | null;
  },
): string {
  if (!article) return "—";
  const year = article.year ? `${article.year}` : null;
  const volume = article.volume ? `Tập ${article.volume}` : null;
  const issue = article.issue ? `Số ${article.issue}` : null;
  const pages =
    article.pageStart !== null || article.pageEnd !== null
      ? `Trang ${article.pageStart ?? "?"}-${article.pageEnd ?? "?"}`
      : null;

  const tokens = [year, volume, issue, pages].filter(Boolean);
  return tokens.length > 0 ? tokens.join(" • ") : "—";
}

// ✅ detail state
const detail = ref<PublicResearchDetail | null>(null);
const detailLoading = ref(false);
const detailError = ref<string | null>(null);

// load detail whenever open + item changes
watch(
  () => [props.open, props.item?.id] as const,
  async ([open, id]) => {
    if (!open || !id) {
      detail.value = null;
      detailError.value = null;
      detailLoading.value = false;
      return;
    }

    detailLoading.value = true;
    detailError.value = null;
    try {
      detail.value = await loadPublicResearchDetailService(id);
    } catch (e: any) {
      detail.value = null;
      detailError.value = e?.message || "Không tải được chi tiết công trình";
    } finally {
      detailLoading.value = false;
    }
  },
  { immediate: true }
);
</script>