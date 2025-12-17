# Ghi chep luong xac thuc

## Guard, middleware, config chinh
- Guard mac dinh `web` (session, provider users), guard `sanctum` dung PAT; thoi gian het han PAT `sanctum.expiration` = 120 phut.
- Sanctum stateful guard kiem `guard` = [`web`]; cookie stateful chap nhan domain cau hinh `SANCTUM_STATEFUL_DOMAINS`.
- Middleware di kem: `auth`, `verified`, `throttle:*`, `signed`, `auth:sanctum`, `auto.rotate.sanctum` (tu xoay PAT khi sap het han), `role:ADMIN` cho route demo.
- AutoRotateSanctumToken: neu PAT con lai <= `token.sanctum_refresh_threshold` (mac dinh 5 phut) thi tao token moi, tra header `X-Token-Renewed`, `X-New-Token`, `X-Token-Minutes-Left`; co the revoke token cu khi `token.revoke_old_on_rotate` = true.

## Endpoints session/cookie (routes/web.php, middleware `web` + CSRF)
- `POST /login` (throttle:login): dang nhap bang email/password, kiem tra verified; regenerate session. Khong phat hanh PAT cho SPA; phan hoi giu field `token: null` de tuong thich. SPA dung cookie session + CSRF.
- `POST /logout` (middleware `auth`): dang xuat, huy session, revoke PAT gan voi session (`session_token_id`) va/hoac PAT Bearer dang goi.
- `POST /register` (throttle:login): tao user, gan role "GV" neu co Spatie, phat su kien Registered de goi email verify.
- `POST /password/forgot` (throttle 6/1): goi mail reset password.
- `POST /password/reset` (throttle 6/1): doi mat khau bang token reset, cap nhat remember_token.
- `GET /csrf-token`: tra CSRF token JSON.
- `GET /email/verify/{id}/{hash}` (auth + signed): xac thuc email.
- `POST /email/verification-notification` (auth + throttle 6/1): goi lai email verify.
- Nhom `auth` + `verified`: `GET /me`, `PUT /profile`, `PUT /profile/password`.

## Endpoints Bearer token (routes/api.php, prefix `/api`)
- Nhom `/api/auth` voi middleware `auth:sanctum`, `auto.rotate.sanctum`:
  - `GET /auth/tokens`: liet ke PAT cua user.
  - `POST /auth/token/issue`: cap PAT moi (dung cho tich hop/API); chi `ADMIN` hoac `QL` duoc phep.
  - `POST /auth/token/revoke`: revoke PAT hien tai hoac theo `token_id`.
  - `POST /auth/token/revoke-all`: xoa tat ca PAT cua user.
  - `POST /auth/token/rotate`: xoay PAT hien tai, tao token moi (giu/ghi de abilities, name tu input); chi `ADMIN` hoac `QL` duoc phep.
  - `GET /auth/token/ttl`: tra `minutes_left`, nguong refresh va cau hinh expiration.
  - `GET /auth/me`: lay profile (tai dung ProfileController@me).
- Route demo quyen: `GET /api/admin/ping` (middleware `auth:sanctum`, `role:ADMIN`).

## Cach dung Sanctum
- SPA/first-party: dang nhap qua `/login` (guard `web` + CSRF), chi dung session cookie + CSRF (khong PAT); `sanctum.guard = web` cho phep CSRF/cookie hoat dong.
- API/Bearer: dung PAT qua header `Authorization: Bearer <token>` cho cac route `/api/auth/*` va tai nguyen can `auth:sanctum` (vd `/api/admin/ping`).
- PAT het han sau 120 phut; middleware `auto.rotate.sanctum` ho tro xoay tu dong va tra token moi qua header khi gan het han.
