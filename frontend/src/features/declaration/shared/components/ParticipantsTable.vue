<template>
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <!-- Header -->
    <header
      class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between md:p-6"
    >
      <div class="min-w-0">
        <h2 class="text-sm font-semibold text-slate-900">
          Danh sách người tham gia
        </h2>
        <p class="mt-1 text-xs text-slate-500">
          Thêm/xóa chỉ khả dụng khi đang ở trạng thái bản nháp.
        </p>
      </div>

      <div class="flex items-center justify-end">
        <button
          type="button"
          class="inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="readOnly"
          @click="addRow"
          aria-label="Thêm người tham gia"
        >
          <Plus class="h-4 w-4" />
          <span>Thêm</span>
        </button>
      </div>
    </header>

    <!-- Desktop Table -->
    <div class="hidden md:block">
      <div class="overflow-visible">
        <div class="max-h-[520px] overflow-auto">
          <table class="min-w-full text-sm">
            <colgroup>
              <col class="w-12" />
              <col class="w-[38%]" />
              <col class="w-[22%]" />
              <col class="w-[20%]" />
              <col class="w-[14%]" />
              <col v-if="!readOnly" class="w-[6%]" />
            </colgroup>

            <thead class="sticky top-0 z-10 bg-slate-50">
              <tr>
                <th
                  class="px-4 py-3 text-center text-xs font-semibold text-slate-600 md:px-6"
                  scope="col"
                  title="Giảng viên ngoài"
                >
                  Ngoài
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-semibold text-slate-600 md:px-6"
                  scope="col"
                >
                  Họ tên
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                  scope="col"
                >
                  Đơn vị
                </th>
                <th
                  class="px-4 py-3 text-left text-xs font-semibold text-slate-600"
                  scope="col"
                >
                  Vai trò
                </th>
                <th
                  class="px-4 py-3 text-right text-xs font-semibold text-slate-600"
                  scope="col"
                >
                  Giờ NCKH
                </th>
                <th
                  v-if="!readOnly"
                  class="px-4 py-3 text-right text-xs font-semibold text-slate-600 md:px-6"
                  scope="col"
                ></th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-200 bg-white">
              <tr
                v-for="(row, idx) in modelValue"
                :key="idx"
                class="group relative hover:bg-slate-50"
                :class="[
                  row.lecturer_id === currentLecturerId ? 'bg-slate-50/60' : '',
                  activeLecturerRow === idx && !isLecturerPanelOpen
                    ? 'z-40'
                    : '',
                ]"
              >
                <!-- Ngoài (cột đầu) -->
                <td class="px-4 py-3 text-center align-top md:px-6">
                  <div class="pt-2">
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="readOnly"
                      :checked="!!row.is_external"
                      :title="'Giảng viên ngoài'"
                      :aria-label="`Giảng viên ngoài - dòng ${idx + 1}`"
                      @change="
                        toggleExternal(
                          idx,
                          ($event.target as HTMLInputElement).checked,
                        )
                      "
                    />
                  </div>
                </td>

                <!-- Họ tên -->
                <td
                  class="relative px-4 py-3 align-top md:px-6"
                  :class="
                    activeLecturerRow === idx && !isLecturerPanelOpen
                      ? 'z-50'
                      : ''
                  "
                >
                  <div class="flex flex-col gap-2">
                    <template v-if="row.is_external">
                      <input
                        type="text"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                        placeholder="Nhập họ tên..."
                        :disabled="readOnly"
                        :value="row.external_full_name ?? ''"
                        @input="
                          updateRow(idx, {
                            external_full_name: (
                              $event.target as HTMLInputElement
                            ).value,
                          })
                        "
                        :aria-label="`Họ tên giảng viên ngoài - dòng ${idx + 1}`"
                      />
                    </template>

                    <template v-else>
                      <div class="relative">
                        <div class="flex items-center gap-2">
                          <input
                            type="text"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                            placeholder="Tìm theo tên/mã giảng viên"
                            :disabled="readOnly"
                            :value="lecturerInputValue(idx, row.lecturer_id)"
                            :ref="
                              (el) =>
                                setDesktopInputRef(
                                  idx,
                                  el as HTMLInputElement | null,
                                )
                            "
                            @focus="onLecturerFocus(idx)"
                            @blur="onLecturerBlur(idx)"
                            @input="
                              onLecturerInput(
                                idx,
                                ($event.target as HTMLInputElement).value,
                              )
                            "
                            :aria-label="`Tìm giảng viên - dòng ${idx + 1}`"
                          />
                          <button
                            type="button"
                            class="inline-flex h-10 shrink-0 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                            @mousedown.prevent="openLecturerPanel(idx)"
                          >
                            Chi tiết
                          </button>
                        </div>
                      </div>
                    </template>
                  </div>
                </td>

                <!-- Đơn vị -->
                <td class="px-4 py-3 align-top text-slate-700">
                  <template v-if="row.is_external">
                    <input
                      type="text"
                      class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                      placeholder="Nhập đơn vị..."
                      :disabled="readOnly"
                      :value="row.external_department_name ?? ''"
                      @input="
                        updateRow(idx, {
                          external_department_name: (
                            $event.target as HTMLInputElement
                          ).value,
                        })
                      "
                      :aria-label="`Đơn vị giảng viên ngoài - dòng ${idx + 1}`"
                    />
                  </template>
                  <template v-else>
                    <div class="pt-2">
                      <div class="block">
                        {{ lecturerDepartmentName(row.lecturer_id) || "—" }}
                      </div>
                      <span
                        v-if="isOutsideFaculty(row)"
                        class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                      >
                        Ngoài khoa
                      </span>
                    </div>
                  </template>
                </td>

                <!-- Vai trò -->
                <td class="px-4 py-3 align-top">
                  <select
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                    :disabled="readOnly"
                    :value="row.member_role_id ?? ''"
                    @change="onMemberRoleChange(idx, $event)"
                    :aria-label="`Chọn vai trò - dòng ${idx + 1}`"
                  >
                    <option value="">— Chọn vai trò —</option>
                    <option
                      v-for="r in memberRoles"
                      :key="r.id"
                      :value="r.id"
                      :disabled="isMemberRoleDisabled(idx, r.id)"
                    >
                      {{ r.name }}
                    </option>
                  </select>
                  <label
                    v-if="isChiefEditorRow(row)"
                    class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600"
                  >
                    <input
                      type="checkbox"
                      class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="readOnly"
                      :checked="isChiefEditorCoauthorChecked(row)"
                      @change="
                        onChiefEditorCoauthorToggle(
                          idx,
                          ($event.target as HTMLInputElement).checked,
                        )
                      "
                    />
                    <span>Chủ biên đồng tác giả</span>
                  </label>
                </td>

                <!-- Giờ -->
                <td
                  class="px-4 py-3 align-top text-right font-medium text-slate-900"
                >
                  <div class="pt-2">
                    <span v-if="row.is_external" class="text-slate-400">—</span>
                    <span v-else>
                      {{
                        formatHours(
                          hoursByLecturerId[row.lecturer_id ?? -1] ?? 0,
                        )
                      }}
                    </span>
                  </div>
                </td>

                <!-- Remove -->
                <td
                  v-if="!readOnly"
                  class="px-4 py-3 text-right align-top md:px-6"
                >
                  <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                    @click="removeRow(idx)"
                    :aria-label="`Xóa dòng ${idx + 1}`"
                    title="Xóa"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </td>
              </tr>

              <tr v-if="modelValue.length === 0">
                <td
                  :colspan="readOnly ? 5 : 6"
                  class="px-6 py-10 text-center text-sm text-slate-500"
                >
                  <div class="flex flex-col items-center gap-2">
                    <div class="text-2xl">👥</div>
                    <div>Chưa có danh sách người tham gia.</div>
                    <button
                      v-if="!readOnly"
                      type="button"
                      class="mt-2 inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
                      @click="addRow"
                    >
                      <Plus class="h-4 w-4" />
                      Thêm người tham gia
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="shouldShowDesktopInlineDropdown"
        class="fixed z-120 max-h-56 overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl"
        :style="desktopDropdownStyle"
        @mousedown="cancelLecturerBlurTimer"
      >
        <button
          type="button"
          class="block w-full px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50"
          @mousedown.prevent="chooseLecturer(activeLecturerRow as number, null)"
        >
          — Chọn giảng viên —
        </button>
        <button
          v-for="lecturer in filteredLecturersForRow(
            activeLecturerRow as number,
          )"
          :key="`desktop-floating-option-${activeLecturerRow as number}-${lecturer.id}`"
          type="button"
          class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
          @mousedown.prevent="
            chooseLecturer(activeLecturerRow as number, lecturer)
          "
        >
          {{ lecturer.full_name }} ({{ lecturer.code }})
        </button>
        <div
          v-if="
            filteredLecturersForRow(activeLecturerRow as number).length === 0
          "
          class="px-3 py-2 text-sm text-slate-500"
        >
          Không tìm thấy giảng viên phù hợp.
        </div>
      </div>
    </Teleport>

    <!-- Mobile Cards (giữ nguyên như bạn đang có) -->
    <div class="md:hidden">
      <div v-if="modelValue.length === 0" class="p-6 text-center">
        <div class="text-2xl">👥</div>
        <div class="mt-2 text-sm font-medium text-slate-900">
          Chưa có danh sách người tham gia
        </div>
        <div class="mt-1 text-xs text-slate-500">
          Thêm người tham gia để phân công vai trò và theo dõi giờ NCKH.
        </div>
        <button
          v-if="!readOnly"
          type="button"
          class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
          @click="addRow"
        >
          <Plus class="h-4 w-4" />
          Thêm người tham gia
        </button>
      </div>

      <div v-else class="divide-y divide-slate-200">
        <article
          v-for="(row, idx) in modelValue"
          :key="`m-${idx}`"
          class="p-4"
          :class="row.lecturer_id === currentLecturerId ? 'bg-slate-50/60' : ''"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="text-xs font-semibold text-slate-600">
                Người tham gia #{{ idx + 1 }}
              </div>

              <!-- Mobile: checkbox vẫn giữ trong card -->
              <label
                class="mt-2 inline-flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1 text-xs text-slate-600 hover:bg-slate-50"
                :class="readOnly ? 'cursor-not-allowed opacity-60' : ''"
              >
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-2 focus:ring-slate-900/20"
                  :disabled="readOnly"
                  :checked="!!row.is_external"
                  @change="
                    toggleExternal(
                      idx,
                      ($event.target as HTMLInputElement).checked,
                    )
                  "
                />
                Giảng viên ngoài
              </label>
            </div>

            <button
              v-if="!readOnly"
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
              @click="removeRow(idx)"
              :aria-label="`Xóa dòng ${idx + 1}`"
              title="Xóa"
            >
              <Trash2 class="h-4 w-4" />
            </button>
          </div>

          <div class="mt-3 grid gap-3">
            <!-- Name -->
            <div>
              <div class="text-xs font-medium text-slate-700">Họ tên</div>
              <div class="mt-1">
                <template v-if="row.is_external">
                  <input
                    type="text"
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                    placeholder="Nhập họ tên..."
                    :disabled="readOnly"
                    :value="row.external_full_name ?? ''"
                    @input="
                      updateRow(idx, {
                        external_full_name: ($event.target as HTMLInputElement)
                          .value,
                      })
                    "
                  />
                </template>
                <template v-else>
                  <div class="relative">
                    <input
                      type="text"
                      class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                      placeholder="Tìm theo tên/mã giảng viên"
                      :disabled="readOnly"
                      :value="lecturerInputValue(idx, row.lecturer_id)"
                      @focus="onLecturerFocus(idx)"
                      @blur="onLecturerBlur(idx)"
                      @input="
                        onLecturerInput(
                          idx,
                          ($event.target as HTMLInputElement).value,
                        )
                      "
                    />

                    <div
                      v-if="activeLecturerRow === idx && !readOnly"
                      class="absolute left-0 right-0 top-full z-50 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg"
                    >
                      <button
                        type="button"
                        class="block w-full px-3 py-2 text-left text-sm text-slate-500 hover:bg-slate-50"
                        @mousedown.prevent="chooseLecturer(idx, null)"
                      >
                        — Chọn giảng viên —
                      </button>
                      <button
                        v-for="lecturer in filteredLecturersForRow(idx)"
                        :key="`mobile-option-${idx}-${lecturer.id}`"
                        type="button"
                        class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                        @mousedown.prevent="chooseLecturer(idx, lecturer)"
                      >
                        {{ lecturer.full_name }} ({{ lecturer.code }})
                      </button>
                      <div
                        v-if="filteredLecturersForRow(idx).length === 0"
                        class="px-3 py-2 text-sm text-slate-500"
                      >
                        Không tìm thấy giảng viên phù hợp.
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </div>

            <!-- Department -->
            <div>
              <div class="text-xs font-medium text-slate-700">Đơn vị</div>
              <div class="mt-1">
                <template v-if="row.is_external">
                  <input
                    type="text"
                    class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                    placeholder="Nhập đơn vị..."
                    :disabled="readOnly"
                    :value="row.external_department_name ?? ''"
                    @input="
                      updateRow(idx, {
                        external_department_name: (
                          $event.target as HTMLInputElement
                        ).value,
                      })
                    "
                  />
                </template>
                <template v-else>
                  <div
                    class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700"
                  >
                    {{ lecturerDepartmentName(row.lecturer_id) || "—" }}
                  </div>
                  <span
                    v-if="isOutsideFaculty(row)"
                    class="mt-1 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800"
                  >
                    Ngoài khoa
                  </span>
                </template>
              </div>
            </div>

            <!-- Role + Hours -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <div class="text-xs font-medium text-slate-700">Vai trò</div>
                <select
                  class="mt-1 h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:opacity-60"
                  :disabled="readOnly"
                  :value="row.member_role_id ?? ''"
                  @change="onMemberRoleChange(idx, $event)"
                >
                  <option value="">— Chọn vai trò —</option>
                  <option
                    v-for="r in memberRoles"
                    :key="r.id"
                    :value="r.id"
                    :disabled="isMemberRoleDisabled(idx, r.id)"
                  >
                    {{ r.name }}
                  </option>
                </select>
                <label
                  v-if="isChiefEditorRow(row)"
                  class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/15 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="readOnly"
                    :checked="isChiefEditorCoauthorChecked(row)"
                    @change="
                      onChiefEditorCoauthorToggle(
                        idx,
                        ($event.target as HTMLInputElement).checked,
                      )
                    "
                  />
                  <span>Chủ biên đồng tác giả</span>
                </label>
              </div>

              <div>
                <div class="text-xs font-medium text-slate-700">Giờ NCKH</div>
                <div
                  class="mt-1 flex h-10 items-center justify-end rounded-xl bg-slate-50 px-3 text-sm font-semibold text-slate-900"
                >
                  <span v-if="row.is_external" class="text-slate-400">—</span>
                  <span v-else>
                    {{
                      formatHours(hoursByLecturerId[row.lecturer_id ?? -1] ?? 0)
                    }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>

    <!-- Desktop side picker -->
    <aside
      v-if="isLecturerPanelOpen && activeLecturerRow !== null && !readOnly"
      class="fixed z-90 hidden w-[460px] rounded-2xl border border-slate-200 bg-white shadow-2xl md:block"
      :style="desktopPanelStyle"
      @mousedown="cancelLecturerBlurTimer"
    >
      <div
        class="flex cursor-move items-center justify-between border-b border-slate-200 px-4 py-3"
        @pointerdown="startPanelDrag"
      >
        <div class="text-sm font-semibold text-slate-900">Chọn giảng viên</div>
        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"
          @mousedown.prevent="closeLecturerPanel"
          @pointerdown.stop
          aria-label="Đóng panel chọn giảng viên"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="border-b border-slate-200 px-4 py-3">
        <input
          type="text"
          class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
          placeholder="Tìm theo họ tên hoặc mã giảng viên"
          :value="activeLecturerQuery"
          @input="
            onActiveLecturerPanelInput(
              ($event.target as HTMLInputElement).value,
            )
          "
          aria-label="Tìm giảng viên ở panel bên phải"
        />
      </div>

      <div class="max-h-[420px] overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="sticky top-0 z-10 bg-slate-50">
            <tr>
              <th
                class="px-3 py-2 text-left text-xs font-semibold text-slate-600"
              >
                Họ tên
              </th>
              <th
                class="px-3 py-2 text-left text-xs font-semibold text-slate-600"
              >
                Mã GV
              </th>
              <th
                class="px-3 py-2 text-left text-xs font-semibold text-slate-600"
              >
                Đơn vị
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="lecturer in activeLecturerOptions"
              :key="`side-option-${lecturer.id}`"
              class="cursor-pointer hover:bg-slate-50"
              :class="
                activeLecturerRowSelectedId === lecturer.id
                  ? 'bg-slate-100'
                  : ''
              "
              @mousedown.prevent="
                chooseLecturer(activeLecturerRow as number, lecturer)
              "
            >
              <td class="px-3 py-2 text-slate-800">{{ lecturer.full_name }}</td>
              <td class="px-3 py-2 text-slate-700">{{ lecturer.code }}</td>
              <td class="px-3 py-2 text-slate-600">
                {{ lecturer.department_name || "—" }}
              </td>
            </tr>

            <tr v-if="activeLecturerOptions.length === 0">
              <td
                colspan="3"
                class="px-3 py-6 text-center text-sm text-slate-500"
              >
                Không tìm thấy giảng viên phù hợp.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </aside>

    <!-- Warnings -->
    <div v-if="warnings.length" class="border-t border-slate-200 p-4 md:p-6">
      <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3">
        <div class="flex items-start gap-2">
          <AlertTriangle class="mt-0.5 h-4 w-4 text-amber-700" />
          <div class="text-xs font-semibold text-amber-900">Cảnh báo</div>
        </div>

        <div class="mt-2 space-y-2">
          <div
            v-for="(w, i) in warnings"
            :key="i"
            class="flex items-start gap-2 text-xs text-amber-800"
          >
            <span
              class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-amber-700"
            ></span>
            <div>{{ w }}</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { AlertTriangle, Plus, Trash2, X } from "lucide-vue-next";
import type {
  LecturerOptionDto,
  MemberRoleDto,
} from "../contracts/declarationSharedContract";

export type ParticipantRowModel = {
  lecturer_id: number | null;
  member_role_id: number | null;

  // UI-only helper (NOT persisted)
  member_role_code?: string | null;

  // UI-only: participant external/outside school
  is_external?: boolean;
  external_full_name?: string | null;
  external_department_name?: string | null;

  // UI-only: với vai trò chief_editor, có tính vào nhóm đồng tác giả (4/5) hay không
  chief_editor_is_coauthor?: boolean | null;
};

const props = defineProps<{
  modelValue: ParticipantRowModel[];
  lecturers: LecturerOptionDto[];
  memberRoles: MemberRoleDto[];
  readOnly: boolean;
  currentLecturerId: number | null;
  hoursByLecturerId: Record<number, number>;
  ownerFacultyId?: number | null;
  showChiefEditorCoauthorToggle?: boolean;
  chiefEditorRoleCode?: string;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: ParticipantRowModel[]): void;
  (e: "request-search", query: string): void;
}>();

