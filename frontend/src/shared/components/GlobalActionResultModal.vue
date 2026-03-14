<template>
  <ActionResultModal
    :open="actionResultModal.open"
    :type="actionResultModal.type"
    :title="actionResultModal.title"
    :message="actionResultModal.message"
    :details="actionResultModal.details"
    :loading="actionResultModal.loading"
    :disable-close="actionResultModal.disableClose"
    :require-explicit-close="actionResultModal.requireExplicitClose"
    :close-label="actionResultModal.closeLabel"
    :secondary-label="actionResultModal.secondaryLabel"
    @close="onClose"
    @secondary="onSecondary"
  />
</template>

<script setup lang="ts">
import ActionResultModal from "@/shared/components/ActionResultModal.vue";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

const { actionResultModal, closeActionResultModal, runActionResultSecondary } =
  useActionResultModal();

async function onClose() {
  await closeActionResultModal();
}

async function onSecondary() {
  await runActionResultSecondary();
}
</script>
