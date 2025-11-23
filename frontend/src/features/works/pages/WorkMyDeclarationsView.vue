<!-- src/features/works/pages/WorkMyDeclarationsView.vue -->
<template>
  <div class="space-y-6">
    <header>
      <h1 class="text-2xl font-semibold text-slate-800">
        Công trình khoa học của tôi
      </h1>
      <p class="mt-1 text-sm text-slate-500">
        Danh sách các bài báo, đề tài, sách chuyên khảo mà bạn đã khai báo trong
        năm học hiện tại.
      </p>
    </header>

    <section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
      <div class="mb-4 flex items-center justify-between gap-3">
        <div>
          <p class="text-sm font-semibold text-slate-800">Tổng kết nhanh</p>
          <p class="text-xs text-slate-500">
            Dữ liệu mô phỏng, sẽ kết nối API sau.
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg bg-sky-600 px-4 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700"
        >
          Thêm công trình mới
        </button>
      </div>

      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg bg-slate-50 p-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Số công trình đã khai báo
          </p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">12</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Đã được phê duyệt
          </p>
          <p class="mt-2 text-2xl font-semibold text-emerald-600">8</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-3">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
            Đang chờ phê duyệt
          </p>
          <p class="mt-2 text-2xl font-semibold text-amber-600">4</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
// tạm thời chỉ mock UI, logic sẽ thêm sau
</script>
