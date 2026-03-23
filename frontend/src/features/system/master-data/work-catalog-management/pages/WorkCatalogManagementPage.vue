<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Danh mục công trình"
          subtitle="Quản lý danh mục phục vụ kê khai và xét duyệt công trình nghiên cứu khoa học"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <CatalogTabs
        :model-value="activeTab"
        :tabs="tabs"
        @update:model-value="setActiveTab"
      />

      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <div
          v-if="errorMessage"
          class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
        >
          {{ errorMessage }}
        </div>

        <div v-if="loading" class="py-6 text-sm text-slate-600">
          Đang tải dữ liệu...
        </div>

        <template v-else>
          <WorkTypeCatalogSection
            v-if="activeTab === 'work_type'"
            :rows="pagedWorkTypes"
            :start-index="(pageWorkType - 1) * pageSizeWorkType"
            :total="workTypeTotal"
            :search="qWorkType"
            :page="pageWorkType"
            :page-size="pageSizeWorkType"
            :format-date-time="formatDateTime"
            :modal-open="modalWorkTypeOpen"
            :modal-title="
              modalModeWorkType === 'create'
                ? 'Thêm loại công trình'
                : 'Chỉnh sửa loại công trình'
            "
            :submitting="loading"
            :form="workTypeForm"
            :errors="workTypeErrors"
            @update:search="qWorkType = $event"
            @update:page="pageWorkType = $event"
            @update:pageSize="pageSizeWorkType = $event"
            @create="openCreateWorkType"
            @edit="onEditWorkType"
            @close-modal="modalWorkTypeOpen = false"
            @submit="handleSaveWorkType"
            @update:form="onUpdateWorkTypeForm"
          />

          <WorkLevelCatalogSection
            v-else-if="activeTab === 'work_level'"
            :rows="pagedWorkLevels"
            :start-index="(pageWorkLevel - 1) * pageSizeWorkLevel"
            :total="workLevelTotal"
            :search="qWorkLevel"
            :page="pageWorkLevel"
            :page-size="pageSizeWorkLevel"
            :modal-open="modalWorkLevelOpen"
            :modal-title="
              modalModeWorkLevel === 'create'
                ? 'Thêm cấp công trình'
                : 'Chỉnh sửa cấp công trình'
            "
            :submitting="loading"
            :form="workLevelForm"
            :errors="workLevelErrors"
            @update:search="qWorkLevel = $event"
            @update:page="pageWorkLevel = $event"
            @update:pageSize="pageSizeWorkLevel = $event"
            @create="openCreateWorkLevel"
            @edit="onEditWorkLevel"
            @close-modal="modalWorkLevelOpen = false"
            @submit="handleSaveWorkLevel"
            @update:form="onUpdateWorkLevelForm"
          />

          <JournalCatalogSection
            v-else-if="activeTab === 'journal'"
            :rows="pagedJournals"
            :start-index="(pageJournal - 1) * pageSizeJournal"
            :total="journalTotal"
            :search="qJournal"
            :page="pageJournal"
            :page-size="pageSizeJournal"
            :modal-open="modalJournalOpen"
            :modal-title="
              modalModeJournal === 'create'
                ? 'Thêm tạp chí khoa học'
                : 'Chỉnh sửa tạp chí khoa học'
            "
            :submitting="loading"
            :form="journalForm"
            :errors="journalErrors"
            :suggestions-open="journalSuggestionModalOpen"
            :suggestions="journalSuggestions"
            :suggestions-loading="journalSuggestionLoading"
            :approving-suggestion-id="journalApprovingSuggestionId"
            @update:search="qJournal = $event"
            @update:page="pageJournal = $event"
            @update:pageSize="pageSizeJournal = $event"
            @create="openCreateJournal"
            @edit="onEditJournal"
            @open-suggestions="handleOpenJournalSuggestions"
            @close-suggestions="closeJournalSuggestions"
            @approve-suggestion="handleApproveJournalSuggestion"
            @reject-suggestion="handleRejectJournalSuggestion"
            @close-modal="modalJournalOpen = false"
            @submit="handleSaveJournal"
            @update:form="onUpdateJournalForm"
          />

          <PublisherCatalogSection
            v-else-if="activeTab === 'publisher'"
            :rows="pagedPublishers"
            :start-index="(pagePublisher - 1) * pageSizePublisher"
            :total="publisherTotal"
            :search="qPublisher"
            :page="pagePublisher"
            :page-size="pageSizePublisher"
            :modal-open="modalPublisherOpen"
            :modal-title="
              modalModePublisher === 'create'
                ? 'Thêm nhà xuất bản'
                : 'Chỉnh sửa nhà xuất bản'
            "
            :submitting="loading"
            :form="publisherForm"
            :errors="publisherErrors"
            :suggestions-open="publisherSuggestionModalOpen"
            :suggestions="publisherSuggestions"
            :suggestions-loading="publisherSuggestionLoading"
            :approving-suggestion-id="publisherApprovingSuggestionId"
            @update:search="qPublisher = $event"
            @update:page="pagePublisher = $event"
            @update:pageSize="pageSizePublisher = $event"
            @create="openCreatePublisher"
            @edit="onEditPublisher"
            @open-suggestions="handleOpenPublisherSuggestions"
            @close-suggestions="closePublisherSuggestions"
            @approve-suggestion="handleApprovePublisherSuggestion"
            @reject-suggestion="handleRejectPublisherSuggestion"
            @close-modal="modalPublisherOpen = false"
            @submit="handleSavePublisher"
            @update:form="onUpdatePublisherForm"
          />

          <ConferenceCatalogSection
            v-else-if="activeTab === 'conference'"
            :rows="pagedConferences"
            :start-index="(pageConference - 1) * pageSizeConference"
            :total="conferenceTotal"
            :search="qConference"
            :page="pageConference"
            :page-size="pageSizeConference"
            :modal-open="modalConferenceOpen"
            :modal-title="
              modalModeConference === 'create'
                ? 'Thêm hội nghị khoa học'
                : 'Chỉnh sửa hội nghị khoa học'
            "
            :submitting="loading"
            :form="conferenceForm"
            :errors="conferenceErrors"
            :suggestions-open="conferenceSuggestionModalOpen"
            :suggestions="conferenceSuggestions"
            :suggestions-loading="conferenceSuggestionLoading"
            :approving-suggestion-id="conferenceApprovingSuggestionId"
            @update:search="qConference = $event"
            @update:page="pageConference = $event"
            @update:pageSize="pageSizeConference = $event"
            @create="openCreateConference"
            @edit="onEditConference"
            @open-suggestions="handleOpenConferenceSuggestions"
            @close-suggestions="closeConferenceSuggestions"
            @approve-suggestion="handleApproveConferenceSuggestion"
            @reject-suggestion="handleRejectConferenceSuggestion"
            @close-modal="modalConferenceOpen = false"
            @submit="handleSaveConference"
            @update:form="onUpdateConferenceForm"
          />

          <ResearchFieldCatalogSection
            v-else
            :rows="pagedResearchFields"
            :start-index="(pageResearchField - 1) * pageSizeResearchField"
            :total="researchFieldTotal"
            :search="qResearchField"
            :page="pageResearchField"
            :page-size="pageSizeResearchField"
            :modal-open="modalResearchFieldOpen"
            :modal-title="
              modalModeResearchField === 'create'
                ? 'Thêm lĩnh vực nghiên cứu'
                : 'Chỉnh sửa lĩnh vực nghiên cứu'
            "
            :submitting="loading"
            :form="researchFieldForm"
            :errors="researchFieldErrors"
            @update:search="qResearchField = $event"
            @update:page="pageResearchField = $event"
            @update:pageSize="pageSizeResearchField = $event"
            @create="openCreateResearchField"
            @edit="onEditResearchField"
            @close-modal="modalResearchFieldOpen = false"
            @submit="handleSaveResearchField"
            @update:form="onUpdateResearchFieldForm"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { BookOpen, Building2, Users, Brain } from "lucide-vue-next";

