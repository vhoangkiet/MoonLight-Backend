# Auth Module - API Documentation (Detailed)

## Overview
Module Authentication cho MoonLight API được xây dựng theo kiến trúc **Modular Laravel**, tuân thủ 3 lớp (Service/Repository) để tách biệt logic nghiệp vụ và truy vấn dữ liệu.

## Folder Structure
Tất cả code liên quan nằm tại `Modules/Auth`:
- `app/Http/Controllers/Api`: Chứa các controller xử lý request (Auth, Password, User).
- `app/Http/Requests`: Chứa logic Validation (FormRequests).
- `app/Http/Resources`: Định dạng dữ liệu trả về (AuthResource, UserResource).
- `app/Services`: Xử lý logic nghiệp vụ chính (Cấp token, verify email...).
- `app/Repositories`: Thao tác trực tiếp với Database (Eloquent).
- `routes/api.php`: Khai báo tất cả Route của module (Prefix `/v1/auth`).

## Roles & Permissions
Hệ thống sử dụng **Spatie Permission**. Mặc định có 3 roles:
1. `admin`: Toàn quyền quản trị.
2. `staff`: Quản lý nội dung/đơn hàng.
3. `customer`: Khách hàng mua hàng (Mặc định gán khi đăng ký).

## Setup Environment
Để module hoạt động, cần cấu hình các thông số sau trong `.env`:
```env
# Passport Client (Sử dụng Password Grant)
PASSPORT_PASSWORD_CLIENT_ID=019d...
PASSPORT_PASSWORD_CLIENT_SECRET=FMD9u...

# App URL & Frontend
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
```

## API Reference (Prefix: `/api/v1/auth`)

### 1. Register Account
**Endpoint**: `POST /register`
- **Request Body**:
```json
{
  "first_name": "Nguyen",
  "last_name": "An",
  "email": "an@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```
- **Behavior**: Tạo user mới -> Gán role `customer` -> Gửi mail xác thực.

### 2. Login
**Endpoint**: `POST /login`
- **Request Body**:
```json
{
  "email": "an@example.com",
  "password": "Password123!"
}
```
- **Response (200 OK)**:
```json
{
  "status": "success",
  "message": "Login successful.",
  "data": {
    "token_type": "Bearer",
    "expires_in": 1296000,
    "access_token": "eyJ0eXAi...",
    "refresh_token": "def502..."
  }
}
```

### 3. Forgot Password
**Endpoint**: `POST /password/forgot`
- **Behavior**: Gửi Link reset mật khẩu vào email user. Link này sẽ dẫn đến route `GET /password/reset/{token}` trên Backend, sau đó tự động redirect về trang chủ Frontend kèm token.

### 4. Update Profile (with Avatar)
**Endpoint**: `PUT /profile` (Sử dụng `multipart/form-data`)
- **Fields**: `first_name`, `last_name`, `avatar` (file ảnh).
- **Behavior**: Cập nhật thông tin và lưu ảnh thông qua Spatie Media Library (Collection: `avatar`).

## Email Templates
- **Branding**: Đã được thiết kế lại theo theme MoonLight (Indigo Color).
- **Customization**: Chỉnh sửa tại `resources/views/vendor/notifications/email.blade.php`.
- **CSS**: Chỉnh sửa tại `resources/views/vendor/mail/html/themes/default.css`.

## Technical Features
- **Passport v13 internal handling**: Sử dụng `app()->handle($request)` để thay thế `Route::dispatch`, khắc phục lỗi grant type trong Laravel 12.
- **Rate Limiting**: Giới hạn đăng nhập (5 lần/phút) để chống Brute-force.
- **Standardized Response**: Mọi API đều trả về format chuẩn thông qua `BaseController::execute()`.

## Change Log

### 2026-03-26
- **Register**:
  - Không bắt buộc gửi `password_confirmation` (bỏ rule `confirmed`).
  - Không còn gửi email verify sau khi đăng ký.
- **Login**:
  - Không yêu cầu email đã verify (`email_verified_at` có thể `null` vẫn đăng nhập được).
- **API message**:
  - Message khi đăng ký đổi thành: `Registration successful.`
