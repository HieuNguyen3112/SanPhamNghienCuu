<template>
  <section class="bg-white">
    <div class="mx-auto max-w-6xl px-4 py-12 md:px-6">
      <div class="grid gap-8 md:grid-cols-12 md:items-start">
        <div class="md:col-span-7">
          <h2 class="text-2xl font-extrabold text-slate-900">Giới thiệu tổng quan</h2>
          <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Hệ thống hỗ trợ tra cứu các công trình nghiên cứu khoa học đã được phê duyệt theo quy trình của nhà trường.
            Dữ liệu công khai chỉ phục vụ mục đích tham khảo, minh bạch thông tin và thống kê cơ bản.
          </p>
          <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Bạn có thể tìm theo giảng viên (tên/mã), khoa, loại công trình và năm học. Kết quả hiển thị thông tin chính,
            cho phép xem chi tiết qua drawer và mở PDF (khi có).
          </p>

          <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              <div class="text-xs font-semibold text-slate-500">Phạm vi</div>
              <div class="mt-1 text-sm font-extrabold text-slate-900">Công khai</div>
              <div class="mt-1 text-xs text-slate-500">Không hiển thị KPI/giờ NCKH nội bộ.</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              <div class="text-xs font-semibold text-slate-500">Dữ liệu</div>
              <div class="mt-1 text-sm font-extrabold text-slate-900">Đã phê duyệt</div>
              <div class="mt-1 text-xs text-slate-500">Theo khoa & trường xác nhận.</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
              <div class="text-xs font-semibold text-slate-500">Trải nghiệm</div>
              <div class="mt-1 text-sm font-extrabold text-slate-900">Tìm nhanh</div>
              <div class="mt-1 text-xs text-slate-500">Bộ lọc rõ ràng, phân trang ổn định.</div>
            </div>
          </div>
        </div>

        <div class="md:col-span-5">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="text-sm font-extrabold text-slate-900">Mạng lưới khoa/trường</div>
                <div class="mt-1 text-xs text-slate-500">Placeholder minh hoạ (chưa nối API).</div>
              </div>
              <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[#234a74]/10 text-[#234a74]"
                >🏫</span
              >
            </div>

            <div class="mt-4 grid grid-cols-4 gap-3">
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                CNTT
              </div>
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                Toán
              </div>
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                Văn
              </div>
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                Anh
              </div>
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                Lý
              </div>
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                Hoá
              </div>
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                Sinh
              </div>
              <div class="flex h-14 items-center justify-center rounded-2xl bg-white text-xs font-extrabold text-slate-700 shadow-sm">
                GD
              </div>
            </div>

            <div class="mt-4 rounded-xl bg-white px-3 py-2 text-xs text-slate-500 shadow-sm">
              Gợi ý: sau này có thể thay bằng “Bản đồ đơn vị / logo khoa”, hoặc số liệu thống kê.
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts"></script>