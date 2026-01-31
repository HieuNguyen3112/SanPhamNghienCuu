<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <component :is="icon" v-if="icon" class="h-5 w-5 text-slate-700" />
            <h1 class="truncate text-lg font-semibold text-slate-900">
              {{ title }}
            </h1>
          </div>
          <p class="mt-0.5 text-sm text-slate-500">{{ description }}</p>
        </div>

        <div class="flex items-center gap-2">
          <DeclarationStatusBadge :status="status" />
        </div>
      </div>
    </div>

    <!-- Intro / guidance -->
    <div
      v-if="$slots.intro"
      class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
    >
      <slot name="intro" :read-only="readOnly" />
    </div>

    <!-- Main content -->
    <div class="space-y-4">
      <slot :read-only="readOnly" />
    </div>

    <!-- Error state -->
    <div
      v-if="errorMessage"
      class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
    >
      {{ errorMessage }}
    </div>

    <!-- Footer actions -->
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
      <div
        class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
      >
        <div class="text-sm text-slate-600">
          <span class="font-medium text-slate-900">Trạng thái:</span>
          <span class="ml-1">{{ statusText }}</span>
          <span v-if="submittedAt" class="ml-2 text-slate-400">
            • Gửi: {{ formatDateTime(submittedAt) }}
          </span>
          <span v-if="approvedAt" class="ml-2 text-slate-400">
            • Duyệt: {{ formatDateTime(approvedAt) }}
          </span>
        </div>

        <div class="flex items-center justify-end gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow-sm hover:bg-slate-50 disabled:opacity-50"
            :disabled="pending"
            @click="$emit('save-draft')"
          >
            <Save class="h-4 w-4" />
            Lưu bản nháp
          </button>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800 disabled:opacity-50"
            :disabled="pending || !canSubmit || status !== 'DRAFT'"
            @click="$emit('submit')"
          >
            <Send class="h-4 w-4" />
            Gửi duyệt
          </button>
        </div>
      </div>

      <div
        v-if="status !== 'DRAFT'"
        class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600"
      >
        Bản kê khai đang ở trạng thái
        <span class="font-semibold">{{ statusText }}</span> nên được chuyển sang
        chế độ chỉ xem.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Save, Send } from "lucide-vue-next";
import DeclarationStatusBadge from "./DeclarationStatusBadge.vue";
import type { DeclarationStatusUi } from "../contracts/declarationSharedContract";

const props = defineProps<{
  title: string;
  description: string;
  icon?: any;
  status: DeclarationStatusUi;
  canSubmit: boolean;
  pending: boolean;
  errorMessage?: string | null;
  submittedAt?: string | null;
  approvedAt?: string | null;
}>();

defineEmits<{
  (e: "save-draft"): void;
  (e: "submit"): void;
}>();

const readOnly = computed(() => props.status !== "DRAFT");

const statusText = computed(() => {
  switch (props.status) {
    case "DRAFT":
      return "Bản nháp";
    case "SUBMITTED":
      return "Đã gửi duyệt";
    case "APPROVED":
      return "Được duyệt";
    case "REJECTED":
      return "Bị từ chối";
  }
});

function formatDateTime(iso: string) {
  const d = new Date(iso);
  return d.toLocaleString();
}
</script>
