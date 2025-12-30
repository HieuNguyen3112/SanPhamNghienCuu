<template>
  <footer class="mt-10 bg-[#234a74] text-white">
    <div class="mx-auto max-w-6xl px-4 py-10 md:px-6">
      <div class="grid min-w-0 gap-8 md:grid-cols-4">
        <!-- Col 1 -->
        <div class="min-w-0">
          <div class="flex items-center gap-3">
            <img src="/logo.png" alt="Logo" class="h-10 w-auto object-contain" />
            <div class="text-sm font-extrabold">
              Hệ thống NCKH – SP HCM
            </div>
          </div>

          <p class="mt-3 text-sm text-white/80 leading-relaxed">
            Cổng tra cứu công trình nghiên cứu khoa học đã được phê duyệt.
          </p>

          <div class="mt-4 space-y-2 text-sm text-white/80 leading-relaxed">
            <div>📍 280 An Dương Vương, Phường Chợ Quán, TP. Hồ Chí Minh</div>
            <div>✉️ Email: phongctct@hcmue.edu.vn</div>
            <div>☎️ Hotline: 028 - 38352020</div>
          </div>
        </div>

        <!-- Col 2 -->
        <div class="min-w-0">
          <div class="text-sm font-extrabold">Liên kết</div>
          <ul class="mt-3 space-y-2 text-sm text-white/80">
            <li class="hover:text-white">Trang chủ</li>
            <li class="hover:text-white">Tra cứu</li>
            <li class="hover:text-white">Giới thiệu</li>
          </ul>
        </div>

        <!-- Col 3 -->
        <div class="min-w-0">
          <div class="text-sm font-extrabold">Hỗ trợ</div>
          <ul class="mt-3 space-y-2 text-sm text-white/80">
            <li class="hover:text-white">Hướng dẫn sử dụng</li>
            <li class="hover:text-white">Câu hỏi thường gặp</li>
            <li class="hover:text-white">Liên hệ</li>
          </ul>
        </div>

        <!-- Col 4 -->
        <div class="min-w-0">
          <div class="text-sm font-extrabold">Ghi chú</div>
          <p class="mt-3 text-sm text-white/80 leading-relaxed">
            Chỉ hiển thị công trình đã được khoa & trường phê duyệt.
            Không hiển thị giờ NCKH / KPI.
          </p>
        </div>
      </div>

      <div class="mt-10 border-t border-white/15 pt-4 text-center text-xs text-white/70">
        © {{ new Date().getFullYear() }} Phần mềm quản lý NCKH
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts"></script>
