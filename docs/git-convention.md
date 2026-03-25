# Quy tắc Commit Git (Conventional Commits)

Để dự án chuyên nghiệp và dễ dàng theo dõi lịch sử, chúng ta sẽ áp dụng chuẩn **Conventional Commits**.

## Cấu trúc Message
```text
<type>(<scope>): <description>

[body]

[footer]
```

## Các loại (Type) phổ biến
- **feat**: Một tính năng mới cho người dùng.
- **fix**: Sửa một lỗi (bug) cho người dùng.
- **docs**: Thay đổi về tài liệu (ví dụ: README, hướng dẫn).
- **style**: Thay đổi không ảnh hưởng đến ý nghĩa của code (whitespace, format, missing semi-colons, v.v.).
- **refactor**: Thay đổi code không phải sửa lỗi cũng không phải thêm tính năng (tối ưu hóa cấu trúc).
- **perf**: Thay đổi code để cải thiện hiệu năng.
- **test**: Thêm các test thiếu hoặc sửa các test hiện có.
- **chore**: Các thay đổi về build process hoặc công cụ hỗ trợ (ví dụ: cập nhật dependencies).
- **ci**: Thay đổi cấu hình CI (GitHub Actions, GitLab CI).

## Quy định chi tiết
1. **Tiêu đề (Subject line)**:
   - Sử dụng câu mệnh lệnh (ví dụ: `add` thay vì `added` hoặc `adds`).
   - Không viết hoa chữ cái đầu tiên (trừ khi là tên riêng).
   - Không có dấu chấm ở cuối câu.
   - Giới hạn dưới 50 ký tự.
2. **Nội dung (Body)** (nếu có):
   - Giải thích **tại sao** thay đổi, thay vì **thế nào**.
   - Cách dòng so với tiêu đề.
3. **Phạm vi (Scope)** (tùy chọn):
   - Chỉ định phần code bị ảnh hưởng (ví dụ: `feat(auth)`, `fix(user-repo)`).

## Ví dụ mẫu
```text
feat(auth): thêm tính năng đăng nhập bằng Google
fix(user): sửa lỗi không hiển thị avatar khi tạo user mới
docs: cập nhật hướng dẫn cài đặt môi trường
refactor(base): tối ưu hóa hàm applyFilters trong BaseRepository
```
