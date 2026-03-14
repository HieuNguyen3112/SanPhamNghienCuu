<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[90] bg-slate-950/45 backdrop-blur-[1.5px]"
        aria-hidden="true"
        @click="$emit('close')"
      />
    </Transition>

    <Transition
      enter-active-class="transition duration-200"
      enter-from-class="translate-y-3 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-3 opacity-0"
      @after-enter="onAfterEnter"
      @before-leave="onBeforeLeave"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[95] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
      >
        <div
          ref="panelRef"
          tabindex="-1"
          class="grid h-[88vh] min-h-[24rem] w-full max-w-6xl grid-rows-[auto_minmax(0,1fr)_auto] overflow-hidden rounded-2xl border border-slate-300/70 bg-white shadow-[0_26px_70px_-32px_rgba(15,23,42,0.55)]"
          @click.stop
        >
          <header class="shrink-0 flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
            <div class="min-w-0">
              <div class="truncate text-sm font-semibold text-slate-900">
                {{ title }}
              </div>
              <div class="truncate text-xs text-slate-500">
                {{ fileName }}
              </div>
            </div>

            <button
              ref="closeButtonRef"
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900"
              aria-label="Đóng xem minh chứng"
              @click="$emit('close')"
            >
              <X class="h-4 w-4" />
            </button>
          </header>

          <section
            ref="viewerSectionRef"
            class="relative flex min-h-0 min-h-[220px] flex-1 overflow-hidden bg-slate-100"
          >
            <div
              v-if="viewerBooting"
              class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 bg-slate-100 text-slate-600"
            >
              <div
                class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600"
              />
              <p class="text-sm">Đang chuẩn bị khung xem trước...</p>
            </div>

            <div
              v-else-if="combinedError"
              class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 px-4 text-center"
            >
              <p class="text-sm font-medium text-slate-700">{{ combinedError }}</p>
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                @click="$emit('retry')"
              >
                Thử lại
              </button>
            </div>

            <PdfCanvasViewer
              v-else-if="previewVisible && previewUrl"
              :src="previewUrl"
              :key="`pdf-preview-${previewUrl}`"
              class="h-full w-full"
              @loaded="onViewerLoaded"
              @error="onViewerError"
            />
          </section>

          <footer class="shrink-0 flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-3">
            <button
              v-if="showDownload"
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="propsLoading || !canDownload"
              @click="$emit('download')"
            >
              Tải xuống
            </button>
            <button
              type="button"
              class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
              @click="$emit('close')"
            >
              Đóng
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { X } from "lucide-vue-next";
import PdfCanvasViewer from "@/shared/components/pdf/PdfCanvasViewer.vue";
import {
  focusModalElement,
  restoreModalFocus,
} from "@/shared/utils/modalFocus";

const props = withDefaults(
  defineProps<{
    open: boolean;
    title?: string;
    fileName?: string;
    previewUrl?: string | null;
    loading?: boolean;
    errorMessage?: string | null;
    canDownload?: boolean;
    showDownload?: boolean;
  }>(),
  {
    title: "Xem minh chứng",
    fileName: "minh-chung.pdf",
    previewUrl: null,
    loading: false,
    errorMessage: null,
    canDownload: false,
    showDownload: true,
  },
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "retry"): void;
  (e: "download"): void;
}>();

const viewerError = ref<string | null>(null);
const previewReady = ref(false);
const panelRef = ref<HTMLElement | null>(null);
const viewerSectionRef = ref<HTMLElement | null>(null);
const closeButtonRef = ref<HTMLButtonElement | null>(null);
let previousFocusedElement: HTMLElement | null = null;
let modalReadyFrame: number | null = null;
const FOCUSABLE_SELECTOR =
  "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])";

const propsLoading = computed(() => Boolean(props.loading));
const combinedError = computed(() => props.errorMessage || viewerError.value || null);
const previewBooted = computed(() => Boolean(props.previewUrl) && previewReady.value);
const viewerBooting = computed(() => propsLoading.value || !previewBooted.value);
const previewVisible = computed(
  () => previewBooted.value && !propsLoading.value && !combinedError.value,
);

function onViewerLoaded(): void {
  viewerError.value = null;
}

function onViewerError(message: string): void {
  viewerError.value = message;
}

function setReadyStateIfOpen(): void {
  if (!props.open) {
    previewReady.value = false;
    return;
  }

  if (modalReadyFrame !== null) {
    cancelAnimationFrame(modalReadyFrame);
  }

  modalReadyFrame = requestAnimationFrame(() => {
    modalReadyFrame = requestAnimationFrame(() => {
      previewReady.value = true;
      nextTick().then(() => {
        viewerSectionRef.value?.scrollTo?.(0, 0);
      });
    });
  });
}

function getFocusableElements() {
  if (!panelRef.value) return [];
  return Array.from(
    panelRef.value.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR),
  ).filter((element) => !element.hasAttribute("disabled"));
}

function trapFocus(event: KeyboardEvent): void {
  const focusableElements = getFocusableElements();
  if (focusableElements.length === 0) {
    return;
  }

  const first = focusableElements[0];
  const last = focusableElements[focusableElements.length - 1];
  if (!first || !last) return;

  const active = document.activeElement as HTMLElement | null;
  if (event.shiftKey) {
    if (active === first || !panelRef.value?.contains(active)) {
      event.preventDefault();
      focusModalElement(last, { preventScroll: true });
    }
    return;
  }

  if (active === last || !panelRef.value?.contains(active)) {
    event.preventDefault();
    focusModalElement(first, { preventScroll: true });
  }
}

function onAfterEnter(): void {
  window.removeEventListener("keydown", onWindowKeydown);
  window.addEventListener("keydown", onWindowKeydown);
  setReadyStateIfOpen();
  focusModalElement(closeButtonRef.value, { preventScroll: true });
}

function onBeforeLeave(): void {
  previewReady.value = false;
}

function onWindowKeydown(event: KeyboardEvent): void {
  if (!props.open) return;

  if (event.key === "Escape") {
    event.preventDefault();
    emit("close");
    return;
  }

  if (event.key === "Tab") {
    trapFocus(event);
  }
}

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) {
      previewReady.value = false;
      if (modalReadyFrame !== null) {
        cancelAnimationFrame(modalReadyFrame);
        modalReadyFrame = null;
      }
      window.removeEventListener("keydown", onWindowKeydown);
      restoreModalFocus(previousFocusedElement, { preventScroll: true });
      previousFocusedElement = null;
      return;
    }

    previousFocusedElement = document.activeElement as HTMLElement | null;
    previewReady.value = false;
    await nextTick();
  },
  { immediate: true },
);

watch(
  () => props.errorMessage,
  () => {
    if (!props.open) return;
    viewerError.value = null;
  },
  { immediate: true },
);

onBeforeUnmount(() => {
  if (modalReadyFrame !== null) {
    cancelAnimationFrame(modalReadyFrame);
    modalReadyFrame = null;
  }
  window.removeEventListener("keydown", onWindowKeydown);
  restoreModalFocus(previousFocusedElement, { preventScroll: true });
});
</script>
