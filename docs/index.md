# MoonLight E-commerce API (V1) — Tài liệu dự án

Tài liệu này là **living docs** cho backend e-commerce (US-only) viết bằng **Laravel 12**.

## Mục tiêu

- Ghi lại **quyết định thiết kế** (decisions) và **rule nghiệp vụ** để tránh quên/lệch khi triển khai.
- Chuẩn hoá **schema**, **API contract**, **flow checkout**, **shipping/tax**, **media upload**, **RBAC**.

## Mục lục

- [00-decisions.md](00-decisions.md) — Decision log (các quyết định đã chốt)
- [01-conventions.md](01-conventions.md) — Quy ước chung (UUID/status/response/errors/auth/guest_key)
- [02-auth-otp-sanctum.md](02-auth-otp-sanctum.md) — Auth email + OTP + Sanctum sessions
- [03-rbac-spatie-permission.md](03-rbac-spatie-permission.md) — Roles (Spatie Permission)
- [04-catalog-variants-attributes.md](04-catalog-variants-attributes.md) — Catalog: Product/Variant/Attributes + Search/Filter
- [05-media-r2-medialibrary-pendingmedia.md](05-media-r2-medialibrary-pendingmedia.md) — Upload media R2 + PendingMedia + cleanup
- [06-cart-wishlist-guest-merge.md](06-cart-wishlist-guest-merge.md) — Cart/Wishlist guest + merge union
- [07-pricing-discount-voucher-shipping-tax.md](07-pricing-discount-voucher-shipping-tax.md) — Pricing engine: discount/voucher/shipping/tax
- [08-checkout-stripe-cashier-stock-reserve.md](08-checkout-stripe-cashier-stock-reserve.md) — Stripe Cashier checkout + stock reservation
- [09-orders-shipping-admin.md](09-orders-shipping-admin.md) — Orders + manual shipping (admin)
- [10-api-docs-scramble.md](10-api-docs-scramble.md) — OpenAPI docs bằng Scramble
- [11-testing-strategy.md](11-testing-strategy.md) — Test strategy (Feature tests)

