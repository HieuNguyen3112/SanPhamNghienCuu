<template>
  <div class="space-y-4">
    <header>
      <h1 class="text-xl font-semibold text-slate-900">Thông tin liên hệ</h1>
      <p class="mt-1 text-sm text-slate-500">
        Thông tin cá nhân, học hàm và kênh liên hệ của giảng viên.
      </p>
    </header>

    <section class="rounded-md bg-white p-6 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-semibold text-slate-800">
          Hồ sơ cá nhân - Thông tin liên hệ
        </h2>

        <div class="flex items-center gap-2">
          <button
            v-if="isEditing"
            type="button"
            class="rounded bg-slate-100 px-4 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-200"
            @click="cancelEdit"
          >
            Hủy
          </button>
          <button
            type="button"
            class="rounded bg-[#234a74] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#1b3a5a]"
            :disabled="saving"
            @click="onClickPrimary"
          >
            {{ isEditing ? "Lưu thay đổi" : "Chỉnh sửa" }}
          </button>
        </div>
      </div>

      <div v-if="!isEditing" class="grid gap-4 md:grid-cols-2">
        <ProfileFieldDisplay label="Họ và tên" :value="contact.fullName" />
        <ProfileFieldDisplay label="Giới tính" :value="contact.gender" />
        <ProfileFieldDisplay label="Ngày sinh" :value="contact.birthDate" />
        <ProfileFieldDisplay label="Nơi sinh" :value="contact.birthPlace" />
        <ProfileFieldDisplay label="Dân tộc" :value="contact.ethnicity" />
        <ProfileFieldDisplay label="Quê quán" :value="contact.hometown" />
        <ProfileFieldDisplay label="Chức vụ" :value="contact.position" />
        <ProfileFieldDisplay
          label="Đơn vị công tác"
          :value="contact.department"
        />
        <ProfileFieldDisplay
          label="Địa chỉ liên hệ"
          :value="contact.address"
          custom-class="md:col-span-2"
        />
        <ProfileFieldDisplay label="Học vị" :value="contact.degree" />
        <ProfileFieldDisplay label="Chức danh" :value="contact.academicTitle" />
        <ProfileFieldDisplay
          label="Chuyên ngành giảng dạy"
          :value="contact.teachingSpecialty"
          custom-class="md:col-span-2"
        />
        <ProfileFieldDisplay
          label="Lĩnh vực nghiên cứu"
          :value="contact.researchAreas"
          custom-class="md:col-span-2"
        />
        <ProfileFieldDisplay
          label="Ngoại ngữ"
          :value="contact.languages"
          custom-class="md:col-span-2"
        />
        <ProfileFieldDisplay label="Email" :value="contact.email" />
        <ProfileFieldDisplay label="Số điện thoại" :value="contact.phone" />
      </div>

      <form v-else class="grid gap-4 md:grid-cols-2" @submit.prevent="onSave">
        <ProfileFieldInput
          v-model="editContact.fullName"
          label="Họ và tên"
          required
        />

        <div>
          <label
            class="block text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Giới tính
          </label>
          <select
            v-model="editContact.gender"
            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          >
            <option value="">-- Chọn giới tính --</option>
            <option value="Nam">Nam</option>
            <option value="Nữ">Nữ</option>
            <option value="Khác">Khác</option>
          </select>
        </div>

        <ProfileFieldInput
          v-model="editContact.birthDate"
          label="Ngày sinh"
          type="date"
        />
        <ProfileFieldInput v-model="editContact.birthPlace" label="Nơi sinh" />

        <ProfileFieldInput v-model="editContact.ethnicity" label="Dân tộc" />
        <ProfileFieldInput v-model="editContact.hometown" label="Quê quán" />

        <ProfileFieldInput v-model="editContact.position" label="Chức vụ" />
        <ProfileFieldInput
          v-model="editContact.department"
          label="Đơn vị công tác"
        />

        <ProfileFieldInput
          v-model="editContact.address"
          label="Địa chỉ liên hệ"
          custom-class="md:col-span-2"
        />

        <ProfileFieldInput v-model="editContact.degree" label="Học vị" />
        <ProfileFieldInput
          v-model="editContact.academicTitle"
          label="Chức danh"
        />

        <ProfileFieldTextarea
          v-model="editContact.teachingSpecialty"
          label="Chuyên ngành giảng dạy"
          custom-class="md:col-span-2"
        />
        <ProfileFieldTextarea
          v-model="editContact.researchAreas"
          label="Lĩnh vực nghiên cứu"
          custom-class="md:col-span-2"
        />
        <ProfileFieldInput
          v-model="editContact.languages"
          label="Ngoại ngữ"
          custom-class="md:col-span-2"
          placeholder="VD: Tiếng Anh (IELTS 6.5), Tiếng Pháp B1..."
        />

        <ProfileFieldInput
          v-model="editContact.email"
          label="Email"
          type="email"
        />
        <ProfileFieldInput v-model="editContact.phone" label="Số điện thoại" />
      </form>
    </section>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from "vue";