const rowSearchQueries = ref<Record<number, string>>({});
const activeLecturerRow = ref<number | null>(null);
const isLecturerPanelOpen = ref(false);
const desktopInputRefs = ref<Record<number, HTMLInputElement | null>>({});
const cachedLecturersById = ref<Record<number, LecturerOptionDto>>({});
const isDesktopViewport = ref(false);
const desktopDropdownStyle = ref<Record<string, string>>({});
const panelPosition = ref({ x: 0, y: 96 });
const panelDragOffset = ref({ x: 0, y: 0 });
const isDraggingPanel = ref(false);
const isPanelPositionInitialized = ref(false);
let lecturerBlurTimer: ReturnType<typeof setTimeout> | null = null;

const activeLecturerQuery = computed(() => {
  if (activeLecturerRow.value === null) return "";
  return rowSearchQueries.value[activeLecturerRow.value] ?? "";
});

const activeLecturerOptions = computed(() => {
  if (activeLecturerRow.value === null) return [];
  return filteredLecturersForRow(activeLecturerRow.value);
});

const activeLecturerRowSelectedId = computed(() => {
  if (activeLecturerRow.value === null) return null;
  return props.modelValue[activeLecturerRow.value]?.lecturer_id ?? null;
});

const shouldShowDesktopInlineDropdown = computed(() => {
  return (
    isDesktopViewport.value &&
    activeLecturerRow.value !== null &&
    !props.readOnly &&
    !isLecturerPanelOpen.value
  );
});

