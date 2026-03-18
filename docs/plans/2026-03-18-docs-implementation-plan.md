# Kế hoạch triển khai tài liệu (Docs) V1

> Mục tiêu: dùng workflow viết-plan → brainstorming → executing-plans → code-reviewer để ra bộ docs chuẩn, sau đó mới bám docs để code.

## 1. Phạm vi docs V1
- Chuẩn hoá và hoàn thiện các file đã có trong `docs/`:
  - `00-decisions.md` (Decision log) — cập nhật lại thay đổi mới (vd: không còn “UUID primary key mọi bảng” nếu sau này đổi).
  - `01-conventions.md` — conventions code, response format, status strings, id/uuid rule, comment tiếng Việt, git rule.
  - `02-11` theo từng module (Auth, RBAC, Catalog, Media, Cart/Wishlist, Pricing, Checkout, Orders, Scramble, Testing).
- Kết nối docs với workflow dev:
  - Link từ `README.md` vào `docs/index.md`.
  - Mỗi module docs nêu rõ “API chính + class chính” để dev bám theo.

## 2. Chiến lược ID/UUID trong docs (cần cập nhật)
- Giữ **`id` bigint** làm primary key cho tất cả bảng (theo migration hiện tại).
- Chỉ thêm **`uuid`** cho các bảng “public/nhạy cảm” để expose qua API (users, catalog, orders,…).
- Docs cần sửa lại các đoạn cũ đang viết “Tất cả bảng dùng UUID PK”.

## 3. Danh sách task docs (chia batch + nhánh git)

### Batch 1 — Core decisions & conventions
- **Task D1**: Cập nhật `00-decisions.md`
  - Điều chỉnh lại các decision lệch với thực tế (đặc biệt D-DB-001 về UUID PK).
  - Ghi rõ rule mới: `id` bigint + `uuid` public cho bảng cần expose.
- **Task D2**: Cập nhật `01-conventions.md`
  - Làm rõ:
    - cách dùng `id` vs `uuid` trong API.
    - format response chuẩn (ApiUtil/ApiResource).
    - rule về comment tiếng Việt.
    - Conventional Commits rule.
- **Nhánh git đề xuất**: `feature/docs-core-decisions`

### Batch 2 — Module Auth + OTP + Sanctum
- **Task D3**: Rà soát & hoàn thiện `02-auth-otp-sanctum.md`
  - Đồng bộ với plan `auth-otp-sanctum-module`.
  - Bổ sung:
    - mapping chi tiết giữa API ↔ Controller ↔ Service ↔ Repo ↔ Model ↔ Resource ↔ Test.
    - ví dụ payload request/response (json).
    - rule security: OTP, rate limit, mail, lock policy.
- **Nhánh git đề xuất**: `feature/docs-auth-otp-sanctum`

### Batch 3 — RBAC & Catalog
- **Task D4**: Cập nhật `03-rbac-spatie-permission.md`
  - Ghi rõ cách dùng Spatie Permission với `users.id` bigint, không đụng vendor.
  - Nêu rõ mapping role cho các endpoint admin.
- **Task D5**: Cập nhật `04-catalog-variants-attributes.md`
  - Xác nhận lại rule “mọi sản phẩm là variant”.
  - Thêm ví dụ API filter/search, response mẫu.
- **Nhánh git đề xuất**: `feature/docs-rbac-catalog`

### Batch 4 — Media, Cart/Wishlist
- **Task D6**: Cập nhật `05-media-r2-medialibrary-pendingmedia.md`
  - Đảm bảo không động schema vendor (media.id bigint).
  - Mô tả rõ flow PendingMedia, cleanup TTL, security.
- **Task D7**: Cập nhật `06-cart-wishlist-guest-merge.md`
  - Mô tả chi tiết merge rule, conflict, clamp stock.
- **Nhánh git đề xuất**: `feature/docs-media-cart-wishlist`

### Batch 5 — Pricing, Checkout, Orders
- **Task D8**: Cập nhật `07-pricing-discount-voucher-shipping-tax.md`
  - Làm rõ tax/shipping/voucher allocation, rounding, exemptions.
- **Task D9**: Cập nhật `08-checkout-stripe-cashier-stock-reserve.md`
  - Sequence flow với PaymentIntent + stock reservation.
- **Task D10**: Cập nhật `09-orders-shipping-admin.md`
  - Trạng thái order/shipment, audit/snapshot rule.
- **Nhánh git đề xuất**: `feature/docs-pricing-checkout-orders`

### Batch 6 — Scramble + Testing + README hook
- **Task D11**: Cập nhật `10-api-docs-scramble.md`
  - Cách config Scramble, auth schemes, grouping, export spec.
- **Task D12**: Cập nhật `11-testing-strategy.md`
  - Liên kết test file thực tế (Auth, Catalog, etc.).
- **Task D13**: Cập nhật `README.md`
  - Thêm 1 mục “Project docs” trỏ tới `docs/index.md`.
- **Nhánh git đề xuất**: `feature/docs-infra-scramble-testing`

## 4. Workflow thực hiện (áp dụng superpowers)

Cho **mỗi batch**:

1. **Brainstorming** (bạn đã attach skill):
   - Rà lại module tương ứng, đặt thêm câu hỏi nếu cần (1 câu/lần).
   - Chuẩn hoá design docs của từng file `.md` trong batch.
2. **Executing-plans**:
   - Tạo nhánh `feature/docs-...` theo tên batch.
   - Cập nhật các file `.md` liên quan.
   - Chạy `php vendor/bin/pint` nếu có thay đổi PHP (với batch docs nhiều khi không cần).
3. **Code review**:
   - Dùng subagent `code-reviewer` theo skill `requesting-code-review`.
   - Sửa theo feedback (nếu có).
4. **Git**:
   - Commit theo **Conventional Commits**, ví dụ:
     - `docs: update core decisions for id/uuid rule`
     - `docs: refine auth otp module flow`
   - Mở PR từ `feature/docs-*` vào `develop`, merge sau khi review ổn.

## 5. Tiêu chí hoàn thành docs V1
- Mỗi module docs:
  - có luồng nghiệp vụ rõ ràng, dùng tiếng Việt.
  - có mapping API ↔ class ↔ DB đủ để dev bám theo.
  - không mâu thuẫn với migration/config thực tế (đặc biệt id/uuid, packages).
- README có link rõ ràng tới docs.

