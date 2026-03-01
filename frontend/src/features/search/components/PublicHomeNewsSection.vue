<template>
  <section class="bg-slate-50">
    <div class="mx-auto max-w-6xl px-4 py-12 md:px-6">
      <div class="flex items-end justify-between gap-4">
        <div>
          <h2 class="text-2xl font-extrabold text-slate-900">Tin tức - Thông báo</h2>
          <p class="mt-2 text-sm text-slate-600">Nội dung mẫu (placeholder) theo layout ảnh tham chiếu.</p>
        </div>

        <button
          type="button"
          class="hidden h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 sm:inline-flex"
        >
          Xem thêm
        </button>
      </div>

      <div class="mt-6 grid gap-6 md:grid-cols-12">
        <!-- LEFT LIST -->
        <div class="md:col-span-7">
          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <ul class="divide-y divide-slate-200">
              <li
                v-for="n in news"
                :key="n.title"
                class="group cursor-pointer p-4 hover:bg-slate-50"
              >
                <div class="flex items-start gap-3">
                  <span class="mt-1 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#234a74]/10 text-[#234a74]">
                    {{ n.icon }}
                  </span>

                  <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-extrabold text-[#234a74] group-hover:underline">
                      {{ n.title }}
                    </div>
                    <div class="mt-1 text-xs leading-relaxed text-slate-500">
                      {{ n.desc }}
                    </div>
                  </div>

                  <div class="shrink-0 text-xs font-semibold text-slate-400">
                    {{ n.date }}
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- RIGHT SIDE CARDS -->
        <div class="space-y-4 md:col-span-5">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#e11d48]/10 text-[#e11d48]">
                ❓
              </div>
              <div>
                <div class="text-sm font-extrabold text-slate-900">Câu hỏi thường gặp</div>
                <div class="text-xs text-slate-500">Giải đáp nhanh cho giảng viên.</div>
              </div>
            </div>

            <ul class="mt-3 space-y-2 text-sm text-slate-600">
              <li class="flex gap-2"><span class="text-slate-400">•</span> Quy trình phê duyệt công trình</li>
              <li class="flex gap-2"><span class="text-slate-400">•</span> Cách tìm theo giảng viên/khoa</li>
              <li class="flex gap-2"><span class="text-slate-400">•</span> Khi nào có PDF/chi tiết minh chứng</li>
            </ul>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#234a74]/10 text-[#234a74]">
                📄
              </div>
              <div>
                <div class="text-sm font-extrabold text-slate-900">Tài liệu hướng dẫn</div>
                <div class="text-xs text-slate-500">Mẫu biểu, quy định, hướng dẫn kê khai.</div>
              </div>
            </div>

            <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
              Placeholder: sau này gắn link tải file / trang hướng dẫn.
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 flex sm:hidden">
        <button
          type="button"
          class="h-10 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50"
        >
          Xem thêm
        </button>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const news = [
  {
    icon: "📌",
    title: "Hướng dẫn cập nhật thông tin cá nhân khoa học",
    desc: "Quy trình cập nhật thông tin và minh chứng theo biểu mẫu chuẩn.",
    date: "—",
  },
  {
    icon: "🗓️",
    title: "Kế hoạch xét duyệt công trình theo năm học",
    desc: "Mốc thời gian và điều kiện để công trình được đưa vào hệ thống công khai.",
    date: "—",
  },
  {
    icon: "✅",
    title: "Lưu ý về tiêu chí hiển thị công khai",
    desc: "Chỉ hiển thị công trình đã được khoa & trường phê duyệt; không hiển thị dữ liệu quản trị nội bộ.",
    date: "—",
  },
  {
    icon: "📣",
    title: "Thông báo chung từ phòng/ban chức năng",
    desc: "Các thông tin liên quan đến NCKH, biểu mẫu và liên hệ hỗ trợ.",
    date: "—",
  },
];
</script>