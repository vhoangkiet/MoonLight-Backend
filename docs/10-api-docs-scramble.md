# API Docs (OpenAPI) bằng Scramble

Nguồn tham khảo: [Scramble documentation](https://scramble.dedoc.co/).

## Mục tiêu
- Generate OpenAPI spec tự động từ code để FE/QA có contract rõ ràng.
- Giảm rủi ro docs bị outdated so với code.

## Scope V1
- Document toàn bộ `/api/v1`.
- Document auth:
  - Bearer token (Sanctum)
  - guest cookie `guest_key` (ghi chú ở endpoints cart/wishlist)

## Quy ước khi viết API để Scramble hiểu tốt
- Dùng FormRequest cho validation.
- Dùng API Resources cho response.
- Trả HTTP status đúng (200/201/204/401/403/422).

## Deliverables
- UI docs route (nội bộ): ví dụ `/docs/api` (chốt khi implement).
- Export OpenAPI json/yaml để FE import vào Postman/Insomnia.

## Lưu ý bảo mật
- Docs route chỉ nên bật ở môi trường dev/staging hoặc require admin auth.

