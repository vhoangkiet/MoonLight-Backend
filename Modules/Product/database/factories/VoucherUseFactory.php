<?php

namespace Modules\Product\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Product\Models\Voucher;
use Modules\Product\Models\VoucherUse;

class VoucherUseFactory extends Factory
{
    protected $model = VoucherUse::class;

    public function definition(): array
    {
        return [
            'voucher_id' => Voucher::factory(),
            'user_id' => User::factory(),
            'order_amount' => $this->faker->randomFloat(2, 50, 1000),
            'discount_amount' => $this->faker->randomFloat(2, 5, 200),
            'used_at' => now(),
        ];
    }

    public function withVoucher(Voucher $voucher): static
    {
        return $this->state(fn (array $attributes) => [
            'voucher_id' => $voucher->id,
        ]);
    }

    public function withUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
