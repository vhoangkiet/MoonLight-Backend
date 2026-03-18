# Test strategy (V1)

## Mục tiêu
- Bảo vệ các rule nghiệp vụ dễ sai: OTP, merge guest, pricing allocation, tax exemptions, Stripe idempotency.
- Ưu tiên **Feature tests** (PHPUnit) cho API.

## Nhóm test bắt buộc
### Auth/OTP/Sessions
- register → request OTP → verify → login
- OTP expired
- OTP lock sau nhiều lần sai
- revoke current token / revoke all

### Catalog/Search
- public list/filter theo category
- filter theo attributes trên variant
- in_stock filter

### Cart/Wishlist guest + merge
- guest add items (cookie guest_key)
- merge union sau login
- clamp qty theo stock

### Pricing engine
- discount trước voucher sau
- voucher order-level allocation xuống item để tính tax
- tax by state
- exemptions: product override > category
- shipping theo weight tiers

### Checkout/Stripe
- `checkout/intent` tạo reservations
- webhook succeed finalize order idempotent
- expired reservations release

### Admin authorization
- customer bị 403 khi gọi admin endpoints

## Khuyến nghị kỹ thuật
- Mỗi module có 1-2 test file riêng (vd `tests/Feature/Auth/*`, `tests/Feature/Checkout/*`).
- Chạy test theo file khi thay đổi để nhanh.

