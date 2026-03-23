<template>
  <div v-if="isOpen" class="fixed inset-0 z-50" aria-live="polite">
    <!-- Overlay (fade) -->
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        class="absolute inset-0 bg-slate-900/50 backdrop-blur-[1px]"
        @click="emit('close')"
        aria-hidden="true"
      />
    </Transition>

    <!-- Drawer (slide) -->
    <Transition
      enter-active-class="transition-transform duration-200 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-150 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full"
    >
      <aside
        class="absolute right-0 top-0 h-full w-full bg-white shadow-2xl ring-1 ring-slate-200/70 sm:w-[92vw] lg:w-[860px]"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex h-full flex-col">
          <!-- Header -->
          <div class="border-b border-slate-200 px-4 py-4 lg:px-5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <h3 class="truncate text-sm font-semibold text-slate-900">
                    {{ drawerTitle }}
                  </h3>
                  <span class="text-xs text-slate-400">•</span>
                  <span class="text-xs font-medium text-slate-600">{{
                    drawerSubtitle
                  }}</span>
                </div>

                <p class="mt-1 text-xs text-slate-600">
                  {{ drawerHelperText }}
                </p>
              </div>

              <button
                type="button"
                class="h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                <X class="h-4 w-4" />
              </button>
            </div>

            <div v-if="selectedResearchWorkApprovalEntry" class="mt-3">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  :class="
                    mapApprovalStatusToBadgeClass(
                      selectedResearchWorkApprovalEntry.approvalStatus,
                    )
                  "
                >
                  {{
                    mapApprovalStatusToDisplayName(
                      selectedResearchWorkApprovalEntry.approvalStatus,
                    )
                  }}
                </span>

                <span class="text-xs text-slate-500">
                  Năm học {{ selectedResearchWorkApprovalEntry.academicYear }}
                </span>

                <span class="text-xs text-slate-500">•</span>

                <span class="text-xs font-medium text-slate-700">
                  {{
                    mapResearchWorkTypeToDisplayName(
                      selectedResearchWorkApprovalEntry.researchWorkType,
                    )
                  }}
                </span>
              </div>

              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="scrollToSection('general')"
                >
                  Thông tin chung
                </button>
                <button
                  type="button"
                  class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="scrollToSection('evidence')"
                >
                  Minh chứng
                </button>
                <button
                  type="button"
                  class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="scrollToSection('members')"
                >
                  Thành viên
                </button>
                <button
                  type="button"
                  class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                  @click="scrollToSection('history')"
                >
                  Lịch sử duyệt
                </button>
              </div>
            </div>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-y-auto px-4 py-4 lg:px-5">
            <div v-if="selectedResearchWorkApprovalEntry" class="space-y-4">
              <!-- 1. Thông tin chung -->
              <section
                ref="generalSectionElement"
                class="rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="text-xs font-semibold text-slate-700">
                  Thông tin chung
                </div>
                <div class="mt-1 text-sm font-semibold text-slate-900">
                  {{ selectedResearchWorkApprovalEntry.researchWorkTitle }}
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Loại công trình
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.researchWorkKindDisplayName ||
                        mapResearchWorkTypeToDisplayName(
                          selectedResearchWorkApprovalEntry.researchWorkType,
                        )
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Phân loại
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.researchWorkCategoryDisplayName ||
                        "—"
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Vai trò
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ submittingAuthorRoleDisplayName }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">Năm</div>
                    <div class="mt-1 text-slate-900">
                      {{ selectedResearchWorkApprovalEntry.academicYear }}
                    </div>
                  </div>

                  <div
                    v-if="approvalScopeIdentifier !== 'UNIVERSITY_SCOPE'"
                    class="md:col-span-2"
                  >
                    <div class="text-xs font-semibold text-slate-500">
                      Nơi công bố/đơn vị
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.facultyDisplayName ||
                        "—"
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Ngày gửi
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        formatDateTimeDisplayValue(
                          selectedResearchWorkApprovalEntry.submittedAtDateTimeString,
                        )
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Ngày duyệt
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ reviewedAtDisplayValue }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Giờ NCKH của bạn
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{ submittingLecturerHoursDisplayValue }}
                    </div>
                  </div>

                  <p class="mt-1 text-xs text-slate-500 md:col-span-2">
                    Thành viên kê khai sẽ được tô nổi bật trong bảng “Thành viên
                    & số giờ”.
                  </p>
                </div>
              </section>

              <section
                v-if="selectedResearchWorkApprovalEntry.journalInfo"
                class="rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Thông tin tạp chí
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                  <div class="md:col-span-2">
                    <div class="text-xs font-semibold text-slate-500">
                      Tên tạp chí
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.journalInfo
                          .journalName || "—"
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">ISSN</div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.journalInfo.issn ||
                        "—"
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Điểm tạp chí
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.journalInfo
                          .workScore === null
                          ? "—"
                          : selectedResearchWorkApprovalEntry.journalInfo
                              .workScore
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Phạm vi
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.journalInfo
                          .journalScope || "—"
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Nguồn xếp loại
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.journalInfo
                          .journalSourceName || "—"
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Cơ quan xuất bản
                    </div>
                    <div class="mt-1 text-slate-900">
                      {{
                        selectedResearchWorkApprovalEntry.journalInfo
                          .journalPublisher || "—"
                      }}
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold text-slate-500">
                      Website
                    </div>
                    <a
                      v-if="
                        selectedResearchWorkApprovalEntry.journalInfo
                          .journalWebsite
                      "
                      :href="
                        selectedResearchWorkApprovalEntry.journalInfo
                          .journalWebsite
                      "
                      target="_blank"
                      rel="noopener noreferrer"
                      class="mt-1 inline-flex text-sky-700 hover:underline"
                    >
                      {{
                        selectedResearchWorkApprovalEntry.journalInfo
                          .journalWebsite
                      }}
                    </a>
                    <div v-else class="mt-1 text-slate-900">—</div>
                  </div>
                </div>
              </section>

              <section
                v-if="approverConflictMessage"
                class="rounded-2xl border border-rose-200 bg-rose-50/60 p-4"
              >
                <div class="text-sm font-semibold text-rose-900">
                  Bạn đang tham gia công trình này
                </div>
                <p class="mt-1 text-sm text-rose-900/90">
                  {{ approverConflictMessage }}
                </p>
              </section>

              <!-- 2. Minh chứng -->
              <section
                ref="evidenceSectionElement"
                class="rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="flex items-center justify-between">
                  <div class="text-sm font-semibold text-slate-900">
                    Minh chứng
                  </div>
                  <div class="text-xs text-slate-500">
                    {{
                      selectedResearchWorkApprovalEntry.evidenceAttachmentList
                        .length
                    }}
                    tệp
                  </div>
                </div>

                <div
                  v-if="
                    selectedResearchWorkApprovalEntry.evidenceAttachmentList
                      .length === 0
                  "
                  class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
                >
                  Chưa có minh chứng.
                </div>

                <div v-else class="mt-3 space-y-2">
                  <div
                    v-for="evidenceAttachment in selectedResearchWorkApprovalEntry.evidenceAttachmentList"
                    :key="evidenceAttachment.evidenceAttachmentIdentifier"
                    class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/40 px-3 py-2 hover:bg-slate-50"
                  >
                    <div class="min-w-0">
                      <div class="truncate text-sm font-medium text-slate-900">
                        {{ evidenceAttachment.evidenceAttachmentDisplayName }}
                      </div>
                      <div class="mt-0.5 text-xs text-slate-600">
                        Định dạng:
                        {{ evidenceAttachment.evidenceAttachmentFileType }}
                      </div>
                    </div>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="
                        isEvidenceLoading(
                          evidenceAttachment.evidenceAttachmentIdentifier,
                        )
                      "
                      @click="openEvidenceAttachment(evidenceAttachment)"
                    >
                      {{
                        isEvidenceLoading(
                          evidenceAttachment.evidenceAttachmentIdentifier,
                        )
                          ? "Đang mở..."
                          : "Xem"
                      }}
                    </button>
                  </div>
                </div>
              </section>

              <!-- 3. 1 BẢNG: Thành viên + Vai trò + Khoa + Số giờ -->
              <section
                ref="membersSectionElement"
                class="rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-slate-900">
                      Thành viên & số giờ
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                      Hiển thị giờ quy đổi dự kiến theo quy tắc tính giờ hiện
                      tại của công trình. Giờ chính thức chỉ được ghi nhận sau
                      bước duyệt giờ NCKH.
                    </p>
                  </div>

                  <div class="text-xs text-slate-500">
                    {{ authorHourRows.length }} thành viên
                  </div>
                </div>

                <div
                  v-if="selectedResearchWorkApprovalEntry?.ruleSummary"
                  class="mt-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700"
                >
                  {{ selectedResearchWorkApprovalEntry.ruleSummary }}
                </div>

                <div
                  v-if="selectedResearchWorkApprovalEntry?.hoursResolutionNote"
                  class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
                >
                  {{ selectedResearchWorkApprovalEntry.hoursResolutionNote }}
                </div>

                <div
                  class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white"
                >
                  <div
                    class="flex items-center justify-between border-b border-slate-200 px-3 py-2"
                  >
                    <div class="text-xs font-semibold text-slate-700">
                      Bảng thành viên
                    </div>
                    <div class="text-xs text-slate-500">
                      Giờ quy đổi theo quy tắc
                    </div>
                  </div>

                  <div
                    v-if="authorHourRows.length === 0"
                    class="px-3 py-3 text-sm text-slate-600"
                  >
                    Chưa có danh sách tác giả.
                  </div>

                  <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left">
                      <thead class="bg-slate-50">
                        <tr class="text-xs font-semibold text-slate-600">
                          <th class="w-10 px-3 py-2">#</th>
                          <th class="px-3 py-2">Tên thành viên</th>
                          <th class="px-3 py-2">Vai trò</th>
                          <th
                            v-if="
                              approvalScopeIdentifier !== 'UNIVERSITY_SCOPE'
                            "
                            class="px-3 py-2"
                          >
                            Khoa/Đơn vị
                          </th>
                          <th class="px-3 py-2">Giờ quy đổi (dự kiến)</th>
                        </tr>
                      </thead>

                      <tbody class="divide-y divide-slate-200">
                        <tr
                          v-for="(a, i) in authorHourRows"
                          :key="a.authorIdentifier"
                          class="text-sm"
                          :class="
                            a.isSubmittingLecturer
                              ? 'bg-amber-50'
                              : i % 2 === 0
                                ? 'bg-white'
                                : 'bg-slate-50/40'
                          "
                        >
                          <td class="px-3 py-2 text-slate-500">{{ i + 1 }}</td>

                          <td class="px-3 py-2">
                            <div
                              class="truncate"
                              :class="
                                a.isSubmittingLecturer
                                  ? 'font-extrabold text-slate-900'
                                  : 'font-semibold text-slate-900'
                              "
                            >
                              {{ a.authorDisplayName }}
                            </div>
                          </td>

                          <td class="px-3 py-2">
                            <span
                              v-if="a.isPrimaryAuthor"
                              class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-900"
                            >
                              {{ a.authorRoleDisplayName ?? "Tác giả chính" }}
                            </span>
                            <span
                              v-else
                              class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700"
                            >
                              {{ a.authorRoleDisplayName ?? "Đồng tác giả" }}
                            </span>
                          </td>

                          <td
                            v-if="
                              approvalScopeIdentifier !== 'UNIVERSITY_SCOPE'
                            "
                            class="px-3 py-2"
                          >
                            <div class="text-slate-700">
                              {{ a.authorFacultyDisplayName || "—" }}
                            </div>
                            <span
                              v-if="a.isOutsideFaculty"
                              class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                            >
                              Ngoài khoa
                            </span>
                          </td>

                          <td class="px-3 py-2">
                            <div class="font-semibold text-slate-900">
                              {{
                                formatHourValue(
                                  a.computedMemberHours ??
                                    a.declaredHours ??
                                    a.recommendedHoursByPolicy ??
                                    a.officialHours,
                                )
                              }}
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </section>

              <!-- 4. Lịch sử duyệt -->
              <section
                ref="historySectionElement"
                class="rounded-2xl border border-slate-200 bg-white p-4"
              >
                <div class="text-sm font-semibold text-slate-900">
                  Lịch sử duyệt
                </div>
                <div class="mt-3 space-y-2">
                  <div
                    v-for="history in selectedResearchWorkApprovalEntry.approvalHistoryList"
                    :key="history.historyIdentifier"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2"
                  >
                    <div
                      class="flex flex-wrap items-center justify-between gap-2"
                    >
                      <div class="text-sm font-semibold text-slate-900">
                        {{ history.reviewLevelDisplayName }} —
                        {{ history.reviewActionDisplayName }}
                      </div>
                      <div class="text-xs text-slate-500">
                        {{
                          formatDateTimeDisplayValue(
                            history.reviewedAtDateTimeString,
                          )
                        }}
                      </div>
                    </div>
                    <div class="mt-1 text-sm text-slate-700">
                      {{ history.reviewNote }}
                    </div>
                  </div>
                </div>
              </section>

              <!-- Rejection Panel -->
              <section
                v-if="isRejectionPanelVisible"
                ref="rejectionPanelElement"
                class="rounded-2xl border border-rose-200 bg-rose-50/60 p-4"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-semibold text-rose-900">
                      Lý do từ chối (bắt buộc)
                    </div>
                    <p class="mt-1 text-sm text-rose-900/90">
                      Lý do rõ ràng giúp bên dưới điều chỉnh nhanh và đối soát
                      minh bạch.
                    </p>
                  </div>

                  <button
                    type="button"
                    class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-medium text-rose-900 hover:bg-rose-50"
                    @click="cancelRejectionFlow"
                  >
                    Hủy
                  </button>
                </div>

                <div class="mt-3 space-y-2">
                  <label
                    v-for="option in rejectionReasonOptionList"
                    :key="option.value"
                    class="flex cursor-pointer items-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2"
                  >
                    <input
                      class="h-4 w-4"
                      type="radio"
                      name="rejectionReason"
                      :value="option.value"
                      v-model="selectedRejectionReasonType"
                    />
                    <span class="text-sm font-medium text-slate-800">{{
                      option.label
                    }}</span>
                  </label>

                  <div v-if="isOtherRejectionReasonSelected" class="mt-2">
                    <label class="text-xs font-semibold text-rose-900"
                      >Nội dung bổ sung</label
                    >
                    <textarea
                      v-model="rejectionReasonDetail"
                      rows="3"
                      class="mt-1 w-full rounded-xl border border-rose-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-rose-300 focus:ring-0"
                      placeholder="Nhập lý do cụ thể..."
                    />
                  </div>

                  <div
                    v-if="shouldShowRejectionValidationHint"
                    class="mt-3 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs text-rose-900"
                  >
                    Vui lòng chọn lý do. Nếu chọn “Khác”, cần nhập nội dung chi
                    tiết (>= 5 ký tự).
                  </div>
                </div>
              </section>
            </div>

            <div
              v-else
              class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-sm text-slate-700"
            >
              Chưa chọn công trình.
            </div>
          </div>

          <!-- Footer -->
          <div
            class="sticky bottom-0 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur-sm lg:px-5"
          >
            <div class="flex flex-wrap items-center justify-end gap-2">
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                @click="emit('close')"
              >
                Đóng
              </button>

              <button
                type="button"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-100 disabled:opacity-60"
                :disabled="isApproveActionDisabled"
                @click="approveSelectedResearchWork"
              >
                {{ primaryActionButtonLabel }}
              </button>

              <button
                type="button"
                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-900 hover:bg-rose-100 disabled:opacity-60"
                :disabled="isRejectActionDisabled"
                @click="onRejectActionButtonClicked"
              >
                {{ rejectActionButtonDisplayName }}
              </button>
            </div>

            <p class="mt-2 text-xs text-slate-600">
              {{ footerHelperText }}
            </p>
          </div>
        </div>
      </aside></Transition
    >

    <PdfPreviewModal
      :open="previewOpen"
      :title="previewTitle"
      :file-name="previewFileName"
      :preview-url="previewUrl"
      :loading="previewLoading"
      :error-message="previewError"
      :can-download="canDownload"
      @close="closePdfPreview"
      @retry="retryOpenPdfPreview"
      @download="downloadPreviewedPdf"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch, toRefs } from "vue";
