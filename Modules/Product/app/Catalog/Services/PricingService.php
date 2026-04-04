<?php

namespace Modules\Product\Catalog\Services;

use App\Exceptions\DomainException;
use Modules\Product\Catalog\Repositories\Interfaces\ProductRepositoryInterface;
use Modules\Product\Catalog\Repositories\Interfaces\ProductVariantRepositoryInterface;
use Modules\Product\Promotion\Models\Discount;
use Modules\Product\Promotion\Models\Voucher;

class PricingService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ProductVariantRepositoryInterface $variantRepository
    ) {}

    /**
     * Calculate final price with discounts and vouchers.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function calculate(int $productId, int $variantId, array $options = []): array
    {
        $variant = $this->variantRepository->find($variantId);

        if (! $variant || $variant->product_id !== $productId) {
            throw new DomainException('Invalid product variant', 422);
        }

        $basePrice = $variant->price;
        $discountAmount = 0;
        $voucherAmount = 0;
        $appliedDiscounts = [];

        // Apply active discounts
        $discounts = $this->getActiveDiscounts($productId, $variantId);
        foreach ($discounts as $discount) {
            $discountValue = $discount->calculateDiscount($basePrice - $discountAmount);
            $discountAmount += $discountValue;
            $appliedDiscounts[] = [
                'id' => $discount->id,
                'name' => $discount->name,
                'type' => $discount->type,
                'value' => $discount->value,
                'amount' => $discountValue,
            ];
        }

        $priceAfterDiscount = $basePrice - $discountAmount;

        // Apply voucher if provided
        $voucherCode = $options['voucher_code'] ?? null;
        $appliedVoucher = null;

        if ($voucherCode) {
            $voucher = Voucher::byCode($voucherCode)->first();

            if ($voucher && $voucher->canApply($priceAfterDiscount)) {
                $voucherAmount = $voucher->calculateDiscount($priceAfterDiscount);
                $appliedVoucher = [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'type' => $voucher->type,
                    'value' => $voucher->value,
                    'amount' => $voucherAmount,
                ];
            }
        }

        $finalPrice = max(0, $priceAfterDiscount - $voucherAmount);

        return [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'base_price' => $basePrice,
            'discount_amount' => $discountAmount,
            'voucher_amount' => $voucherAmount,
            'final_price' => $finalPrice,
            'currency' => 'VND',
            'applied_discounts' => $appliedDiscounts,
            'applied_voucher' => $appliedVoucher,
        ];
    }

    /**
     * Validate and apply voucher.
     *
     * @throws DomainException
     */
    public function validateVoucher(string $code, float $orderAmount): Voucher
    {
        $voucher = Voucher::byCode($code)->first();

        if (! $voucher) {
            throw new DomainException('Invalid voucher code', 422);
        }

        if (! $voucher->isValid()) {
            throw new DomainException('Voucher is not valid or has expired', 422);
        }

        if (! $voucher->canApply($orderAmount)) {
            throw new DomainException(
                "Minimum order amount of {$voucher->min_order_amount} required",
                422
            );
        }

        return $voucher;
    }

    /**
     * Get active discounts for product/variant.
     *
     * @return array<int, Discount>
     */
    private function getActiveDiscounts(int $productId, int $variantId): array
    {
        $product = $this->productRepository->find($productId, ['*'], ['discounts']);

        if (! $product) {
            return [];
        }

        $discounts = [];

        foreach ($product->discounts as $discount) {
            // Check if discount applies to this variant
            $pivotVariantId = $discount->pivot->product_variant_id;
            if ($pivotVariantId === null || (int) $pivotVariantId === $variantId) {
                if ($discount->isActive()) {
                    $discounts[] = $discount;
                }
            }
        }

        return $discounts;
    }
}
