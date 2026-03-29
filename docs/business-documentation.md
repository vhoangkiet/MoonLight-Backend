# Tài liệu Nghiệp vụ - Business Documentation

## Tổng quan Hệ thống

MoonLight là hệ thống quản lý sản phẩm và bán hàng cho ngành nghề kinh doanh sản phẩm có nhiều biến thể (variants) như: mỹ phẩm, thời trang, nội thất, v.v.

## Đối tượng Sử dụng

### 1. Admin (Quản trị viên)
- Quản lý toàn bộ hệ thống
- Quản lý sản phẩm, danh mục, giảm giá
- Quản lý người dùng và phân quyền
- Xem báo cáo doanh thu

### 2. Staff (Nhân viên)
- Quản lý sản phẩm (CRUD)
- Quản lý đơn hàng
- Quản lý kho (stock)
- Không có quyền xóa dữ liệu quan trọng

### 3. Customer (Khách hàng)
- Xem sản phẩm
- Tính toán giá với khuyến mãi
- Áp dụng voucher
- Đặt hàng (nếu có module Order)

## Luồng Nghiệp vụ Chính

### 1. Quản lý Sản phẩm

#### 1.1 Tạo Sản phẩm Mới
```
Admin/Staff
    ↓
Tạo Danh mục (nếu chưa có)
    ↓
Tạo Sản phẩm (name, slug, description, category)
    ↓
Tạo Biến thể (variants)
    - SKU (auto-generated từ shape/length/tonal/size)
    - Giá
    - Số lượng tồn kho
    - Thuộc tính (hình dạng, kích thước, màu sắc)
    ↓
Kích hoạt sản phẩm (status: active)
```

#### 1.2 Cập nhật Sản phẩm
```
Admin/Staff
    ↓
Tìm sản phẩm
    ↓
Cập nhật thông tin
    ↓
Cập nhật giá/kho (có thể batch update)
    ↓
Lưu thay đổi
```

#### 1.3 Xóa Sản phẩm
```
Admin (chỉ Admin)
    ↓
Kiểm tra không có đơn hàng liên quan
    ↓
Soft delete (giữ lại dữ liệu)
    ↓
Cập nhật trạng thái: archived
```

### 2. Quản lý Biến thể (Variants)

#### 2.1 Thuộc tính Biến thể
Mỗi biến thể có 4 thuộc tính tạo nên SKU:

| Thuộc tính | Ví dụ | Mã trong SKU |
|------------|-------|--------------|
| Shape (Hình dạng) | Round, Oval, Square | ROU, OVA, SQU |
| Length (Kích thước) | 50cm, 70cm, 100cm | 50C, 70C, 100C |
| Tonal Palette (Tone màu) | Warm, Cool, Neutral | WAR, COO, NEU |
| Size (Cỡ) | Small, Medium, Large | SMA, MED, LAR |

**Ví dụ SKU**: `ROU-50C-WAR-MED` = Round 50cm Warm Medium

#### 2.2 Luồng tạo Biến thể
```
Staff
    ↓
Chọn Sản phẩm cha
    ↓
Nhập thuộc tính (shape, length, tonal, size)
    ↓
Hệ thống tự động generate SKU
    ↓
Nhập giá và số lượng tồn kho
    ↓
Kiểm tra trùng lặp SKU
    ↓
Lưu biến thể
```

#### 2.3 Cập nhật Kho (Stock)
```
Staff
    ↓
Tìm biến thể
    ↓
Cập nhật số lượng
    ↓
Lý do điều chỉnh (nhập kho, bán hàng, hư hỏng...)
    ↓
Lưu và ghi log
```

### 3. Quản lý Giảm giá (Discount)

#### 3.1 Tạo Chương trình Giảm giá
```
Admin
    ↓
Tạo Discount
    - Code (ví dụ: SALE20)
    - Loại: percentage (%) hoặc fixed (VNĐ)
    - Giá trị giảm
    - Điều kiện: min_order_amount
    - Giới hạn: max_discount_amount
    - Thời gian: start_date → end_date
    ↓
Chọn sản phẩm áp dụng
    ↓
Chọn biến thể cụ thể (nếu cần)
    ↓
Kích hoạt discount
```

