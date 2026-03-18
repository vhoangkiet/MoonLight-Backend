<?php

namespace Tests\Feature\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class AuthControllerTest extends TestCase
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

    public function test_happy_path_register_verify_login(): void
    {
        // Đăng ký user
        $email = 'user@example.com';
        $password = 'secret123';

        $this->postJson('/api/v1/auth/register', [
            'email' => $email,
            'password' => $password,
        ])->assertCreated()
            ->assertJsonPath('data.email', $email);

        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // Ghi đè OTP record với mã biết trước để test
        $otp = '123456';
        OtpVerification::query()->updateOrCreate(
            ['email' => $email],
            [
                'otp_hash' => Hash::make($otp),
                'expires_at' => CarbonImmutable::now()->addMinutes(10),
                'attempts' => 0,
                'last_sent_at' => CarbonImmutable::now(),
                'status' => 'active',
            ],
        );

        // Verify OTP
        $this->postJson('/api/v1/auth/otp/verify', [
            'email' => $email,
            'otp' => $otp,
        ])->assertOk()
            ->assertJsonPath('data.email', $email);

        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // Login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk();

        $response->assertJsonPath('data.user.email', $email);
        $this->assertIsString($response->json('data.access_token'));
        $this->assertIsString($response->json('data.refresh_token'));
        $this->assertIsInt($response->json('data.expires_in'));
    }

    public function test_register_validation_errors(): void
    {
        // Thiếu email + password quá ngắn
        $this->postJson('/api/v1/auth/register', [
            'email' => 'not-an-email',
            'password' => '123',
        ])->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                    'password',
                ],
            ]);
    }

    public function test_request_otp_respects_cooldown(): void
    {
        $email = 'cooldown@example.com';
        User::factory()->create([
            'email' => $email,
        ]);

        // Lần 1: ok
        $this->postJson('/api/v1/auth/otp/resend', [
            'email' => $email,
        ])->assertOk();

        // Lần 2 ngay lập tức: bị chặn
        $this->postJson('/api/v1/auth/otp/resend', [
            'email' => $email,
        ])->assertStatus(429)
            ->assertJsonPath('code', 'OTP_RESEND_COOLDOWN');
    }

    public function test_otp_expired(): void
    {
        $email = 'expired@example.com';
        User::factory()->create([
            'email' => $email,
        ]);

        OtpVerification::query()->create([
            'email' => $email,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => CarbonImmutable::now()->subMinute(),
            'attempts' => 0,
            'last_sent_at' => CarbonImmutable::now()->subMinutes(2),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/verify', [
            'email' => $email,
            'otp' => '123456',
        ])->assertStatus(400)
            ->assertJsonPath('code', 'OTP_EXPIRED');
    }

    public function test_otp_locked_after_max_attempts(): void
    {
        $email = 'locked@example.com';
        User::factory()->create([
            'email' => $email,
        ]);

        OtpVerification::query()->create([
            'email' => $email,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => CarbonImmutable::now()->addMinutes(10),
            'attempts' => 5,
            'last_sent_at' => CarbonImmutable::now()->subMinutes(1),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/verify', [
            'email' => $email,
            'otp' => '000000',
        ])->assertStatus(429)
            ->assertJsonPath('code', 'OTP_TOO_MANY_ATTEMPTS');
    }

    public function test_wrong_otp_increases_attempts(): void
    {
        $email = 'wrong-otp@example.com';
        User::factory()->create([
            'email' => $email,
        ]);

        $record = OtpVerification::query()->create([
            'email' => $email,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => CarbonImmutable::now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => CarbonImmutable::now()->subMinutes(1),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/otp/verify', [
            'email' => $email,
            'otp' => '000000',
        ])->assertStatus(400)
            ->assertJsonPath('code', 'OTP_INVALID');

        $this->assertDatabaseHas('otp_verifications', [
            'id' => $record->id,
            'attempts' => 1,
        ]);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'login-fail@example.com',
            'password' => Hash::make('correct-password'),
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('code', 'LOGIN_FAILED');
    }

    public function test_login_blocked_if_email_not_verified(): void
    {
        $email = 'unverified@example.com';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('secret123'),
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ])->assertStatus(403)
            ->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_logout_current_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'email_verified_at' => now(),
        ]);

        // Tạo token bằng cách login
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk();

        $token = $login->json('data.access_token');

        // Gọi logout với bearer token
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(204);
    }

    public function test_logout_all_tokens(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'email_verified_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk();

        $token = $login->json('data.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout-all')
            ->assertStatus(204);
    }

    public function test_list_sessions_returns_tokens(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'email_verified_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk();

        $token = $login->json('data.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/sessions')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'sessions',
                ],
            ]);
    }

    public function test_change_password_happy_and_then_old_password_fails(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'email_verified_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'old-password',
        ])->assertOk();

        $token = $login->json('data.access_token');

        // Đổi mật khẩu (sau đó tất cả token bị revoke qua logoutAllTokens)
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/password/change', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertStatus(204);

        // Đăng nhập bằng mật khẩu cũ phải fail
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'old-password',
        ])->assertStatus(401)
            ->assertJsonPath('code', 'LOGIN_FAILED');

        // Đăng nhập bằng mật khẩu mới ok
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertOk();
    }

    public function test_change_password_with_wrong_current_password_fails(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
            'email_verified_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'old-password',
        ])->assertOk();

        $token = $login->json('data.access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/password/change', [
                'current_password' => 'wrong-current',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertStatus(422)
            ->assertJsonPath('code', 'CURRENT_PASSWORD_INVALID');
    }

    public function test_refresh_token_returns_new_tokens(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
            'email_verified_at' => now(),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk();

        $refreshToken = $login->json('data.refresh_token');

        $refresh = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ])->assertOk();

        $refresh->assertJsonPath('data.user.email', $user->email);
        $this->assertIsString($refresh->json('data.access_token'));
        $this->assertNotSame($login->json('data.access_token'), $refresh->json('data.access_token'));
        $this->assertIsString($refresh->json('data.refresh_token'));
        $this->assertIsInt($refresh->json('data.expires_in'));

        $this->withHeader('Authorization', 'Bearer '.$refresh->json('data.access_token'))
            ->getJson('/api/v1/auth/sessions')
            ->assertOk();
    }

    public function test_refresh_with_invalid_token_returns_401(): void
    {
        $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'invalid-or-expired-refresh-token-xyz',
        ])->assertStatus(401)
            ->assertJsonPath('code', 'REFRESH_TOKEN_INVALID');
    }

    public function test_refresh_without_token_returns_422(): void
    {
        $this->postJson('/api/v1/auth/refresh', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_forgot_and_reset_password_flow(): void
    {
        $email = 'reset@example.com';
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('old-password'),
        ]);

        // Gọi forgot và mong đợi 200 (reset link gửi thành công)
        $this->postJson('/api/v1/auth/password/forgot', [
            'email' => $email,
        ])->assertOk();
    }
}
