# Decision log (V1)

Mục tiêu: ghi lại các quyết định “đã chốt” để triển khai không bị đổi hướng.

## D-COMMON-001 — Market
- **US-only**, **USD**.

## D-DB-001 — Database & conventions
- Database: **PostgreSQL**.
- **Tất cả bảng dùng UUID làm primary key**.
- Các trường `status` dùng **string**, **không** dùng PostgreSQL enum.

## D-AUTH-001 — Authentication
- Auth bằng **email + password**.
- Email xác thực bằng **OTP gửi qua email**.
- Token auth dùng **Laravel Sanctum**.
- Có API quản lý session/token: list/revoke current/revoke all.

## D-RBAC-001 — Authorization
- Role cơ bản: `Admin`, `Staff`, `Customer`.
- Dùng **Spatie Permission** (chưa cần permission chi tiết).

## D-CATALOG-001 — Catalog model
- **Mọi sản phẩm đều là variant**:
  - `Product` là container (thông tin chung).
  - `ProductVariant` là đơn vị bán hàng duy nhất (SKU/price/stock/weight/attributes).
- Search/filter dựa trên dữ liệu ở `ProductVariant`, nhưng UI thường hiển thị theo `Product`.

## D-MEDIA-001 — Media upload & pending
- Upload ảnh/video dùng **Spatie MediaLibrary** + **Cloudflare R2** (S3-compatible).
- Upload trả về **`media.uuid`**.
- Không dùng `media.model_type/model_id = null` (thường NOT NULL).
- Dùng model **`PendingMedia`** để giữ media pending; attach sang `Product` khi submit.
- Có command cleanup pending media theo TTL.
- Media **chỉ attach vào `Product`** (variant không có gallery riêng).

## D-CART-001 — Guest cart/wishlist
- Guest định danh bằng cookie **`guest_key`**.
- Sau login: **merge union**:
  - Wishlist: union theo `variant_id`.
  - Cart: union theo `variant_id` (đề xuất V1: cộng qty, clamp theo stock).

## D-SHIPPING-001 — Shipping (US)
- Không tích hợp bên thứ 3, admin cập nhật trạng thái thủ công.
- Tính phí ship theo **weight thực** (không dùng dim weight).
- **Không** có free shipping threshold trong V1.

## D-TAX-001 — Tax (US)
- Tính thuế **theo state**.
- Tax base: **items sau discount/voucher**, **không tính shipping**.
- Có **miễn thuế** theo category + product override.

## D-PROMO-001 — Promotions
- Cho phép discount program và voucher.
- **Stacking**: áp **discount trước**, sau đó áp **voucher**.
- Voucher có thể **order-level** hoặc **item-level**.

## D-PAY-001 — Payment
- Stripe payment dùng **Laravel Cashier (Stripe)**.
- **Reserve stock khi tạo PaymentIntent**.
- Finalize order/webhook phải **idempotent** theo `payment_intent_id`.

## D-DOCS-001 — API docs
- Generate OpenAPI/Swagger docs bằng **Scramble**: `https://scramble.dedoc.co/`.

## D-COMMENT-001 — Comments language
- Toàn bộ comment/PHPDoc trong source code viết **tiếng Việt**.
- Tên class/method/variable/field/endpoint vẫn theo convention (English) để đọc code dễ.

