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
        class="fixed inset-0 z-[85] bg-slate-950/45 backdrop-blur-[1.5px]"
        aria-hidden="true"
        @click="requestCancel"
      />
    </Transition>

    <Transition
      enter-active-class="transition duration-200"
      enter-from-class="translate-y-3 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-3 opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[90] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="resolvedTitle"
      >
        <div
          ref="panelRef"
          tabindex="-1"
          class="relative flex max-h-[90vh] w-full max-w-[480px] flex-col overflow-hidden rounded-2xl border border-slate-300/70 bg-white shadow-[0_26px_70px_-32px_rgba(15,23,42,0.55)]"
          @click.stop
        >
          <header
            class="flex items-center gap-2 px-5 py-3 text-white"
            :class="toneClasses.header"
          >
            <component :is="toneClasses.icon" class="h-4 w-4" aria-hidden="true" />
            <h2 class="truncate text-sm font-semibold tracking-wide">
              {{ resolvedTitle }}
            </h2>
          </header>

          <section class="px-6 py-6">
            <p class="whitespace-pre-line break-words text-[15px] leading-6 text-slate-700">
              {{ message }}
            </p>
          </section>

          <footer class="flex items-center justify-end gap-2 border-t border-neutral-900/10 px-5 py-4">
            <button
              ref="cancelButtonRef"
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loading"
              @click="requestCancel"
            >
              {{ cancelText }}
            </button>

            <button
              ref="confirmButtonRef"
              type="button"
              class="rounded-xl px-5 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
              :class="toneClasses.confirmButton"
              :disabled="loading"
              @click="emit('confirm')"
            >
              <span v-if="loading">{{ props.loadingText }}</span>
              <span v-else>{{ confirmText }}</span>
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import { AlertTriangle, Info } from "lucide-vue-next";
import {
  focusModalElement,
  restoreModalFocus,
} from "@/shared/utils/modalFocus";

type ConfirmModalVariant = "danger" | "warning" | "primary" | "info";

const props = withDefaults(
  defineProps<{
    open: boolean;
    title?: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    loadingText?: string;
    variant?: ConfirmModalVariant;
    loading?: boolean;
  }>(),
  {
    title: "Xác nhận thao tác",
    confirmText: "Đồng ý",
    cancelText: "Huỷ",
    loadingText: "Đang xử lý...",
    variant: "primary",
    loading: false,
  }
);

const emit = defineEmits<{
  (e: "confirm"): void;
  (e: "cancel"): void;
  (e: "update:open", value: boolean): void;
}>();

const panelRef = ref<HTMLElement | null>(null);
const cancelButtonRef = ref<HTMLButtonElement | null>(null);
const confirmButtonRef = ref<HTMLButtonElement | null>(null);
let previousFocusedElement: HTMLElement | null = null;

const resolvedTitle = computed(() => props.title?.trim() || "Xác nhận thao tác");

const toneClasses = computed(() => {
  if (props.variant === "danger") {
    return {
      header: "bg-rose-600",
      icon: AlertTriangle,
      confirmButton: "bg-rose-600 hover:bg-rose-700",
    };
  }

  if (props.variant === "warning") {
    return {
      header: "bg-amber-500",
      icon: AlertTriangle,
      confirmButton: "bg-amber-500 hover:bg-amber-600",
    };
  }
  if (props.variant === "info") {
    return {
      header: "bg-sky-600",
      icon: Info,
      confirmButton: "bg-sky-600 hover:bg-sky-700",
    };
  }

  return {
    header: "bg-slate-700",
    icon: Info,
    confirmButton: "bg-slate-900 hover:bg-slate-800",
  };
});

function requestCancel() {
  if (props.loading) return;
  emit("cancel");
  emit("update:open", false);
}

function getFocusableElements() {
  if (!panelRef.value) return [];
  return Array.from(
    panelRef.value.querySelectorAll<HTMLElement>(
      "button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])"
    )
  ).filter((item) => !item.hasAttribute("disabled"));
}

function trapFocus(event: KeyboardEvent) {
  const focusableElements = getFocusableElements();
  if (focusableElements.length === 0) return;

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

function onWindowKeydown(event: KeyboardEvent) {
  if (!props.open) return;

  if (event.key === "Escape") {
    event.preventDefault();
    requestCancel();
    return;
  }

  if (event.key === "Tab") {
    trapFocus(event);
  }
}

watch(
  () => props.open,
  async (isOpen) => {
    if (isOpen) {
      previousFocusedElement = document.activeElement as HTMLElement | null;
      window.addEventListener("keydown", onWindowKeydown);
      await nextTick();

      if (props.variant === "danger" || props.variant === "warning") {
        focusModalElement(cancelButtonRef.value, { preventScroll: true });
      } else {
        focusModalElement(confirmButtonRef.value, { preventScroll: true });
      }

      return;
    }

    window.removeEventListener("keydown", onWindowKeydown);
    restoreModalFocus(previousFocusedElement, { preventScroll: true });
    previousFocusedElement = null;
  }
);

onBeforeUnmount(() => {
  window.removeEventListener("keydown", onWindowKeydown);
});
</script>