#### 3.2 Luồng áp dụng Discount
```
Customer xem sản phẩm
    ↓
Hệ thống kiểm tra:
    - Discount có active?
    - Trong thời gian hiệu lực?
    - Áp dụng cho sản phẩm/biến thể này?
    ↓
Hiển thị giá sau giảm (nếu có)
    ↓
Tính toán giá cuối cùng khi thanh toán
```

#### 3.3 Quy tắc Giảm giá

**Loại giảm giá:**
- **Percentage**: Giảm theo % (ví dụ: 20%)
- **Fixed**: Giảm số tiền cố định (ví dụ: 50,000 VNĐ)

**Áp dụng nhiều discount:**
- Tổng discount = tổng tất cả discount hợp lệ
- Ví dụ: Discount1 (10%) + Discount2 (5 fixed) = Tổng discount

**Giới hạn:**
- `min_order_amount`: Đơn hàng tối thiểu để áp dụng
- `max_discount_amount`: Giảm tối đa (tránh giảm quá nhiều)

### 4. Quản lý Voucher

#### 4.1 Tạo Voucher
```
Admin
    ↓
Tạo Voucher
    - Code (ví dụ: WELCOME2024)
    - Loại: percentage hoặc fixed
    - Giá trị
    - Giới hạn sử dụng (usage_limit)
    - Thời hạn: valid_from → valid_until
    ↓
Kích hoạt
```

#### 4.2 Luồng sử dụng Voucher
```
Customer
    ↓
Thêm sản phẩm vào giỏ hàng
    ↓
Nhập mã voucher tại checkout
    ↓
Hệ thống kiểm tra:
    - Code có tồn tại?
    - Còn hiệu lực?
    - Đạt min_order_amount?
    - Còn lượt sử dụng?
    - User đã dùng chưa?
    ↓
Áp dụng giảm giá
    ↓
Cập nhật usage_count
    ↓
Lưu voucher_use record
```

#### 4.3 Quy tắc Voucher

**Hạn chế sử dụng:**
- `usage_limit`: Số lần tối đa voucher có thể dùng (null = unlimited)
- `usage_count`: Số lần đã dùng (tự động tăng)
- `remaining_usage` = usage_limit - usage_count

**Ràng buộc:**
- Không thể dùng voucher hết hạn (valid_until < now)
- Không thể dùng voucher chưa bắt đầu (valid_from > now)
- Không thể dùng voucher đã hết lượt (usage_count >= usage_limit)
- Không thể dùng voucher không active (is_active = false)

### 5. Tính toán Giá (Pricing)

#### 5.1 Công thức Tính giá
```
Base Price (giá biến thể)
    ↓
- Discount Amount (tổng các discount)
- Voucher Amount (nếu có)
    ↓
Final Price (giá cuối cùng)
```

#### 5.2 Luồng tính toán
```
Customer chọn sản phẩm + biến thể
    ↓
Nhập voucher code (optional)
    ↓
Gọi API calculate-price
    ↓
Hệ thống:
    1. Lấy base_price của variant
    2. Tìm active discounts cho product/variant
    3. Tính discount_amount
    4. Kiểm tra và tính voucher_amount
    5. Trừ tổng discount từ base_price
    ↓
Trả về:
    - base_price
    - discount_amount
    - voucher_amount
    - final_price
    - applied_discounts[]
    - applied_voucher
```

#### 5.3 Ví dụ Tính giá

**Ví dụ 1: Chỉ có Discount**
```
Sản phẩm: Tóc giả A
Biến thể: Round 50cm Warm Medium
Base Price: 1,000,000 VNĐ

Discount: SALE20 (20% percentage)
Discount Amount: 1,000,000 × 20% = 200,000 VNĐ

Final Price: 1,000,000 - 200,000 = 800,000 VNĐ
```

