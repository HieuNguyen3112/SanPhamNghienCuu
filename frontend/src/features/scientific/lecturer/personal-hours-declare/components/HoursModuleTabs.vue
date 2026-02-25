<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-2">
    <div class="flex flex-wrap gap-2">
      <RouterLink :to="{ name: 'hours.calculate' }" :class="tabClass(activeTab === 'calculate')">
        Tính giờ NCKH
      </RouterLink>

      <RouterLink
        :to="{ name: 'hours.calculate.evidence_missing' }"
        :class="tabClass(activeTab === 'evidence-missing')"
      >
        Minh chứng chưa nộp
        <span
          class="ml-2 inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-semibold"
          :class="activeTab === 'evidence-missing' ? 'bg-white text-slate-900' : 'bg-slate-200 text-slate-700'"
        >
          {{ missingEvidenceCount }}
        </span>
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  activeTab: "calculate" | "evidence-missing";
  missingEvidenceCount: number;
}>();

function tabClass(active: boolean) {
  return [
    "inline-flex items-center rounded-xl px-3 py-2 text-sm font-medium transition-colors",
    active
      ? "bg-slate-900 text-white"
      : "bg-slate-100 text-slate-700 hover:bg-slate-200",
  ];
}
</script>