const desktopPanelStyle = computed(() => {
  return {
    left: `${panelPosition.value.x}px`,
    top: `${panelPosition.value.y}px`,
  };
});

const warnings = computed(() => {
  const list: string[] = [];

  // chỉ check trùng cho giảng viên nội bộ (is_external != true)
  const ids = props.modelValue
    .filter((r) => !r.is_external)
    .map((r) => r.lecturer_id)
    .filter((x): x is number => typeof x === "number");

  const dup = ids.filter((id, idx) => ids.indexOf(id) !== idx);
  if (dup.length) {
    list.push(
      "Có giảng viên bị trùng trong danh sách (unique(activity_id, lecturer_id)).",
    );
  }

  if (
    typeof props.currentLecturerId === "number" &&
    props.currentLecturerId > 0
  ) {
    const hasDeclarer = props.modelValue.some(
      (row) => !row.is_external && row.lecturer_id === props.currentLecturerId,
    );
    if (!hasDeclarer) {
      list.push(
        "Lưu ý: Người kê khai chưa có trong danh sách thành viên. Vui lòng thêm mình vào danh sách thành viên.",
      );
    }
  }

  return list;
});

const principalRoleId = computed(() => {
  return (
    props.memberRoles.find((role) => role.code?.toLowerCase() === "principal")
      ?.id ?? null
  );
});

