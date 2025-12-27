import type {
  LecturerUser,
  LecturerUserListResponse,
  CreateLecturerUserPayload,
  UpdateLecturerUserPayload,
  UpdateLecturerUserRolesPayload,
} from "@/features/system/users/types";

let mockIdCounter = 3;

let mockUsers: LecturerUser[] = [
  {
    id: 1,
    fullName: "Nguyễn Văn A",
    email: "a.nguyen@university.edu.vn",
    username: "nguyenvana",
    department: "Khoa Công nghệ thông tin",
    staffCode: "GV001",
    roles: ["Giảng viên"],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 2,
    fullName: "Trần Thị B",
    email: "b.tran@university.edu.vn",
    username: "tranthib",
    department: "Khoa Toán",
    staffCode: "GV002",
    roles: ["Giảng viên", "Ban chủ nhiệm "],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 1,
    fullName: "Nguyễn Văn A",
    email: "a.nguyen@university.edu.vn",
    username: "nguyenvana",
    department: "Khoa Công nghệ thông tin",
    staffCode: "GV001",
    roles: ["Giảng viên"],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 2,
    fullName: "Trần Thị B",
    email: "b.tran@university.edu.vn",
    username: "tranthib",
    department: "Khoa Toán",
    staffCode: "GV002",
    roles: ["Giảng viên", "Ban chủ nhiệm "],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 1,
    fullName: "Nguyễn Văn A",
    email: "a.nguyen@university.edu.vn",
    username: "nguyenvana",
    department: "Khoa Công nghệ thông tin",
    staffCode: "GV001",
    roles: ["Giảng viên"],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 2,
    fullName: "Trần Thị B",
    email: "b.tran@university.edu.vn",
    username: "tranthib",
    department: "Khoa Toán",
    staffCode: "GV002",
    roles: ["Giảng viên", "Ban chủ nhiệm "],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 1,
    fullName: "Nguyễn Văn A",
    email: "a.nguyen@university.edu.vn",
    username: "nguyenvana",
    department: "Khoa Công nghệ thông tin",
    staffCode: "GV001",
    roles: ["Giảng viên"],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 2,
    fullName: "Trần Thị B",
    email: "b.tran@university.edu.vn",
    username: "tranthib",
    department: "Khoa Toán",
    staffCode: "GV002",
    roles: ["Giảng viên", "Ban chủ nhiệm "],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 1,
    fullName: "Nguyễn Văn A",
    email: "a.nguyen@university.edu.vn",
    username: "nguyenvana",
    department: "Khoa Công nghệ thông tin",
    staffCode: "GV001",
    roles: ["Giảng viên"],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 2,
    fullName: "Trần Thị B",
    email: "b.tran@university.edu.vn",
    username: "tranthib",
    department: "Khoa Toán",
    staffCode: "GV002",
    roles: ["Giảng viên", "Ban chủ nhiệm "],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 1,
    fullName: "Nguyễn Văn A",
    email: "a.nguyen@university.edu.vn",
    username: "nguyenvana",
    department: "Khoa Công nghệ thông tin",
    staffCode: "GV001",
    roles: ["Giảng viên"],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 2,
    fullName: "Trần Thị B",
    email: "b.tran@university.edu.vn",
    username: "tranthib",
    department: "Khoa Toán",
    staffCode: "GV002",
    roles: ["Giảng viên", "Ban chủ nhiệm "],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 1,
    fullName: "Nguyễn Văn A",
    email: "a.nguyen@university.edu.vn",
    username: "nguyenvana",
    department: "Khoa Công nghệ thông tin",
    staffCode: "GV001",
    roles: ["Giảng viên"],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
  {
    id: 2,
    fullName: "Trần Thị B",
    email: "b.tran@university.edu.vn",
    username: "tranthib",
    department: "Khoa Toán",
    staffCode: "GV002",
    roles: ["Giảng viên", "Ban chủ nhiệm "],
    isActive: true,
    createdAt: new Date().toISOString(),
  },
];

export const fetchLecturerUsers =
  async (): Promise<LecturerUserListResponse> => {
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve({
          data: [...mockUsers],
          total: mockUsers.length,
        });
      }, 300);
    });
  };

export const createLecturerUser = async (
  payload: CreateLecturerUserPayload
): Promise<LecturerUser> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      const newUser: LecturerUser = {
        id: ++mockIdCounter,
        fullName: payload.fullName,
        email: payload.email,
        username: payload.username,
        department: payload.department,
        staffCode: `GV${String(mockIdCounter).padStart(3, "0")}`,
        roles: payload.roles.length ? payload.roles : ["Giảng viên"],
        isActive: true,
        createdAt: new Date().toISOString(),
      };
      mockUsers.push(newUser);
      resolve(newUser);
    }, 400);
  });
};

export const deleteLecturerUser = async (id: number): Promise<void> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      mockUsers = mockUsers.filter((u) => u.id !== id);
      resolve();
    }, 200);
  });
};

