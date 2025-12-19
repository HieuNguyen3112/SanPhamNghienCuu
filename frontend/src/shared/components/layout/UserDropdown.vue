<template>
  <!-- Teleport để dropdown không bị parent overflow/stacking context chặn -->
  <Teleport to="body">
    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="open"
        ref="menuRef"
        class="fixed w-64 origin-top-right rounded-md bg-white py-0.5 text-slate-700 shadow-lg ring-1 ring-black/5"
        :style="menuStyle"
      >
        <!-- Header: tên + mã -->
        <div class="border-b border-slate-200 px-4 py-3 text-center">
          <p class="text-sm font-semibold text-slate-800">
            {{ userName }}
          </p>
          <p class="mt-1 text-xs text-slate-500">
            {{ userCode }}
          </p>
        </div>

        <!-- Actions -->
        <div class="py-2">
          <button
            type="button"
            class="flex w-full items-center gap-3 px-4 py-2 text-sm hover:bg-slate-100"
            @click="$emit('open-profile')"
          >
            <span
              class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-slate-600"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path
                  d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5A1.5 1.5 0 0 0 5.5 20h13A1.5 1.5 0 0 0 20 18.5C20 16 16.42 14 12 14Z"
                />
              </svg>
            </span>
            <span>Hồ sơ của tôi</span>
          </button>

          <button
            type="button"
            class="flex w-full items-center gap-3 px-4 py-2 text-sm hover:bg-slate-100"
            @click="$emit('change-password')"
          >
            <span
              class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700"
            >
              ***
            </span>
            <span>Đổi mật khẩu</span>
          </button>
        </div>

        <!-- Logout -->
        <div class="border-t border-slate-200 px-4 py-3">
          <button
            type="button"
            class="flex w-full items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            @click="$emit('logout')"
          >
            Đăng xuất
          </button>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";

const props = withDefaults(
  defineProps<{
    open: boolean;
    anchorEl: HTMLElement | null; // nút avatar
    userName?: string;
    userCode?: string;
    offsetY?: number; // khoảng cách dưới avatar
  }>(),
  {
    userName: "",
    userCode: "",
    offsetY: 8,
  }
);

defineEmits<{
  (e: "close"): void;
  (e: "open-profile"): void;
  (e: "change-password"): void;
  (e: "logout"): void;
}>();

const menuRef = ref<HTMLElement | null>(null);

const menuStyle = computed(() => {
  // zIndex cực cao để luôn nổi
  const zIndex = 99999;

  if (!props.anchorEl) {
    return { top: "0px", left: "0px", zIndex } as Record<string, any>;
  }

  const rect = props.anchorEl.getBoundingClientRect();
  const top = rect.bottom + props.offsetY;
  const left = rect.right - 256; // width 64 => 16rem => 256px (w-64)

  return {
    top: `${top}px`,
    left: `${Math.max(8, left)}px`,
    zIndex,
  } as Record<string, any>;
});

const handleClickOutside = (event: MouseEvent) => {
  if (!props.open) return;

  const target = event.target as Node | null;

  // click ngoài menu và ngoài anchor => close
  if (
    menuRef.value &&
    !menuRef.value.contains(target) &&
    props.anchorEl &&
    !props.anchorEl.contains(target)
  ) {
    // emit close
    // @ts-ignore
    (getCurrentInstance()?.emit as any)("close");
  }
};

const handleEsc = (event: KeyboardEvent) => {
  if (!props.open) return;
  if (event.key === "Escape") {
    // @ts-ignore
    (getCurrentInstance()?.emit as any)("close");
  }
};

import { getCurrentInstance } from "vue";

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
  document.addEventListener("keydown", handleEsc);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleClickOutside);
  document.removeEventListener("keydown", handleEsc);
});
</script>