const chiefEditorRoleId = computed(() => {
  const roleCode = (props.chiefEditorRoleCode ?? "chief_editor").toLowerCase();
  return (
    props.memberRoles.find((role) => role.code?.toLowerCase() === roleCode)
      ?.id ?? null
  );
});

watch(
  () => props.lecturers,
  (nextLecturers) => {
    const nextCache = { ...cachedLecturersById.value };
    for (const lecturer of nextLecturers) {
      nextCache[lecturer.id] = lecturer;
    }
    cachedLecturersById.value = nextCache;
  },
  { immediate: true },
);

function addRow() {
  const next = [
    ...props.modelValue,
    {
      lecturer_id: null,
      member_role_id: null,
      member_role_code: null,

      is_external: false,
      external_full_name: null,
      external_department_name: null,
    },
  ];
  emit("update:modelValue", next);
}

function removeRow(idx: number) {
  const next = props.modelValue.filter((_, i) => i !== idx);
  const nextQueries: Record<number, string> = {};
  for (const [key, value] of Object.entries(rowSearchQueries.value)) {
    const keyNumber = Number(key);
    if (!Number.isFinite(keyNumber) || keyNumber === idx) continue;
    nextQueries[keyNumber > idx ? keyNumber - 1 : keyNumber] = value;
  }
  rowSearchQueries.value = nextQueries;
  emit("update:modelValue", next);
}