export const toggleLecturerUserActive = async (id: number): Promise<void> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      mockUsers = mockUsers.map((u) =>
        u.id === id ? { ...u, isActive: !u.isActive } : u
      );
      resolve();
    }, 200);
  });
};

export const updateLecturerUser = async (
  payload: UpdateLecturerUserPayload
): Promise<LecturerUser> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      mockUsers = mockUsers.map((u) =>
        u.id === payload.id
          ? {
              ...u,
              fullName: payload.fullName,
              email: payload.email,
              username: payload.username,
              department: payload.department,
              roles: payload.roles,
              isActive: payload.isActive,
            }
          : u
      );
      const updated = mockUsers.find((u) => u.id === payload.id)!;
      resolve(updated);
    }, 300);
  });
};

export const updateLecturerUserRoles = async (
  payload: UpdateLecturerUserRolesPayload
): Promise<LecturerUser> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      mockUsers = mockUsers.map((u) =>
        u.id === payload.id ? { ...u, roles: payload.roles } : u
      );
      const updated = mockUsers.find((u) => u.id === payload.id)!;
      resolve(updated);
    }, 250);
  });
};
import type {
  OrganizationUnit,
  CreateOrganizationUnitPayload,
  UpdateOrganizationUnitPayload,
} from "@/features/system/users/types";

let mockOrgId = 5;

let mockOrgUnits: OrganizationUnit[] = [
  {
    id: 1,
    name: "Cơ sở TP.HCM",
    code: "CS-HCM",
    type: "CAMPUS",
    campus: "TP.HCM",
    parentId: null,
    parentName: undefined,
    order: 1,
    isActive: true,
    lecturerCount: 120,
    managerName: "Nguyễn Văn Hiệu",
    managerTitle: "Hiệu trưởng",
  },
  {
    id: 2,
    name: "Cơ sở Long An",
    code: "CS-LA",
    type: "CAMPUS",
    campus: "Long An",
    parentId: null,
    parentName: undefined,
    order: 2,
    isActive: true,
    lecturerCount: 45,
    managerName: "Trần Thị Cơ",
    managerTitle: "Giám đốc cơ sở",
  },
  {
    id: 3,
    name: "Khoa Công nghệ Thông tin",
    code: "K-CNTT",
    type: "FACULTY",
    campus: "TP.HCM",
    parentId: 1,
    parentName: "Cơ sở TP.HCM",
    order: 1,
    isActive: true,
    lecturerCount: 35,
    managerName: "Lê Văn Khoa",
    managerTitle: "Trưởng khoa",
  },

  // ...
];

const recomputeParentNames = () => {
  const map = new Map<number, OrganizationUnit>();
  mockOrgUnits.forEach((u) => map.set(u.id, u));

  mockOrgUnits = mockOrgUnits.map((u) => ({
    ...u,
    parentName: u.parentId ? map.get(u.parentId)?.name : undefined,
  }));
};

export const fetchOrganizationUnits = async (): Promise<OrganizationUnit[]> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      recomputeParentNames();
      resolve([...mockOrgUnits]);
    }, 300);
  });
};

export const createOrganizationUnit = async (
  payload: CreateOrganizationUnitPayload
): Promise<OrganizationUnit> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      const newUnit: OrganizationUnit = {
        id: ++mockOrgId,
        name: payload.name,
        code: payload.code,
        type: payload.type,
        campus: payload.campus,
        parentId: payload.parentId,
        parentName: undefined, // sẽ được cập nhật bởi recomputeParentNames
        order: payload.order || 1,
        isActive: payload.isActive,
        lecturerCount: 0,
      };
      mockOrgUnits.push(newUnit);
      recomputeParentNames();
      resolve(newUnit);
    }, 300);
  });
};

export const updateOrganizationUnit = async (
  payload: UpdateOrganizationUnitPayload
): Promise<OrganizationUnit> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      mockOrgUnits = mockOrgUnits.map((u) =>
        u.id === payload.id
          ? {
              ...u,
              name: payload.name,
              code: payload.code,
              type: payload.type,
              campus: payload.campus,
              parentId: payload.parentId,
              order: payload.order,
              isActive: payload.isActive,
            }
          : u
      );
      recomputeParentNames();
      const updated = mockOrgUnits.find((u) => u.id === payload.id)!;
      resolve(updated);
    }, 300);
  });
};

export const deleteOrganizationUnit = async (id: number): Promise<void> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      // Demo: xóa thẳng, thực tế nên check có đơn vị con không
      mockOrgUnits = mockOrgUnits.filter((u) => u.id !== id);
      recomputeParentNames();
      resolve();
    }, 250);
  });
};

export const toggleOrganizationUnitActive = async (
  id: number
): Promise<void> => {
  return new Promise((resolve) => {
    setTimeout(() => {
      mockOrgUnits = mockOrgUnits.map((u) =>
        u.id === id ? { ...u, isActive: !u.isActive } : u
      );
      resolve();
    }, 200);
  });
};
