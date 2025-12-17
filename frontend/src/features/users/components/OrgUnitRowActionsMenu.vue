<!-- src/features/users/components/OrgUnitRowActionsMenu.vue -->
<script setup lang="ts">
import {
  ref,
  onMounted,
  onBeforeUnmount,
  nextTick,
  type CSSProperties,
} from "vue";

interface Props {
  isActive: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: "add-child"): void;
  (e: "edit"): void;
  (e: "toggle-active"): void;
  (e: "delete"): void;
}>();

const open = ref(false);
const triggerRef = ref<HTMLElement | null>(null);
const menuRef = ref<HTMLElement | null>(null);

const menuStyle = ref<CSSProperties>({
  top: "0px",
  left: "0px",
});

const calcMenuPosition = () => {
  const trigger = triggerRef.value;
  const menu = menuRef.value;
  if (!trigger || !menu) return;

  const rect = trigger.getBoundingClientRect();
  const menuWidth = menu.offsetWidth || 0;
  const viewportWidth = window.innerWidth;

  // LUÔN xổ xuống dưới, cách nút 8px
  let top = rect.bottom + 8;
  let left = rect.right - menuWidth;

  // Clamp trái/phải cho an toàn
  if (left < 8) left = 8;
  if (left + menuWidth > viewportWidth - 8) {
    left = viewportWidth - menuWidth - 8;
  }

  menuStyle.value = {
    top: `${top + window.scrollY}px`,
    left: `${left + window.scrollX}px`,
  };
};

const openMenu = async () => {
  open.value = true;
  await nextTick();
  calcMenuPosition();
};

const toggle = () => {
  open.value ? (open.value = false) : openMenu();
};

const close = () => {
  open.value = false;
};

const handleOutsideClick = (e: MouseEvent) => {
  const target = e.target as HTMLElement;
  if (
    triggerRef.value?.contains(target) ||
    target.closest("[data-orgunit-row-menu]")
  ) {
    return;
  }
  close();
};

onMounted(() => window.addEventListener("click", handleOutsideClick));
onBeforeUnmount(() => window.removeEventListener("click", handleOutsideClick));
</script>

<template>
  <div class="relative inline-block">
    <!-- Nút 3 chấm -->
    <button
      ref="triggerRef"
      type="button"
      class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50"
      @click.stop="toggle"
    >
      <span class="sr-only">Mở menu thao tác</span>
      <span class="flex flex-col items-center justify-center gap-[3px]">
        <span class="h-[3px] w-[3px] rounded-full bg-slate-400"></span>
        <span class="h-[3px] w-[3px] rounded-full bg-slate-400"></span>
        <span class="h-[3px] w-[3px] rounded-full bg-slate-400"></span>
      </span>
    </button>

    <!-- Menu teleport ra body -->
    <Teleport to="body">
      <div
        v-if="open"
        class="fixed z-50"
        :style="menuStyle"
        data-orgunit-row-menu
      >
        <div
          ref="menuRef"
          class="w-44 rounded-2xl bg-white py-2 text-xs shadow-lg ring-1 ring-slate-200"
        >
          <!-- <button
            type="button"
            class="block w-full px-4 py-2 text-left text-slate-700 hover:bg-slate-50"
            @click.stop="
              () => {
                emit('add-child');
                close();
              }
            "
          >
            + Đơn vị con
          </button> -->

          <button
            type="button"
            class="block w-full px-4 py-2 text-left text-slate-700 hover:bg-slate-50"
            @click.stop="
              () => {
                emit('edit');
                close();
              }
            "
          >
            Sửa
          </button>

          <button
            type="button"
            class="block w-full px-4 py-2 text-left text-emerald-700 hover:bg-emerald-50"
            @click.stop="
              () => {
                emit('toggle-active');
                close();
              }
            "
          >
            {{ props.isActive ? "Vô hiệu" : "Kích hoạt" }}
          </button>

          <button
            type="button"
            class="block w-full px-4 py-2 text-left text-rose-600 hover:bg-rose-50"
            @click.stop="
              () => {
                emit('delete');
                close();
              }
            "
          >
            Xóa
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
