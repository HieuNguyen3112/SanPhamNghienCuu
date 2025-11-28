<template>
  <div class="space-y-6">
    <!-- === PHẦN TIÊU ĐỀ PAGE === -->
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Hồ sơ khoa học</h1>
      <p class="mt-1 text-sm text-slate-500">
        Tổng quan thông tin hồ sơ khoa học của giảng viên: liên hệ, công tác,
        đào tạo, lĩnh vực nghiên cứu, học vị – chức danh và trình độ ngoại ngữ.
      </p>
    </header>

    <!-- === GRID 2 CỘT === -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- ====== THÔNG TIN GIẢNG VIÊN ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Thông tin giảng viên
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Họ và tên:</strong></p>
          <p><strong>Giới tính:</strong></p>
          <p><strong>Ngày sinh:</strong></p>
          <p><strong>Nơi sinh:</strong></p>
          <p><strong>Dân tộc:</strong></p>
          <p><strong>Quê quán:</strong></p>
          <p><strong>Chức vụ:</strong></p>
          <p><strong>Đơn vị công tác:</strong></p>
          <p><strong>Địa chỉ liên hệ:</strong></p>
          <p><strong>Học vị:</strong></p>
          <p><strong>Chức danh:</strong></p>
          <p><strong>Chuyên ngành giảng dạy:</strong></p>
          <p><strong>Lĩnh vực nghiên cứu:</strong></p>
          <p><strong>Ngoại ngữ:</strong></p>
        </div>
      </section>

      <!-- ====== QUÁ TRÌNH CÔNG TÁC ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Quá trình công tác
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Nơi công tác:</strong></p>
        </div>
      </section>

      <!-- ====== QUÁ TRÌNH ĐÀO TẠO ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Quá trình đào tạo
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Nơi đào tạo:</strong></p>
        </div>
      </section>

      <!-- ====== THÔNG TIN ĐẢNG ====== -->
      <section class="rounded-md bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">
          Thông tin Đảng
        </h2>

        <div class="text-sm text-slate-700 space-y-1">
          <p><strong>Đảng viên:</strong></p>
          <p><strong>Ngày vào Đảng:</strong></p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
// giao diện, không có logic
</script>