import CatalogTabs from "../components/CatalogTabs.vue";
import WorkTypeCatalogSection from "../components/WorkTypeCatalogSection.vue";
import WorkLevelCatalogSection from "../components/WorkLevelCatalogSection.vue";
import JournalCatalogSection from "../components/JournalCatalogSection.vue";
import PublisherCatalogSection from "../components/PublisherCatalogSection.vue";
import ConferenceCatalogSection from "../components/ConferenceCatalogSection.vue";
import ResearchFieldCatalogSection from "../components/ResearchFieldCatalogSection.vue";

import type { WorkCatalogTabKey } from "../contracts/workCatalogTabs.contract";
import { useWorkCatalogs } from "../composables/useWorkCatalogs";
import {
  resolveApiErrorMessage,
  useActionFeedback,
} from "@/shared/composables/useActionFeedback";
import { usePageLoadFeedback } from "@/shared/composables/usePageLoadFeedback";

import PageHeader from "@/shared/components/layout/PageHeader.vue";

const tabs: Array<{ key: WorkCatalogTabKey; label: string; icon: any }> = [
  { key: "journal", label: "Tạp chí khoa học", icon: BookOpen },
  { key: "publisher", label: "Nhà xuất bản", icon: Building2 },
  { key: "conference", label: "Hội nghị khoa học", icon: Users },
  { key: "research_field", label: "Lĩnh vực nghiên cứu", icon: Brain },
];

