<?php

namespace App\Repositories;

use App\Models\OtpVerification;
use Carbon\CarbonImmutable;

final class OtpVerificationRepository
{
    public function __construct(
        private readonly OtpVerification $model,
    ) {}

    public function findActiveByEmail(string $email): ?OtpVerification
    {
        return $this->model
            ->newQuery()
            ->where('email', $email)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
    }

    public function createNew(string $email, string $otpHash, CarbonImmutable $expiresAt): OtpVerification
    {
        return $this->model->newQuery()->create([
            'email' => $email,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'last_sent_at' => $expiresAt,
            'status' => 'active',
        ]);
    }

    public function increaseAttempts(OtpVerification $otp): void
    {
        $otp->increment('attempts');
    }

    public function markVerified(OtpVerification $otp): void
    {
        $otp->forceFill([
            'status' => 'verified',
        ])->save();
    }

    public function markExpired(OtpVerification $otp): void
    {
        $otp->forceFill([
            'status' => 'expired',
        ])->save();
    }

    public function markLocked(OtpVerification $otp): void
    {
        $otp->forceFill([
            'status' => 'locked',
        ])->save();
    }
}
