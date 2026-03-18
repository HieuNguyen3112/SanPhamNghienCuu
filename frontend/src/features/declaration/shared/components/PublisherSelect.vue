<template>
  <div class="relative" ref="rootEl">
    <label v-if="label" class="text-xs font-medium text-slate-600">
      {{ label }}
      <span v-if="required" class="text-rose-600">*</span>
    </label>

    <div
      class="mt-1 flex h-10 items-center gap-2 rounded-xl border bg-white px-3 text-sm"
      :class="[
        disabled ? 'opacity-60' : '',
        error
          ? 'border-rose-300 ring-2 ring-rose-100'
          : open
            ? 'border-slate-300 ring-2 ring-slate-200'
            : 'border-slate-200',
      ]"
      @click="onContainerClick"
    >
      <Search class="h-4 w-4 text-slate-500" />

      <input
        ref="inputEl"
        :disabled="disabled"
        :placeholder="placeholder"
        class="w-full bg-transparent outline-none"
        :value="inputText"
        @input="onInput"
        @focus="openDropdown()"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.enter.prevent="selectActive()"
        @keydown.esc.prevent="closeDropdown()"
      />

      <button
        v-if="
          !disabled &&
          (modelValue != null ||
            (publisherName && publisherName.trim().length > 0))
        "
        type="button"
        class="inline-flex h-7 w-7 items-center justify-center rounded-lg hover:bg-slate-50"
        title="Xoá chọn"
        @click.stop="clearSelection"
      >
        <X class="h-4 w-4 text-slate-600" />
      </button>

      <ChevronDown class="h-4 w-4 text-slate-500" />
    </div>

    <p v-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>

    <div
      v-if="open"
      class="absolute z-50 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg"
    >
      <div
        class="flex items-center justify-between border-b border-slate-200 px-3 py-2"
      >
        <div class="text-xs font-semibold text-slate-700">
          Kết quả tìm kiếm
          <span v-if="loading" class="ml-2 text-slate-500">(đang tải...)</span>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-slate-600 hover:bg-slate-50"
          @click="closeDropdown"
        >
          <X class="h-3.5 w-3.5" />
          Đóng
        </button>
      </div>

      <div class="max-h-72 overflow-auto">
        <button
          v-for="(it, idx) in items"
          :key="it.id"
          type="button"
          class="flex w-full items-start gap-3 px-3 py-2 text-left hover:bg-slate-50"
          :class="idx === activeIndex ? 'bg-slate-50' : ''"
          @mousemove="activeIndex = idx"
          @click="choose(it)"
        >
          <div class="mt-0.5">
            <div
              class="inline-flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-white"
            >
              <Check
                v-if="modelValue === it.id"
                class="h-4 w-4 text-emerald-700"
              />
              <span v-else class="text-xs font-semibold text-slate-600">N</span>
            </div>
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <div class="truncate text-sm font-semibold text-slate-900">
                {{ it.name }}
              </div>
              <span
                class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"
              >
                {{ it.code }}
              </span>
            </div>

            <div
              class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-600"
            >
              <span v-if="it.phone">{{ it.phone }}</span>
              <span v-if="it.phone && it.email" class="text-slate-300">•</span>
              <span v-if="it.email">{{ it.email }}</span>
              <span
                v-if="(it.phone || it.email) && it.website"
                class="text-slate-300"
                >•</span
              >
              <span v-if="it.website" class="line-clamp-1">{{
                it.website
              }}</span>
            </div>

            <div
              v-if="it.address"
              class="mt-0.5 text-xs text-slate-500 line-clamp-1"
            >
              {{ it.address }}
            </div>
          </div>
        </button>

        <div
          v-if="!loading && items.length === 0"
          class="space-y-2 px-3 py-6 text-center text-sm"
        >
          <p class="text-slate-500">Không có kết quả trong danh mục.</p>
          <p
            v-if="trimmedInput.length > 0"
            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
          >
            Bạn vẫn có thể nhập “{{ trimmedInput }}”. Hệ thống sẽ gửi đề xuất
            nhà xuất bản mới khi bạn bấm gửi duyệt.
          </p>
          <p v-else class="text-xs text-slate-400">Hãy thử từ khoá khác.</p>
        </div>
      </div>

      <div class="border-t border-slate-200 px-3 py-2 text-xs text-slate-600">
        Tip: Nhấn <span class="font-semibold">↑/↓</span> để chọn,
        <span class="font-semibold">Enter</span> để xác nhận.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Check, ChevronDown, Search, X } from "lucide-vue-next";
