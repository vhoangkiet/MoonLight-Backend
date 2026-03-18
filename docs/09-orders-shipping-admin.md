# Orders + Manual shipping (Admin) (V1)

## Scope
- User xem danh sách đơn và chi tiết đơn.
- Admin quản lý trạng thái order và shipment theo quy trình thủ công (không carrier integration).

## Schema (logical)
- `orders`: totals, status, currency, address snapshots
- `order_items`: tham chiếu `variant_id` + snapshot sku/name/price
- `shipments`: status + tracking_code (optional)

## Status strings (đề xuất)
Order:
- `pending_payment`, `paid`, `processing`, `shipped`, `delivered`, `cancelled`, `refunded`

Shipment:
- `pending`, `packed`, `shipped`, `delivered`, `cancelled`

## User APIs (outline)
- `GET /api/v1/orders`
- `GET /api/v1/orders/{id}`

## Admin APIs (outline)
- `GET /api/v1/admin/orders`
- `GET /api/v1/admin/orders/{id}`
- `PATCH /api/v1/admin/orders/{id}/status`
  - input: `status`
- `PATCH /api/v1/admin/orders/{id}/shipment/status`
  - input: `status`, `tracking_code?`

## Rule snapshot
Mục tiêu: catalog thay đổi không ảnh hưởng lịch sử đơn.
- `order_items.sku_snapshot`, `name_snapshot`, `price` lưu tại thời điểm mua.

## Tests
- User chỉ xem được order của mình.
- Admin update status thành công; customer 403.