import { X } from "lucide-vue-next";
import PdfPreviewModal from "@/shared/components/modals/PdfPreviewModal.vue";
import { usePdfPreview } from "@/shared/composables/usePdfPreview";
import type {
  EvidenceAttachment,
  ResearchWorkApprovalEntry,
  ResearchWorkApprovalScopeIdentifier,
  ResearchWorkRejectionReasonType,
} from "../models/researchWorkApprovalModels";
import { useResearchWorkApprovalDisplayMapping } from "../composables/useResearchWorkApprovalDisplayMapping";
import { useActionResultModal } from "@/shared/composables/useActionResultModal";

const props = defineProps<{
  approvalScopeIdentifier: ResearchWorkApprovalScopeIdentifier;

  isOpen: boolean;
  selectedResearchWorkApprovalEntry: ResearchWorkApprovalEntry | null;

  isOfficialResearchHoursEditable: boolean;

  drawerTitle: string;
  drawerSubtitle: string;
  drawerHelperText: string;

  primaryActionButtonLabel: string;
  dangerActionButtonLabel: string;

  rejectionReasonOptionList: {
    value: ResearchWorkRejectionReasonType;
    label: string;
  }[];

  footerHelperText: string;
}>();

const emit = defineEmits<{
  (eventName: "close"): void;
  (
    eventName: "approve",
    payload: {
      researchWorkIdentifier: number;
      officialResearchHours: number | null; // tạm thời giữ API cũ

      memberHours?: { authorIdentifier: number; officialHours: number }[];
    },
  ): void;
  (
    eventName: "reject",
    payload: {
      researchWorkIdentifier: number;
      rejectionReasonType: ResearchWorkRejectionReasonType;
      rejectionReasonDetail: string | null;
    },
  ): void;
}>();

