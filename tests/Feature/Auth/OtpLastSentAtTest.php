<?php

namespace Tests\Feature\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class OtpLastSentAtTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)->createPasswordGrantClient('Test Password Client', 'users', true);

        config([
            'passport.password_client_id' => (string) $client->id,
            'passport.password_client_secret' => $client->plainSecret,
        ]);
    }

    public function test_request_otp_sets_last_sent_at_to_now_not_expires_at(): void
    {
        $now = CarbonImmutable::parse('2026-01-01 00:00:00');
        CarbonImmutable::setTestNow($now);

        $email = 'last-sent-at@example.com';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('secret123'),
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/v1/auth/otp/resend', [
            'email' => $email,
        ])->assertOk();

        $otp = OtpVerification::query()->where('email', $email)->firstOrFail();

        $this->assertTrue($otp->expires_at->greaterThan($otp->last_sent_at));
        $this->assertSame($now->toDateTimeString(), $otp->last_sent_at->toDateTimeString());
    }
}
