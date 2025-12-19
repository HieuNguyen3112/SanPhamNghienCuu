<template>
  <!-- Root container luôn tồn tại để transition v-show hoạt động mượt -->
  <div
    class="fixed inset-0 z-50"
    :class="isOpen ? 'pointer-events-auto' : 'pointer-events-none'"
  >
    <!-- Overlay fade -->
    <transition name="fade">
      <div
        v-show="isOpen"
        class="absolute inset-0 bg-slate-900/40"
        @click="closeModal"
      />
    </transition>

    <!-- Right drawer slide -->
    <transition name="slideFromRight">
      <div
        v-show="isOpen"
        class="absolute inset-y-0 right-0 w-full max-w-xl overflow-hidden border-l border-slate-200 bg-white shadow-xl"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <!-- HEADER -->
          <div
            class="flex items-start justify-between gap-3 border-b border-slate-200 p-4 md:p-5"
          >
            <div>
              <h3 class="text-base font-semibold text-slate-900">
                Chi tiết cảnh báo giờ nghiên cứu khoa học
              </h3>
              <p class="mt-1 text-sm text-slate-600">
                Màn hình chỉ đọc để phục vụ rà soát và đối soát học thuật.
              </p>
            </div>

            <button
              type="button"
              class="rounded-4xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
              @click="closeModal"
            >
              X
            </button>
          </div>

          <!-- BODY (scroll) -->
          <div class="flex-1 overflow-y-auto p-4 md:p-5">
            <div
              v-if="selectedLecturerResearchHourWarningEntry"
              class="space-y-4"
            >
              <!-- THÔNG TIN GIẢNG VIÊN -->
              <section
                class="rounded-xl border border-slate-200 bg-slate-50/50 p-3"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Thông tin giảng viên
                </div>

                <dl
                  class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 md:grid-cols-2"
                >
                  <div
                    class="flex items-start justify-between gap-3 rounded-lg bg-white px-3 py-2"
                  >
                    <dt class="text-xs font-medium text-slate-600">
                      Giảng viên
                    </dt>
                    <dd class="text-sm font-semibold text-slate-900">
                      {{
                        selectedLecturerResearchHourWarningEntry.lecturerDisplayName
                      }}
                    </dd>
                  </div>

                  <div
                    class="flex items-start justify-between gap-3 rounded-lg bg-white px-3 py-2"
                  >
                    <dt class="text-xs font-medium text-slate-600">Khoa</dt>
                    <dd class="text-sm font-semibold text-slate-900">
                      {{
                        selectedLecturerResearchHourWarningEntry.facultyDisplayName
                      }}
                    </dd>
                  </div>

                  <div
                    class="flex items-start justify-between gap-3 rounded-lg bg-white px-3 py-2"
                  >
                    <dt class="text-xs font-medium text-slate-600">Năm học</dt>
                    <dd class="text-sm font-semibold text-slate-900">
                      {{
                        selectedLecturerResearchHourWarningEntry.academicYear
                      }}
                    </dd>
                  </div>

                  <div
                    class="flex items-start justify-between gap-3 rounded-lg bg-white px-3 py-2"
                  >
                    <dt class="text-xs font-medium text-slate-600">
                      Trạng thái
                    </dt>
                    <dd class="text-sm font-semibold text-slate-900">
                      Chưa đạt chuẩn
                    </dd>
                  </div>
                </dl>
              </section>

              <!-- TỔNG HỢP THEO LOẠI CÔNG TRÌNH -->
              <section class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="text-sm font-semibold text-slate-900">
                  Tổng hợp giờ NCKH theo loại công trình
                </div>

                <div
                  class="mt-2 overflow-hidden rounded-lg border border-slate-200"
                >
                  <table class="w-full border-collapse">
                    <thead class="bg-slate-50">
                      <tr class="border-b border-slate-200">
                        <th
                          class="px-3 py-2 text-left text-xs font-semibold text-slate-700"
                        >
                          Hạng mục
                        </th>
                        <th
                          class="px-3 py-2 text-right text-xs font-semibold text-slate-700"
                        >
                          Giờ quy đổi
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white">
                      <tr class="border-b border-slate-100">
                        <td class="px-3 py-2 text-sm text-slate-800">
                          Bài báo
                        </td>
                        <td
                          class="px-3 py-2 text-right text-sm font-semibold text-slate-900"
                        >
                          {{
                            formatIntegerValue(
                              selectedLecturerResearchHourWarningEntry.researchHoursFromJournalArticles
                            )
                          }}
                        </td>
                      </tr>
                      <tr class="border-b border-slate-100">
                        <td class="px-3 py-2 text-sm text-slate-800">Đề tài</td>
                        <td
                          class="px-3 py-2 text-right text-sm font-semibold text-slate-900"
                        >
                          {{
                            formatIntegerValue(
                              selectedLecturerResearchHourWarningEntry.researchHoursFromResearchProjects
                            )
                          }}
                        </td>
                      </tr>
                      <tr class="border-b border-slate-100">
                        <td class="px-3 py-2 text-sm text-slate-800">
                          Hội thảo
                        </td>
                        <td
                          class="px-3 py-2 text-right text-sm font-semibold text-slate-900"
                        >
                          {{
                            formatIntegerValue(
                              selectedLecturerResearchHourWarningEntry.researchHoursFromConferenceProceedings
                            )
                          }}
                        </td>
                      </tr>
                      <tr>
                        <td class="px-3 py-2 text-sm text-slate-800">
                          Hướng dẫn sinh viên
                        </td>
                        <td
                          class="px-3 py-2 text-right text-sm font-semibold text-slate-900"
                        >
                          {{
                            formatIntegerValue(
                              selectedLecturerResearchHourWarningEntry.researchHoursFromStudentSupervision
                            )
                          }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <!-- TỔNG QUAN GIỜ -->
              <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                  <div class="text-xs font-medium text-slate-600">
                    Tổng giờ đã quy đổi
                  </div>
                  <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{ formatIntegerValue(currentLecturerResearchHours) }}
                  </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-4">
                  <div class="text-xs font-medium text-slate-600">
                    Chuẩn yêu cầu
                  </div>
                  <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{
                      formatIntegerValue(
                        minimumRequiredResearchHoursForSelectedLecturer
                      )
                    }}
                  </div>
                </div>

                <div
                  class="rounded-xl border border-amber-200 bg-amber-50/60 p-4"
                >
                  <div class="text-xs font-medium text-slate-700">
                    Giờ còn thiếu
                  </div>
                  <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{
                      formatIntegerValue(remainingResearchHoursToMeetStandard)
                    }}
                  </div>
                </div>
              </section>

              <!-- GHI CHÚ HỌC THUẬT -->
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Ghi chú học thuật (nếu có)
                </div>
                <p class="mt-2 text-sm leading-relaxed text-slate-700">
                  {{
                    selectedLecturerResearchHourWarningEntry.academicNotes ||
                    "Không có ghi chú."
                  }}
                </p>
              </section>

              <!-- HÀNH ĐỘNG THEO DÕI -->
              <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="text-sm font-semibold text-slate-900">
                  Hành động theo dõi
                </div>
                <p class="mt-2 text-sm text-slate-700">
                  Việc gửi nhắc nhở giúp khoa/bộ môn ghi nhận đã thông báo và hỗ
                  trợ giảng viên hoàn thiện chuẩn giờ NCKH. Màn hình này chỉ ghi
                  nhận trạng thái (demo), không chỉnh sửa dữ liệu giờ.
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100 disabled:opacity-60"
                    :disabled="
                      selectedLecturerResearchHourWarningEntry.lecturerResearchHourWarningNotificationRequestState ===
                      'REQUESTED'
                    "
                    @click="
                      confirmAndSubmitLecturerResearchHourWarningNotificationRequest
                    "
                  >
                    <svg
                      viewBox="0 0 24 24"
                      class="h-4 w-4"
                      fill="none"
                      stroke="currentColor"
                    >
                      <path
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M22 12l-10 6L2 12l10-6 10 6z"
                      />
                      <path
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M22 12l-10 6L2 12"
                      />
                    </svg>
                    Gửi yêu cầu nhắc nhở cho giảng viên
                  </button>

                  <div
                    v-if="
                      selectedLecturerResearchHourWarningEntry.lecturerResearchHourWarningNotificationRequestState ===
                      'REQUESTED'
                    "
                    class="text-sm text-slate-700"
                  >
                    Đã ghi nhận gửi nhắc nhở.
                  </div>
                </div>
              </section>
            </div>

            <div v-else class="p-2 text-sm text-slate-700">
              Không có thông tin để hiển thị.
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { LecturerResearchHourWarningEntry } from "../lecturerResearchHourWarningModels";