const wc = useWorkCatalogs();
const { runPageLoad } = usePageLoadFeedback();

const {
  activeTab,
  loading,
  errorMessage,
  loadActiveTab,
  formatDateTime,

  workTypes,
  workLevels,
  journals,
  publishers,
  conferences,
  researchFields,

  qWorkType,
  qWorkLevel,
  qJournal,
  qPublisher,
  qConference,
  qResearchField,

  pageWorkType,
  pageWorkLevel,
  pageJournal,
  pagePublisher,
  pageConference,
  pageResearchField,
  pageSizeWorkType,
  pageSizeWorkLevel,
  pageSizeJournal,
  pageSizePublisher,
  pageSizeConference,
  pageSizeResearchField,

  pagedWorkTypes,
  pagedWorkLevels,
  pagedJournals,
  pagedPublishers,
  pagedConferences,
  pagedResearchFields,
  workTypeTotal,
  workLevelTotal,
  journalTotal,
  publisherTotal,
  conferenceTotal,
  researchFieldTotal,

  modalModeWorkType,
  modalModeWorkLevel,
  modalModeJournal,
  modalModePublisher,
  modalModeConference,
  modalModeResearchField,

  modalWorkTypeOpen,
  modalWorkLevelOpen,
  modalJournalOpen,
  modalPublisherOpen,
  modalConferenceOpen,
  modalResearchFieldOpen,

  workTypeForm,
  workTypeErrors,
  workLevelForm,
  workLevelErrors,
  journalForm,
  journalErrors,
  journalSuggestionModalOpen,
  journalSuggestionLoading,
  journalApprovingSuggestionId,
  journalSuggestions,
  publisherForm,
  publisherErrors,
  publisherSuggestionModalOpen,
  publisherSuggestionLoading,
  publisherApprovingSuggestionId,
  publisherSuggestions,
  conferenceForm,
  conferenceErrors,
  conferenceSuggestionModalOpen,
  conferenceSuggestionLoading,
  conferenceApprovingSuggestionId,
  conferenceSuggestions,
  researchFieldForm,
  researchFieldErrors,

  openCreateWorkType,
  openEditWorkType,
  saveWorkType,
  onUpdateWorkTypeForm,

  openCreateWorkLevel,
  openEditWorkLevel,
  saveWorkLevel,
  onUpdateWorkLevelForm,

  openCreateJournal,
  openEditJournal,
  openJournalSuggestions,
  closeJournalSuggestions,
  approveJournalSuggestion,
  rejectJournalSuggestion,
  validateJournalForm,
  saveJournal,
  onUpdateJournalForm,

  openCreatePublisher,
  openEditPublisher,
  openPublisherSuggestions,
  closePublisherSuggestions,
  approvePublisherSuggestion,
  rejectPublisherSuggestion,
  savePublisher,
  onUpdatePublisherForm,

  openCreateConference,
  openEditConference,
  openConferenceSuggestions,
  closeConferenceSuggestions,
  approveConferenceSuggestion,
  rejectConferenceSuggestion,
  saveConference,
  onUpdateConferenceForm,

  openCreateResearchField,
  openEditResearchField,
  saveResearchField,
  onUpdateResearchFieldForm,
} = wc;
const { runWithFeedback } = useActionFeedback();