import ProfileFieldDisplay from "@/features/profile/components/ProfileFieldDisplay.vue";
import ProfileFieldInput from "@/features/profile/components/ProfileFieldInput.vue";
import ProfileFieldTextarea from "@/features/profile/components/ProfileFieldTextarea.vue";
import { fetchProfile, updateContact } from "@/features/profile/api";

interface ContactInfo {
  fullName: string;
  gender: string;
  birthDate: string;
  birthPlace: string;
  ethnicity: string;
  hometown: string;
  position: string;
  department: string;
  address: string;
  degree: string;
  academicTitle: string;
  teachingSpecialty: string;
  researchAreas: string;
  languages: string;
  email: string;
  phone: string;
}

const contact = reactive<ContactInfo>({
  fullName: "",
  gender: "",
  birthDate: "",
  birthPlace: "",
  ethnicity: "",
  hometown: "",
  position: "",
  department: "",
  address: "",
  degree: "",
  academicTitle: "",
  teachingSpecialty: "",
  researchAreas: "",
  languages: "",
  email: "",
  phone: "",
});

const editContact = reactive<ContactInfo>({ ...contact });
const isEditing = ref(false);
const saving = ref(false);

function onClickPrimary() {
  if (!isEditing.value) {
    Object.assign(editContact, contact);
    isEditing.value = true;
  } else {
    onSave();
  }
}

async function onSave() {
  saving.value = true;
  try {
    await updateContact({
      full_name: editContact.fullName,
      phone: editContact.phone,
      department_id: null,
      degree_id: null,
      academic_rank_id: null,
      gender: editContact.gender,
      date_of_birth: editContact.birthDate,
      place_of_birth: editContact.birthPlace,
      ethnicity: editContact.ethnicity,
      hometown: editContact.hometown,
      address: editContact.address,
      personal_email: editContact.email,
      alternate_phone: "",
      current_position: editContact.position,
      current_unit: editContact.department,
      teaching_specialization: editContact.teachingSpecialty,
      research_area: editContact.researchAreas,
    });
    Object.assign(contact, editContact);
    isEditing.value = false;
  } catch (e) {
    // TODO: thêm thông báo lỗi
  } finally {
    saving.value = false;
  }
}

function cancelEdit() {
  Object.assign(editContact, contact);
  isEditing.value = false;
}

onMounted(async () => {
  try {
    const { data } = await fetchProfile();
    const payload = data?.data ?? {};
    const lec = payload.lecturer ?? {};
    const prof = payload.profile ?? {};
    contact.fullName = lec.full_name || payload.user?.name || "";
    contact.gender = prof.gender || "";
    contact.birthDate = prof.date_of_birth || "";
    contact.birthPlace = prof.place_of_birth || "";
    contact.ethnicity = prof.ethnicity || "";
    contact.hometown = prof.hometown || "";
    contact.position = prof.current_position || "";
    contact.department = prof.current_unit || "";
    contact.address = prof.address || "";
    contact.degree = payload.lecturer?.degree_name || "";
    contact.academicTitle = payload.lecturer?.academic_rank_name || "";
    contact.teachingSpecialty = prof.teaching_specialization || "";
    contact.researchAreas = prof.research_area || "";
    contact.languages = ""; // có thể ghép từ danh sách languages nếu cần
    contact.email = lec.email || payload.user?.email || "";
    contact.phone = lec.phone || "";
    Object.assign(editContact, contact);
  } catch (e) {
    // nếu lỗi, giữ trống
  }
});
</script>