// giữ reactivity khi bóc props
const {
  approvalScopeIdentifier,
  isOpen,
  selectedResearchWorkApprovalEntry,
  isOfficialResearchHoursEditable,
  drawerTitle,
  drawerSubtitle,
  drawerHelperText,
  primaryActionButtonLabel,
  dangerActionButtonLabel,
  rejectionReasonOptionList,
  footerHelperText,
} = toRefs(props);

// mapping hiển thị
const displayMapping = useResearchWorkApprovalDisplayMapping({
  approvalScopeIdentifier: approvalScopeIdentifier.value,
});

const {
  mapResearchWorkTypeToDisplayName,
  mapApprovalStatusToDisplayName,
  mapApprovalStatusToBadgeClass,
  formatIntegerValue,
  formatDateTimeDisplayValue,
} = displayMapping;
const { openActionResultModal, showErrorModal } = useActionResultModal();
const {
  previewOpen,
  previewLoading,
  previewError,
  previewTitle,
  previewFileName,
  previewUrl,
  canDownload,
  openPdfPreview,
  retryOpenPdfPreview,
  closePdfPreview,
  isPreviewLoading,
  downloadPreviewedPdf,
  prefetchPdfPreview,
} = usePdfPreview();

function isEvidenceLoading(evidenceAttachmentId: number): boolean {
  return isPreviewLoading(`approval-evidence:${evidenceAttachmentId}`);
}

