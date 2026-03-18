# Quy ước chung (V1)

## 1) API versioning
- Tất cả endpoint nằm dưới `/api/v1`.

## 2) Authentication & guest identity
- User auth: `Authorization: Bearer <token>` (Sanctum).
- Guest identity: cookie `guest_key`.
  - Frontend/mobile phải đảm bảo gửi cookie trong mọi request liên quan cart/wishlist.

## 3) UUID-first schema
- Mọi bảng dùng `id` là UUID.
- Mọi FK cũng dùng UUID (vd `user_id`, `product_id`, `variant_id`...).
- Không dùng PostgreSQL enum. Nếu cần canonical list, dùng:
  - constants trong code + validation rules
  - (tuỳ chọn) `CHECK` constraints trong migration (nhưng vẫn là string).

## 4) Status strings (đề xuất canonical list)
> Danh sách có thể thay đổi theo implementation, nhưng phải thống nhất và có validate.

- `product.status`: `active`, `inactive`
- `product_variant.status`: `active`, `inactive`
- `cart.status`: `active`, `checked_out`, `abandoned`
- `order.status`: `pending_payment`, `paid`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`
- `shipment.status`: `pending`, `packed`, `shipped`, `delivered`, `cancelled`
- `payment.status`: `requires_payment_method`, `requires_confirmation`, `processing`, `succeeded`, `failed`, `refunded`
- `pending_media.status`: `active`, `expired`, `consumed`
- `stock_reservation.status`: `active`, `expired`, `released`, `consumed`

## 5) Response convention (khuyến nghị)
Mục tiêu: FE/QA đọc dễ, consistent.

- Success:
  - `data`: object/array
  - `meta`: pagination/totals nếu có
- Error:
  - `message`: mô tả ngắn
  - `errors`: map field → list message (Laravel validation)
  - `code`: mã lỗi nội bộ (nếu muốn)

## 6) Pagination & filter (đề xuất)
- Pagination: `page`, `per_page`
- Sort: `sort` (vd `-created_at`, `price`, `-price`)
- Filter attributes: `attributes[color]=red,blue`, `attributes[size]=M,L`

## 7) Validation, authorization, structure
- Mọi write endpoints dùng `FormRequest`.
- Admin endpoints bắt buộc role `Admin|Staff`.
- Dùng `Policies` cho các rule ownership (addresses, orders…).

## 8) Comments language
- Comment/PHPDoc: tiếng Việt, tập trung vào:
  - rule nghiệp vụ (tax/shipping/voucher allocation)
  - trade-off/intent
  - edge cases
- Tránh comment kể lại code hiển nhiên.

