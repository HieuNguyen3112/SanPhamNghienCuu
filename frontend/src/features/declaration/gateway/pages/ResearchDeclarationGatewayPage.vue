<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <DeclarationIntroSection />

      <div
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <div class="text-sm text-slate-700">Đang tải dữ liệu…</div>
      </div>

      <div
        v-else-if="error"
        class="rounded-2xl border border-rose-200 bg-rose-50 p-4"
      >
        <div class="text-sm font-medium text-rose-700">
          Không tải được dữ liệu
        </div>
        <div class="mt-1 text-xs text-rose-700">{{ error }}</div>
      </div>

      <template v-else>
        <DeclarationTypeGrid
          :types="typeCards"
          @navigate="goToDeclarationType"
        />
        <DraftDeclarationsSection :drafts="drafts" @continue="continueDraft" />
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import DeclarationIntroSection from "../components/DeclarationIntroSection.vue";
import DeclarationTypeGrid from "../components/DeclarationTypeGrid.vue";
import DraftDeclarationsSection from "../components/DraftDeclarationsSection.vue";
import { useResearchDeclarationGateway } from "../composables/useResearchDeclarationGateway";

const {
  typeCards,
  drafts,
  loading,
  error,
  goToDeclarationType,
  continueDraft,
} = useResearchDeclarationGateway();
</script>