function updateRow(idx: number, patch: Partial<ParticipantRowModel>) {
  const next = props.modelValue.map((r, i) =>
    i === idx ? { ...r, ...patch } : r,
  );
  emit("update:modelValue", next);
}

function lecturerInputValue(
  idx: number,
  lecturerId: number | null | undefined,
): string {
  if (Object.prototype.hasOwnProperty.call(rowSearchQueries.value, idx)) {
    return rowSearchQueries.value[idx] ?? "";
  }

  if (typeof lecturerId === "number") {
    const selected =
      props.lecturers.find((l) => l.id === lecturerId) ??
      cachedLecturersById.value[lecturerId];
    if (selected) return `${selected.full_name} (${selected.code})`;
  }

  return "";
}

function onLecturerFocus(idx: number) {
  if (lecturerBlurTimer) {
    clearTimeout(lecturerBlurTimer);
    lecturerBlurTimer = null;
  }

  // Reset query when reopening the picker so the dropdown shows full options.
  rowSearchQueries.value[idx] = "";
  emit("request-search", "");

  isLecturerPanelOpen.value = false;
  activeLecturerRow.value = idx;
  updateDesktopDropdownPosition(idx);
}

function onLecturerBlur(idx: number) {
  lecturerBlurTimer = setTimeout(() => {
    if (activeLecturerRow.value === idx && !isLecturerPanelOpen.value) {
      activeLecturerRow.value = null;
    }
  }, 120);
}

