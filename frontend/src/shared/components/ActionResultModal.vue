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
        class="fixed inset-0 z-[70] bg-slate-950/45 backdrop-blur-[1.5px]"
        aria-hidden="true"
        @click="requestClose"
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
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
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
            class="flex items-center justify-between px-5 py-3 text-white"
            :class="toneClasses.header"
          >
            <h2 class="truncate text-sm font-semibold tracking-wide">
              {{ resolvedTitle }}
            </h2>

            <button
              v-if="!loading"
              ref="closeButtonRef"
              type="button"
              class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/15 bg-white/10 text-white/90 transition hover:bg-white/20 hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="disableClose"
              aria-label="Đóng thông báo"
              @click="requestClose"
            >
              <X class="h-4 w-4" />
            </button>
          </header>

          <section class="flex-1 overflow-y-auto px-6 py-6">
            <div class="mx-auto flex max-w-[360px] flex-col items-center text-center">
              <div
                class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full ring-1"
                :class="toneClasses.iconWrap"
              >
                <LoaderCircle
                  v-if="loading"
                  class="h-8 w-8 animate-spin text-slate-700"
                  aria-hidden="true"
                />
                <component
                  :is="toneClasses.icon"
                  v-else
                  class="h-8 w-8"
                  :class="toneClasses.iconColor"
                  aria-hidden="true"
                />
              </div>

              <p class="whitespace-pre-line break-words text-[15px] leading-6 text-slate-700">
                {{ message }}
              </p>
            </div>
          </section>

          <footer
            v-if="!loading"
            class="flex items-center gap-2 border-t border-neutral-900/10 px-5 py-4"
            :class="secondaryLabel ? 'justify-end' : 'justify-center'"
          >
            <button
              v-if="secondaryLabel"
              type="button"
              class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
              @click="$emit('secondary')"
            >
              {{ secondaryLabel }}
            </button>
            <button
              type="button"
              class="rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="disableClose"
              @click="requestClose"
            >
              {{ closeLabel }}
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import {
  AlertCircle,
  CheckCircle2,
  Info,
  LoaderCircle,
  TriangleAlert,
  X,
} from "lucide-vue-next";
import type { ActionResultType } from "@/shared/composables/useActionResultModal";

const props = withDefaults(
  defineProps<{
    open: boolean;
    type: ActionResultType;
    title: string;
    message: string;
    details?: unknown;
    loading?: boolean;
    closeLabel?: string;
    secondaryLabel?: string | null;
    disableClose?: boolean;
  }>(),
  {
    loading: false,
    closeLabel: "Đóng",
    secondaryLabel: null,
    disableClose: false,
  }
);

const emit = defineEmits<{
  (e: "close"): void;
  (e: "secondary"): void;
}>();

const panelRef = ref<HTMLElement | null>(null);
const closeButtonRef = ref<HTMLButtonElement | null>(null);
let previousFocusedElement: HTMLElement | null = null;

const resolvedTitle = computed(() => {
  const explicitTitle = props.title?.trim();
  if (explicitTitle) return explicitTitle;
  if (props.loading) return "Đang xử lý";
  if (props.type === "success") return "Thành công";
  if (props.type === "error") return "Thất bại";
  if (props.type === "warning") return "Cảnh báo";
  return "Thông báo";
});

const toneClasses = computed(() => {
  if (props.loading) {
    return {
      header: "bg-slate-700",
      iconWrap: "bg-slate-50 ring-slate-200",
      iconColor: "text-slate-700",
      icon: Info,
    };
  }

  switch (props.type) {
    case "success":
      return {
        header: "bg-emerald-600",
        iconWrap: "bg-emerald-50 ring-emerald-200",
        iconColor: "text-emerald-600",
        icon: CheckCircle2,
      };
    case "error":
      return {
        header: "bg-rose-600",
        iconWrap: "bg-rose-50 ring-rose-200",
        iconColor: "text-rose-600",
        icon: AlertCircle,
      };
    case "warning":
      return {
        header: "bg-amber-600",
        iconWrap: "bg-amber-50 ring-amber-200",
        iconColor: "text-amber-600",
        icon: TriangleAlert,
      };
    default:
      return {
        header: "bg-sky-600",
        iconWrap: "bg-sky-50 ring-sky-200",
        iconColor: "text-sky-600",
        icon: Info,
      };
  }
});

function requestClose() {
  if (props.disableClose || props.loading) return;
  emit("close");
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
      last.focus();
    }
    return;
  }

  if (active === last || !panelRef.value?.contains(active)) {
    event.preventDefault();
    first.focus();
  }
}

function onWindowKeydown(event: KeyboardEvent) {
  if (!props.open) return;
  if (event.key === "Escape") {
    event.preventDefault();
    requestClose();
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
      if (props.loading) {
        panelRef.value?.focus();
      } else {
        closeButtonRef.value?.focus();
      }
      return;
    }

    window.removeEventListener("keydown", onWindowKeydown);
    previousFocusedElement?.focus?.();
    previousFocusedElement = null;
  }
);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen && props.details && import.meta.env.DEV) {
      console.debug("[ActionResultModal details]", props.details);
    }
  }
);

onBeforeUnmount(() => {
  window.removeEventListener("keydown", onWindowKeydown);
});
</script>
