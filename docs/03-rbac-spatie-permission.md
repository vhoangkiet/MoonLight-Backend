# RBAC (Spatie Permission) — Roles (V1)

## Scope
- Quản lý role cơ bản:
  - `Admin`
  - `Staff`
  - `Customer`
- Chưa cần permission chi tiết, nhưng dùng Spatie Permission để mở rộng sau.

## Enforcement
- Admin APIs yêu cầu role `Admin|Staff`.
- User APIs (orders/addresses) yêu cầu user login.

## Seed data (đề xuất)
- Seed roles trong seeder (vd `RolesSeeder`):
  - Tạo 3 roles
  - (tuỳ chọn) gán `Admin` cho 1 email cấu hình ban đầu

## APIs (outline)
- `PATCH /api/v1/admin/users/{id}/role`
  - input: `role` ∈ {Admin, Staff, Customer}

## Tests
- Customer gọi admin endpoints → 403.
- Staff được phép CRUD catalog nhưng không gán role (tuỳ chính sách).

