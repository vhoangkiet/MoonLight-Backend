# Pricing engine (V1): Discount + Voucher + Shipping + Tax

## Tổng quan rule V1 (đã chốt)
- **Discount program áp trước**, sau đó **voucher áp sau**.
- Shipping: tính theo **weight thực** (lấy từ `product_variants.weight`), không dùng dim weight.
- Tax: theo **state**.
  - Tax base = **items sau discount/voucher allocation**.
  - **Không** tính tax trên shipping.
  - Có exemptions theo category + product override.

## Input của Pricing engine
- Cart items: `variant_id`, `qty`
- Address: `state` (US)
- Voucher code (optional)

## Output của Quote
- `items_subtotal`
- `discount_total`
- `voucher_total`
- `shipping_total`
- `tax_total`
- `total`
- breakdown theo items (nếu cần cho FE hiển thị)

## Discount/Voucher
### Discount programs
> V1 nên giữ đơn giản: percent/fixed, scope product/category/order, có thời gian hiệu lực.

### Voucher
- 2 scope:
  - order-level: giảm trên tổng đơn
  - item-level: giảm trên item (theo product/category)
- Usage limits:
  - `max_uses`
  - `max_uses_per_user`
- Điều kiện:
  - `min_subtotal`
  - `starts_at`, `ends_at`

### Allocation (quan trọng cho tax)
Để tính tax chính xác theo “items sau discount/voucher”, nếu voucher là order-level cần phân bổ xuống item.

Đề xuất V1:
- Tính `net_item_total` sau discount/item-level voucher.
- Nếu có order-level voucher:
  - phân bổ theo tỷ trọng `net_item_total` của từng item.
  - làm tròn 2 decimals, phần dư dồn vào item cuối để giữ tổng đúng.

## Shipping (weight)
### Data cần có
- Mỗi variant có `weight` (lb) và `weight_unit`.

### Shipping rates
`shipping_rates.rules` (json) có thể chứa:
- weight tiers: `0-1lb`, `1-5lb`, `5-10lb`...
- base fee hoặc fee theo tier

### Shipping calculation (V1)
- `total_weight = Σ(variant.weight * qty)`
- chọn rate theo tier

## Tax (by state + exemptions)
### Tax rules
- `tax_rules`: `state_code` → `rate` (vd 0.0825)

### Exemptions
- `tax_exemptions`:
  - scope = `product` hoặc `category`
  - scope_id = uuid
  - `is_exempt`
- Priority:
  - product override ưu tiên hơn category.

### Tax base
`taxable_base = Σ(taxable_item_net_total)`
Trong đó item taxable nếu:
- product không exempt (override)
- category không exempt

`tax_total = round(taxable_base * state_rate, 2)`

## Quote API (outline)
- `POST /api/v1/checkout/quote`
  - input: address (state), voucher_code optional
  - output: totals + breakdown

## Tests
- Discount trước voucher (stacking order).
- Voucher order-level allocation → tax base đúng.
- Exemption: product override ưu tiên category.
- Shipping tiers theo tổng weight.

