# /me error codes

- 401 UNAUTHENTICATED: `{ code: "UNAUTHENTICATED", message: "Unauthenticated" }` (khong redirect/HTML).
- 403 FORBIDDEN_MISSING_ROLE: `{ code: "FORBIDDEN_MISSING_ROLE", message: "Forbidden: missing role or permission" }` (Spatie role/permission).
- 403 UNVERIFIED_EMAIL: `{ code: "UNVERIFIED_EMAIL", message: "Email chua duoc xac minh" }` (middleware verified).
- 403 FORBIDDEN (khac, neu co) cho `/me`: `{ code: "FORBIDDEN", message: "Forbidden" }`.

Ap dung cho `/me` (web) do middleware `force.json` + Handler tra JSON thay vi redirect.