async function openEvidenceAttachment(
  evidenceAttachment: EvidenceAttachment,
): Promise<void> {
  const evidencePreviewUrl =
    evidenceAttachment.evidenceAttachmentPreviewUrl?.trim() ?? "";
  if (!evidencePreviewUrl || evidencePreviewUrl === "#") {
    showErrorModal(
      "Không thể mở tệp minh chứng ở thời điểm này. Vui lòng thử lại.",
      "Không thể mở minh chứng",
    );
    return;
  }

  const downloadUrl = evidenceAttachment.evidenceAttachmentDownloadUrl ?? null;
  const fallbackFileName =
    evidenceAttachment.evidenceAttachmentDisplayName.trim() ||
    `minh-chung-${evidenceAttachment.evidenceAttachmentIdentifier}.pdf`;

  await openPdfPreview({
    cacheKey: `approval-evidence:${evidenceAttachment.evidenceAttachmentIdentifier}`,
    title: "Xem minh chứng",
    fallbackFileName,
    previewUrl: evidencePreviewUrl,
    downloadUrl,
    errorMessage: "Không thể mở file minh chứng. Vui lòng thử lại.",
  });
}

watch(
  () => [
    isOpen.value,
    selectedResearchWorkApprovalEntry.value?.researchWorkIdentifier,
    selectedResearchWorkApprovalEntry.value?.evidenceAttachmentList,
  ],
  ([drawerOpen]) => {
    if (!drawerOpen) return;
    const firstAttachment =
      selectedResearchWorkApprovalEntry.value?.evidenceAttachmentList?.[0] ??
      null;
    if (!firstAttachment) return;
    const previewUrl =
      firstAttachment.evidenceAttachmentPreviewUrl?.trim() ?? "";
    if (!previewUrl || previewUrl === "#") return;

    void prefetchPdfPreview({
      cacheKey: `approval-evidence:${firstAttachment.evidenceAttachmentIdentifier}`,
      previewUrl,
      downloadUrl: firstAttachment.evidenceAttachmentDownloadUrl ?? null,
      fallbackFileName:
        firstAttachment.evidenceAttachmentDisplayName.trim() ||
        `minh-chung-${firstAttachment.evidenceAttachmentIdentifier}.pdf`,
    });
  },
  { immediate: true, deep: true },
);