function openLecturerPanel(idx: number) {
  cancelLecturerBlurTimer();
  activeLecturerRow.value = idx;
  ensurePanelPosition();
  isLecturerPanelOpen.value = true;
}

function setDesktopInputRef(idx: number, el: HTMLInputElement | null) {
  desktopInputRefs.value[idx] = el;
}

function updateDesktopViewportFlag() {
  if (typeof window === "undefined") return;
  isDesktopViewport.value = window.innerWidth >= 768;
}

function ensurePanelPosition() {
  if (typeof window === "undefined") return;
  if (isPanelPositionInitialized.value) return;

  panelPosition.value = {
    x: Math.max(16, window.innerWidth - 460 - 16),
    y: 96,
  };
  isPanelPositionInitialized.value = true;
}

function startPanelDrag(event: PointerEvent) {
  if (typeof window === "undefined") return;
  ensurePanelPosition();

  isDraggingPanel.value = true;
  panelDragOffset.value = {
    x: event.clientX - panelPosition.value.x,
    y: event.clientY - panelPosition.value.y,
  };

  window.addEventListener("pointermove", onPanelDrag);
  window.addEventListener("pointerup", stopPanelDrag);
}

function onPanelDrag(event: PointerEvent) {
  if (!isDraggingPanel.value || typeof window === "undefined") return;

  const nextX = event.clientX - panelDragOffset.value.x;
  const nextY = event.clientY - panelDragOffset.value.y;
  const maxX = Math.max(16, window.innerWidth - 460 - 16);
  const maxY = Math.max(16, window.innerHeight - 120);

  panelPosition.value = {
    x: Math.min(Math.max(16, nextX), maxX),
    y: Math.min(Math.max(16, nextY), maxY),
  };
}

