<template>
  <div class="min-h-screen bg-slate-100">
    <PublicHomeTopHeader />

    <PublicHomeNavBar
      :is-authenticated="isAuthenticated"
      :lecturer-name="meName"
      :lecturer-code="meCode"
      :initials="meInitials"
      @logout="handleLogout"
    />

    <main class="mx-auto max-w-6xl px-4 py-8 md:px-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-2xl font-extrabold text-slate-900">Thông tin</div>
            <div class="mt-1 text-sm text-slate-600">
              Khoa: <b>{{ lecturer?.department_name ?? "—" }}</b>
            </div>
          </div>

          <button
            class="h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50"
            @click="$router.back()"
          >
            Quay lại
          </button>
        </div>

        <div v-if="loading" class="mt-6 text-sm text-slate-600">Đang tải dữ liệu...</div>
        <div v-else-if="error" class="mt-6 text-sm text-rose-700">{{ error }}</div>

        <div v-else class="mt-6 grid gap-6 md:grid-cols-12">
          <!-- left: avatar + name -->
          <div class="md:col-span-4">
            <div class="aspect-square w-full overflow-hidden rounded-2xl bg-slate-100" />
            <div class="mt-4 text-center text-lg font-extrabold text-slate-900">
              {{ lecturer?.full_name ?? "—" }}
            </div>
            <div class="mt-1 text-center text-sm text-slate-500">
              {{ lecturer?.code ?? "" }}
            </div>
            <div class="mt-2 text-center text-sm text-[#e11d48] font-semibold">
              {{ titleLine }}
            </div>
          </div>

          <!-- right: counters + info cards -->
          <div class="md:col-span-8">
            <!-- counters (giống Huế: 4 ô) -->
            <div class="grid gap-3 sm:grid-cols-4">
              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100" @click="goTo('paper')">
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.paper }}</div>
                <div class="mt-1 text-xs font-bold text-slate-700">BÀI BÁO</div>
              </button>

              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100" @click="goTo('book')">
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.book_total }}</div>
                <div class="mt-1 text-xs font-bold text-slate-700">SÁCH - GIÁO TRÌNH</div>
              </button>

              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100" @click="goTo('project')">
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.project }}</div>
                <div class="mt-1 text-xs font-bold text-slate-700">ĐỀ TÀI KHOA HỌC</div>
              </button>

              <button class="rounded-2xl bg-slate-50 p-4 text-left hover:bg-slate-100" @click="goTo('conference')">
                <div class="text-2xl font-extrabold text-[#e11d48]">{{ counts.conference }}</div>
                <div class="mt-1 text-xs font-bold text-slate-700">HỘI THẢO</div>
              </button>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
              <!-- Thông tin -->
              <div>
                <div class="text-sm font-extrabold text-slate-900">Thông tin:</div>
                <div class="mt-2 space-y-3">
                  <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <div><b>Họ và tên:</b> {{ lecturer?.full_name ?? "—" }}</div>
                    <div><b>Giới tính:</b> {{ profile?.gender ?? "—" }}</div>
                    <div><b>Năm sinh:</b> {{ birthYear }}</div>
                  </div>

                  <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <div><b>Địa chỉ:</b> {{ profile?.address ?? "—" }}</div>
                  </div>

                  <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <div><b>Liên hệ:</b></div>
                    <div>Điện thoại: {{ lecturer?.phone ?? "—" }}</div>
                    <div>Email: {{ lecturer?.email ?? profile?.personal_email ?? "—" }}</div>
                  </div>
                </div>
              </div>

              <!-- Chuyên môn -->
              <div>
                <div class="text-sm font-extrabold text-slate-900">Chuyên môn:</div>
                <div class="mt-2 space-y-3">
                  <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <div><b>Chức danh:</b> {{ profile?.current_position ?? "—" }}</div>
                    <div class="mt-2"><b>Đơn vị:</b> {{ profile?.current_unit ?? "—" }}</div>
                  </div>

                  <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <div><b>Chuyên ngành giảng dạy:</b> {{ profile?.teaching_specialization ?? "—" }}</div>
                    <div class="mt-2"><b>Lĩnh vực nghiên cứu:</b> {{ profile?.research_area ?? "—" }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Process tables -->
            <div class="mt-8 space-y-6">
              <div>
                <div class="text-sm font-extrabold text-slate-900">Quá trình công tác</div>
                <div class="mt-2 overflow-hidden rounded-2xl border border-slate-200">
                  <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-700">
                      <tr>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3">Chức danh</th>
                        <th class="px-4 py-3">Cơ quan</th>
                        <th class="px-4 py-3">Ghi chú</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(w, idx) in workHistories" :key="idx" class="border-t">
                        <td class="px-4 py-3 text-slate-600">
                          {{ formatRange(w.start_date, w.end_date, w.is_current) }}
                        </td>
                        <td class="px-4 py-3">{{ w.position ?? "—" }}</td>
                        <td class="px-4 py-3">{{ w.organization ?? w.workplace ?? "—" }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ w.notes ?? "—" }}</td>
                      </tr>
                      <tr v-if="workHistories.length === 0">
                        <td class="px-4 py-3 text-slate-600" colspan="4">—</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div>
                <div class="text-sm font-extrabold text-slate-900">Quá trình đào tạo</div>
                <div class="mt-2 overflow-hidden rounded-2xl border border-slate-200">
                  <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-slate-700">
                      <tr>
                        <th class="px-4 py-3">Bậc đào tạo</th>
                        <th class="px-4 py-3">Cơ sở đào tạo</th>
                        <th class="px-4 py-3">Ngành</th>
                        <th class="px-4 py-3">Thời gian</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(e, idx) in educations" :key="idx" class="border-t">
                        <td class="px-4 py-3">{{ e.degree_name ?? e.degree_title ?? "—" }}</td>
                        <td class="px-4 py-3">{{ e.institution ?? "—" }}</td>
                        <td class="px-4 py-3">{{ e.major ?? "—" }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ formatRange(e.start_date, e.end_date, e.is_current) }}</td>
                      </tr>
                      <tr v-if="educations.length === 0">
                        <td class="px-4 py-3 text-slate-600" colspan="4">—</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>

    <PublicHomeFooter />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useUserStore } from "@/app/stores/userStore";
