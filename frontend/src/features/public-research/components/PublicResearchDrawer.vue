<template>
  <Teleport to="body">
    <div v-if="open" class="fixed inset-0 z-[60]">
      <!-- overlay -->
      <div class="absolute inset-0 bg-black/30" @click="$emit('close')"></div>

      <!-- panel -->
      <div
        class="absolute right-0 top-0 h-full w-full max-w-4xl bg-white shadow-xl"
      >
        <div class="flex h-full flex-col">
          <!-- header -->
          <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div class="min-w-0">
              <div class="text-xs text-slate-500">
                Trang chủ / Công trình / {{ item ? workTypeLabel(item.workType) : "" }}
              </div>

              <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
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

          <!-- body -->
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="!item" class="text-sm text-slate-500">Không có dữ liệu.</div>

            <div v-else class="grid gap-6 md:grid-cols-12">
              <!-- LEFT CONTENT -->
              <div class="md:col-span-8">
                <h1 class="text-xl font-extrabold leading-snug text-slate-900">
                  {{ item.title }}
                </h1>

                <div class="mt-3 text-sm font-semibold text-slate-900">
                  {{ item.lecturerName }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ item.lecturerCode }} • {{ item.facultyName }}
                </div>

                <div class="mt-4">
                  <div class="text-sm font-extrabold text-slate-900">Từ khóa</div>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <span
                      v-for="k in keywords"
                      :key="k"
                      class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700"
                    >
                      {{ k }}
                    </span>
                  </div>
                </div>

                <div class="mt-6">
                  <div class="text-sm font-extrabold text-slate-900">Tóm tắt</div>
                  <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">
                    {{ item.abstract }}
                  </p>
                </div>
              </div>

              <!-- RIGHT SIDEBAR -->
              <aside class="md:col-span-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                  <div class="aspect-[3/4] w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                    <!-- cover placeholder -->
                    <div class="flex h-full items-center justify-center text-xs font-semibold text-slate-400">
                      Ảnh bìa (mock)
                    </div>
                  </div>

                  <button
                    type="button"
                    class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    @click="onPdfClick(item)"
                  >
                    PDF
                  </button>

                  <div class="mt-4 text-xs text-slate-500">
                    <div class="flex justify-between gap-2 py-1">
                      <span>Chuyên mục</span>
                      <span class="font-semibold text-slate-700">{{ workTypeLabel(item.workType) }}</span>
                    </div>
                    <div class="flex justify-between gap-2 py-1">
                      <span>Năm học</span>
                      <span class="font-semibold text-slate-700">{{ item.academicYearCode }}</span>
                    </div>
                    <div class="flex justify-between gap-2 py-1">
                      <span>Khoa</span>
                      <span class="font-semibold text-slate-700">{{ item.facultyName }}</span>
                    </div>
                  </div>
                </div>
              </aside>
            </div>
          </div>

          <!-- footer note -->
          <div class="border-t border-slate-200 bg-amber-50 px-5 py-3 text-sm text-amber-800">
            ⚠️ Chỉ hiển thị các công trình đã được khoa và trường phê duyệt.
            Không hiển thị giờ NCKH hoặc dữ liệu quản lý nội bộ.
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { PublicResearchItem } from "@/features/public-research/models/publicResearchModels";

const props = defineProps<{
  open: boolean;
  item: PublicResearchItem | null;
}>();

defineEmits<{
  (e: "close"): void;
}>();

function workTypeLabel(type: PublicResearchItem["workType"]): string {
  if (type === "ARTICLE") return "BÀI BÁO";
  if (type === "BOOK") return "SÁCH";
  if (type === "PROJECT") return "ĐỀ TÀI";
  if (type === "CONFERENCE") return "HỘI THẢO";
  return "KHÁC";
}

const keywords = computed(() => {
  if (!props.item) return [];
  // mock keywords: lấy từ khoa + loại + năm học + 1 từ trong title
  const firstWord = props.item.title.split(/\s+/).slice(0, 2).join(" ");
  return [
    "Nghiên cứu",
    props.item.facultyName,
    workTypeLabel(props.item.workType),
    props.item.academicYearCode,
    firstWord,
  ].slice(0, 5);
});

function onPdfClick(_item: PublicResearchItem) {
  alert("Chưa có file PDF (mock). Khi nối BE, map pdf_url vào đây là mở được.");
}
</script>
