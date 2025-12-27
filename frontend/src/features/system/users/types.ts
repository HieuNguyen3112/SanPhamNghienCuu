export interface LecturerUser {
  id: number;
  fullName: string;
  email: string;
  username: string;
  department: string;
  staffCode?: string;
  roles: string[];
  isActive: boolean;
  createdAt: string; // ISO datetime
}

export interface CreateLecturerUserPayload {
  fullName: string;
  email: string;
  username: string;
  department: string;
  password: string;
  roles: string[];
}

export interface LecturerUserListResponse {
  data: LecturerUser[];
  total: number;
}

export interface UpdateLecturerUserPayload {
  id: number;
  fullName: string;
  email: string;
  username: string;
  department: string;
  roles: string[];
  isActive: boolean;
}

export interface UpdateLecturerUserRolesPayload {
  id: number;
  roles: string[];
}
export type OrgUnitType = "CAMPUS" | "FACULTY" | "DEPARTMENT" | "CENTER";

export interface OrganizationUnit {
  id: number;
  name: string;
  code: string;
  type: OrgUnitType;
  campus: string; // VD: "TP.HCM", "Long An"
  parentId: number | null;
  parentName?: string; // server hoặc API có thể trả sẵn
  order: number; // thứ tự hiển thị
  isActive: boolean;

  // mới thêm
  lecturerCount: number; // số lượng giảng viên thuộc đơn vị (tổng)
  managerName?: string; // tên người quản lý (Hiệu trưởng, Trưởng khoa, ...)
  managerTitle?: string; // chức danh: "Hiệu trưởng", "Trưởng khoa", ...
}

export interface CreateOrganizationUnitPayload {
  name: string;
  code: string;
  type: OrgUnitType;
  campus: string;
  parentId: number | null;
  order: number;
  isActive: boolean;

  lecturerCount?: number;
  managerName?: string;
  managerTitle?: string;
}

export interface UpdateOrganizationUnitPayload
  extends CreateOrganizationUnitPayload {
  id: number;
}
