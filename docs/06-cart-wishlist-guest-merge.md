# Cart/Wishlist (guest) + Merge union sau login (V1)

## Scope
- Guest có thể:
  - add/remove wishlist item
  - add/update/remove cart item
- Checkout chỉ cho phép sau khi login.
- Sau login: **merge union** từ guest → user.

## Guest identity
- Cookie: `guest_key`.
- `guest_key` được tạo/refresh khi guest dùng cart/wishlist lần đầu.

## Schema (logical)
- `guest_sessions` (uuid): `guest_key` (unique), `last_seen_at`
- `wishlists` (uuid): owner_type(string: `user|guest`), `user_id?`, `guest_key?`, status
- `wishlist_items` (uuid): `wishlist_id`, `variant_id`
- `carts` (uuid): owner_type(`user|guest`), `user_id?`, `guest_key?`, `currency` (USD), status
- `cart_items` (uuid): `cart_id`, `variant_id`, `qty`

## APIs (outline)
Wishlist:
- `GET /api/v1/wishlist`
- `POST /api/v1/wishlist/items` (input: `variant_id`)
- `DELETE /api/v1/wishlist/items/{variantId}`

Cart:
- `GET /api/v1/cart`
- `POST /api/v1/cart/items` (input: `variant_id`, `qty`)
- `PATCH /api/v1/cart/items/{itemId}` (input: `qty`)
- `DELETE /api/v1/cart/items/{itemId}`
- `POST /api/v1/cart/merge` (optional; nếu muốn client chủ động gọi sau login)

## Merge rules (V1)
Wishlist:
- Union theo `variant_id` (không trùng).

Cart:
- Union theo `variant_id`.
- Nếu variant đã có trong user cart:
  - đề xuất: `qty = qty_user + qty_guest`
  - clamp theo `stock_qty` của variant
- Variant inactive/out-of-stock:
  - đề xuất: bỏ qua hoặc giữ lại với warning trong response (chốt khi implement)

## Tests (Feature)
- Guest add wishlist/cart thành công (theo cookie).
- Login + merge union đúng.
- Clamp qty theo stock.

