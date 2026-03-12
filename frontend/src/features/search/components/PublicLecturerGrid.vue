<template>
  <div>
    <div class="flex items-end justify-between gap-3">
      <div>
        <div class="text-lg font-extrabold text-slate-900">
          Danh sách giảng viên
        </div>
        <div class="mt-1 text-sm text-slate-600">
          Hiển thị <b>{{ lecturers.length }}</b> giảng viên (theo dữ liệu hiện
          có).
        </div>
      </div>
    </div>

    <div
      v-if="lecturers.length === 0"
      class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600"
    >
      Không có giảng viên phù hợp.
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="lec in lecturers"
        :key="lec.lecturerId"
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md"
      >
        <!-- header -->
        <div class="flex items-start justify-between gap-3">
          <div class="flex min-w-0 items-start gap-3">
            <!-- avatar -->
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-sm font-extrabold text-[#234a74]"
              aria-hidden="true"
            >
              <img
                src="/avatar.jpg"
                alt="Avatar giảng viên"
                class="h-full w-full object-cover"
              />
              {{ lec.initials }}
            </div>

            <div class="min-w-0">
              <div class="truncate text-base font-extrabold text-[#e11d48]">
                {{ lec.lecturerName }}
              </div>
              <div class="mt-1 text-xs text-slate-600">
                <span class="font-semibold">Mã:</span> {{ lec.lecturerCode }}
                <span class="mx-2 text-slate-300">•</span>
                <span class="font-semibold">Khoa:</span>
                {{ lec.facultyName || "—" }}
              </div>
            </div>
          </div>

          <!-- detail button -->
          <RouterLink
            :to="{
              name: 'public-lecturer-detail',
              params: { lecturerCode: lec.lecturerCode },
            }"
            class="shrink-0 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-extrabold text-slate-700 shadow-sm hover:bg-slate-50"
          >
            Chi tiết
          </RouterLink>
        </div>

        <!-- counters -->
        <div class="mt-4 grid grid-cols-4 gap-2">
          <button
            class="rounded-2xl bg-slate-50 p-3 text-left hover:bg-slate-100"
            @click="
              $emit('jump-work', {
                type: 'ARTICLE',
                lecturerQuery: lec.lecturerQueryPretty,
              })
            "
          >
            <div class="text-lg font-extrabold text-slate-900">
              {{ lec.counts.ARTICLE }}
            </div>
            <div class="mt-0.5 text-[11px] font-bold text-slate-600">
              Bài báo
            </div>
          </button>

          <button
            class="rounded-2xl bg-slate-50 p-3 text-left hover:bg-slate-100"
            @click="
              $emit('jump-work', {
                type: 'BOOK',
                lecturerQuery: lec.lecturerQueryPretty,
              })
            "
          >
            <div class="text-lg font-extrabold text-slate-900">
              {{ lec.counts.BOOK }}
            </div>
            <div class="mt-0.5 text-[11px] font-bold text-slate-600">Sách</div>
          </button>

          <button
            class="rounded-2xl bg-slate-50 p-3 text-left hover:bg-slate-100"
            @click="
              $emit('jump-work', {
                type: 'PROJECT',
                lecturerQuery: lec.lecturerQueryPretty,
              })
            "
          >
            <div class="text-lg font-extrabold text-slate-900">
              {{ lec.counts.PROJECT }}
            </div>
            <div class="mt-0.5 text-[11px] font-bold text-slate-600">
              Đề tài
            </div>
          </button>

          <button
            class="rounded-2xl bg-slate-50 p-3 text-left hover:bg-slate-100"
            @click="
              $emit('jump-work', {
                type: 'CONFERENCE',
                lecturerQuery: lec.lecturerQueryPretty,
              })
            "
          >
            <div class="text-lg font-extrabold text-slate-900">
              {{ lec.counts.CONFERENCE }}
            </div>
            <div class="mt-0.5 text-[11px] font-bold text-slate-600">
              Hội thảo
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterLink } from "vue-router";
import type { PublicResearchWorkType } from "@/features/public-research/models/publicResearchModels";

export type LecturerCard = {
  lecturerId: number;
  lecturerCode: string;
  lecturerName: string;
  facultyName: string;
  initials: string;
  lecturerQueryPretty: string; // "Tên / Mã"
  counts: Record<PublicResearchWorkType, number>;
};

defineProps<{ lecturers: LecturerCard[] }>();

defineEmits<{
  (
    e: "jump-work",
    payload: { type: PublicResearchWorkType; lecturerQuery: string },
  ): void;
}>();
</script>
