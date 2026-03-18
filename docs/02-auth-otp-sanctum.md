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
- `users`:
  - `id` (bigint, PK nội bộ)
  - `uuid` (public id cho API)
  - `email`, `password`, `email_verified_at`, `status` (string, vd `active`, `blocked`)
- `otp_verifications`:
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

## Mapping lớp (định hướng implement)

> Tài liệu này mô tả kiến trúc mục tiêu cho module Auth/OTP. Code sẽ bám theo cấu trúc này.

- **Controller**
  - `App\Http\Controllers\Auth\AuthController`
  - Kế thừa `ApiController`, dùng helper `execute()` để:
    - nhận `FormRequest` đã validate
    - gọi phương thức tương ứng trong `AuthService`
    - bắt `DomainException` (nếu có) và trả JSON theo format chung.

- **Form Requests** (kế thừa `BaseRequest`)
  - `RegisterRequest`
  - `RequestOtpRequest`
  - `VerifyOtpRequest`
  - `LoginRequest`
  - `ChangePasswordRequest`
  - `ForgotPasswordRequest`
  - `ResetPasswordRequest`
  - Mỗi request:
    - định nghĩa `rules()` + `messages()` dùng translate (`resources/lang/*`).
    - override `failedValidation()` từ `BaseRequest` để trả `ApiResponse::validationError`.

- **Service**
  - `App\Services\Auth\AuthService`
  - Chứa toàn bộ nghiệp vụ:
    - đăng ký + gửi OTP
    - verify OTP
    - tạo/revoke Sanctum token
    - forgot/reset password
  - Khi gặp lỗi nghiệp vụ (OTP sai, hết hạn, vượt số lần, user chưa verify, user bị block...), service sẽ **throw `DomainException`** với:
    - `status` (HTTP status)
    - `translationKey` (vd `auth.otp.invalid`, `auth.login.blocked`...)
    - `code` (vd `OTP_INVALID`, `USER_BLOCKED`...)

- **Repository**
  - `App\Repositories\OtpVerificationRepository`
    - Đóng gói query với bảng `otp_verifications` (find/create/update theo email).

- **Models**
  - `App\Models\User`
    - có trait `HasPublicUuid` để tự sinh `uuid`.
  - `App\Models\OtpVerification`
    - dùng `BaseModel` hoặc `Model` chuẩn, theo schema đã mô tả ở trên.

- **Resource**
  - `App\Http\Resources\Auth\LoginResource`
    - chuẩn hoá dữ liệu trả về khi login thành công:
      - `user` (dùng `uuid` public)
      - `token` (Sanctum)
      - thông tin bổ sung nếu cần (roles, quyền cơ bản...).

## Ví dụ payload (tham khảo)

- **Đăng ký**
```json
POST /api/v1/auth/register
{
  "email": "user@example.com",
  "password": "secret123"
}
```

- **Request OTP**
```json
POST /api/v1/auth/register/request-otp
{
  "email": "user@example.com"
}
```

- **Verify OTP**
```json
POST /api/v1/auth/register/verify-otp
{
  "email": "user@example.com",
  "otp": "123456"
}
```

- **Login**
```json
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "secret123"
}
```


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

