<template>
  <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-4 md:p-6">
      <div class="text-sm font-semibold text-slate-900">Minh chứng</div>
      <div class="mt-0.5 text-xs text-slate-500">
        Upload PDF/hình ảnh. Link minh chứng: hiện chỉ lưu tạm (TODO backend).
      </div>

      <div class="mt-3 flex flex-col gap-2 md:flex-row md:items-center">
        <input
          type="file"
          multiple
          accept="application/pdf,image/*"
          class="block w-full text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800 disabled:opacity-60"
          :disabled="readOnly"
          @change="onPickFiles"
        />

        <div class="flex items-center gap-2">
          <input
            v-model.trim="linkUrl"
            type="url"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm placeholder:text-slate-400 focus:border-slate-300 focus:outline-none md:w-[320px]"
            placeholder="Thêm link minh chứng (URL)"
            :disabled="readOnly"
          />
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-900 hover:bg-slate-50 disabled:opacity-50"
            :disabled="readOnly || !linkUrl"
            @click="addLink"
          >
            <Plus class="h-4 w-4" />
            Thêm link
          </button>
        </div>
      </div>
    </div>

    <!-- Existing files -->
    <div class="p-4 md:p-6">
      <div class="text-xs font-semibold text-slate-600">
        File đã lưu (backend)
      </div>
      <div
        v-if="existingFiles.length === 0"
        class="mt-2 text-sm text-slate-500"
      >
        Chưa có file minh chứng.
      </div>

      <div v-else class="mt-2 space-y-2">
        <div
          v-for="f in existingFiles"
          :key="f.id"
          class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 md:flex-row md:items-center md:justify-between"
        >
          <div class="min-w-0">
            <div class="truncate text-sm font-medium text-slate-900">
              {{ f.original_name }}
            </div>
            <div class="mt-0.5 flex flex-wrap gap-2 text-xs text-slate-500">
              <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-700">
                {{ f.file_type_name ?? `file_type_id=${f.file_type_id}` }}
              </span>
              <span class="text-slate-300">•</span>
              <span>{{ f.mime_type }}</span>
              <span class="text-slate-300">•</span>
              <span>{{ fmtBytes(f.size_bytes) }}</span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a
              v-if="f.url"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              :href="f.url"
              target="_blank"
              rel="noreferrer"
            >
              Xem
            </a>
            <button
              v-if="!readOnly"
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              @click="$emit('remove-existing', f.id)"
            >
              Xóa
            </button>
          </div>
        </div>
      </div>

      <!-- Pending uploads -->
      <div class="mt-5 text-xs font-semibold text-slate-600">
        File đang chọn (chưa upload)
      </div>
      <div v-if="pendingFiles.length === 0" class="mt-2 text-sm text-slate-500">
        Chưa chọn file.
      </div>

      <div v-else class="mt-2 space-y-2">
        <div
          v-for="(p, idx) in pendingFiles"
          :key="idx"
          class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 md:flex-row md:items-center md:justify-between"
        >
          <div class="min-w-0">
            <div class="truncate text-sm font-medium text-slate-900">
              {{ p.file.name }}
            </div>
            <div
              class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-slate-500"
            >
              <span>{{ p.file.type || "unknown" }}</span>
              <span class="text-slate-300">•</span>
              <span>{{ fmtBytes(p.file.size) }}</span>
            </div>
          </div>

          <div class="flex flex-col gap-2 md:flex-row md:items-center">
            <select
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-300 focus:outline-none disabled:opacity-60"
              :disabled="readOnly"
              :value="p.file_type_id ?? ''"
              @change="setPendingType(idx, $event)"
            >
              <option value="">— Chọn loại minh chứng —</option>
              <option v-for="t in fileTypes" :key="t.id" :value="t.id">
                {{ t.name }}
              </option>
            </select>

            <button
              v-if="!readOnly"
              type="button"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              @click="removePending(idx)"
            >
              Xóa
            </button>
          </div>
        </div>
      </div>

      <!-- Pending links -->
      <div class="mt-5 text-xs font-semibold text-slate-600">
        Link minh chứng (tạm thời)
      </div>
      <div v-if="pendingLinks.length === 0" class="mt-2 text-sm text-slate-500">
        Chưa có link.
      </div>
      <div v-else class="mt-2 space-y-2">
        <div
          v-for="(l, idx) in pendingLinks"
          :key="idx"
          class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white p-3"
        >
          <a
            class="truncate text-sm text-slate-700 hover:underline"
            :href="l.url"
            target="_blank"
            rel="noreferrer"
          >
            {{ l.url }}
          </a>
          <button
            v-if="!readOnly"
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
            @click="removeLink(idx)"
          >
            Xóa
          </button>
        </div>

        <div
          class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700"
        >
          TODO (P0): backend chưa có nơi lưu link minh chứng (xem Field Coverage
          Report).
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Plus } from "lucide-vue-next";
import type {
  EvidenceFileDto,
  EvidenceFileTypeDto,
} from "../contracts/declarationSharedContract";

export type PendingEvidenceFile = {
  file: File;
  file_type_id: number | null;
};

export type PendingEvidenceLink = {
  url: string;
};

const props = defineProps<{
  existingFiles: EvidenceFileDto[];
  pendingFiles: PendingEvidenceFile[];
  pendingLinks: PendingEvidenceLink[];
  fileTypes: EvidenceFileTypeDto[];
  readOnly: boolean;
}>();

const emit = defineEmits<{
  (e: "update:pendingFiles", v: PendingEvidenceFile[]): void;
  (e: "update:pendingLinks", v: PendingEvidenceLink[]): void;
  (e: "remove-existing", id: number): void;
}>();

const linkUrl = ref("");

function onPickFiles(e: Event) {
  const input = e.target as HTMLInputElement;
  const files = Array.from(input.files ?? []);
  if (files.length === 0) return;

  const next = [
    ...props.pendingFiles,
    ...files.map((f) => ({ file: f, file_type_id: null })),
  ];
  emit("update:pendingFiles", next);

  input.value = "";
}

function setPendingType(idx: number, e: Event) {
  const v = (e.target as HTMLSelectElement).value;
  const n = v ? Number(v) : null;
  const next = props.pendingFiles.map((p, i) =>
    i === idx
      ? {
          ...p,
          file_type_id: Number.isFinite(n as number) ? (n as number) : null,
        }
      : p
  );
  emit("update:pendingFiles", next);
}

function removePending(idx: number) {
  const next = props.pendingFiles.filter((_, i) => i !== idx);
  emit("update:pendingFiles", next);
}

function addLink() {
  const url = linkUrl.value.trim();
  if (!url) return;
  const next = [...props.pendingLinks, { url }];
  emit("update:pendingLinks", next);
  linkUrl.value = "";
}

function removeLink(idx: number) {
  const next = props.pendingLinks.filter((_, i) => i !== idx);
  emit("update:pendingLinks", next);
}

function fmtBytes(n: number) {
  if (n < 1024) return `${n} B`;
  const kb = n / 1024;
  if (kb < 1024) return `${kb.toFixed(1)} KB`;
  const mb = kb / 1024;
  return `${mb.toFixed(1)} MB`;
}
</script>