// chỉ cấp trường mới được chốt giờ
const canFinalizeHours = computed(() => {
  return (
    approvalScopeIdentifier.value === "UNIVERSITY_SCOPE" &&
    isOfficialResearchHoursEditable.value
  );
});

/** =========================
 *  REJECT FLOW (CHỈ 1 LẦN)
 *  ========================= */
const isRejectionPanelVisible = ref(false);
const rejectionPanelElement = ref<HTMLElement | null>(null);
const selectedRejectionReasonType = ref<ResearchWorkRejectionReasonType | null>(
  null,
);
const rejectionReasonDetail = ref("");
const shouldShowRejectionValidationHint = ref(false);
const generalSectionElement = ref<HTMLElement | null>(null);
const evidenceSectionElement = ref<HTMLElement | null>(null);
const membersSectionElement = ref<HTMLElement | null>(null);
const historySectionElement = ref<HTMLElement | null>(null);

const isOtherRejectionReasonSelected = computed(
  () => selectedRejectionReasonType.value === "OTHER",
);

const isRejectionFormValid = computed(() => {
  if (!selectedRejectionReasonType.value) return false;
  if (selectedRejectionReasonType.value !== "OTHER") return true;
  return rejectionReasonDetail.value.trim().length >= 5;
});

const rejectActionButtonDisplayName = computed(() => {
  return isRejectionPanelVisible.value
    ? "Xác nhận từ chối"
    : dangerActionButtonLabel.value;
});