**Ví dụ 2: Có Discount + Voucher**
```
Sản phẩm: Tóc giả B
Biến thể: Oval 70cm Cool Large
Base Price: 2,000,000 VNĐ

Discount 1: SALE10 (10%) = 200,000 VNĐ
Discount 2: FIXED50 (50,000 fixed) = 50,000 VNĐ
Tổng Discount: 250,000 VNĐ

Voucher: WELCOME (100,000 fixed)
Voucher Amount: 100,000 VNĐ

Final Price: 2,000,000 - 250,000 - 100,000 = 1,650,000 VNĐ
```

**Ví dụ 3: Nhiều Discount, có Max Discount**
```
Sản phẩm: Tóc giả C
Base Price: 500,000 VNĐ

Discount: BIGSALE (50% percentage)
Tính toán: 500,000 × 50% = 250,000 VNĐ
Max Discount: 200,000 VNĐ

Actual Discount: 200,000 VNĐ (bị giới hạn)

Final Price: 500,000 - 200,000 = 300,000 VNĐ
```

### 6. Quản lý Danh mục (Category)

#### 6.1 Cấu trúc Danh mục
- Danh mục đơn giản (không có nested)
- Mỗi sản phẩm thuộc 1 danh mục
- Danh mục có thể active/inactive

#### 6.2 Luồng quản lý
```
Admin
    ↓
Tạo Category (name, slug, description)
    ↓
Gán sản phẩm vào category
    ↓
Có thể filter sản phẩm theo category
```

### 7. Báo cáo và Thống kê

#### 7.1 Báo cáo Sản phẩm
- Tổng số sản phẩm theo trạng thái
- Sản phẩm bán chạy nhất
- Sản phẩm hết hàng
- Sản phẩm tồn kho nhiều

#### 7.2 Báo cáo Giảm giá
- Tổng số discount đang active
- Hiệu quả discount (conversion rate)
- Doanh thu từ discount

#### 7.3 Báo cáo Voucher
- Tổng số voucher đã phát hành
- Tổng số lần sử dụng
- Doanh thu từ voucher

## Quy tắc Nghiệp vụ (Business Rules)

### 1. Sản phẩm

**BR-001**: Sản phẩm phải có ít nhất 1 biến thể để có thể bán.

**BR-002**: Slug sản phẩm phải unique trên toàn hệ thống.

**BR-003**: Sản phẩm đã có đơn hàng không thể xóa hoàn toàn, chỉ có thể archive.

**BR-004**: Chỉ Admin mới có quyền xóa sản phẩm, Staff chỉ có thể deactivate.

### 2. Biến thể

**BR-101**: SKU phải unique trên toàn hệ thống.

**BR-102**: Tổ hợp 4 thuộc tính (shape, length, tonal, size) phải unique trong 1 sản phẩm.

**BR-103**: Giá biến thể phải >= 0.

**BR-104**: Số lượng tồn kho phải >= 0.

**BR-105**: Không thể tạo biến thể cho sản phẩm không tồn tại.

### 3. Giảm giá (Discount)

**BR-201**: Discount chỉ áp dụng khi `is_active = true` và trong khoảng thời gian hiệu lực.

**BR-202**: Có thể áp dụng nhiều discount cùng lúc cho 1 sản phẩm.

**BR-203**: Tổng discount không thể vượt quá `max_discount_amount`.

**BR-204**: Discount có thể áp dụng cho toàn bộ sản phẩm hoặc chỉ biến thể cụ thể.

**BR-205**: Discount code phải unique.

### 4. Voucher

**BR-301**: Voucher chỉ áp dụng khi `is_active = true` và trong thời hạn.

**BR-302**: Mỗi voucher có giới hạn số lần sử dụng (hoặc unlimited).

**BR-303**: Voucher có thể giới hạn `min_order_amount` để áp dụng.

**BR-304**: Voucher code phải unique.

**BR-305**: Khi voucher được sử dụng, `usage_count` tự động tăng.

