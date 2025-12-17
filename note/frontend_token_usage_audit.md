# Frontend token usage audit

- Tong quan: SPA dung axios client `src/lib/http.ts` voi `withCredentials: true`, `xsrfCookieName/header` va interceptor doc cookie `XSRF-TOKEN` de gan `X-XSRF-TOKEN`. Khong co cau hinh `Authorization` hay Bearer.
- Login/logout/me: `src/features/auth/api.ts` goi `ensureCsrfCookie()` roi POST `/login`, POST `/logout`, GET `/me` dung session cookie; khong doc/luu token tra ve.
- Luu tru token: tim kiem `Authorization`/`Bearer`/`localStorage`/`sessionStorage` khong thay; Pinia `userStore` chi giu thong tin user/role, khong co token.
- Endpoint khac: `src/features/profile/api.ts` va cac api khac deu dung `http` chung, PUT/POST goi `ensureCsrfCookie`; dua vao cookie + CSRF, khong dinh kem token.
- Ket luan: frontend hien dua hoan toan vao cookie/session Sanctum + CSRF; neu backend tra token tu `/login` thi SPA khong su dung, khong luu va khong dinh kem vao header cho cac request.