const componentProperties = defineProps<{
  isOpen: boolean;
  selectedLecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry | null;

  // REQUIRED VARIABLES (prop-driven, read-only)
  minimumRequiredResearchHours: number;
  currentLecturerResearchHours: number;
  remainingResearchHoursToMeetStandard: number;
}>();

const componentEvents = defineEmits<{
  (eventName: "close"): void;
  (
    eventName: "submitLecturerResearchHourWarningNotificationRequest",
    lecturerResearchHourWarningEntry: LecturerResearchHourWarningEntry
  ): void;
}>();

const minimumRequiredResearchHoursForSelectedLecturer = computed<number>(() => {
  return (
    componentProperties.selectedLecturerResearchHourWarningEntry
      ?.minimumRequiredResearchHours ??
    componentProperties.minimumRequiredResearchHours
  );
});

function closeModal(): void {
  componentEvents("close");
}

function formatIntegerValue(value: number): string {
  return new Intl.NumberFormat("vi-VN").format(Math.round(value));
}
function confirmAndSubmitLecturerResearchHourWarningNotificationRequest(): void {
  if (!componentProperties.selectedLecturerResearchHourWarningEntry) return;

  const hasConfirmed = window.confirm(
    "Xác nhận gửi yêu cầu nhắc nhở về giờ NCKH cho giảng viên này? (Demo UI)"
  );

  if (!hasConfirmed) return;

  componentEvents(
    "submitLecturerResearchHourWarningNotificationRequest",
    componentProperties.selectedLecturerResearchHourWarningEntry
  );
}
</script>

<style scoped>
/* Overlay fade */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 160ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Drawer slide from right */
.slideFromRight-enter-active,
.slideFromRight-leave-active {
  transition: transform 220ms ease;
}
.slideFromRight-enter-from,
.slideFromRight-leave-to {
  transform: translateX(100%);
}
</style>