function resetRejectionFlowState(): void {
  isRejectionPanelVisible.value = false;
  selectedRejectionReasonType.value = null;
  rejectionReasonDetail.value = "";
  shouldShowRejectionValidationHint.value = false;
}

function cancelRejectionFlow(): void {
  resetRejectionFlowState();
}

function scrollToSection(
  section: "general" | "evidence" | "members" | "history",
): void {
  const targetBySection = {
    general: generalSectionElement.value,
    evidence: evidenceSectionElement.value,
    members: membersSectionElement.value,
    history: historySectionElement.value,
  } as const;
  targetBySection[section]?.scrollIntoView({
    behavior: "smooth",
    block: "start",
  });
}

/** =========================
 *  AUTHORS (table 1)
 *  ========================= */
type AuthorEntry = NonNullable<
  ResearchWorkApprovalEntry["researchWorkAuthorList"]
>[number];

const authorRows = computed<AuthorEntry[]>(() => {
  const list =
    selectedResearchWorkApprovalEntry.value?.researchWorkAuthorList ?? [];
  return [...list].sort(
    (a, b) =>
      Number(b.isPrimaryAuthor) - Number(a.isPrimaryAuthor) ||
      Number(b.isSubmittingLecturer) - Number(a.isSubmittingLecturer) ||
      a.authorDisplayName.localeCompare(b.authorDisplayName),
  );
});

/** =========================
 *  HOURS TABLE (table 2) - UI only
 *  ========================= */
type AuthorHourRow = AuthorEntry & {
  declaredHours: number | null;
  recommendedHoursByPolicy: number | null;
  officialHours: number | null;
};