function stopPanelDrag() {
  if (typeof window === "undefined") return;

  isDraggingPanel.value = false;
  window.removeEventListener("pointermove", onPanelDrag);
  window.removeEventListener("pointerup", stopPanelDrag);
}

function updateDesktopDropdownPosition(idx: number | null) {
  if (idx === null) return;
  const inputEl = desktopInputRefs.value[idx];
  if (!inputEl) return;

  const rect = inputEl.getBoundingClientRect();
  desktopDropdownStyle.value = {
    left: `${rect.left}px`,
    top: `${rect.bottom + 4}px`,
    width: `${rect.width}px`,
  };
}

function cancelLecturerBlurTimer() {
  if (!lecturerBlurTimer) return;
  clearTimeout(lecturerBlurTimer);
  lecturerBlurTimer = null;
}

function closeLecturerPanel() {
  cancelLecturerBlurTimer();
  isLecturerPanelOpen.value = false;
  activeLecturerRow.value = null;
}

function onActiveLecturerPanelInput(value: string) {
  if (activeLecturerRow.value === null) return;
  onLecturerInput(activeLecturerRow.value, value);
}

function isMemberRoleDisabled(idx: number, roleId: number): boolean {
  if (principalRoleId.value === null) return false;
  if (roleId !== principalRoleId.value) return false;

  const currentRoleId = props.modelValue[idx]?.member_role_id ?? null;
  if (currentRoleId === principalRoleId.value) return false;

  return props.modelValue.some(
    (row, rowIdx) =>
      rowIdx !== idx && row.member_role_id === principalRoleId.value,
  );
}

function onMemberRoleChange(idx: number, e: Event) {
  const nextRoleId = toNumber(e);

  if (
    typeof nextRoleId === "number" &&
    principalRoleId.value !== null &&
    nextRoleId === principalRoleId.value &&
    isMemberRoleDisabled(idx, nextRoleId)
  ) {
    return;
  }

  const shouldHandleChiefCoauthor =
    props.showChiefEditorCoauthorToggle === true;
  const isChiefEditorRole =
    shouldHandleChiefCoauthor &&
    chiefEditorRoleId.value !== null &&
    nextRoleId === chiefEditorRoleId.value;

  updateRow(idx, {
    member_role_id: nextRoleId,
    chief_editor_is_coauthor: shouldHandleChiefCoauthor
      ? isChiefEditorRole
        ? (props.modelValue[idx]?.chief_editor_is_coauthor ?? true)
        : null
      : (props.modelValue[idx]?.chief_editor_is_coauthor ?? null),
  });
}

function isChiefEditorRow(row: ParticipantRowModel): boolean {
  return (
    props.showChiefEditorCoauthorToggle === true &&
    chiefEditorRoleId.value !== null &&
    row.member_role_id === chiefEditorRoleId.value
  );
}