async function loadActiveTabWithFeedback() {
  await runPageLoad(() => loadActiveTab(), {
    loading: {
      title: "Đang tải danh mục công trình",
      message: "Hệ thống đang chuẩn bị dữ liệu danh mục phục vụ kê khai...",
    },
    onError: (error) => console.error(error),
  });
}

const setActiveTab = (tab: WorkCatalogTabKey) => {
  activeTab.value = tab;
  void loadActiveTabWithFeedback();
};

onMounted(() => {
  void loadActiveTabWithFeedback();
});

function onEditWorkType(id: number) {
  const item = workTypes.value.find((x: any) => x.id === id);
  if (item) openEditWorkType(item);
}

function onEditWorkLevel(id: number) {
  const item = workLevels.value.find((x: any) => x.id === id);
  if (item) openEditWorkLevel(item);
}

function onEditJournal(id: number) {
  const item = journals.value.find((x: any) => x.id === id);
  if (item) openEditJournal(item);
}

function onEditPublisher(id: number) {
  const item = publishers.value.find((x: any) => x.id === id);
  if (item) openEditPublisher(item);
}

function onEditConference(id: number) {
  const item = conferences.value.find((x: any) => x.id === id);
  if (item) openEditConference(item);
}

function onEditResearchField(id: number) {
  const item = researchFields.value.find((x: any) => x.id === id);
  if (item) openEditResearchField(item);
}

function resolveActionErrorMessage(error: unknown, fallback: string) {
  return resolveApiErrorMessage(error, fallback);
}

function buildStatusActionText(
  mode: "create" | "edit",
  isActive: boolean,
  createText: string,
  updateText: string,
) {
  if (mode === "create") return createText;
  if (!isActive) return "Ngừng sử dụng thành công.";
  return updateText;
}

async function runCatalogAction(
  action: () => Promise<void>,
  successMessage: string,
) {
  await runWithFeedback(action, {
    loading: {
      title: "Đang xử lý",
      message: "Hệ thống đang cập nhật danh mục...",
    },
    success: {
      title: "Thành công",
      message: successMessage,
    },
    error: {
      title: "Có lỗi xảy ra",
      message: (error) =>
        resolveActionErrorMessage(
          error,
          "Thao tác thất bại. Vui lòng thử lại.",
        ),
    },
    rethrow: false,
  });
}

async function handleSaveWorkType() {
  const successMessage = buildStatusActionText(
    modalModeWorkType.value,
    workTypeForm.isActive,
    "Thêm loại công trình thành công.",
    "Cập nhật loại công trình thành công.",
  );
  await runCatalogAction(() => saveWorkType(), successMessage);
}

async function handleSaveWorkLevel() {
  const successMessage = buildStatusActionText(
    modalModeWorkLevel.value,
    workLevelForm.isActive,
    "Thêm cấp công trình thành công.",
    "Cập nhật cấp công trình thành công.",
  );
  await runCatalogAction(() => saveWorkLevel(), successMessage);
}

