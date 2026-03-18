# Auth email + OTP + Sanctum sessions (V1)

## Scope
- Register/login bằng email.
- Verify email bằng OTP gửi qua email.
- Forgot/reset password dùng cơ chế Laravel.
- Quản lý session/token qua Sanctum.

## OTP policy (đề xuất V1)
> Có thể điều chỉnh khi implement nhưng phải ghi lại ở đây.

- OTP: 6 digits
- TTL: 10 phút
- Resend cooldown: 60 giây
- Max attempts: 5 lần → lock 15 phút

## Schema (logical)
- `users` (uuid): `email`, `password`, `email_verified_at`, `status`
- `otp_verifications` (uuid):
  - `email` (index)
  - `otp_hash` (không lưu otp plain text)
  - `expires_at`
  - `attempts`
  - `last_sent_at`
  - `status` (`active`, `verified`, `expired`, `locked`)

## APIs (outline)
- `POST /api/v1/auth/register`
  - input: `email`, `password`
  - effect: tạo user (status `active`), gửi OTP, chưa set `email_verified_at`

- `POST /api/v1/auth/register/request-otp`
  - input: `email`
  - effect: tạo/refresh OTP record + gửi email

- `POST /api/v1/auth/register/verify-otp`
  - input: `email`, `otp`
  - effect: verify → set `users.email_verified_at`

- `POST /api/v1/auth/login`
  - input: `email`, `password`
  - rule: yêu cầu `email_verified_at` đã có
  - output: Sanctum token

- `GET /api/v1/auth/sessions`
  - list tokens (thiết bị/session) của user

- `POST /api/v1/auth/logout`
  - revoke token hiện tại

- `POST /api/v1/auth/logout-all`
  - revoke toàn bộ tokens

- `POST /api/v1/auth/password/change`
- `POST /api/v1/auth/password/forgot`
- `POST /api/v1/auth/password/reset`

## Security notes
- Rate limit mạnh cho `request-otp` và `verify-otp`.
- Email nội dung OTP cần TTL rõ ràng.
- Nên gửi mail qua queue để tránh timeout.

## Tests (Feature)
- Happy path: register → request OTP → verify → login.
- OTP expired.
- OTP sai quá `max attempts` → locked.
- Login bị chặn nếu chưa verify email.
- Revoke current token / revoke all.

