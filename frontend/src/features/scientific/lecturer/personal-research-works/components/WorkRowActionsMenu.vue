<template>
  <div class="relative inline-flex">
    <button
      ref="triggerEl"
      type="button"
      class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
      :aria-expanded="open ? 'true' : 'false'"
      aria-haspopup="menu"
      @click="emit('toggle', !open)"
      @keydown.esc.prevent.stop="emit('close')"
      title="Hành động"
    >
      ⋯
    </button>

    <!-- ✅ Teleport ra body để không bị table overflow cắt -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
      >
        <div
          v-if="open"
          ref="menuEl"
          class="fixed z-9999 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
          role="menu"
          :style="menuStyle"
        >
          <div class="p-1">
            <!-- View -->
            <button
              v-if="showView"
              type="button"
              class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
              role="menuitem"
              @click="onClickView"
            >
              <span aria-hidden="true">👁</span>
              {{ viewLabelResolved }}
            </button>

            <!-- Draft -->
            <button
              v-if="statusCode === 'draft'"
              type="button"
              class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
              role="menuitem"
              @click="onClickEditDraft"
            >
              <span aria-hidden="true">✏️</span>
              Tiếp tục kê khai
            </button>

            <!-- Rejected -->
            <button
              v-if="statusCode === 'rejected'"
              type="button"
              class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
              role="menuitem"
              @click="onClickCopyRejected"
            >
              <span aria-hidden="true">📄</span>
              Sao chép kê khai lại
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";
import type { PersonalWorkStatusCode } from "../contracts/personalResearchWorksContracts";

const props = withDefaults(
  defineProps<{
    open: boolean;
    activityId: number;
    statusCode: PersonalWorkStatusCode;

    showView?: boolean;
    viewLabel?: string | null;
  }>(),
  {
    showView: true,
    viewLabel: null,
  }
);

const emit = defineEmits<{
  (e: "toggle", nextOpen: boolean): void;
  (e: "close"): void;
  (e: "view", activityId: number): void;
  (e: "edit-draft", activityId: number): void;
  (e: "copy-rejected", activityId: number): void;
}>();

const triggerEl = ref<HTMLButtonElement | null>(null);
const menuEl = ref<HTMLElement | null>(null);

const menuTop = ref(0);
const menuLeft = ref(0);

const viewLabelResolved = computed(() => {
  if (props.viewLabel?.trim()) return props.viewLabel.trim();
  return props.statusCode === "rejected" ? "Xem lý do" : "Xem chi tiết";
});

const menuStyle = computed(() => ({
  top: `${menuTop.value}px`,
  left: `${menuLeft.value}px`,
}));

function onClickView() {
  emit("view", props.activityId);
  emit("close");
}
function onClickEditDraft() {
  emit("edit-draft", props.activityId);
  emit("close");
}
function onClickCopyRejected() {
  emit("copy-rejected", props.activityId);
  emit("close");
}

function updateMenuPosition() {
  const trigger = triggerEl.value;
  if (!trigger) return;

  const rect = trigger.getBoundingClientRect();

  // menu width = w-56 = 14rem = 224px
  const menuWidth = 224;
  const viewportPadding = 8;

  // left: canh phải theo button, nhưng clamp trong viewport
  const desiredLeft = rect.right - menuWidth;
  const clampedLeft = Math.min(
    Math.max(viewportPadding, desiredLeft),
    window.innerWidth - viewportPadding - menuWidth
  );

  menuLeft.value = clampedLeft;

  // top: default mở xuống
  const defaultTop = rect.bottom + 8;

  // nếu gần đáy -> flip lên
  const menuHeight = menuEl.value?.getBoundingClientRect().height ?? 220;
  const wouldOverflowBottom =
    defaultTop + menuHeight > window.innerHeight - viewportPadding;

  menuTop.value = wouldOverflowBottom
    ? Math.max(viewportPadding, rect.top - 8 - menuHeight)
    : defaultTop;
}

function onDocPointerDown(event: Event) {
  if (!props.open) return;
  const target = event.target as Node | null;
  const trigger = triggerEl.value;
  const menu = menuEl.value;
  if (!target) return;

  // click trong trigger hoặc trong menu => không đóng
  if (trigger?.contains(target)) return;
  if (menu?.contains(target)) return;

  emit("close");
}

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return;

    await nextTick();
    updateMenuPosition();

    document.addEventListener("pointerdown", onDocPointerDown, true);
    window.addEventListener("resize", updateMenuPosition);
    // capture scroll ở mọi container (kể cả table scroll)
    window.addEventListener("scroll", updateMenuPosition, true);
  }
);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) return;
    document.removeEventListener("pointerdown", onDocPointerDown, true);
    window.removeEventListener("resize", updateMenuPosition);
    window.removeEventListener("scroll", updateMenuPosition, true);
  }
);

onBeforeUnmount(() => {
  document.removeEventListener("pointerdown", onDocPointerDown, true);
  window.removeEventListener("resize", updateMenuPosition);
  window.removeEventListener("scroll", updateMenuPosition, true);
});
</script>