import { useLogoutFeedback } from "@/features/auth/composables/useLogoutFeedback";

import PublicHomeTopHeader from "@/features/search/components/PublicHomeTopHeader.vue";
import PublicHomeNavBar from "@/features/search/components/PublicHomeNavBar.vue";
import PublicHomeFooter from "@/features/search/components/PublicHomeFooter.vue";

type DetailRes = {
  success: boolean;
  data: {
    lecturer: {
      id: number;
      code: string;
      full_name: string;
      department_name: string | null;
      degree_name: string | null;
      academic_rank_name: string | null;
      email?: string | null;
      phone?: string | null;
    };
    profile: {
      gender?: string | null;
      date_of_birth?: string | null;
      address?: string | null;
      current_position?: string | null;
      current_unit?: string | null;
      research_area?: string | null;
      teaching_specialization?: string | null;
      personal_email?: string | null;
    } | null;
    research_works: {
      counts_by_kind: {
        paper: number;
        project: number;
        conference: number;
        book_only: number;
        textbook: number;
        total: number;
      };
    };
    work_histories: Array<any>;
    educations: Array<any>;
  };
};

async function fetchLecturerDetail(code: string): Promise<DetailRes> {
  const res = await fetch(`/api/public/lecturers/${encodeURIComponent(code)}`, {
    headers: { Accept: "application/json" },
  });
  if (!res.ok) throw new Error(await res.text());
  return res.json();
}

const router = useRouter();
const route = useRoute();
const userStore = useUserStore();
const { logoutWithFeedback } = useLogoutFeedback("/");

onMounted(async () => {
  if (!userStore.isInitialized) await userStore.bootstrapAuth();
});

const isAuthenticated = computed(() => userStore.isAuthenticated);
const meName = computed(() => userStore.currentUser?.name ?? "");
const meCode = computed(() => userStore.currentUser?.code ?? "");
const meInitials = computed(() => {
  const name = meName.value.trim();
  if (!name) return "U";
  const parts = name.split(/\s+/).filter(Boolean);
  return ((parts[0]?.[0] ?? "U") + (parts.length > 1 ? parts[parts.length - 1]?.[0] ?? "" : "")).toUpperCase();
});
async function handleLogout() {
  await logoutWithFeedback();
}

const lecturerCode = computed(() => String(route.params.lecturerCode ?? ""));
const loading = ref(false);
const error = ref<string | null>(null);

const lecturer = ref<DetailRes["data"]["lecturer"] | null>(null);
const profile = ref<DetailRes["data"]["profile"] | null>(null);
const workHistories = ref<any[]>([]);
const educations = ref<any[]>([]);
const counts = ref({ paper: 0, project: 0, conference: 0, book_total: 0 });

const titleLine = computed(() => {
  const a = lecturer.value?.academic_rank_name;
  const d = lecturer.value?.degree_name;
  return [a, d].filter(Boolean).join(" - ") || "Giảng viên";
});

const birthYear = computed(() => {
  const dob = profile.value?.date_of_birth;
  if (!dob) return "—";
  return String(dob).slice(0, 4);
});

function formatRange(start?: string | null, end?: string | null, isCurrent?: boolean) {
  const s = start ? start : "—";
  const e = isCurrent ? "nay" : (end ? end : "—");
  return `${s} → ${e}`;
}

onMounted(async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await fetchLecturerDetail(lecturerCode.value);
    lecturer.value = res.data.lecturer;
    profile.value = res.data.profile;
    workHistories.value = res.data.work_histories ?? [];
    educations.value = res.data.educations ?? [];

    const c = res.data.research_works?.counts_by_kind;
    counts.value = {
      paper: Number(c?.paper ?? 0),
      project: Number(c?.project ?? 0),
      conference: Number(c?.conference ?? 0),
      book_total: Number((c?.book_only ?? 0) + (c?.textbook ?? 0)),
    };
  } catch (e: any) {
    error.value = e?.message || "Không tải được hồ sơ giảng viên";
  } finally {
    loading.value = false;
  }
});

const lecturerQuery = computed(() => {
  const name = lecturer.value?.full_name?.trim() || "";
  const code = lecturer.value?.code?.trim() || "";
  return `${name}${code ? " / " + code : ""}`;
});

function goTo(kind: "paper" | "book" | "project" | "conference") {
  const map: Record<string, string> = {
    paper: "/bai-bao-khoa-hoc",
    project: "/de-tai-nghien-cuu",
    conference: "/hoi-thao-bao-cao-khoa-hoc",
    book: "/sach-giao-trinh",
  };
  router.push({ path: map[kind], query: { lecturer: lecturerQuery.value } });
}
</script>
