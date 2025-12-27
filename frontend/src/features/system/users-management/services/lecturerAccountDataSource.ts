import type {
  LecturerAccountDTO,
  LecturerAccountFilterState,
  RoleKey,
  AccountStatus,
} from "../contracts/lecturerAccountManagement.contract";

export interface LecturerAccountDataSource {
  getAccounts(
    filter: LecturerAccountFilterState
  ): Promise<LecturerAccountDTO[]>;
  updateBasicInfo(payload: {
    id: number;
    full_name: string;
    email: string;
    unit_name: string;
    faculty_name: string | null;
    position_title: string | null;
  }): Promise<void>;
  assignRoles(payload: { id: number; role_keys: RoleKey[] }): Promise<void>;
  setStatus(payload: { id: number; status: AccountStatus }): Promise<void>;
}