const authorHourRows = computed<AuthorHourRow[]>(() => {
  // hiện mock author chưa có giờ => null; sau này đổ data thật
  return authorRows.value.map((a) => {
    const anyA = a as any;
    return {
      ...a,
      declaredHours: anyA.declaredHours ?? null,
      recommendedHoursByPolicy: anyA.recommendedHoursByPolicy ?? null,
      officialHours: anyA.officialHours ?? null,
    };
  });
});

function formatHourValue(v: number | null): string {
  return v == null ? "—" : formatIntegerValue(v);
}

/** draft nhập giờ chính thức theo từng người (chỉ cấp trường) */
const officialHoursDraftByAuthorId = ref<Record<number, number | null>>({});
const shouldShowOfficialHoursValidationHint = ref(false);

/** ✅ watch đặt SAU khi mọi ref/computed cần dùng đã có */
watch(
  () => selectedResearchWorkApprovalEntry.value?.researchWorkIdentifier,
  () => {
    const map: Record<number, number | null> = {};
    for (const r of authorHourRows.value) {
      map[r.authorIdentifier] =
        r.officialHours ??
        r.recommendedHoursByPolicy ??
        r.declaredHours ??
        null;
    }
    officialHoursDraftByAuthorId.value = map;

    resetRejectionFlowState();
    shouldShowOfficialHoursValidationHint.value = false;
  },
  { immediate: true },
);

const totalOfficialHours = computed<number>(() => {
  return authorHourRows.value.reduce((sum, r) => {
    const v = officialHoursDraftByAuthorId.value[r.authorIdentifier];
    return sum + (Number.isFinite(v as number) ? (v as number) : 0);
  }, 0);
});

const isOfficialHoursValid = computed<boolean>(() => {
  if (!canFinalizeHours.value) return true;
  if (authorHourRows.value.length === 0) return false;

  return authorHourRows.value.every((r) => {
    const v = officialHoursDraftByAuthorId.value[r.authorIdentifier];
    return Number.isFinite(v as number) && (v as number) >= 0;
  });
});

/** =========================
 *  APPROVE / REJECT LOGIC
 *  ========================= */
function getPendingStatusValue() {
  return approvalScopeIdentifier.value === "FACULTY_SCOPE"
    ? "PENDING_FACULTY_APPROVAL"
    : "PENDING_UNIVERSITY_APPROVAL";
}

const isApproveActionDisabled = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return true;
  return (
    entry.approvalStatus !== getPendingStatusValue() ||
    entry.hasApproverConflict === true
  );
});

const isRejectActionDisabled = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return true;
  return (
    entry.approvalStatus !== getPendingStatusValue() ||
    entry.hasApproverConflict === true
  );
});

const approverConflictMessage = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry?.hasApproverConflict) return null;
  return (
    entry.approverConflictMessage?.trim() ||
    "Bạn không thể duyệt hoặc từ chối công trình mà mình tham gia. Vui lòng chuyển hồ sơ cho thành viên hội đồng khoa khác."
  );
});

const submittingAuthorRoleDisplayName = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return "—";
  const submitting = entry.researchWorkAuthorList.find(
    (author) => author.isSubmittingLecturer,
  );
  return submitting?.authorRoleDisplayName?.trim() || "—";
});

const reviewedAtDisplayValue = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return "—";

  const latestHistoryReviewedAt =
    [...entry.approvalHistoryList]
      .reverse()
      .find((history) => history.reviewedAtDateTimeString?.trim())
      ?.reviewedAtDateTimeString ?? null;

  const reviewedAt =
    approvalScopeIdentifier.value === "UNIVERSITY_SCOPE"
      ? (entry.universityReviewedAtDateTimeString ??
        entry.facultyReviewedAtDateTimeString ??
        latestHistoryReviewedAt)
      : (entry.facultyReviewedAtDateTimeString ?? latestHistoryReviewedAt);
  if (!reviewedAt) return "—";
  return formatDateTimeDisplayValue(reviewedAt);
});

const submittingLecturerHoursDisplayValue = computed(() => {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return "—";

  // Prefer the hours mapped for the submitting lecturer in member list.
  const submittingRow = authorHourRows.value.find(
    (row) => row.isSubmittingLecturer,
  );
  const valueFromRow =
    submittingRow?.computedMemberHours ??
    submittingRow?.officialHours ??
    submittingRow?.recommendedHoursByPolicy ??
    submittingRow?.declaredHours ??
    null;

  const official = entry.officialResearchHours;
  const recommended = entry.recommendedResearchHoursByPolicy;
  const declared = entry.lecturerDeclaredResearchHours;
  const value = valueFromRow ?? official ?? recommended ?? declared;
  return Number.isFinite(value) ? formatIntegerValue(value) : "—";
});

