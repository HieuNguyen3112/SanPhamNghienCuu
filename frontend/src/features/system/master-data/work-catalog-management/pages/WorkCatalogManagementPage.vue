<template>
  <div class="min-h-screen bg-slate-50">
    <div class="mx-auto w-full space-y-4 p-4 md:p-6">
      <!-- Header -->
      <div
        class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-6"
      >
        <PageHeader
          title="Danh mục công trình"
          subtitle="Quản lý các danh mục nền phục vụ kê khai và xét duyệt công trình nghiên cứu khoa học"
          :show-export-pdf="false"
          :show-export-excel="false"
          @exportPdfClicked="() => {}"
          @exportExcelClicked="() => {}"
        />
      </div>

      <!-- Tabs -->
      <CatalogTabs
        :model-value="activeTab"
        :tabs="tabs"
        @update:model-value="setActiveTab"
      />

      <!-- Content Card -->
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
              modalMode === 'create'
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
            @submit="saveWorkType"
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
              modalMode === 'create'
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
            @submit="saveWorkLevel"
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
              modalMode === 'create'
                ? 'Thêm tạp chí khoa học'
                : 'Chỉnh sửa tạp chí khoa học'
            "
            :submitting="loading"
            :form="journalForm"
            :errors="journalErrors"
            @update:search="qJournal = $event"
            @update:page="pageJournal = $event"
            @update:pageSize="pageSizeJournal = $event"
            @create="openCreateJournal"
            @edit="onEditJournal"
            @close-modal="modalJournalOpen = false"
            @submit="saveJournal"
            @update:form="onUpdateJournalForm"
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
              modalMode === 'create'
                ? 'Thêm hội nghị khoa học'
                : 'Chỉnh sửa hội nghị khoa học'
            "
            :submitting="loading"
            :form="conferenceForm"
            :errors="conferenceErrors"
            @update:search="qConference = $event"
            @update:page="pageConference = $event"
            @update:pageSize="pageSizeConference = $event"
            @create="openCreateConference"
            @edit="onEditConference"
            @close-modal="modalConferenceOpen = false"
            @submit="saveConference"
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
              modalMode === 'create'
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
            @submit="saveResearchField"
            @update:form="onUpdateResearchFieldForm"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { Layers, Flag, BookOpen, Users, Brain } from "lucide-vue-next";

import CatalogTabs from "../components/CatalogTabs.vue";
import WorkTypeCatalogSection from "../components/WorkTypeCatalogSection.vue";
import WorkLevelCatalogSection from "../components/WorkLevelCatalogSection.vue";
import JournalCatalogSection from "../components/JournalCatalogSection.vue";
import ConferenceCatalogSection from "../components/ConferenceCatalogSection.vue";
import ResearchFieldCatalogSection from "../components/ResearchFieldCatalogSection.vue";

import type { WorkCatalogTabKey } from "../contracts/workCatalogTabs.contract";
import { useWorkCatalogs } from "../composables/useWorkCatalogs";

import PageHeader from "@/shared/components/layout/PageHeader.vue";

// tabs config
const tabs: Array<{ key: WorkCatalogTabKey; label: string; icon: any }> = [
  { key: "work_type", label: "Loại công trình", icon: Layers },
  { key: "work_level", label: "Cấp công trình", icon: Flag },
  { key: "journal", label: "Tạp chí khoa học", icon: BookOpen },
  { key: "conference", label: "Hội nghị khoa học", icon: Users },
  { key: "research_field", label: "Lĩnh vực nghiên cứu", icon: Brain },
];

const wc = useWorkCatalogs();

const {
  // global
  activeTab,
  loading,
  errorMessage,
  loadActiveTab,
  formatDateTime,

  // data stores
  workTypes,
  workLevels,
  journals,
  conferences,
  researchFields,

  // search
  qWorkType,
  qWorkLevel,
  qJournal,
  qConference,
  qResearchField,

  // pagination
  pageWorkType,
  pageWorkLevel,
  pageJournal,
  pageConference,
  pageResearchField,
  pageSizeWorkType,
  pageSizeWorkLevel,
  pageSizeJournal,
  pageSizeConference,
  pageSizeResearchField,

  // computed rows
  pagedWorkTypes,
  pagedWorkLevels,
  pagedJournals,
  pagedConferences,
  pagedResearchFields,
  workTypeTotal,
  workLevelTotal,
  journalTotal,
  conferenceTotal,
  researchFieldTotal,

  // modal mode + open states
  modalMode,
  modalWorkTypeOpen,
  modalWorkLevelOpen,
  modalJournalOpen,
  modalConferenceOpen,
  modalResearchFieldOpen,

  // forms + errors
  workTypeForm,
  workTypeErrors,
  workLevelForm,
  workLevelErrors,
  journalForm,
  journalErrors,
  conferenceForm,
  conferenceErrors,
  researchFieldForm,
  researchFieldErrors,

  // actions
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
  saveJournal,
  onUpdateJournalForm,

  openCreateConference,
  openEditConference,
  saveConference,
  onUpdateConferenceForm,

  openCreateResearchField,
  openEditResearchField,
  saveResearchField,
  onUpdateResearchFieldForm,
} = wc;

const setActiveTab = (tab: WorkCatalogTabKey) => {
  activeTab.value = tab;
  loadActiveTab();
};

onMounted(async () => {
  await loadActiveTab();
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
function onEditConference(id: number) {
  const item = conferences.value.find((x: any) => x.id === id);
  if (item) openEditConference(item);
}
function onEditResearchField(id: number) {
  const item = researchFields.value.find((x: any) => x.id === id);
  if (item) openEditResearchField(item);
}
</script>
