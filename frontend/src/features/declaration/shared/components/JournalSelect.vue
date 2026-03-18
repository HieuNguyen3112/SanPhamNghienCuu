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
          (modelValue != null || (journalName && journalName.trim().length > 0))
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

    <p v-if="hint" class="mt-1 text-xs text-slate-500">
      {{ hint }}
    </p>
    <p v-if="error" class="mt-1 text-xs text-rose-600">
      {{ error }}
    </p>

    <!-- Dropdown -->
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
              <span v-else class="text-xs font-semibold text-slate-600">J</span>
            </div>
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <div class="truncate text-sm font-semibold text-slate-900">
                {{ it.name }}
              </div>

              <span
                v-if="pointsLabel(it)"
                class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"
                :title="pointsTitle(it)"
              >
                {{ pointsLabel(it) }}
              </span>
            </div>

            <div
              class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-600"
            >
              <span v-if="it.issn">ISSN: {{ it.issn }}</span>
              <span v-if="it.issn && it.address" class="text-slate-300">•</span>
              <span v-if="it.address" class="line-clamp-1">{{
                it.address
              }}</span>
              <span
                v-if="(it.issn || it.address) && it.country"
                class="text-slate-300"
                >•</span
              >
              <span v-if="it.country">{{ it.country }}</span>
            </div>

            <div class="mt-0.5">
              <span
                class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                :class="
                  it.isActive
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-slate-100 text-slate-600'
                "
              >
                {{ it.isActive ? "Đang dùng" : "Ngừng dùng" }}
              </span>
            </div>
          </div>
        </button>

        <div
          v-if="!loading && items.length === 0"
          class="px-3 py-6 text-center text-sm text-slate-500"
        >
          Không có kết quả. Hãy thử từ khoá khác.
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

type JournalOptionDto = {
  id: number;
  name: string;
  issn: string | null;
  journal_type?: string | null;
  research_field?: string | null;
  website?: string | null;
  address: string | null;
  country: string | null;
  notes: string | null;
  source_name: string | null;
  publisher?: string | null;
  point: string | number | null;
  classification: string;
  research_hours: number;
  is_active: boolean;
};

type JournalOption = {
  id: number;
  name: string;
  issn: string | null;
  journalType: string | null;
  researchField: string | null;
  website: string | null;
  address: string;
  country: string | null;
  notes: string | null;
  sourceName: string | null;
  publisher: string | null;
  point: number | null;
  classification: string;
  researchHours: number;
  isActive: boolean;
};

const props = defineProps<{
  modelValue: number | null;
  journalName: string;
  issn: string;

  disabled?: boolean;
  required?: boolean;
  label?: string;
  placeholder?: string;
  hint?: string;
  error?: string;

  /** GET /api/lookups/journals?search=...&active=1 */
  searchFn: (q: string) => Promise<JournalOptionDto[]>;
  debounceMs?: number;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", v: number | null): void;
  (e: "update:journalName", v: string): void;
  (e: "update:issn", v: string): void;
  (e: "select", v: JournalOption): void;
  (e: "clear"): void;
}>();

const disabled = computed(() => !!props.disabled);
const placeholder = computed(() => props.placeholder ?? "Gõ để tìm tạp chí...");
const debounceMs = computed(() => props.debounceMs ?? 250);

const open = ref(false);
const loading = ref(false);
const items = ref<JournalOption[]>([]);
const activeIndex = ref(0);

const rootEl = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);

const inputText = computed(() => props.journalName ?? "");

function toNumberOrNull(v: string | number | null): number | null {
  if (v == null) return null;
  if (typeof v === "number") return Number.isFinite(v) ? v : null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

function dtoToModel(dto: JournalOptionDto): JournalOption {
  return {
    id: dto.id,
    name: dto.name,
    issn: dto.issn,
    journalType: dto.journal_type ?? null,
    researchField: dto.research_field ?? null,
    website: dto.website ?? null,
    address: dto.address ?? "",
    country: dto.country,
    notes: dto.notes,
    sourceName: dto.source_name,
    publisher: dto.publisher ?? null,
    point: toNumberOrNull(dto.point),
    classification: dto.classification ?? "OTHER",
    researchHours: dto.research_hours ?? 0,
    isActive: !!dto.is_active,
  };
}

let timer: number | null = null;

async function runSearch(q: string) {
  loading.value = true;
  try {
    const dtos = await props.searchFn(q);
    items.value = (dtos ?? []).map(dtoToModel);
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
    scheduleSearch((props.journalName ?? "").trim());
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
  emit("update:journalName", v);
  emit("update:modelValue", null);
  // không tự động overwrite issn khi gõ
  scheduleSearch(v.trim());
  openDropdown();
}

function choose(it: JournalOption) {
  emit("update:modelValue", it.id);
  emit("update:journalName", it.name);
  emit("update:issn", it.issn ?? "");
  emit("select", it);
  closeDropdown();
}

function clearSelection() {
  emit("update:modelValue", null);
  emit("update:journalName", "");
  emit("update:issn", "");
  emit("clear");
  items.value = [];
  activeIndex.value = 0;
  inputEl.value?.focus();
  openDropdown();
}
function formatPoint(n: number): string {
  // 2 chữ số thập phân nhưng bỏ .00 cho gọn
  const s = n.toFixed(2);
  return s.endsWith(".00") ? s.slice(0, -3) : s.replace(/0$/, "");
}
function pointsLabel(it: { point: number | null }): string | null {
  if (it.point == null) return null;
  return formatPoint(it.point);
}

function pointsTitle(it: { point: number | null }): string {
  if (it.point == null) return "";
  return `Điểm: ${formatPoint(it.point)}`;
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
  if (!open.value) return;
  const it = items.value[activeIndex.value];
  if (it) choose(it);
}

function onClickOutside(ev: MouseEvent) {
  if (!open.value) return;
  const el = rootEl.value;
  if (!el) return;
  if (ev.target instanceof Node && !el.contains(ev.target)) closeDropdown();
}

onMounted(() => window.addEventListener("mousedown", onClickOutside));
onBeforeUnmount(() => {
  window.removeEventListener("mousedown", onClickOutside);
  if (timer) window.clearTimeout(timer);
});
</script>
