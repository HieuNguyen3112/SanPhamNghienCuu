<template>
  <div class="min-h-screen bg-slate-50">
    <div class="space-y-4 p-4 md:p-6">
      <!-- Header -->
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Thông báo xác nhận tham gia công trình"
          subtitle="Khi người khác kê khai công trình có tên bạn, hệ thống sẽ gửi yêu cầu xác nhận tại đây."
          :show-export-pdf="false"
          :show-export-excel="false"
        />
      </div>

      <!-- Notification -->
      <div
        v-if="notificationMessage"
        class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
      >
        {{ notificationMessage }}
      </div>

      <!-- Filters -->
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <div class="grid gap-3 md:grid-cols-12 md:items-end">
          <div class="md:col-span-3">
            <label class="text-xs font-medium text-slate-600">Trạng thái</label>
            <select
              v-model="filters.status"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
            >
              <option value="ALL">Tất cả</option>
              <option value="PENDING">Chờ xác nhận</option>
              <option value="ACCEPTED">Đã xác nhận</option>
              <option value="REJECTED">Đã từ chối</option>
            </select>
          </div>

          <div class="md:col-span-3">
            <label class="text-xs font-medium text-slate-600"
              >Tìm theo tên công trình</label
            >
            <div class="relative mt-1">
              <Search
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              />
              <input
                v-model.trim="filters.q"
                type="text"
                placeholder="Nhập tên công trình..."
                class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm focus:border-slate-300 focus:outline-none"
              />
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="text-xs font-medium text-slate-600">Từ ngày</label>
            <input
              v-model="filters.from"
              type="date"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
            />
          </div>

          <div class="md:col-span-2">
            <label class="text-xs font-medium text-slate-600">Đến ngày</label>
            <input
              v-model="filters.to"
              type="date"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none"
            />
          </div>

          <div class="md:col-span-2 flex justify-end">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 md:w-auto"
              @click="resetFilters"
              title="Đặt lại bộ lọc"
              aria-label="Đặt lại bộ lọc"
            >
              <RotateCcw class="h-5 w-5 text-slate-700" />
              <span class="hidden md:inline">Reset</span>
            </button>
          </div>
        </div>
      </div>

      <!-- List -->
      <ParticipationNotificationTable
        :rows="rows"
        :loading="loading"
        :current-page-number="currentPageNumber"
        :page-size="pageSize"
        :total-item-count="totalItems"
        :total-pages="totalPages"
        @update:currentPageNumber="onUpdateCurrentPageNumber"
        @update:pageSize="onUpdatePageSize"
        @row-click="openDetail"
      />

      <!-- Detail panel -->
      <ParticipationNotificationDetailPanel
        :open="detailOpen"
        :notification="selected"
        :processing="processingDecision"
        @close="closeDetail"
        @accept="acceptSelected"
        @reject="rejectSelected"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { RotateCcw, Search } from "lucide-vue-next";

import PageHeader from "@/shared/components/layout/PageHeader.vue";
import ParticipationNotificationTable from "../components/ParticipationNotificationTable.vue";
import ParticipationNotificationDetailPanel from "../components/ParticipationNotificationDetailPanel.vue";
import { useParticipationNotifications } from "../composables/useParticipationNotifications";

const {
  loading,

  filters,
  resetFilters,

  currentPageNumber,
  pageSize,
  totalPages,
  totalItems,
  onUpdateCurrentPageNumber,
  onUpdatePageSize,

  rows,

  detailOpen,
  selected,
  openDetail,
  closeDetail,
  processingDecision,

  acceptSelected,
  rejectSelected,

  notificationMessage,
} = useParticipationNotifications();
</script>