import type { PublisherOptionDto } from "../contracts/declarationSharedContract";

const props = defineProps<{
  modelValue: number | null;
  publisherName: string;
  disabled?: boolean;
  required?: boolean;
  label?: string;
  placeholder?: string;
  hint?: string;
  error?: string;
  searchFn: (q: string) => Promise<PublisherOptionDto[]>;
  debounceMs?: number;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", v: number | null): void;
  (e: "update:publisherName", v: string): void;
  (e: "select", v: PublisherOptionDto): void;
  (e: "clear"): void;
}>();

const disabled = computed(() => !!props.disabled);
const placeholder = computed(
  () => props.placeholder ?? "Gõ để tìm nhà xuất bản...",
);
const debounceMs = computed(() => props.debounceMs ?? 250);

const open = ref(false);
const loading = ref(false);
const items = ref<PublisherOptionDto[]>([]);
const activeIndex = ref(0);

const rootEl = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);

const inputText = computed(() => props.publisherName ?? "");
const trimmedInput = computed(() => (props.publisherName ?? "").trim());

let timer: number | null = null;

async function runSearch(q: string) {
  loading.value = true;
  try {
    items.value = (await props.searchFn(q)) ?? [];
    activeIndex.value = 0;
  } finally {
    loading.value = false;
  }
}

function scheduleSearch(q: string) {
  if (timer) window.clearTimeout(timer);
  timer = window.setTimeout(() => {
    runSearch(q);
  }, debounceMs.value);
}

function openDropdown() {
  if (disabled.value) return;
  if (!open.value) {
    open.value = true;
    scheduleSearch((props.publisherName ?? "").trim());
  }
}

function closeDropdown() {
  open.value = false;
}

function onContainerClick() {
  if (disabled.value) return;
  inputEl.value?.focus();
  openDropdown();
}

function onInput(e: Event) {
  const v = (e.target as HTMLInputElement).value;
  emit("update:publisherName", v);
  emit("update:modelValue", null);
  scheduleSearch(v.trim());
  openDropdown();
}

function choose(it: PublisherOptionDto) {
  emit("update:modelValue", it.id);
  emit("update:publisherName", it.name);
  emit("select", it);
  closeDropdown();
}

function clearSelection() {
  emit("update:modelValue", null);
  emit("update:publisherName", "");
  emit("clear");
  items.value = [];
  activeIndex.value = 0;
  inputEl.value?.focus();
  openDropdown();
}

function move(delta: number) {
  if (!open.value) openDropdown();
  if (items.value.length === 0) return;
  const next = activeIndex.value + delta;
  if (next < 0) activeIndex.value = items.value.length - 1;
  else if (next >= items.value.length) activeIndex.value = 0;
  else activeIndex.value = next;
}

function selectActive() {
  if (!open.value) {
    openDropdown();
    return;
  }
  if (items.value.length === 0) return;
  const item = items.value[activeIndex.value];
  if (!item) return;
  choose(item);
}

function onClickOutside(e: MouseEvent) {
  if (!rootEl.value) return;
  const target = e.target as Node | null;
  if (target && rootEl.value.contains(target)) return;
  closeDropdown();
}

onMounted(() => {
  document.addEventListener("mousedown", onClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onClickOutside);
  if (timer) window.clearTimeout(timer);
});
</script>