async function handleSaveJournal() {
  if (!validateJournalForm()) {
    return;
  }

  const successMessage = buildStatusActionText(
    modalModeJournal.value,
    journalForm.isActive,
    "Thêm tạp chí khoa học thành công.",
    "Cập nhật tạp chí khoa học thành công.",
  );
  await runCatalogAction(() => saveJournal(), successMessage);
}

async function handleOpenJournalSuggestions() {
  try {
    await openJournalSuggestions();
  } catch (error) {
    errorMessage.value = resolveActionErrorMessage(
      error,
      "Không tải được danh sách đề xuất tạp chí.",
    );
  }
}

async function handleApproveJournalSuggestion(id: number, reviewNote?: string) {
  await runCatalogAction(
    () => approveJournalSuggestion(id, reviewNote),
    "Đã duyệt đề xuất tạp chí và lưu vào danh mục.",
  );
}

async function handleRejectJournalSuggestion(id: number, reviewNote?: string) {
  await runCatalogAction(
    () => rejectJournalSuggestion(id, reviewNote),
    "Đã từ chối đề xuất tạp chí.",
  );
}

async function handleSavePublisher() {
  const successMessage = buildStatusActionText(
    modalModePublisher.value,
    publisherForm.isActive,
    "Thêm nhà xuất bản thành công.",
    "Cập nhật nhà xuất bản thành công.",
  );
  await runCatalogAction(() => savePublisher(), successMessage);
}

async function handleOpenPublisherSuggestions() {
  try {
    await openPublisherSuggestions();
  } catch (error) {
    errorMessage.value = resolveActionErrorMessage(
      error,
      "Không tải được danh sách đề xuất nhà xuất bản.",
    );
  }
}

async function handleApprovePublisherSuggestion(
  id: number,
  reviewNote?: string,
) {
  await runCatalogAction(
    () => approvePublisherSuggestion(id, reviewNote),
    "Đã duyệt đề xuất nhà xuất bản và lưu vào danh mục.",
  );
}

async function handleRejectPublisherSuggestion(
  id: number,
  reviewNote?: string,
) {
  await runCatalogAction(
    () => rejectPublisherSuggestion(id, reviewNote),
    "Đã từ chối đề xuất nhà xuất bản.",
  );
}

async function handleSaveConference() {
  const successMessage = buildStatusActionText(
    modalModeConference.value,
    conferenceForm.isActive,
    "Thêm hội nghị khoa học thành công.",
    "Cập nhật hội nghị khoa học thành công.",
  );
  await runCatalogAction(() => saveConference(), successMessage);
}

async function handleOpenConferenceSuggestions() {
  try {
    await openConferenceSuggestions();
  } catch (error) {
    errorMessage.value = resolveActionErrorMessage(
      error,
      "Không tải được danh sách đề xuất hội nghị.",
    );
  }
}

async function handleApproveConferenceSuggestion(
  id: number,
  reviewNote?: string,
) {
  await runCatalogAction(
    () => approveConferenceSuggestion(id, reviewNote),
    "Đã duyệt đề xuất hội nghị và lưu vào danh mục.",
  );
}

async function handleRejectConferenceSuggestion(
  id: number,
  reviewNote?: string,
) {
  await runCatalogAction(
    () => rejectConferenceSuggestion(id, reviewNote),
    "Đã từ chối đề xuất hội nghị.",
  );
}

async function handleSaveResearchField() {
  const successMessage = buildStatusActionText(
    modalModeResearchField.value,
    researchFieldForm.isActive,
    "Thêm lĩnh vực nghiên cứu thành công.",
    "Cập nhật lĩnh vực nghiên cứu thành công.",
  );
  await runCatalogAction(() => saveResearchField(), successMessage);
}
</script>
