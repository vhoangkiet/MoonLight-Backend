# Checkout (Stripe Cashier) + Stock reservation on PaymentIntent (V1)

## Mục tiêu
- Chỉ user login mới được checkout.
- Tạo Stripe `PaymentIntent` bằng Laravel Cashier.
- **Reserve stock khi tạo PaymentIntent** để hạn chế oversell.
- Finalize order qua webhook/confirm phải **idempotent**.

## Stripe flow (high level)
1) Client gọi `POST /checkout/intent` (server tính quote, tạo intent)\n
2) Client xác nhận thanh toán với Stripe (client secret)\n
3) Stripe gửi webhook `payment_intent.succeeded` (hoặc fail)\n
4) Server finalize `Order` (idempotent theo `payment_intent_id`)

## Schema (logical)
- `payments`:
  - `provider` = `stripe`
  - `provider_ref` = `payment_intent_id` (unique)
  - `status`
  - `amount`
- `stock_reservations` (đề xuất):
  - `payment_intent_id` (unique/index)
  - `variant_id`
  - `qty`
  - `expires_at`
  - `status` (`active`, `expired`, `released`, `consumed`)

## Reserve stock rule (V1)
- Khi tạo PaymentIntent:
  - kiểm tra stock đủ
  - tạo reservations cho từng cart item
  - set TTL (vd 15 phút)
- Khi payment succeeded:
  - chuyển reservation `consumed`
  - trừ stock thực trên variant
- Khi payment failed hoặc hết TTL:
  - chuyển reservation `released/expired`
  - không trừ stock

## APIs (outline)
- `POST /api/v1/checkout/intent`
  - input: shipping_address_id (hoặc address snapshot), voucher_code optional
  - effect:
    - compute quote
    - create PaymentIntent (Cashier)
    - create stock reservations
  - output: `payment_intent_id`, `client_secret`, totals

- `POST /api/v1/checkout/confirm`
  - input: `payment_intent_id`
  - effect: finalize nếu webhook chưa tới (vẫn idempotent)

- `POST /api/v1/webhooks/stripe`
  - handle events:
    - `payment_intent.succeeded`
    - `payment_intent.payment_failed`

## Idempotency rules
- 1 `payment_intent_id` → tối đa 1 `order`.
- Webhook retry nhiều lần không tạo order trùng.
- Nếu `confirm` chạy trước webhook, webhook tới sau phải “no-op”.

## Tests
- Create intent tạo reservations đúng.
- Succeeded webhook finalize order đúng và không double.
- Expired reservations được release (job/command).

