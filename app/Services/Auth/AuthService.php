<?php

namespace App\Services\Auth;

use App\Exceptions\DomainException;
use App\Models\User;
use App\Repositories\OtpVerificationRepository;
use Carbon\CarbonImmutable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    private const OTP_TTL_SECONDS = 600;

    private const OTP_RESEND_COOLDOWN_SECONDS = 60;

    private const OTP_MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly AuthFactory $auth,
        private readonly OtpVerificationRepository $otpVerifications,
    ) {}

    /**
     * @param  array{email:string,password:string}  $data
     */
    public function register(array $data): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'name' => $data['email'],
        ]);

        $this->requestRegisterOtp($user->email);

        return $user;
    }

    public function requestRegisterOtp(string $email): void
    {
        $now = CarbonImmutable::now();

        $existing = $this->otpVerifications->findActiveByEmail($email);

        if ($existing !== null) {
            $cooldown = self::OTP_RESEND_COOLDOWN_SECONDS;

            if ($existing->last_sent_at !== null && $existing->last_sent_at->gt($now->subSeconds($cooldown))) {
                throw new DomainException(
                    status: Response::HTTP_TOO_MANY_REQUESTS,
                    translationKey: 'auth.otp.too_many_requests',
                    code: 'OTP_RESEND_COOLDOWN',
                );
            }
        }

        $otp = $this->generateNumericOtp();
        $hash = Hash::make($otp);

        $expiresAt = $now->addSeconds(self::OTP_TTL_SECONDS);

        if ($existing === null) {
            $this->otpVerifications->createNew($email, $hash, $expiresAt, $now);
        } else {
            $existing->forceFill([
                'otp_hash' => $hash,
                'expires_at' => $expiresAt,
                'last_sent_at' => $now,
                'attempts' => 0,
                'status' => 'active',
            ])->save();
        }
    }

    public function verifyRegisterOtp(string $email, string $otp): void
    {
        $record = $this->otpVerifications->findActiveByEmail($email);

        if ($record === null) {
            throw new DomainException(
                status: Response::HTTP_BAD_REQUEST,
                translationKey: 'auth.otp.not_found',
                code: 'OTP_NOT_FOUND',
            );
        }

        if ($record->attempts >= self::OTP_MAX_ATTEMPTS) {
            $this->otpVerifications->markLocked($record);

            throw new DomainException(
                status: Response::HTTP_TOO_MANY_REQUESTS,
                translationKey: 'auth.otp.too_many_attempts',
                code: 'OTP_TOO_MANY_ATTEMPTS',
            );
        }

        $now = CarbonImmutable::now();

        if ($record->expires_at !== null && $record->expires_at->lte($now)) {
            $this->otpVerifications->markExpired($record);

            throw new DomainException(
                status: Response::HTTP_BAD_REQUEST,
                translationKey: 'auth.otp.expired',
                code: 'OTP_EXPIRED',
            );
        }

        $this->otpVerifications->increaseAttempts($record);

        if (! Hash::check($otp, $record->otp_hash)) {
            throw new DomainException(
                status: Response::HTTP_BAD_REQUEST,
                translationKey: 'auth.otp.invalid',
                code: 'OTP_INVALID',
            );
        }

        $this->otpVerifications->markVerified($record);

        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            throw new DomainException(
                status: Response::HTTP_BAD_REQUEST,
                translationKey: 'auth.user_not_found',
                code: 'USER_NOT_FOUND',
            );
        }

        $user->forceFill([
            'email_verified_at' => $now,
        ])->save();
    }

    /**
     * @return array{user:User,access_token:string,refresh_token:string,expires_in:int}
     */
    public function login(string $email, string $password): array
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new DomainException(
                status: Response::HTTP_UNAUTHORIZED,
                translationKey: 'auth.login_failed',
                code: 'LOGIN_FAILED',
            );
        }

        if ($user->email_verified_at === null) {
            throw new DomainException(
                status: Response::HTTP_FORBIDDEN,
                translationKey: 'auth.login_email_not_verified',
                code: 'EMAIL_NOT_VERIFIED',
            );
        }

        $response = $this->requestOauthToken([
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new DomainException(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                translationKey: 'auth.token_issue_failed',
                code: 'TOKEN_ISSUE_FAILED',
            );
        }

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return [
            'user' => $user,
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => (int) $data['expires_in'],
        ];
    }

    /**
     * @return array{user:User,access_token:string,refresh_token:string,expires_in:int}
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = $this->requestOauthToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new DomainException(
                status: Response::HTTP_UNAUTHORIZED,
                translationKey: 'auth.refresh_token_invalid',
                code: 'REFRESH_TOKEN_INVALID',
            );
        }

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $userId = $this->getUserIdFromAccessToken($data['access_token']);

        /** @var User|null $user */
        $user = $userId ? User::query()->find($userId) : null;

        if ($user === null) {
            throw new DomainException(
                status: Response::HTTP_UNAUTHORIZED,
                translationKey: 'auth.refresh_token_invalid',
                code: 'REFRESH_TOKEN_INVALID',
            );
        }

        return [
            'user' => $user,
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'expires_in' => (int) $data['expires_in'],
        ];
    }

    /**
     * @param  array<string, string>  $params
     */
    private function requestOauthToken(array $params): Response
    {
        $clientId = config('passport.password_client_id');
        $clientSecret = config('passport.password_client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            throw new DomainException(
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                translationKey: 'auth.token_issue_failed',
                code: 'TOKEN_ISSUE_FAILED',
            );
        }

        $params['client_id'] = $clientId;
        $params['client_secret'] = $clientSecret;

        $request = Request::create(
            uri: '/oauth/token',
            method: 'POST',
            parameters: $params,
            server: [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_ACCEPT' => 'application/json',
            ],
        );
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        return app()->handle($request);
    }

    private function getUserIdFromAccessToken(string $accessToken): ?int
    {
        $parts = explode('.', $accessToken);

        if (count($parts) !== 3) {
            return null;
        }

        $decoded = base64_decode(strtr($parts[1], '-_', '+/'), true);
        $payload = is_string($decoded) ? json_decode($decoded, true) : null;

        return isset($payload['sub']) ? (int) $payload['sub'] : null;
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function listSessions(): array
    {
        $user = $this->currentUser();

        return $user->tokens()
            ->where('revoked', false)
            ->get()
            ->map(static function ($token): array {
                return [
                    'id' => $token->id,
                    'name' => $token->name ?? 'API Session',
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                ];
            })
            ->all();
    }

    public function logoutCurrentToken(): void
    {
        $user = $this->currentUser();
        $token = $user->currentAccessToken();

        if ($token instanceof Token) {
            $token->revoke();
            $token->refreshToken?->revoke();
        }
    }

    public function logoutAllTokens(): void
    {
        $user = $this->currentUser();

        Token::where('user_id', $user->getKey())->each(function (Token $token): void {
            $token->revoke();
            $token->refreshToken?->revoke();
        });
    }

    public function changePassword(string $currentPassword, string $newPassword): void
    {
        $user = $this->currentUser();

        if (! Hash::check($currentPassword, $user->password)) {
            throw new DomainException(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                translationKey: 'auth.password.current_invalid',
                code: 'CURRENT_PASSWORD_INVALID',
            );
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        $this->logoutAllTokens();
    }

    public function forgotPassword(string $email): void
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new DomainException(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                translationKey: 'auth.password.forgot_failed',
                code: 'FORGOT_PASSWORD_FAILED',
            );
        }
    }

    /**
     * @param  array{email:string,token:string,password:string}  $data
     */
    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            [
                'email' => $data['email'],
                'token' => $data['token'],
                'password' => $data['password'],
            ],
            static function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new DomainException(
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                translationKey: 'auth.password.reset_failed',
                code: 'RESET_PASSWORD_FAILED',
            );
        }
    }

    private function currentUser(): User
    {
        /** @var User|null $user */
        $user = $this->auth->guard('api')->user();

        if ($user === null) {
            throw new AuthenticationException;
        }

        return $user;
    }

    private function generateNumericOtp(): string
    {
        $otp = '';

        for ($i = 0; $i < 6; $i++) {
            $otp .= random_int(0, 9);
        }

        return $otp;
    }
}
