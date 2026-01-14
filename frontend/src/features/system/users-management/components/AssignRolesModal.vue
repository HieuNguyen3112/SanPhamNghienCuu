<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-slate-900/40" @click="emit('close')" />

    <div
      class="absolute left-1/2 top-1/2 w-[94vw] max-w-[720px] -translate-x-1/2 -translate-y-1/2"
    >
      <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
      >
        <div class="border-b border-slate-200 px-4 py-4 md:px-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="text-sm font-semibold text-slate-900">
                Phân quyền tài khoản
              </div>
              <div class="mt-1 text-xs text-slate-500">
                Việc phân quyền ảnh hưởng đến phạm vi truy cập dữ liệu. Vui
                lòng kiểm tra kỹ trước khi lưu.
              </div>
            </div>
            <button
              type="button"
              class="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 hover:bg-slate-50"
              @click="emit('close')"
              aria-label="Đóng"
              title="Đóng"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div class="p-4 md:p-5">
          <div v-if="!account" class="text-sm text-slate-700">
            Không có dữ liệu.
          </div>

          <div v-else class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="text-sm font-semibold text-slate-900">
                {{ account.fullName }}
              </div>
              <div class="mt-1 text-xs text-slate-500">
                {{ account.lecturerCode }} • {{ account.email }}
              </div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
              <div class="flex items-start gap-2">
                <AlertTriangle class="mt-0.5 h-4 w-4 text-amber-700" />
                <div class="text-sm text-amber-900">
                  Việc phân quyền ảnh hưởng đến phạm vi truy cập dữ liệu. Vui
                  lòng kiểm tra kỹ trước khi lưu.
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <label
                v-for="r in roleOptions"
                :key="r.key"
                class="flex cursor-pointer items-start gap-2 rounded-2xl border border-slate-200 bg-white p-3 hover:bg-slate-50"
              >
                <input
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-0"
                  :checked="selectedRoleSet.has(r.key)"
                  @change="onToggleRole(r.key, $event)"
                />
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-slate-900">
                    {{ r.label }}
                  </div>
                  <div
                    v-if="r.description"
                    class="mt-0.5 text-xs text-slate-500"
                  >
                    {{ r.description }}
                  </div>
                </div>
              </label>
            </div>

            <div
              v-if="error"
              class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700"
            >
              {{ error }}
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
                :disabled="saving"
              >
                Hủy
              </button>

              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-60"
                :disabled="saving"
                @click="onSave"
              >
                <ShieldCheck class="h-4 w-4" />
                Lưu phân quyền
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { AlertTriangle, ShieldCheck, X } from "lucide-vue-next";
import type {
  AssignRolesPayload,
  LecturerAccount,
  RoleKey,
  RoleOption,
} from "../contracts/lecturerAccountManagement.contract";

const props = defineProps<{
  open: boolean;
  account: LecturerAccount | null;
  roleOptions: RoleOption[];
  saving: boolean;
  error: string | null;
}>();

const emit = defineEmits<{
  (e: "close"): void;
  (e: "save", payload: AssignRolesPayload): void;
}>();

const selectedRoleKeys = ref<RoleKey[]>([]);
const selectedRoleSet = computed(() => new Set(selectedRoleKeys.value));

watch(
  () => props.account,
  (acc) => {
    if (!acc) {
      selectedRoleKeys.value = [];
      return;
    }
    selectedRoleKeys.value = [...acc.roleKeys];
  },
  { immediate: true }
);

function onToggleRole(role: RoleKey, event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  const next = new Set(selectedRoleKeys.value);
  if (checked) next.add(role);
  else next.delete(role);
  selectedRoleKeys.value = [...next];
}

function onSave() {
  if (!props.account) return;
  emit("save", {
    id: props.account.id,
    role_keys: [...selectedRoleKeys.value],
  });
}
</script>
