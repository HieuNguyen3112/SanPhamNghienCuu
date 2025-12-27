<template>
  <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
    <KpiCard
      title="Tổng số giảng viên"
      :value="String(totalLecturers)"
      tone="neutral"
      icon="👥"
    />
    <KpiCard
      title="Đạt định mức"
      :value="String(hitCount)"
      tone="hit"
      icon="🟢"
    />
    <KpiCard
      title="Chưa đạt"
      :value="String(missCount)"
      tone="miss"
      icon="🔴"
    />
    <KpiCard
      title="Tỷ lệ hoàn thành KPI"
      :value="`${hitRate.toFixed(1)}%`"
      :tone="hitRateTone"
      icon="📈"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import KpiCard from "@/features/scientific/management/lecturer-hours-management/components/internal/KpiCard.vue";

interface Props {
  totalLecturers: number;
  hitCount: number;
  missCount: number;
  hitRate: number;
}

const props = defineProps<Props>();

const hitRateTone = computed(() => {
  if (props.hitRate >= 100) return "hit";
  if (props.hitRate >= 80) return "near";
  return "miss";
});
</script>
