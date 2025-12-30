import { computed, ref } from "vue";

export type AuthUser = {
  id: number;
  fullName: string;
  avatarUrl?: string | null;
};

const STORAGE_KEY = "spnc_mock_auth_user";

const currentUserRef = ref<AuthUser | null>(null);

function readUserFromStorage(): AuthUser | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    return JSON.parse(raw) as AuthUser;
  } catch {
    return null;
  }
}

function writeUserToStorage(user: AuthUser | null) {
  try {
    if (!user) {
      localStorage.removeItem(STORAGE_KEY);
      return;
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
  } catch {
    // ignore storage errors
  }
}

// Initialize once (idempotent)
if (typeof window !== "undefined" && currentUserRef.value === null) {
  currentUserRef.value = readUserFromStorage();
}

export function useAuth() {
  const isAuthenticated = computed(() => currentUserRef.value !== null);
  const currentUser = computed(() => currentUserRef.value);

  // Optional helpers (not used by public home content; only for mock/testing)
  const setMockUser = (user: AuthUser | null) => {
    currentUserRef.value = user;
    writeUserToStorage(user);
  };

  return {
    // State (required)
    isAuthenticated,
    currentUser,

    // Optional actions
    setMockUser,
  };
}
