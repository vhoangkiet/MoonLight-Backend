# Media: Cloudflare R2 + Spatie MediaLibrary + PendingMedia (V1)

## Mục tiêu
- Upload ảnh/video lên Cloudflare R2 (S3-compatible).
- Upload trả về **`uuid`** của media vừa upload.
- Media upload xong chưa chắc dùng → cần cơ chế **pending** + cleanup.

## Quyết định thiết kế
- Không để `media.model_type/model_id = null` (vì migration mặc định thường NOT NULL).
- Tạo model **`PendingMedia`** làm “bucket” để attach media pending.
- Khi submit create/edit product: attach media từ PendingMedia → `Product`.
- Có command cleanup để xoá media pending quá TTL.

## Schema (logical)
- Bảng Spatie: `media` (có cột `uuid`).
- `pending_media` (uuid):
  - `created_by` (user_id uuid, nullable nếu cần)
  - `expires_at`
  - `status` (`active`, `expired`, `consumed`)
  - timestamps

## Media collections (đề xuất)
- `Product`:
  - `images`
  - `videos`

## Admin APIs (outline)
- `POST /api/v1/admin/media`
  - multipart: `file`
  - validate: mime allowlist + size limit (image/video)
  - effect: attach media vào `PendingMedia` bucket của user
  - output:
    - `uuid`
    - `original_name`
    - `mime_type`
    - `size`
    - `url` (tuỳ policy public/signed)

- `POST /api/v1/admin/products/{productId}/media/attach`
  - input: `media_uuids[]`
  - rule:
    - chỉ cho phép attach media đang thuộc pending bucket của user (hoặc user có quyền admin)
    - attach sang `Product` collection phù hợp (image/video)

- `DELETE /api/v1/admin/products/{productId}/media/{mediaUuid}`
  - rule: chỉ admin/staff
  - effect: detach + delete record + delete object trên R2

## Cleanup pending
- Artisan command (đề xuất):
  - `php artisan media:cleanup-pending --ttl=72` (giờ)
- Rule:
  - media vẫn còn attach vào `PendingMedia` quá TTL → delete (kéo theo xoá object R2)
  - cập nhật `pending_media.status = expired` hoặc xoá record tuỳ chiến lược

## Security notes
- Với video: cần limit size rõ ràng để tránh cost và timeout.
- URL policy:
  - nội bộ admin có thể dùng signed url
  - public product media có thể public hoặc signed tuỳ yêu cầu bảo mật/cost

## Tests (Feature)
- Upload trả `uuid`.
- Attach fail nếu media_uuid không thuộc pending bucket hợp lệ.
- Cleanup xoá pending quá hạn.
