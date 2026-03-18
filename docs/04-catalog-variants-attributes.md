# Catalog: Product/Variant/Attributes + Search/Filter (V1)

## Quy tắc quan trọng
- **Mọi sản phẩm đều là variant**.
  - `Product` là container (thông tin chung).
  - `ProductVariant` là đơn vị bán hàng duy nhất (SKU/price/stock/weight/attributes/status).

## Schema (logical)
- `categories` (uuid): `parent_id?`, `name`, `slug`, `status`
- `products` (uuid): `category_id`, `name`, `slug`, `description`, `status`
- `product_variants` (uuid):
  - `product_id`
  - `sku` (unique)
  - `price`, `compare_at_price?`, `currency` (USD)
  - `stock_qty`
  - `status`
  - `weight` + `weight_unit` (khuyến nghị `lb`)
  - `dimensions` (json optional: `length`, `width`, `height`, unit `in`)
- `attributes` (uuid): `code` (vd `size`, `color`, `dimension`), `name`
- `attribute_values` (uuid): `attribute_id`, `value`, `meta` (json, vd `hex` cho color)
- Pivot: `product_variant_attribute_values`:
  - `product_variant_id`
  - `attribute_value_id`

## Status strings
- `product.status`: `active`, `inactive`
- `product_variant.status`: `active`, `inactive`

## Admin APIs (outline)
> Khuyến nghị tạo product kèm variants trong 1 request để tránh product “trống”.

- `POST /api/v1/admin/products`
  - input: product fields + `variants[]` (ít nhất 1)
- `PATCH /api/v1/admin/products/{id}`
- `POST /api/v1/admin/products/{id}/variants`
- `PATCH /api/v1/admin/variants/{id}`
- CRUD categories / attributes / attribute_values

## Public APIs (outline)
- `GET /api/v1/categories`
- `GET /api/v1/products`
  - query:
    - `q`
    - `category` (id hoặc slug)
    - `price_min`, `price_max`
    - `in_stock=1`
    - `attributes[color]=red,blue`
    - `attributes[size]=M,L`
    - `sort` (vd `-created_at`, `price`, `-price`)
    - `page`, `per_page`
  - behavior:
    - filter dựa trên variant
    - trả về kết quả theo product (kèm “primary variant” hoặc min/max price tuỳ UI)

- `GET /api/v1/products/{slug}`
  - output: product + `variants[]` (attributes + price + stock + weight)

## Edge cases & rules
- Variant `inactive` không hiển thị public, không cho add-to-cart.
- Nếu product `inactive` → ẩn toàn bộ.
- Search/filter cần tránh N+1: dùng eager loading + join/pivot hợp lý.

## Tests (Feature)
- Filter theo category.
- Filter theo attributes.
- Filter theo in_stock.
- Product detail trả variants đúng.