### 5. Giá và Thanh toán

**BR-401**: Giá cuối cùng không thể âm (nếu discount > giá gốc, giá = 0).

**BR-402**: Discount được tính trước voucher.

**BR-403**: Tổng giảm giá = Discount Amount + Voucher Amount.

**BR-404**: Giá hiển thị cho customer phải là giá sau khi áp dụng discount (nếu có).

### 6. Phân quyền

**BR-501**: Admin có toàn quyền trên hệ thống.

**BR-502**: Staff có quyền CRUD sản phẩm, discount, voucher nhưng không thể xóa.

**BR-503**: Customer chỉ có quyền xem sản phẩm và tính toán giá.

**BR-504**: Chỉ Admin mới có thể quản lý users và roles.

## Luồng Dữ liệu (Data Flow)

### 1. Tạo Sản phẩm Hoàn chỉnh
```
[Admin] 
    → [CategoryController] Tạo category
    → [CategoryRepository] Lưu DB
    
    → [ProductController] Tạo product
    → [ProductService] Validate, Xử lý business logic
    → [ProductRepository] Lưu DB
    
    → [ProductVariantController] Tạo variants
    → [ProductVariantService] Generate SKU, Validate
    → [ProductVariantRepository] Lưu DB
    
    → [ProductResource] Format response
    → [Admin] Nhận thông báo thành công
```

### 2. Tính toán Giá
```
[Customer]
    → [CustomerProductController] Gửi product_id, variant_id, voucher_code
    → [PricingService]
        → [ProductRepository] Lấy variant
        → [DiscountRepository] Tìm active discounts
        → [VoucherRepository] Kiểm tra voucher
        → Tính toán giá
    → [CustomerProductController] Trả về kết quả
    → [Customer] Hiển thị giá
```

### 3. Sử dụng Voucher
```
[Customer]
    → Checkout page
    → Nhập voucher code
    → [VoucherController] Kiểm tra
        → Code tồn tại?
        → Còn hiệu lực?
        → Còn lượt sử dụng?
        → Đạt min_order?
    → [VoucherService] Tính discount
    → [VoucherUseRepository] Ghi log usage
    → [Customer] Áp dụng giảm giá vào đơn hàng
```

## Trạng thái Hệ thống

### Trạng thái Sản phẩm
```
draft → active → inactive → archived
        ↓         ↓
      (bán)   (tạm ngừng)
```

### Trạng thái Biến thể
```
active → out_of_stock → inactive → discontinued
   ↓         ↓            ↓            ↓
(đang bán) (hết hàng) (tạm ngừng) (ngừng SX)
```

### Trạng thái Discount
```
Tạo → Chờ đến start_date → Active → Hết end_date → Expired
       ↓                    ↓
    (chưa chạy)        (đang chạy)
```

### Trạng thái Voucher
```
Tạo → Còn lượt + Còn hạn → Được sử dụng → Hết lượt/Hết hạn
       ↓                      ↓
    (có thể dùng)        (đã dùng)
```

## Lưu ý Quan trọng

### 1. Bảo toàn Dữ liệu
- Không xóa hard delete dữ liệu quan trọng (sản phẩm đã bán)
- Sử dụng soft delete để có thể khôi phục
- Ghi log mọi thay đổi giá, kho

### 2. Hiệu năng
- Cache danh sách categories (ít thay đổi)
- Cache product details (30 phút)
- Cache active discounts (10 phút)
- Không cache giá tính toán (luôn tính real-time)

### 3. Đồng bộ
- Khi thay đổi giá gốc, cần cập nhật lại giá hiển thị
- Khi discount hết hạn, cần cập nhật ngay lập tức
- Voucher usage phải được ghi log đồng bộ

### 4. Bảo mật
- Không expose discount codes cho customer trừ khi được công khai
- Voucher codes nên được validate cẩn thận
- Giá gốc (base_price) luôn được bảo vệ, chỉ hiển thị giá cuối