function isChiefEditorCoauthorChecked(row: ParticipantRowModel): boolean {
  return row.chief_editor_is_coauthor !== false;
}

function onChiefEditorCoauthorToggle(idx: number, checked: boolean) {
  updateRow(idx, { chief_editor_is_coauthor: checked });
}

function filteredLecturersForRow(idx: number): LecturerOptionDto[] {
  const keyword = (rowSearchQueries.value[idx] ?? "").trim().toLowerCase();
  if (!keyword) return props.lecturers;

  return props.lecturers.filter((lecturer) => {
    const haystack = `${lecturer.full_name} ${lecturer.code}`.toLowerCase();
    return haystack.includes(keyword);
  });
}

function chooseLecturer(idx: number, lecturer: LecturerOptionDto | null) {
  if (!lecturer) {
    rowSearchQueries.value[idx] = "";
    updateRow(idx, { lecturer_id: null });
    activeLecturerRow.value = null;
    return;
  }

  rowSearchQueries.value[idx] = `${lecturer.full_name} (${lecturer.code})`;
  updateRow(idx, { lecturer_id: lecturer.id });
  isLecturerPanelOpen.value = false;
  activeLecturerRow.value = null;
}

function handleFloatingDropdownReposition() {
  if (!shouldShowDesktopInlineDropdown.value) return;
  updateDesktopDropdownPosition(activeLecturerRow.value);
}

onMounted(() => {
  updateDesktopViewportFlag();
  ensurePanelPosition();
  if (typeof window !== "undefined") {
    window.addEventListener("resize", updateDesktopViewportFlag);
    window.addEventListener("resize", handleFloatingDropdownReposition);
    window.addEventListener("scroll", handleFloatingDropdownReposition, true);
  }
});

onBeforeUnmount(() => {
  if (lecturerBlurTimer) {
    clearTimeout(lecturerBlurTimer);
    lecturerBlurTimer = null;
  }

  if (typeof window !== "undefined") {
    stopPanelDrag();
    window.removeEventListener("resize", updateDesktopViewportFlag);
    window.removeEventListener("resize", handleFloatingDropdownReposition);
    window.removeEventListener(
      "scroll",
      handleFloatingDropdownReposition,
      true,
    );
  }
});

function onLecturerInput(idx: number, value: string) {
  rowSearchQueries.value[idx] = value;
  activeLecturerRow.value = idx;
  emit("request-search", value);
  updateDesktopDropdownPosition(idx);
}

function toggleExternal(idx: number, checked: boolean) {
  if (checked) {
    // chuyển sang GV ngoài: bỏ lecturer_id
    updateRow(idx, {
      is_external: true,
      lecturer_id: null,
      external_full_name: props.modelValue[idx]?.external_full_name ?? "",
      external_department_name:
        props.modelValue[idx]?.external_department_name ?? "",
    });
    rowSearchQueries.value[idx] = "";
  } else {
    // chuyển về GV nội bộ: clear các field external
    updateRow(idx, {
      is_external: false,
      external_full_name: null,
      external_department_name: null,
    });
  }
}

function toNumber(e: Event): number | null {
  const v = (e.target as HTMLSelectElement).value;
  if (!v) return null;
  const n = Number(v);
  return Number.isFinite(n) ? n : null;
}

function lecturerDepartmentName(lecturer_id: number | null) {
  if (!lecturer_id) return null;
  return (
    props.lecturers.find((l) => l.id === lecturer_id)?.department_name ??
    cachedLecturersById.value[lecturer_id]?.department_name ??
    null
  );
}

function lecturerFacultyId(lecturer_id: number | null) {
  if (!lecturer_id) return null;
  return (
    props.lecturers.find((l) => l.id === lecturer_id)?.faculty_id ??
    cachedLecturersById.value[lecturer_id]?.faculty_id ??
    null
  );
}

function isOutsideFaculty(row: ParticipantRowModel): boolean {
  if (row.is_external) return false;
  if (!props.ownerFacultyId) return false;
  const memberFacultyId = lecturerFacultyId(row.lecturer_id);
  if (!memberFacultyId) return false;
  return Number(memberFacultyId) !== Number(props.ownerFacultyId);
}

function formatHours(v: number) {
  const n = Math.round(v * 100) / 100;
  return `${n.toFixed(2)} giờ`;
}
</script>
