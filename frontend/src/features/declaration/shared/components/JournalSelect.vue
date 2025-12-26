<template>
  <div class="relative">
    <label v-if="label" class="text-xs font-medium text-slate-600">
      {{ label }}
      <span v-if="required" class="text-rose-600">*</span>
    </label>

    <div
      class="mt-1 flex h-10 items-center gap-2 rounded-xl border bg-white px-3 text-sm"
      :class="[
        disabled ? 'opacity-60' : '',
        open ? 'border-slate-300 ring-2 ring-slate-200' : 'border-slate-200',
      ]"
      @click="onContainerClick"
      ref="rootEl"
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
                v-if="it.currentRank"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="rankBadgeClass(it.currentRank)"
              >
                {{ it.currentRank }}
              </span>

              <span
                v-if="it.currentRankEffectiveFrom"
                class="text-xs text-slate-500"
              >
                (từ {{ formatDate(it.currentRankEffectiveFrom) }})
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Check, ChevronDown, Search, X } from "lucide-vue-next";

type JournalRank = "Q1" | "Q2" | "Q3" | "Q4" | "Q5" | "OTHER";

type JournalOptionDto = {
  id: number;
  name: string;
  address: string;
  issn: string | null;
  current_rank: JournalRank | null;
  current_rank_effective_from: string | null; // YYYY-MM-DD hoặc ISO
  is_active: boolean;
};

/** UI model camelCase */
type JournalOption = {
  id: number;
  name: string;
  address: string;
  issn: string | null;
  currentRank: JournalRank | null;
  currentRankEffectiveFrom: string | null;
  isActive: boolean;
};

const props = defineProps<{
  /** journal_id (nếu có), có thể để null */
  modelValue: number | null;
  /** snapshot journal_name đang lưu ở paper_details.journal_name */
  journalName: string;
  /** snapshot issn đang lưu ở paper_details.issn */
  issn: string;

  disabled?: boolean;
  required?: boolean;
  label?: string;
  placeholder?: string;
  hint?: string;
  error?: string;

  /** Hàm search async: GET /api/catalog/journals?query=...&active=1... */
  searchFn: (q: string) => Promise<JournalOptionDto[]>;

  /** Debounce ms */
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

function dtoToModel(dto: JournalOptionDto): JournalOption {
  return {
    id: dto.id,
    name: dto.name,
    address: dto.address,
    issn: dto.issn,
    currentRank: dto.current_rank,
    currentRankEffectiveFrom: dto.current_rank_effective_from,
    isActive: dto.is_active,
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
  // User đang gõ tự do => coi như chưa chọn từ danh mục
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
onBeforeUnmount(() => window.removeEventListener("mousedown", onClickOutside));

watch(
  () => props.modelValue,
  (v) => {
    // Nếu đã có journal_id mà journalName rỗng (load từ backend), vẫn giữ behavior bình thường.
    // Không auto fetch detail ở đây để tránh phụ thuộc endpoint /api/journals/{id}.
    if (v == null) return;
  }
);

function rankBadgeClass(rank: JournalRank): string {
  if (rank === "Q1") return "bg-emerald-50 text-emerald-700";
  if (rank === "Q2") return "bg-sky-50 text-sky-700";
  if (rank === "Q3") return "bg-amber-50 text-amber-800";
  if (rank === "Q4") return "bg-orange-50 text-orange-800";
  if (rank === "Q5") return "bg-slate-900/5 text-slate-800";
  return "bg-slate-100 text-slate-700";
}

function formatDate(value: string): string {
  const d = new Date(value.length === 10 ? `${value}T00:00:00` : value);
  if (Number.isNaN(d.getTime())) return value;
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
}
</script>