function onEscapeKeyDown(event: KeyboardEvent): void {
  if (event.key !== "Escape") return;
  if (!isOpen.value) return;
  emit("close");
}

watch(
  () => isOpen.value,
  (drawerOpen) => {
    if (typeof document === "undefined") return;
    document.body.style.overflow = drawerOpen ? "hidden" : "";
  },
  { immediate: true },
);

watch(
  () => isOpen.value,
  (drawerOpen) => {
    if (typeof window === "undefined") return;
    if (drawerOpen) {
      window.addEventListener("keydown", onEscapeKeyDown);
      return;
    }
    window.removeEventListener("keydown", onEscapeKeyDown);
  },
  { immediate: true },
);

onBeforeUnmount(() => {
  if (typeof document !== "undefined") {
    document.body.style.overflow = "";
  }
  if (typeof window !== "undefined") {
    window.removeEventListener("keydown", onEscapeKeyDown);
  }
});

function approveSelectedResearchWork(): void {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return;
  if (entry.hasApproverConflict) {
    showErrorModal(
      approverConflictMessage.value ??
        "Bạn không thể duyệt hoặc từ chối công trình mà mình tham gia. Vui lòng chuyển hồ sơ cho thành viên hội đồng khoa khác.",
      "Không thể xét duyệt",
    );
    return;
  }

  if (canFinalizeHours.value) {
    shouldShowOfficialHoursValidationHint.value = true;
    if (!isOfficialHoursValid.value) return;
  }

  const approvalPayload = {
    researchWorkIdentifier: entry.researchWorkIdentifier,
    // giu API cu: cap truong gui tong gio, cap khoa gui null.
    officialResearchHours: canFinalizeHours.value
      ? Math.round(totalOfficialHours.value)
      : null,
    memberHours: authorHourRows.value.map((row) => ({
      authorIdentifier: row.authorIdentifier,
      officialHours: Number(
        officialHoursDraftByAuthorId.value[row.authorIdentifier] ?? 0,
      ),
    })),
  };

  openActionResultModal({
    type: "warning",
    title: "Xác nhận",
    message: canFinalizeHours.value
      ? "Xác nhận duyệt và chốt giờ NCKH cho công trình này?"
      : "Xác nhận hồ sơ hợp lệ và duyệt công trình?",
    closeLabel: "Hủy",
    secondaryLabel: "Duyệt",
    onSecondary: () => {
      emit("approve", approvalPayload);
    },
  });
}

async function onRejectActionButtonClicked(): Promise<void> {
  const entry = selectedResearchWorkApprovalEntry.value;
  if (!entry) return;
  if (entry.hasApproverConflict) {
    showErrorModal(
      approverConflictMessage.value ??
        "Bạn không thể duyệt hoặc từ chối công trình mà mình tham gia. Vui lòng chuyển hồ sơ cho thành viên hội đồng khoa khác.",
      "Không thể xét duyệt",
    );
    return;
  }

  if (!isRejectionPanelVisible.value) {
    isRejectionPanelVisible.value = true;
    await nextTick();
    rejectionPanelElement.value?.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
    return;
  }

  shouldShowRejectionValidationHint.value = true;
  if (!isRejectionFormValid.value || !selectedRejectionReasonType.value) return;

  const rejectionPayload = {
    researchWorkIdentifier: entry.researchWorkIdentifier,
    rejectionReasonType: selectedRejectionReasonType.value,
    rejectionReasonDetail:
      selectedRejectionReasonType.value === "OTHER"
        ? rejectionReasonDetail.value.trim()
        : null,
  };

  openActionResultModal({
    type: "warning",
    title: "Xác nhận",
    message: "Xác nhận từ chối công trình này?",
    closeLabel: "Hủy",
    secondaryLabel: "Từ chối",
    onSecondary: () => {
      emit("reject", rejectionPayload);
      resetRejectionFlowState();
    },
  });
}
</script>
